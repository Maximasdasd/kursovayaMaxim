<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nAllow: " . siteUrl('/') . "\n";
foreach (['cart', 'checkout', 'admin', 'account', 'order', 'login', 'register'] as $section) {
    echo 'Disallow: ' . siteUrl('/' . $section . '/') . "\n";
}
echo 'Sitemap: ' . origin() . "/sitemap.xml\n";
