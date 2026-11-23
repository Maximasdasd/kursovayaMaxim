<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$urls = ['/', '/catalog/', '/about/', '/contacts/'];
foreach (rows('SELECT slug FROM categories ORDER BY id') as $category) {
    $urls[] = '/catalog/' . $category['slug'] . '/';
}
foreach (rows('SELECT slug FROM variants WHERE visible=1 ORDER BY id') as $variant) {
    $urls[] = itemPath($variant);
}
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach ($urls as $path) {
    echo '<url><loc>' . esc(origin() . $path) . '</loc></url>';
}
echo '</urlset>';
