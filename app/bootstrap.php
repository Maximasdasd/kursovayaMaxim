<?php
declare(strict_types=1);
const ROOT = __DIR__ . '/..';
const IMAGES = [
    'plywood',
    'plywood-light',
    'tile-travertine',
    'tile-graphite',
    'tile-sage',
    'tile-terrazzo',
    'tile-cotto',
    'glue-basic',
    'glue-flex',
    'glue-white',
    'glue-universal',
    'glue-rapid'
];
function config(): array
{
    static $settings;
    if ($settings === null) {
        $settings = require ROOT . '/config.example.php';
        if (is_file(ROOT . '/config.local.php')) {
            $settings = array_replace($settings, require ROOT . '/config.local.php');
        }
    }
    return $settings;
}

function db(): PDO
{
    static $pdo;
    if ($pdo === null) {
        $c = config();
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['db_host'], $c['db_port'], $c['db_name']);
        $pdo = new PDO($dsn, $c['db_user'], $c['db_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    return $pdo;
}

function basePath(): string
{
    $directory = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    return rtrim(str_replace('\\', '/', $directory), '/');
}

function siteUrl(string $path): string
{
    return basePath() . $path;
}

function origin(): string
{
    $configured = config()['public_url'];
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^(?:[a-z0-9.-]+|\[[a-f0-9:]+\])(?::[0-9]+)?$/iD', $host)) {
        $host = 'localhost';
    }
    $scheme = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
    return $scheme . '://' . $host . basePath();
}

function run(string $sql, array $args = []): PDOStatement
{
    $s = db()->prepare($sql);
    $s->execute($args);
    return $s;
}
function rows(string $sql, array $args = []): array
{
    return run($sql, $args)->fetchAll();
}
function one(string $sql, array $args = []): ?array
{
    return run($sql, $args)->fetch() ?: null;
}
function esc($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function money($value): string
{
    $c = (int) $value;
    return number_format($c / 100, $c % 100 ? 2 : 0, ',', ' ') . ' ₽';
}
function titleOf(array $v): string
{
    return $v['name'] . ' · ' . $v['option_name'];
}
function itemPath(array $v): string
{
    return '/product/' . $v['slug'] . '/';
}
function imagePath(string $key): string
{
    if (preg_match('/^upload-[a-f0-9]{32}\.(jpg|png|webp)$/D', $key)) {
        return '/assets/images/' . rawurlencode($key);
    }
    return '/assets/images/' . rawurlencode($key) . '.webp';
}
function imageUrl(string $key): string
{
    return siteUrl(imagePath($key));
}
const VARIANT_TABLES = ' FROM variants v JOIN products p ON p.id=v.product_id JOIN categories c ON c.id=p.category_id';
function selectVariants(): string
{
    return 'SELECT v.*,p.name,p.description,p.category_id,c.slug AS category_slug,c.name AS category_name' .
        VARIANT_TABLES;
}
function variant(int $id): ?array
{
    return one(selectVariants() . ' WHERE v.id=? AND v.visible=1', [$id]);
}
function cartLines(): array
{
    $ids = array_keys($_SESSION['cart'] ?? []);
    if (!$ids) {
        return [];
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $lines = rows(selectVariants() . " WHERE v.id IN ($in) ORDER BY v.id", $ids);
    foreach ($lines as &$line) {
        $line['qty'] = $_SESSION['cart'][$line['id']];
    }
    return $lines;
}
function total(array $lines): int
{
    return array_sum(array_map(function ($v) { return $v['qty'] * $v['price']; }, $lines));
}
function user(): ?array
{
    if (!isset($_SESSION['uid'])) {
        return null;
    }
    $u = one('SELECT id,name,email,role,password_hash FROM users WHERE id=?', [$_SESSION['uid']]);
    if (!$u || (isset($_SESSION['auth_stamp']) && !hash_equals($_SESSION['auth_stamp'], hash('sha256', $u['password_hash'])))) {
        unset($_SESSION['uid'], $_SESSION['auth_stamp']);
        return null;
    }
    $_SESSION['auth_stamp'] = hash('sha256', $u['password_hash']);
    unset($u['password_hash']);
    return $u;
}
function go(string $url): void
{
    header('Location: ' . siteUrl($url), true, 303);
    exit();
}
function tokenField(): string
{
    return '<input type="hidden" name="csrf" value="' . esc($_SESSION['csrf']) . '">';
}
function formStart(string $action, string $class = '', bool $upload = false): string
{
    return '<form class="' .
        esc($class) .
        '" method="post" action="' .
        esc(siteUrl('/api.php')) .
        '"' . ($upload ? ' enctype="multipart/form-data"' : '') . '>' .
        tokenField() .
        '<input type="hidden" name="action" value="' .
        esc($action) .
        '">';
}
function requireAdmin(): void
{
    if ((user()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Доступ ограничен.');
    }
}
function positive($value, int $max = 9999): int
{
    $n = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $max]]);
    if ($n === false) {
        throw new DomainException('Введите целое положительное количество до ' . $max . '.');
    }
    return $n;
}
function stringField(string $key, int $max): string
{
    $s = trim((string) ($_POST[$key] ?? ''));
    if ($s === '' || mb_strlen($s) > $max) {
        throw new DomainException('Проверьте заполненные поля.');
    }
    return $s;
}
function priceStamp(array $lines): array
{
    $prices = [];
    foreach ($lines as $v) {
        $prices[$v['id']] = [(int) $v['price'], (int) $v['qty']];
    }
    return $prices;
}
function beginSession(): void
{
    session_save_path(ROOT . '/data/sessions');
    session_name('sloy_store');
    session_set_cookie_params(0, siteUrl('/') . '; SameSite=Lax', '', ($_SERVER['HTTPS'] ?? '') === 'on', true);
    session_start();
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
}
function catalog(array $category = null): array
{
    $conditions = ['v.visible=1'];
    $args = [];
    $q = mb_substr(trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : ''), 0, 100);
    if ($category) {
        $conditions[] = 'p.category_id=?';
        $args[] = $category['id'];
    }
    if ($q !== '') {
        $conditions[] = '(p.name LIKE ? OR v.sku=? OR v.option_name LIKE ? OR c.name LIKE ?)';
        array_push($args, '%' . $q . '%', $q, '%' . $q . '%', '%' . $q . '%');
    }
    foreach (['option' => 'option_name', 'format' => 'format_name'] as $key => $col) {
        $values = $_GET[$key] ?? [];
        if (!is_array($values)) {
            $values = [$values];
        }
        $values = array_values(array_unique(array_filter($values, function ($v) { return is_string($v) && $v !== ''; })));
        if ($values) {
            $conditions[] = "v.$col IN (" . implode(',', array_fill(0, count($values), '?')) . ')';
            array_push($args, ...$values);
        }
    }
    if (isset($_GET['stock'])) {
        $conditions[] = 'v.stock>0';
    }
    $sort = $_GET['sort'] ?? '';
    $sort = is_string($sort) ? $sort : '';
    $orders = ['low' => 'v.price ASC, v.id', 'high' => 'v.price DESC, v.id'];
    $order = $orders[$sort] ?? 'p.id, v.id';
    $where = ' WHERE ' . implode(' AND ', $conditions);
    $count = (int) run('SELECT COUNT(*)' . VARIANT_TABLES . $where, $args)->fetchColumn();
    $perPage = 6;
    $pages = max(1, (int) ceil($count / $perPage));
    $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 0;
    $items = [];
    if ($count && $page >= 1 && $page <= $pages) {
        $offset = ($page - 1) * $perPage;
        $items = rows(selectVariants() . $where . " ORDER BY $order LIMIT $perPage OFFSET $offset", $args);
    }
    return ['items' => $items, 'pages' => $pages, 'page' => $page, 'count' => $count, 'q' => $q, 'sort' => $sort];
}

require_once __DIR__ . '/features.php';
