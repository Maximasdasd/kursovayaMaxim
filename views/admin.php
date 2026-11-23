<?php $tab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : 'orders'; ?>
<section class="shell basic">
    <div class="section-line">
        <h1>Управление магазином</h1>
        <a class="textlink" href="<?= esc(siteUrl('/account/profile/')) ?>">Учётная запись</a>
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
    <?= formStart('delete_category', 'category-delete') ?>
        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        <?php $productCount = (int) run('SELECT COUNT(*) FROM products WHERE category_id=?', [$c['id']])->fetchColumn(); ?>
        <?php if ($productCount): ?>
            <label>Перенести товары перед удалением
                <select name="target_id" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $target): ?>
                        <?php if ((int) $target['id'] !== (int) $c['id']): ?>
                            <option value="<?= (int) $target['id'] ?>"><?= esc($target['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <button class="textlink danger" data-confirm="Удалить категорию? Её товары будут перенесены в выбранную категорию.">Удалить категорию</button>
    </form>
    <?php endforeach; ?>
    <?= formStart('category', 'editor') ?>
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
        
        $listing = assortmentListing();
        $query = $listing['query'];
        $vs = $listing['items'];
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
    <form class="orders-filters" action="<?= esc(siteUrl('/admin/')) ?>" method="get">
        <input type="hidden" name="tab" value="products">
        <label>Название, вариант или артикул
            <input type="search" name="q" value="<?= esc($query['q']) ?>" placeholder="Например, фанера или FK-12">
        </label>
        <label>Категория
            <select name="category_id">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (string) $cat['id'] === $query['category_id'] ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Серия
            <select name="series_id">
                <option value="">Все серии</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>" <?= (string) $product['id'] === $query['series_id'] ? 'selected' : '' ?>><?= esc($product['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Наличие
            <select name="availability">
                <option value="">Любой остаток</option>
                <option value="in" <?= $query['availability'] === 'in' ? 'selected' : '' ?>>В наличии</option>
                <option value="out" <?= $query['availability'] === 'out' ? 'selected' : '' ?>>Нет в наличии</option>
            </select>
        </label>
        <label>Видимость
            <select name="visibility">
                <option value="">Все варианты</option>
                <option value="1" <?= $query['visibility'] === '1' ? 'selected' : '' ?>>На витрине</option>
                <option value="0" <?= $query['visibility'] === '0' ? 'selected' : '' ?>>Скрытые</option>
            </select>
        </label>
        <div><button class="btn">Применить</button> <a class="textlink" href="<?= esc(siteUrl('/admin/?tab=products')) ?>">Сбросить</a></div>
    </form>
    <details class="editor" id="series" <?= isset($_GET['series_page']) ? 'open' : '' ?>>
        <summary>Товарные серии · Найдено: <?= $listing['series_count'] ?></summary>
        <p>Страница <?= $listing['series_page'] ?> из <?= $listing['series_pages'] ?>. Удаление серии удаляет все её варианты. Оформленные заказы сохраняются.</p>
        <?php if (!$listing['series']): ?><p>По заданным условиям серии не найдены.</p><?php endif; ?>
        <?php foreach (array_merge($listing['series'], [['id' => 0, 'name' => '', 'description' => '', 'category_id' => 0]]) as $series): ?>
        <?php if (!$series['id']): ?><h2>Новая товарная серия</h2><?php endif; ?>
        <?= formStart('series', 'edit-fields') ?>
            <?= assortmentFields($listing) ?>
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
        <?php if ($series['id']): ?>
            <?= formStart('delete_series', 'category-delete') ?>
                <?= assortmentFields($listing) ?>
                <input type="hidden" name="id" value="<?= (int) $series['id'] ?>">
                <button class="textlink danger" data-confirm="<?= esc('Удалить серию «' . $series['name'] . '» и все её варианты? Это действие нельзя отменить. История заказов сохранится.') ?>">Удалить серию полностью</button>
            </form>
        <?php endif; ?>
        <?php endforeach; ?>
        <?= assortmentPages($listing, true) ?>
    </details>
    <p class="resultcount" id="variants">Найдено вариантов: <?= $listing['count'] ?> · Страница <?= $listing['page'] ?> из <?= $listing['pages'] ?></p>
    <?php if (!$listing['items']): ?><div class="empty"><h2>Варианты не найдены</h2><p>Измените фильтры или добавьте новый вариант.</p></div><?php endif; ?>
    <?php foreach ( $vs as $v ): ?>
    <details class="editor">
        <summary><?= esc( $v['id'] ? $v['name'] . ' · ' . $v['option_name'] . ' · ' . $v['sku'] : 'Добавить вариант' ) ?><?= $v['id'] && !$v['visible'] ? '(скрыт)' : '' ?></summary>
        <?= formStart('variant', 'edit-fields', true) ?>
            <?= assortmentFields($listing) ?>
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
                    <?php foreach (array_unique(array_merge(IMAGES, $v['image'] ? [$v['image']] : [])) as $im): ?>
                    <option value="<?= esc($im) ?>" <?= $im === $v['image'] ? 'selected' : '' ?>><?= $im ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ($v['image']): ?>
                <img class="admin-image" src="<?= esc(imageUrl($v['image'])) ?>" alt="Текущее изображение товара" width="160" height="120" loading="lazy">
            <?php endif; ?>
            <label>Загрузить новое изображение
                <input name="image_file" type="file" accept="image/jpeg,image/png,image/webp">
                <small>JPG, PNG или WebP, до 2 МБ. Загруженный файл заменит выбранное изображение этого варианта.</small>
            </label>
            <label class="check">
                <input name="visible" type="checkbox" <?= !$v[ 'id' ] || $v['visible'] ? 'checked' : '' ?>>На витрине
            </label>
            <button class="btn">Сохранить вариант</button>
        </form>
    </details>
    <?php endforeach; ?>
    <?= assortmentPages($listing) ?>
    <?php else: ?>
        <?php $listing = orderListing(); $query = $listing['query']; ?>
        <form class="orders-filters" action="<?= esc(siteUrl('/admin/')) ?>" method="get">
            <input type="hidden" name="tab" value="orders">
            <label>Поиск по номеру, имени или телефону
                <input name="q" type="search" value="<?= esc($query['q']) ?>" placeholder="SL-…, имя или телефон">
            </label>
            <label>Статус
                <select name="status">
                    <option value="">Все статусы</option>
                    <?php foreach (['Новый', 'Готов к выдаче', 'Выдан'] as $status): ?>
                        <option value="<?= esc($status) ?>" <?= $query['status'] === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Дата от<input name="from_date" type="date" value="<?= esc($query['from_date']) ?>"></label>
            <label>Дата до<input name="to_date" type="date" value="<?= esc($query['to_date']) ?>"></label>
            <button class="btn">Найти</button>
            <a class="textlink" href="<?= esc(siteUrl('/admin/')) ?>">Сбросить</a>
        </form>
        <p class="resultcount">Найдено заказов: <?= $listing['count'] ?> · Страница <?= $listing['page'] ?> из <?= $listing['pages'] ?></p>
        <?php if (!$listing['items']): ?><div class="empty"><h2>Заказы не найдены</h2><p>Измените условия поиска.</p></div><?php endif; ?>
        <?php foreach ($listing['items'] as $o): ?>
            <div class="editor">
                <div class="section-line">
                    <a href="<?= esc(siteUrl('/order/' . $o['number'] . '/')) ?>"><strong><?= esc($o['number']) ?> ↗</strong></a>
                    <b><?= money($o['total']) ?></b>
                </div>
                <p><?= esc($o['created_at'] . ' · ' . $o['name'] . ' · ' . $o['phone']) ?></p>
                <strong><?= esc($o['status']) ?></strong>
                <?php if ($o['status'] !== 'Выдан'): ?>
                    <?= formStart('status', 'inline-form') ?>
                        <input name="id" type="hidden" value="<?= (int) $o['id'] ?>">
                        <input name="from" type="hidden" value="<?= esc($o['status']) ?>">
                        <?php foreach (array_merge($query, ['page' => $listing['page']]) as $key => $value): ?>
                            <input type="hidden" name="<?= esc($key) ?>" value="<?= esc($value) ?>">
                        <?php endforeach; ?>
                        <button><?= $o['status'] === 'Новый' ? 'Готов к выдаче' : 'Отметить выданным' ?></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($listing['pages'] > 1): ?>
            <nav class="pagination" aria-label="Страницы заказов">
                <?php if ($listing['page'] > 1): ?><a href="<?= esc(pageLink('/admin/', array_merge($query, ['page' => $listing['page'] - 1]))) ?>">Назад</a><?php endif; ?>
                <?php for ($page = max(1, $listing['page'] - 2); $page <= min($listing['pages'], $listing['page'] + 2); $page++): ?>
                    <a href="<?= esc(pageLink('/admin/', array_merge($query, ['page' => $page]))) ?>" <?= $page === $listing['page'] ? 'aria-current="page"' : '' ?>><?= $page ?></a>
                <?php endfor; ?>
                <?php if ($listing['page'] < $listing['pages']): ?><a href="<?= esc(pageLink('/admin/', array_merge($query, ['page' => $listing['page'] + 1]))) ?>">Далее</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
