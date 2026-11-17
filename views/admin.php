<?php $tab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : 'orders'; ?>
<section class="shell basic">
    <div class="section-line">
        <h1>Управление магазином</h1>
        <?= formStart( 'logout' ) ?>
            <button class="textlink">Выйти</button>
        </form>
    </div>
    <nav class="tabs">
        <?php foreach ( ['orders' => 'Заказы', 'products' => 'Ассортимент', 'categories' => 'Категории'] as $k => $n ): ?>
        <a href="<?= esc(siteUrl('/admin/?tab=' . $k)) ?>" <?= $k === $tab ? 'aria-current="page"' : '' ?>><?= $n ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($tab === 'categories'): foreach ($categories as $c): ?>
    <?= formStart('category', 'editor') ?>
        <input type="hidden" name="id" value="<?= $c[ 'id' ] ?>">
        <label>Название
            <input name="name" required value="<?= esc( $c['name'] ) ?>">
        </label>
        <label>Описание
            <textarea name="description" required><?= esc( $c['description'] ) ?></textarea>
        </label>
        <button class="btn">Сохранить категорию</button>
    </form>
    <?php endforeach; ?>
    <?= formStart( 'category', 'editor' ) ?>
        <h2>Новая категория</h2>
        <label>Название
            <input name="name" required>
        </label>
        <label>Адрес (латиница)
            <input name="slug" required>
        </label>
        <label>Описание
            <textarea name="description" required></textarea>
        </label>
        <button class="btn">Добавить категорию</button>
    </form>
    <?php
        elseif ($tab === 'products'):
        
        $vs = rows(selectVariants() . ' ORDER BY p.id,v.id');
        $products = rows('SELECT * FROM products ORDER BY id');
        $vs[] = array_fill_keys(
            [
                'id',
                'name',
                'product_id',
                'sku',
                'slug',
                'option_name',
                'format_name',
                'unit',
                'coverage',
                'price',
                'stock',
                'image',
                'visible'
            ],
            null
        );
    ?>
    <details class="editor">
        <summary>Товарные серии</summary>
        <?php foreach ( array_merge($products, [['id' => 0, 'name' => '', 'description' => '', 'category_id' => 0]]) as $series ): ?>
        <?= formStart('series', 'edit-fields') ?>
            <input type="hidden" name="id" value="<?= $series[ 'id' ] ?>">
            <label>Название серии
                <input name="name" required value="<?= esc( $series['name'] ) ?>">
            </label>
            <label>Категория<select name="category_id">
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat[ 'id' ] ?>" <?= $cat['id'] === $series['category_id'] ? 'selected' : '' ?>><?= esc( $cat['name'] ) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Описание
                <textarea name="description" required><?= esc( $series['description'] ) ?></textarea>
            </label>
            <button class="btn"><?= $series['id'] ? 'Сохранить серию' : 'Добавить серию' ?></button>
        </form>
        <?php endforeach; ?>
    </details>
    <?php foreach ( $vs as $v ): ?>
    <details class="editor">
        <summary><?= esc( $v['id'] ? $v['name'] . ' · ' . $v['option_name'] . ' · ' . $v['sku'] : 'Добавить вариант' ) ?><?= $v['id'] && !$v['visible'] ? '(скрыт)' : '' ?></summary>
        <?= formStart('variant', 'edit-fields') ?>
            <input type="hidden" name="id" value="<?= (int) $v[ 'id' ] ?>">
            <label>Серия<select name="product_id">
                    <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p[ 'id' ] === $v['product_id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <?php
                foreach (
                    [
                        'sku' => 'Артикул',
                        'slug' => 'Адрес (латиница)',
                        'option_name' => 'Вариант',
                        'format_name' => 'Формат',
                        'unit' => 'Единица продажи',
                        'coverage' => 'Площадь упаковки, м²',
                        'price' => 'Цена, ₽',
                        'stock' => 'Остаток'
                    ]
                    as $key => $title
                ):
            ?>
            <label><?= $title ?>
                <input name="<?= $key ?>" <?= $key === 'coverage' ? '' : 'required' ?> value="<?= esc( $key === 'price' ? ($v[$key] ?? 0) / 100 : $v[$key] ) ?>">
            </label>
            <?php endforeach; ?>
            <label>Изображение<select name="image">
                    <?php foreach (IMAGES as $im): ?>
                    <option value="<?= $im ?>" <?= $im === $v['image'] ? 'selected' : '' ?>><?= $im ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="check">
                <input name="visible" type="checkbox" <?= !$v[ 'id' ] || $v['visible'] ? 'checked' : '' ?>>На витрине
            </label>
            <button class="btn">Сохранить вариант</button>
        </form>
    </details>
    <?php
        endforeach;
        else:
        
            $q = is_string($_GET['number'] ?? null) ? trim($_GET['number']) : '';
            $orders = rows('SELECT * FROM orders ' . ($q ? 'WHERE number=? ' : '') . 'ORDER BY id DESC', $q ? [$q] : []);
    ?>
    <form class="adminsearch" action="<?= esc( siteUrl('/admin/') ) ?>">
        <label>Номер заказа
            <input name="number" placeholder="SL-…" value="<?= esc( $q ) ?>">
        </label>
        <button class="btn">Найти</button>
    </form>

    <?php if (!$orders): ?>
    <p>Заказы не найдены.</p>
    <?php
        endif;
        foreach ($orders as $o):
    ?>
    <div class="editor">
        <div class="section-line">
            <a href="<?= esc( siteUrl('/order/' . $o['number'] . '/') ) ?>">
                <strong><?= esc($o['number']) ?> ↗</strong>
            </a>
            <b><?= money($o['total']) ?></b>
        </div>
        <p><?= esc( $o['created_at'] . ' · ' . $o['name'] . ' · ' . $o['phone'] ) ?></p>
        <strong><?= esc($o['status']) ?></strong>

        <?php if ($o['status'] !== 'Выдан'): ?>
        <?= formStart('status', 'inline-form') ?>
            <input name="id" type="hidden" value="<?= $o[ 'id' ] ?>">
            <input name="from" type="hidden" value="<?= esc($o['status']) ?>">
            <button><?= $o['status'] === 'Новый' ? 'Готов к выдаче' : 'Отметить выданным' ?></button>
        </form>
        <?php endif; ?>
    </div>
    <?php
        endforeach;
        
        endif;
    ?>
</section>
