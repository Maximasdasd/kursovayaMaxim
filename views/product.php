<article class="shell product-page">
    <div class="detail-grid">
        <div class="gallery">
            <div class="gallery-main">
                <img id="main-photo" fetchpriority="high" src="<?= esc( imageUrl($v['image']) ) ?>" width="900" height="900"
                    alt="<?= esc(titleOf($v)) ?>">
            </div>
            <div class="gallery-controls">
                <button data-view="normal" aria-pressed="true">Общий вид</button>
                <button data-view="zoom" aria-pressed="false">Фактура крупно</button>
            </div>
        </div>
        <div class="detail-copy">
            <div class="eyebrow">
                <i></i><?= esc($v['category_name']) ?>
            </div>
            <h1><?= esc( $v['name'] ) ?></h1>
            <p class="sku mono"><?= esc($v['sku']) ?> · <?= esc($v['format_name']) ?></p>
            <p><?= esc( $v['description'] ) ?></p>
            <h2 class="label-title">Вариант</h2>
            <div class="tabs">
                <?php
                    $multipleFormats = count(array_unique(array_column($siblings, 'format_name'))) > 1;
                    foreach ($siblings as $s):
                ?>
                <a href="<?= esc(siteUrl(itemPath($s))) ?>" <?= $s['id'] === $v['id'] ? 'aria-current="page"' : '' ?>><?= esc($s['option_name']) ?>
                    <?php if ($multipleFormats): ?>
                    · <?= esc($s['format_name']) ?>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="buy-panel">
                <div class="price"><?= money($v['price']) ?> <small>/ <?= esc( $v['unit'] ) ?></small>
                </div>
                <p class="availability"><?= $v['stock'] ? 'В наличии · ' . (int) $v['stock'] . ' ед.' : 'Временно нет в наличии' ?></p>

                <?php if ( $v['coverage'] ): ?>
                <label class="area-label">Площадь укладки, м²
                    <input type="number" id="area" min=".01" step=".01" inputmode="decimal" placeholder="Например, 10"
                        data-coverage="<?= esc( $v['coverage'] ) ?>" data-price="<?= $v['price'] ?>">
                </label>
                <p>В упаковке <?= esc((float) $v['coverage']) ?> м² · <?= money( (int) round($v['price'] / $v['coverage']) ) ?>/м²</p>
                <output id="estimate" aria-live="polite"></output>
                <?php endif; ?>
                <?= formStart('add', 'buy-form') ?>
                    <input type="hidden" name="id" value="<?= $v[ 'id' ] ?>">
                    <label>Количество
                        <input id="buy-qty" type="number" name="qty" min="1" max="<?= max( 1, $v['stock'] ) ?>" value="1"
                            required>
                    </label>
                    <button class="btn lime" <?= $v['stock'] ? '' : 'disabled' ?>>В корзину <?= icon( 'bag' ) ?></button>
                </form>
            </div>
            <p class="pickup">Самовывоз · Оплата при получении</p>
        </div>
    </div>
    <section class="character">
        <h2>Характеристики</h2>
        <dl>
            <?php
                foreach (
                    [
                        'Артикул' => $v['sku'],
                        'Вариант' => $v['option_name'],
                        'Формат' => $v['format_name'],
                        'Единица продажи' => $v['unit']
                    ]
                    as $k => $value
                ):
            ?>
            <div>
                <dt><?= esc($k) ?></dt>
                <dd><?= esc($value) ?></dd>
            </div>
            <?php endforeach; ?>
        </dl>
    </section>
</article>
