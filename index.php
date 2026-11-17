<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require ROOT . '/app/presentation.php';

set_exception_handler(function (Throwable $error) {
    error_log($error->getMessage());
    http_response_code(500);
    echo '<p lang="ru">Не удалось открыть страницу. Проверьте подключение к базе магазина.</p>';
});
beginSession();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
require ROOT . '/app/page.php';
