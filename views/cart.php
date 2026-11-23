<?php
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>
<section class="shell basic" id="cart-page">

    <?php
        if ($type === 'cart' || $type === 'checkout'):
        
        $lines = cartLines();
        $ordering = $type === 'checkout';
    ?>
    <h1><?= $ordering ? 'Оформление заказа' : 'Корзина' ?></h1>

    <?php if ( !$lines ): ?>
    <div class="empty">
        <h2>Ваша корзина пуста</h2>
        <p>Выберите материал и нужное количество в каталоге.</p>
        <a class="btn" href="<?= esc( siteUrl('/catalog/') ) ?>">В каталог <?= icon('arrow') ?></a>
    </div>

    <?php else: ?>
    <p id="cart-status" role="status" aria-live="polite"></p>
    <div id="cart-empty" class="empty" hidden>
        <h2>Ваша корзина пуста</h2>
        <a class="btn" href="<?= esc(siteUrl('/catalog/')) ?>">В каталог</a>
    </div>
    <div class="order-layout" id="cart-content">
        <div>

            <?php foreach ($lines as $line): ?>
            <div class="cart-row" data-cart-id="<?= (int) $line['id'] ?>" data-price="<?= (int) $line['price'] ?>">
                <img width="120" height="100" src="<?= esc( imageUrl($line['image']) ) ?>" alt="">
                <div>
                    <a href="<?= esc(siteUrl(itemPath($line))) ?>">
                        <strong><?= esc(titleOf($line)) ?></strong>
                    </a>
                    <small>Артикул <?= esc($line['sku'] . ' · ' . $line['format_name']) ?></small>
                    <p><?= money($line['price']) ?> / <?= esc( $line['unit'] ) ?></p>

                    <?php if (!$ordering): ?>
                    <?= formStart('set', 'inline-form cart-quantity') ?>
                        <input type="hidden" name="id" value="<?= $line[ 'id' ] ?>">
                        <input name="qty" type="number" min="1" max="9999" value="<?= $line['qty'] ?>"
                            aria-label="Количество <?= esc( $line['sku'] ) ?>" required>
                        <button>Обновить</button>
                    </form>
                    <?= formStart('remove', 'inline-form cart-remove') ?>
                        <input type="hidden" name="id" value="<?= $line[ 'id' ] ?>">
                        <button>Удалить</button>
                    </form>
                    <?php else: ?>
                    Количество: <?= $line['qty'] ?>
                    <?php endif; ?>

                    <?php if (!$line['visible'] || $line['qty'] > $line['stock']): ?>
                    <p class="error">Доступно: <?= $line['visible'] ? $line['stock'] : 0 ?>. Исправьте корзину.</p>
                    <?php endif; ?>
                </div>
                <b data-line-total><?= money($line['price'] * $line['qty']) ?></b>
            </div>
            <?php endforeach; ?>

            <?php if ($ordering): ?>
            <a href="<?= esc( siteUrl('/cart/') ) ?>" class="textlink">Изменить корзину</a>
            <?php endif; ?>
        </div>
        <aside class="summary">
            <h2>Ваш заказ</h2>
            <div class="total">
                <span>Итого</span>
                <strong data-cart-total><?= money(total($lines)) ?></strong>
            </div>
            <p>Самовывоз · Оплата при получении</p>

            <?php if (!$ordering): ?>
            <a class="btn" id="cart-checkout" href="<?= esc(siteUrl('/checkout/')) ?>">Оформить заказ <?= icon( 'arrow' ) ?></a>
            <?php
                else:
                $qt = bin2hex(random_bytes(24));
                $_SESSION['quotes'][$qt] = priceStamp($lines);
                $_SESSION['quotes'] = array_slice($_SESSION['quotes'], -8, null, true);
            ?>
            <?= formStart( 'checkout', 'form-stack' ) ?>
                <input type="hidden" name="quote" value="<?= $qt ?>">
                <label>Имя получателя
                    <input name="name" autocomplete="given-name" maxlength="100" required value="<?= esc( $old['name'] ?? ($me['name'] ?? '') ) ?>">
                </label>
                <label>Телефон
                    <input name="phone" type="tel" autocomplete="tel" placeholder="+7 900 000-00-00" minlength="10"
                        maxlength="32" required value="<?= esc( $old['phone'] ?? '' ) ?>">
                </label>
                <button class="btn">Подтвердить заказ <?= icon( 'arrow' ) ?></button>
            </form>
            <?php endif; ?>
        </aside>
    </div>
    <?php endif; ?>

    <?php elseif ($type === 'receipt'): ?>
    <div class="eyebrow">
        <i></i><?= esc($order['status']) ?>
    </div>
    <h1>Заказ <?= esc( $order['number'] ) ?></h1>
    <p class="lead">Получатель — <?= esc($order['name']) ?>. Самовывоз, оплата при получении.</p>
    <div class="paper-list">
        <?php foreach ( rows('SELECT * FROM order_items WHERE order_id=? ORDER BY id', [$order['id']]) as $line ): ?>
        <div>
            <span>
                <strong><?= esc($line['name']) ?></strong>
                <small>Артикул <?= esc($line['sku']) ?> · <?= $line[ 'quantity' ] ?> × <?= money($line['price']) ?> / <?= esc($line['unit']) ?></small>
            </span>
            <b><?= money( $line['quantity'] * $line['price'] ) ?></b>
        </div>
        <?php endforeach; ?>
        <div>
            <strong>Итого</strong>
            <strong><?= money($order['total']) ?></strong>
        </div>
    </div>
    <a class="btn" href="<?= esc( siteUrl('/catalog/') ) ?>">Продолжить покупки <?= icon('arrow') ?></a>

    <?php endif; ?>
</section>
