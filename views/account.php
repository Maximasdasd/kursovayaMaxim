<?php
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>
<section class="shell basic">

    <?php
        if (in_array($type, ['login', 'register'], true)):
        $new = $type === 'register';
    ?>
    <div class="narrow">
        <div class="eyebrow">
            <i></i>Личный кабинет
        </div>
        <h1><?= $new ? 'Регистрация' : 'Вход' ?></h1>
        <?= formStart($type, 'form-stack') ?>
            <?php if ($new): ?>
            <label>Имя
                <input name="name" autocomplete="given-name" maxlength="100" required value="<?= esc( $old['name'] ?? '' ) ?>">
            </label>
            <?php endif; ?>
            <label>Электронная почта
                <input name="email" type="email" autocomplete="email" maxlength="190" required
                    value="<?= esc( $old['email'] ?? '' ) ?>">
            </label>
            <label>Пароль
                <input name="password" type="password" autocomplete="<?= $new ? 'new-password' : 'current-password' ?>" <?= $new ? 'minlength="8"' : '' ?> required>
            </label>
            <button class="btn"><?= $new ? 'Создать аккаунт' : 'Войти' ?><?= icon('arrow') ?></button>
        </form>
        <a class="textlink" href="<?= esc(siteUrl($new ? '/login/' : '/register/')) ?>"><?= $new ? 'Уже есть аккаунт? Войти' : 'Создать аккаунт' ?></a>
    </div>

    <?php elseif ($type === 'account/profile'): ?>
        <div class="section-line">
            <h1>Учётная запись</h1>
            <?= formStart('logout') ?><button class="textlink">Выйти</button></form>
        </div>
        <nav class="tabs" aria-label="Личный кабинет">
            <a href="<?= esc(siteUrl('/account/profile/')) ?>" aria-current="page">Мои данные</a>
            <a href="<?= esc(siteUrl('/account/orders/')) ?>">Мои заказы</a>
            <?php if ($me['role'] === 'admin'): ?><a href="<?= esc(siteUrl('/admin/')) ?>">Управление магазином</a><?php endif; ?>
        </nav>
        <div class="account-grid">
            <?= formStart('profile', 'editor form-stack') ?>
                <h2>Личные данные</h2>
                <label>Имя<input name="name" autocomplete="name" maxlength="100" value="<?= esc($me['name']) ?>" required></label>
                <label>Электронная почта<input name="email" type="email" autocomplete="email" maxlength="190" value="<?= esc($me['email']) ?>" required></label>
                <label>Текущий пароль<input name="current_password" type="password" autocomplete="current-password" required></label>
                <button class="btn">Сохранить данные</button>
            </form>
            <?= formStart('password', 'editor form-stack') ?>
                <h2>Изменить пароль</h2>
                <label>Текущий пароль<input name="current_password" type="password" autocomplete="current-password" required></label>
                <label>Новый пароль<input name="password" type="password" autocomplete="new-password" minlength="8" required></label>
                <label>Повторите новый пароль<input name="password_repeat" type="password" autocomplete="new-password" minlength="8" required></label>
                <button class="btn">Изменить пароль</button>
            </form>
        </div>
    <?php else: ?>
    <nav class="tabs" aria-label="Личный кабинет">
        <a href="<?= esc(siteUrl('/account/profile/')) ?>">Мои данные</a>
        <a href="<?= esc(siteUrl('/account/orders/')) ?>" aria-current="page">Мои заказы</a>
    </nav>
    <div class="section-line">
        <h1>Мои заказы</h1>
        <?= formStart( 'logout' ) ?>
            <button class="textlink">Выйти</button>
        </form>
    </div>
    <p><?= esc($me['name'] . ' · ' . $me['email']) ?></p>

    <?php
        $orders = rows('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC', [$me['id']]);
        if (!$orders):
    ?>
    <div class="empty">
        <h2>Заказов пока нет</h2>
        <a href="<?= esc( siteUrl('/catalog/') ) ?>" class="btn">Выбрать материалы</a>
    </div>
    <?php else: ?>
    <div class="paper-list">
        <?php foreach ( $orders as $o ): ?>
        <div>
            <a class="textlink" href="<?= esc(siteUrl('/order/' . $o['number'] . '/')) ?>"><?= esc( $o['number'] ) ?> ↗</a>
            <span><?= esc($o['status']) ?></span>
            <b><?= money( $o['total'] ) ?></b>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
        endif;
        
        endif;
    ?>
</section>
