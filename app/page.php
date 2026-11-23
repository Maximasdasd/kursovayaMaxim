<?php
declare(strict_types=1);
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$prefix = basePath();
$path = strpos($requestPath, $prefix . '/') === 0 ? substr($requestPath, strlen($prefix)) : $requestPath;
if ($path !== '/' && substr($path, -1) !== '/' && preg_match('~^/[a-z0-9/-]+$~D', $path)) {
    header(
        'Location: ' .
            siteUrl($path . '/') .
            (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''),
        true,
        301
    );
    exit();
}
$me = user();
if (!empty($_SESSION['cart'])) cartLines();
$categories = rows('SELECT * FROM categories ORDER BY id');
$type = 'missing';
$title = 'Страница не найдена';
$desc = 'Каталог строительных материалов Слой.';
$canonical = origin() . $path;
$private = false;
$schema = [];
$crumbs = [];
$category = null;
if ($path === '/') {
    $type = 'home';
    $title = 'Слой — магазин строительных материалов';
    $desc =
        'Фанера, керамическая плитка и плиточный клей. Выберите размер, рассчитайте количество упаковок и оформите заказ на самовывоз.';
} elseif (in_array($path, ['/about/', '/contacts/'], true)) {
    $type = trim($path, '/');
    $title = ($type === 'about' ? 'О компании' : 'Контакты') . ' — Слой';
    $desc = $type === 'about' ? 'Магазин стройматериалов Слой. Фанера, плитка, клей и подбор материалов для ремонта.' : 'Контакты магазина Слой, адрес самовывоза и часы работы.';
    $crumbs = [[$type === 'about' ? 'О компании' : 'Контакты', $path]];
} elseif ($path === '/catalog/' || preg_match('~^/catalog/([a-z0-9-]+)/$~D', $path, $m)) {
    $category = $path === '/catalog/' ? null : one('SELECT * FROM categories WHERE slug=?', [$m[1]]);
    if ($category || $path === '/catalog/') {
        $listing = catalog($category);
        if ($listing['page'] >= 1 && $listing['page'] <= $listing['pages']) {
            $type = 'catalog';
            $title = ($category ? $category['name'] : 'Каталог материалов') . ' — Слой';
            $desc =
                $category['description'] ??
                'Фанера, плитка и сухие смеси. Поиск по артикулу, выбор толщины, формата и фасовки.';
            $params = array_filter($_GET, function ($value) { return $value !== '' && $value !== []; });
            unset($params['page']);
            foreach (array_keys($params) as $key) {
                if (strpos((string) $key, 'utm_') === 0) {
                    unset($params[$key]);
                }
            }
            if ($listing['pages'] === 1) {
                unset($params['sort']);
            }
            $private = (bool) $params;
            if ($listing['page'] > 1) {
                $canonical .= '?page=' . $listing['page'];
                $title .= ' · страница ' . $listing['page'];
                $desc .= ' Страница ' . $listing['page'] . '.';
            }
            if ($category) {
                $crumbs[] = ['Каталог', '/catalog/'];
            }
            $crumbs[] = [$category['name'] ?? 'Каталог материалов', $path];
        }
    }
} elseif (preg_match('~^/product/([a-z0-9-]+)/$~D', $path, $m)) {
    $v = one(selectVariants() . ' WHERE v.slug=? AND v.visible=1', [$m[1]]);
    if ($v) {
        $type = 'product';
        $title = titleOf($v) . ' · ' . $v['format_name'] . ' — Слой';
        $desc =
            $v['name'] .
            ' · ' .
            $v['sku'] .
            '. ' .
            $v['description'] .
            ' Цена за ' .
            $v['unit'] .
            ' — ' .
            money($v['price']) .
            '.';
        $siblings = rows(selectVariants() . ' WHERE p.id=? AND v.visible=1 ORDER BY v.id', [$v['product_id']]);
        $crumbs = [
            ['Каталог', '/catalog/'],
            [$v['category_name'], '/catalog/' . $v['category_slug'] . '/'],
            [titleOf($v), $path]
        ];
        $schema[] = [
            '@type' => 'Product',
            'name' => titleOf($v),
            'image' => origin() . imagePath($v['image']),
            'description' => $v['description'],
            'sku' => $v['sku'],
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonical,
                'price' => number_format($v['price'] / 100, 2, '.', ''),
                'priceCurrency' => 'RUB',
                'availability' => 'https://schema.org/' . ($v['stock'] ? 'InStock' : 'OutOfStock')
            ]
        ];
    }
} elseif (in_array($path, ['/cart/', '/checkout/', '/login/', '/register/', '/account/orders/', '/account/profile/', '/admin/'], true)) {
    $private = true;
    $type = trim($path, '/');
    $title = [
        'cart' => 'Корзина',
        'checkout' => 'Оформление заказа',
        'login' => 'Вход',
        'register' => 'Регистрация',
        'account/orders' => 'Мои заказы',
        'account/profile' => 'Учётная запись',
        'admin' => 'Управление магазином'
    ][$type];
    if (in_array($type, ['admin', 'account/orders', 'account/profile'], true) && !$me) {
        go('/login/');
    }
    if ($type === 'admin' && $me['role'] !== 'admin') {
        $type = 'forbidden';
        $title = 'Доступ ограничен';
        http_response_code(403);
    }
} elseif (preg_match('~^/order/(SL-[A-F0-9]{10})/$~D', $path, $m)) {
    $private = true;
    $order = one('SELECT * FROM orders WHERE number=?', [$m[1]]);
    if (
        $order &&
        (in_array($order['number'], $_SESSION['receipts'] ?? [], true) ||
            ($me && ($me['role'] === 'admin' || $order['user_id'] === $me['id'])))
    ) {
        $type = 'receipt';
        $title = 'Заказ ' . $order['number'];
    }
}
if ($type === 'missing') {
    http_response_code(404);
    $private = true;
}
if ($crumbs) {
    $entries = [];
    foreach (array_merge([['Главная', '/']], $crumbs) as $i => $c) {
        $entries[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => origin() . $c[1]];
    }
    $schema[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $entries];
}
if ($private) {
    header('X-Robots-Tag: noindex, follow');
}
header('Cache-Control: private, no-store');
if ($type === 'catalog' && ($_SERVER['HTTP_X_SLOY_FRAGMENT'] ?? '') === 'catalog') {
    header('Content-Type: application/json; charset=utf-8');
    ob_start();
    renderListing($listing, $path);
    $selected = array_filter(
        array_merge((array) ($_GET['option'] ?? []), (array) ($_GET['format'] ?? [])),
        'is_string'
    );
    echo json_encode(
        [
            'html' => ob_get_clean(),
            'title' => $title,
            'description' => $desc,
            'selected' => array_values($selected),
            'canonical' => $private ? null : $canonical
        ],
        JSON_UNESCAPED_UNICODE
    );
    exit();
}
require ROOT . '/views/layout.php';
