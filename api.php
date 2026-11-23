<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require ROOT . '/app/actions.php';

set_exception_handler(function (Throwable $error) {
    error_log($error->getMessage());
    http_response_code(500);
    if (ajaxCart()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Не удалось сохранить изменения. Повторите попытку.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo '<p lang="ru">Не удалось открыть страницу. Проверьте подключение к базе магазина.</p>';
});
beginSession();
handleAction();
