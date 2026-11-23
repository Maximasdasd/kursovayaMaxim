<?php
declare(strict_types=1);
function checkout(string $name, string $phone, string $token): string
{
    if (!preg_match('/^[a-f0-9]{48}$/D', $token)) {
        throw new DomainException('Обновите форму заказа.');
    }
    if (isset($_SESSION['receipts'][$token])) {
        return $_SESSION['receipts'][$token];
    }
    $quote = $_SESSION['quotes'][$token] ?? null;
    if (!$quote) {
        throw new DomainException('Состав корзины изменился. Проверьте заказ ещё раз.');
    }
    $saved = one('SELECT number FROM orders WHERE request_token=?', [$token]);
    if ($saved) {
        $_SESSION['receipts'][$token] = $saved['number'];
        unset($_SESSION['cart'], $_SESSION['quotes']);
        return $saved['number'];
    }
    if (empty($_SESSION['cart'])) {
        throw new DomainException('Корзина пуста.');
    }
    $ids = array_keys($_SESSION['cart']);
    sort($ids, SORT_NUMERIC);
    db()->beginTransaction();
    try {
        $sqlite = db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        if ($sqlite) db()->exec('UPDATE variants SET stock=stock WHERE 0');
        $locked = [];
        foreach ($ids as $id) {
            $v = one(selectVariants() . ' WHERE v.id=?' . ($sqlite ? '' : ' FOR UPDATE'), [$id]);
            $qty = $_SESSION['cart'][$id];
            if (!$v || !$v['visible'] || $v['stock'] < $qty) {
                throw new DomainException('Недостаточно материала. Измените количество в корзине.');
            }
            $v['qty'] = $qty;
            $locked[] = $v;
        }
        if (priceStamp($locked) !== $quote) {
            throw new DomainException(
                'Цена или состав заказа изменились. Проверьте обновлённый итог и подтвердите ещё раз.'
            );
        }
        $number = 'SL-' . strtoupper(bin2hex(random_bytes(5)));
        run('INSERT INTO orders(number,request_token,user_id,name,phone,total) VALUES(?,?,?,?,?,?)', [
            $number,
            $token,
            $_SESSION['uid'] ?? null,
            $name,
            $phone,
            total($locked)
        ]);
        $orderId = db()->lastInsertId();
        foreach ($locked as $v) {
            run('INSERT INTO order_items(order_id,variant_id,sku,name,unit,price,quantity) VALUES(?,?,?,?,?,?,?)', [
                $orderId,
                $v['id'],
                $v['sku'],
                titleOf($v) . ' · ' . $v['format_name'],
                $v['unit'],
                $v['price'],
                $v['qty']
            ]);
            run('UPDATE variants SET stock=stock-? WHERE id=?', [$v['qty'], $v['id']]);
        }
        db()->commit();
        $_SESSION['receipts'][$token] = $number;
        unset($_SESSION['cart'], $_SESSION['quotes'], $_SESSION['old']);
        return $number;
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}
function handleAction(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit();
    }
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(403);
        exit('Обновите страницу и отправьте форму заново.');
    }
    $action = (string) ($_POST['action'] ?? '');
    $back = '/cart/';
    try {
        switch ($action) {
            case 'add':
            case 'set':
            case 'remove':
                $id = positive($_POST['id'] ?? 0, PHP_INT_MAX);
                if ($action === 'remove') {
                    unset($_SESSION['cart'][$id]);
                    if (ajaxCart()) cartResponse();
                    go('/cart/');
                }
                $v = variant($id);
                if (!$v) {
                    throw new DomainException('Вариант снят с продажи.');
                }
                if ($action === 'add') {
                    $back = itemPath($v);
                }
                $qty = positive($_POST['qty'] ?? 1) + ($action === 'add' ? $_SESSION['cart'][$id] ?? 0 : 0);
                if ($qty > $v['stock'] || $qty > 9999) {
                    throw new DomainException('Доступно ' . $v['stock'] . ' ед. Проверьте количество в корзине.');
                }
                $_SESSION['cart'][$id] = $qty;
                if (ajaxCart()) cartResponse();
                go('/cart/');
            case 'checkout':
                $back = '/checkout/';
                $_SESSION['old'] = [
                    'name' => substr((string) ($_POST['name'] ?? ''), 0, 200),
                    'phone' => substr((string) ($_POST['phone'] ?? ''), 0, 32)
                ];
                $name = stringField('name', 100);
                $phone = stringField('phone', 32);
                if (
                    !preg_match('/^[+0-9 ()-]+$/D', $phone) ||
                    !preg_match('/^\d{10,15}$/D', preg_replace('/\D/', '', $phone))
                ) {
                    throw new DomainException('Телефон должен содержать от 10 до 15 цифр.');
                }
                go('/order/' . checkout($name, $phone, (string) ($_POST['quote'] ?? '')) . '/');
            case 'login':
            case 'register':
                $back = '/' . $action . '/';
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $pass = (string) ($_POST['password'] ?? '');
                $_SESSION['old'] = [
                    'email' => substr($email, 0, 190),
                    'name' => substr((string) ($_POST['name'] ?? ''), 0, 200)
                ];
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                    throw new DomainException('Введите действительный адрес почты.');
                }
                if ($action === 'register') {
                    $name = stringField('name', 100);
                    if (strlen($pass) < 8 || strlen($pass) > 72) {
                        throw new DomainException('Введите пароль длиной от 8 до 72 байт.');
                    }
                    try {
                        run('INSERT INTO users(email,name,password_hash) VALUES(?,?,?)', [
                            $email,
                            $name,
                            password_hash($pass, PASSWORD_DEFAULT)
                        ]);
                    } catch (PDOException $e) {
                        if ($e->getCode() === '23000') {
                            throw new DomainException('Этот адрес уже зарегистрирован. Войдите в аккаунт.');
                        }
                        throw $e;
                    }
                }
                $u = one('SELECT * FROM users WHERE email=?', [$email]);
                if (!$u || !password_verify($pass, $u['password_hash'])) {
                    throw new DomainException('Проверьте почту и пароль.');
                }
                session_regenerate_id(true);
                $_SESSION['uid'] = $u['id'];
                $_SESSION['auth_stamp'] = hash('sha256', $u['password_hash']);
                $_SESSION['csrf'] = bin2hex(random_bytes(24));
                unset($_SESSION['old']);
                go($u['role'] === 'admin' ? '/admin/' : '/account/orders/');
            case 'profile':
            case 'password':
                $back = '/account/profile/';
                editAccount($action);
                break;
            case 'delete_category':
                requireAdmin();
                $back = '/admin/?tab=categories';
                deleteCategory(positive($_POST['id'] ?? 0, PHP_INT_MAX), (int) ($_POST['target_id'] ?? 0));
                $_SESSION['success'] = 'Категория удалена.';
                go($back);
            case 'logout':
                $_SESSION = [];
                session_regenerate_id(true);
                go('/');
            case 'status':
                requireAdmin();
                $return = [];
                foreach (['q', 'status', 'from_date', 'to_date', 'page'] as $key) {
                    if (is_string($_POST[$key] ?? null)) $return[$key] = $_POST[$key];
                }
                $back = '/admin/?' . http_build_query($return);
                $id = positive($_POST['id'] ?? 0, PHP_INT_MAX);
                $next = ['Новый' => 'Готов к выдаче', 'Готов к выдаче' => 'Выдан'];
                $from = (string) ($_POST['from'] ?? '');
                if (
                    !isset($next[$from]) ||
                    !run('UPDATE orders SET status=? WHERE id=? AND status=?', [$next[$from], $id, $from])->rowCount()
                ) {
                    throw new DomainException('Статус уже изменился. Обновите список.');
                }
                go($back);
            case 'category':
                requireAdmin();
                $back = '/admin/?tab=categories';
                $id = (int) ($_POST['id'] ?? 0);
                $name = stringField('name', 100);
                $description = stringField('description', 400);
                if ($id) {
                    run('UPDATE categories SET name=?,description=? WHERE id=?', [$name, $description, $id]);
                } else {
                    $slug = stringField('slug', 64);
                    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) {
                        throw new DomainException('Проверьте адрес категории.');
                    }
                    try {
                        run('INSERT INTO categories(name,description,slug) VALUES(?,?,?)', [
                            $name,
                            $description,
                            $slug
                        ]);
                    } catch (PDOException $e) {
                        if ($e->getCode() === '23000') {
                            throw new DomainException('Такой адрес уже занят.');
                        }
                        throw $e;
                    }
                }
                go($back);
            case 'delete_series':
                requireAdmin();
                $back = assortmentReturn();
                $id = positive($_POST['id'] ?? 0, PHP_INT_MAX);
                deleteSeries($id);
                $_SESSION['success'] = 'Товарная серия и все её варианты удалены. История заказов сохранена.';
                go(assortmentReturn($id));
            case 'series':
                requireAdmin();
                $back = assortmentReturn();
                $id = (int) ($_POST['id'] ?? 0);
                $category = positive($_POST['category_id'] ?? 0, PHP_INT_MAX);
                if (!one('SELECT id FROM categories WHERE id=?', [$category])) {
                    throw new DomainException('Выберите существующую категорию.');
                }
                $values = [$category, stringField('name', 140), stringField('description', 3000)];
                if ($id) {
                    $values[] = $id;
                    run('UPDATE products SET category_id=?,name=?,description=? WHERE id=?', $values);
                } else {
                    run('INSERT INTO products(category_id,name,description) VALUES(?,?,?)', $values);
                }
                go($back);
            case 'variant':
                requireAdmin();
                $back = assortmentReturn();
                $id = (int) ($_POST['id'] ?? 0);
                $price = str_replace(',', '.', (string) ($_POST['price'] ?? ''));
                $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
                $image = (string) ($_POST['image'] ?? '');
                $product = (int) ($_POST['product_id'] ?? 0);
                if (
                    !preg_match('/^[0-9]{1,7}(\.\d{1,2})?$/D', $price) ||
                    (float) $price <= 0 ||
                    $stock === false ||
                    $stock < 0 ||
                    $stock > 999999 ||
                    !validImage($image) ||
                    !one('SELECT id FROM products WHERE id=?', [$product])
                ) {
                    throw new DomainException('Проверьте цену, остаток, серию и изображение.');
                }
                $area = str_replace(',', '.', trim((string) ($_POST['coverage'] ?? '')));
                if ($area !== '' && (!is_numeric($area) || (float) $area <= 0 || (float) $area > 99999)) {
                    throw new DomainException('Площадь упаковки указана неверно.');
                }
                $sku = stringField('sku', 48);
                $slug = stringField('slug', 150);
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) {
                    throw new DomainException('Адрес должен состоять из латинских букв, цифр и дефисов.');
                }
                if ($id && !one('SELECT id FROM variants WHERE id=?', [$id])) {
                    throw new DomainException('Товар уже удалён. Обновите список.');
                }
                $option = stringField('option_name', 80);
                $format = stringField('format_name', 80);
                $unit = stringField('unit', 32);
                $newImage = uploadedImage();
                if ($newImage !== null) $image = $newImage;
                $vals = [
                    $product,
                    $sku,
                    $slug,
                    $option,
                    $format,
                    $unit,
                    $area === '' ? null : $area,
                    (int) round((float) $price * 100),
                    $stock,
                    $image,
                    isset($_POST['visible']) ? 1 : 0
                ];
                try {
                    if ($id) {
                        $vals[] = $id;
                        run(
                            'UPDATE variants SET product_id=?,sku=?,slug=?,option_name=?,format_name=?,unit=?,coverage=?,price=?,stock=?,image=?,visible=? WHERE id=?',
                            $vals
                        );
                    } else {
                        run(
                            'INSERT INTO variants(product_id,sku,slug,option_name,format_name,unit,coverage,price,stock,image,visible) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
                            $vals
                        );
                    }
                } catch (Throwable $e) {
                    if ($newImage !== null) unlink(ROOT . '/assets/images/' . $newImage); 
                    if ($e->getCode() === '23000') {
                        throw new DomainException('Артикул и адрес должны быть уникальными.');
                    }
                    throw $e;
                }
                go($back);
            default:
                http_response_code(400);
                exit('Неизвестное действие.');
        }
    } catch (DomainException $e) {
        if (ajaxCart()) cartResponse(422, $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        go($back);
    }
}
