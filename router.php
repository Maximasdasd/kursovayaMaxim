<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit();
}
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (
    (strpos($path, '..') !== false) ||
    (strpos($path, '\\') !== false) ||
    (strpos($path, "\0") !== false) ||
    preg_match('#^/(app|views|data|database|bin|tests|docs)(/|$)|(^|/)\.#i', $path)
) {
    http_response_code(403);
    exit('Forbidden');
}
if (preg_match('#^/config[.]|[.](sql|md|ini|docx)$#i', $path)) {
    http_response_code(403);
    exit();
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
if ($path === '/robots.txt') {
    require __DIR__ . '/robots.php';
    return true;
}
if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}
if ($path === '/api.php') {
    require __DIR__ . '/api.php';
    return true;
}
if (preg_match('#^/assets/[a-zA-Z0-9/_-]+\.(css|js|svg|webp|png|jpg)$#D', $path) && is_file(__DIR__ . $path)) {
    return false;
}
require __DIR__ . '/index.php';
