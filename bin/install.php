<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit();
}
require dirname(__DIR__) . '/app/bootstrap.php';
try {
    if (db()->query('SHOW TABLES')->fetchColumn() !== false) {
        throw new RuntimeException('Для установки выберите пустую базу данных.');
    }
    db()->exec(file_get_contents(ROOT . '/database/sloy.sql'));
    echo "Каталог и учебная учётная запись созданы.\n";
} catch (Throwable $error) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
