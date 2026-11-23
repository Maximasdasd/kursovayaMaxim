<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= esc( $title ) ?></title>
        <meta name="description" content="<?= esc($desc) ?>">
        <link rel="icon" href="<?= esc(siteUrl('/assets/favicon.svg')) ?>">
        <link rel="stylesheet" href="<?= esc( siteUrl('/assets/style.css?v=20261123') ) ?>">
        <script defer src="<?= esc(siteUrl('/assets/app.js?v=20261123')) ?>"></script>

        <?php if (!$private): ?>
        <link rel="canonical" href="<?= esc( $canonical ) ?>">
        <?php else: ?>
        <meta name="robots" content="noindex, follow">
        <?php endif; ?>

        <?php if ($type === 'home'): ?>
        <link rel="preload" as="image" href="<?= esc( siteUrl('/assets/images/hero.webp') ) ?>">
        <?php endif; ?>

        <?php if ($schema): ?>
        <script type="application/ld+json">
            <?= json_encode( ['@context' => 'https://schema.org', '@graph' => $schema], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) ?>

        </script>
        <?php endif; ?>
    </head>
    <body>
        <a class="skip" href="#main">К содержанию</a>
        <header class="top">
            <div class="shell head">
                <a class="brand" href="<?= esc( siteUrl('/') ) ?>" aria-label="Слой, главная"><?= icon('logo') ?>СЛОЙ</a>
                <nav class="mainnav" aria-label="Основное меню">
                    <a href="<?= esc(siteUrl('/catalog/')) ?>" <?= $type === 'catalog' ? 'aria-current="page"' : '' ?>>Каталог</a>
                    <a href="<?= esc(siteUrl('/about/')) ?>" <?= $type === 'about' ? 'aria-current="page"' : '' ?>>О компании</a>
                    <a href="<?= esc(siteUrl('/contacts/')) ?>" <?= $type === 'contacts' ? 'aria-current="page"' : '' ?>>Контакты</a>
                </nav>
                <div class="tools">
                    <details class="search">
                        <summary aria-label="Найти материал"><?= icon( 'search' ) ?></summary>
                        <form method="get" action="<?= esc( siteUrl('/catalog/') ) ?>">
                            <label>Название или артикул
                                <input type="search" name="q" placeholder="Например, ФК или FK-12-1525" required>
                            </label>
                            <button class="btn">Найти</button>
                        </form>
                    </details>
                    <a class="tool" aria-label="Личный кабинет" href="<?= esc( siteUrl(!$me ? '/login/' : ($me['role'] === 'admin' ? '/admin/' : '/account/profile/')) ) ?>"><?= icon('user') ?></a>
                    <a class="cartlink" href="<?= esc(siteUrl('/cart/')) ?>"><?= icon('bag') ?><span>Корзина</span>
                        <b data-cart-count><?= array_sum( $_SESSION['cart'] ?? [] ) ?></b>
                    </a>
                </div>
            </div>
        </header>
        <main id="main">
            <?php if (!empty($_SESSION['error'])): ?>
            <p role="alert" class="notice shell"><?= esc( $_SESSION['error'] ) ?></p>
            <?php unset($_SESSION['error']);endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <p role="status" class="notice shell success"><?= esc($_SESSION['success']) ?></p>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if ($crumbs): ?>
            <nav class="crumbs shell" aria-label="Путь к странице">
                <a href="<?= esc( siteUrl('/') ) ?>">Главная</a>
                <?php foreach ($crumbs as $i => $c): ?>
                <span>/</span>
                <?php if ($i === count($crumbs) - 1): ?>
                <?= esc($c[0]) ?>
                <?php else: ?>
                <a href="<?= esc(siteUrl($c[1])) ?>"><?= esc($c[0]) ?></a>
                <?php endif;endforeach; ?>
            </nav>
            <?php endif; ?>

            <?php
                switch ($type) {
                case 'home':
                case 'catalog':
                case 'product':
                    require ROOT . '/views/' . $type . '.php';
                    break;
                case 'about':
                case 'contacts':
                    require ROOT . '/views/company.php';
                    break;
                case 'admin':
                    require ROOT . '/views/admin.php';
                    break;
                case 'cart':
                case 'checkout':
                case 'receipt':
                    require ROOT . '/views/cart.php';
                    break;
                case 'login':
                case 'register':
                case 'account/orders':
                case 'account/profile':
                    require ROOT . '/views/account.php';
                    break;
                default:
            ?>
            <section class="shell basic">
                <div class="eyebrow"><?= http_response_code() ?></div>
                <h1><?= esc( $title ) ?></h1>
                <a href="<?= esc(siteUrl('/catalog/')) ?>" class="btn">Перейти в каталог <?= icon( 'arrow' ) ?></a>
            </section>
            <?php } ?>
        </main>
        <?php $company = company(); ?>
        <footer>
            <div class="shell footer-grid">
                <div>
                    <a class="brand" href="<?= esc(siteUrl('/')) ?>"><?= icon('logo') ?>СЛОЙ</a>
                    <p>Фанера, плитка и клей для ремонта и отделки. Подбор по характеристикам, заказ онлайн и самовывоз.</p>
                </div>
                <nav aria-label="Информация о магазине">
                    <a href="<?= esc(siteUrl('/about/')) ?>">О компании</a>
                    <a href="<?= esc(siteUrl('/contacts/')) ?>">Контакты</a>
                    <a href="<?= esc(siteUrl('/catalog/')) ?>">Каталог</a>
                    <a href="<?= esc(siteUrl('/account/profile/')) ?>">Личный кабинет</a>
                </nav>
                <div>
                    <p><?= esc($company['address']) ?></p>
                    <p><?= esc($company['hours']) ?></p>
                    <p><?= esc($company['phone']) ?> · <?= esc($company['email']) ?></p>
                </div>
            </div>
            <p class="shell footer-note">© <?= date('Y') ?> Слой · Учебный магазин. Контактные данные вымышлены.</p>
        </footer>
    </body>
</html>
