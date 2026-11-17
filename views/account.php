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

    <?php else: ?>
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
