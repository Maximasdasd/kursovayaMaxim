<?php
declare(strict_types=1);

function company(): array
{
    return [
        'name' => 'Слой',
        'phone' => '+7 (000) 123-45-67',
        'email' => 'info@sloy.example',
        'address' => 'Екатеринбург, ул. Проектная, д. 12',
        'hours' => 'Пн–Пт 09:00–19:00 · Сб–Вс 10:00–17:00'
    ];
}

function ajaxCart(): bool
{
    return ($_SERVER['HTTP_X_SLOY_REQUEST'] ?? '') === 'cart';
}

function cartResponse(int $status = 200, string $error = ''): void
{
    $lines = cartLines();
    $items = [];
    foreach ($lines as $line) {
        $items[] = [
            'id' => (int) $line['id'],
            'qty' => (int) $line['qty'],
            'price' => (int) $line['price'],
            'stock' => (int) $line['stock'],
            'visible' => (bool) $line['visible'],
            'subtotal' => money($line['qty'] * $line['price'])
        ];
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, no-store');
    echo json_encode([
        'ok' => $status === 200,
        'error' => $error,
        'items' => $items,
        'count' => array_sum($_SESSION['cart'] ?? []),
        'total' => money(total($lines)),
        'csrf' => $_SESSION['csrf']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function uploadedImage(): ?string
{
    $file = $_FILES['image_file'] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (!is_array($file) || !isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new DomainException('Изображение не загружено. Выберите файл до 2 МБ и повторите отправку.');
    }
    if (!is_uploaded_file($file['tmp_name']) || $file['size'] > 2 * 1024 * 1024) {
        throw new DomainException('Размер изображения должен быть не больше 2 МБ.');
    }
    $size = @getimagesize($file['tmp_name']);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$size || !isset($types[$size['mime']]) || $size[0] * $size[1] > 24000000) {
        throw new DomainException('Выберите изображение JPG, PNG или WebP размером до 24 мегапикселей.');
    }
    $name = 'upload-' . bin2hex(random_bytes(16)) . '.' . $types[$size['mime']];
    if (!move_uploaded_file($file['tmp_name'], ROOT . '/assets/images/' . $name)) {
        throw new DomainException('Не удалось сохранить изображение. Проверьте право записи в assets/images.');
    }
    return $name;
}

function validImage(string $key): bool
{
    return in_array($key, IMAGES, true) || (
        preg_match('/^upload-[a-f0-9]{32}\.(jpg|png|webp)$/D', $key) &&
        is_file(ROOT . '/assets/images/' . $key)
    );
}

function editAccount(string $action): void
{
    $me = user();
    if (!$me) {
        go('/login/');
    }
    $stored = one('SELECT * FROM users WHERE id=?', [$me['id']]);
    $current = (string) ($_POST['current_password'] ?? '');
    if (!$stored || !password_verify($current, $stored['password_hash'])) {
        throw new DomainException('Текущий пароль указан неверно.');
    }
    if ($action === 'profile') {
        $name = stringField('name', 100);
        $email = strtolower(stringField('email', 190));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Введите действительный адрес электронной почты.');
        }
        if ($name !== $stored['name'] || $email !== $stored['email']) {
            try {
                $updated = run('UPDATE users SET name=?,email=? WHERE id=? AND password_hash=?', [
                    $name, $email, $me['id'], $stored['password_hash']
                ])->rowCount();
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    throw new DomainException('Этот адрес электронной почты уже используется.');
                }
                throw $e;
            }
            if (!$updated) {
                throw new DomainException('Данные аккаунта изменились. Войдите заново.');
            }
        }
        $_SESSION['success'] = 'Данные учётной записи сохранены.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8 || strlen($password) > 72) {
            throw new DomainException('Новый пароль должен содержать от 8 до 72 байт.');
        }
        if ($password !== ($_POST['password_repeat'] ?? '')) {
            throw new DomainException('Новый пароль и его повтор не совпадают.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!run('UPDATE users SET password_hash=? WHERE id=? AND password_hash=?', [
            $hash, $me['id'], $stored['password_hash']
        ])->rowCount()) {
            throw new DomainException('Пароль уже изменён. Войдите заново.');
        }
        $_SESSION['auth_stamp'] = hash('sha256', $hash);
        $_SESSION['success'] = 'Пароль изменён.';
    }
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
    go('/account/profile/');
}

function deleteCategory(int $id, int $target): void
{
    db()->beginTransaction();
    try {
        $sqlite = db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        if ($sqlite) {
            db()->exec('UPDATE categories SET name=name WHERE 0');
        }
        $locked = rows('SELECT id FROM categories WHERE id IN (?,?) ORDER BY id' . ($sqlite ? '' : ' FOR UPDATE'), [$id, $target]);
        $ids = array_map('intval', array_column($locked, 'id'));
        if (!in_array($id, $ids, true)) {
            throw new DomainException('Категория уже удалена. Обновите страницу.');
        }
        $count = (int) run('SELECT COUNT(*) FROM products WHERE category_id=?', [$id])->fetchColumn();
        if ($count) {
            if ($target === $id || !in_array($target, $ids, true)) {
                throw new DomainException('Выберите другую категорию для переноса товаров.');
            }
            run('UPDATE products SET category_id=? WHERE category_id=?', [$target, $id]);
        }
        run('DELETE FROM categories WHERE id=?', [$id]);
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function orderListing(): array
{
    $search = $_GET['q'] ?? $_GET['number'] ?? '';
    $q = mb_substr(trim(is_string($search) ? $search : ''), 0, 100);
    $status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
    $from = is_string($_GET['from_date'] ?? null) ? $_GET['from_date'] : '';
    $to = is_string($_GET['to_date'] ?? null) ? $_GET['to_date'] : '';
    $conditions = [];
    $args = [];
    if ($q !== '') {
        $columns = ['o.number', 'o.name', 'o.phone'];
        $parts = [];
        foreach ($columns as $column) {
            $parts[] = db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
                ? 'fold_text(' . $column . ') LIKE fold_text(?)' : $column . ' LIKE ?';
            $args[] = '%' . $q . '%';
        }
        $conditions[] = '(' . implode(' OR ', $parts) . ')';
    }
    if (in_array($status, ['Новый', 'Готов к выдаче', 'Выдан'], true)) {
        $conditions[] = 'o.status=?';
        $args[] = $status;
    } else {
        $status = '';
    }
    foreach (['from_date' => $from, 'to_date' => $to] as $key => $value) {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        if ($date && $date->format('Y-m-d') === $value) {
            $conditions[] = $key === 'from_date' ? 'o.created_at>=?' : 'o.created_at<?';
            $args[] = $key === 'from_date' ? $value : $date->modify('+1 day')->format('Y-m-d');
        }
    }
    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
    $count = (int) run('SELECT COUNT(*) FROM orders o' . $where, $args)->fetchColumn();
    $pages = max(1, (int) ceil($count / 10));
    $page = min($pages, max(1, (int) (filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1)));
    $offset = ($page - 1) * 10;
    return [
        'items' => rows('SELECT o.* FROM orders o' . $where . ' ORDER BY o.id DESC LIMIT 10 OFFSET ' . $offset, $args),
        'count' => $count, 'page' => $page, 'pages' => $pages,
        'query' => ['tab' => 'orders', 'q' => $q, 'status' => $status, 'from_date' => $from, 'to_date' => $to]
    ];
}
