<section class="shell catalog-page">
    <h1><?= esc( $category['name'] ?? 'Каталог материалов' ) ?></h1>
    <p class="lead"><?= esc($desc) ?></p>
    <nav class="tabs" aria-label="Категории">
        <a href="<?= esc(siteUrl('/catalog/')) ?>" <?= !$category ? 'aria-current="page"' : '' ?>>Все материалы</a>
        <?php foreach ($categories as $c): ?>
        <a href="<?= esc( siteUrl('/catalog/' . $c['slug'] . '/') ) ?>" <?= $c['id'] === ($category['id'] ?? null) ? 'aria-current="page"' : '' ?>><?= esc( $c['name'] ) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="catalog-layout">
        <details class="filterpanel" open>
            <summary>Фильтры</summary>
            <form id="filters" method="get" action="<?= esc( siteUrl($path) ) ?>">
                <label>Поиск
                    <input name="q" type="search" placeholder="Название или артикул" value="<?= esc( $listing['q'] ) ?>">
                </label>

                <?php
                    if ($category):
                    $names = [
                        'fanera' => ['Толщина', 'Формат листа'],
                        'plitka' => ['Поверхность', 'Размер'],
                        'klej' => ['Фасовка', 'Тип смеси']
                    ][$category['slug']] ?? ['Вариант', 'Формат'];
                    foreach (['option' => 'option_name', 'format' => 'format_name'] as $key => $col):
                    
                        $options = run(
                            "SELECT DISTINCT v.$col FROM variants v JOIN products p ON p.id=v.product_id WHERE v.visible=1 AND p.category_id=?",
                            [$category['id']]
                        )->fetchAll(PDO::FETCH_COLUMN);
                        natsort($options);
                ?>
                <fieldset>
                    <legend><?= esc($names[$key === 'option' ? 0 : 1]) ?></legend>
                    <?php foreach ( $options as $option ): ?>
                    <label class="check">
                        <input type="checkbox" name="<?= $key ?>[]" value="<?= esc($option) ?>"
                            <?= in_array( $option, (array) ($_GET[$key] ?? []), true ) ? 'checked' : '' ?>><?= esc($option) ?>
                    </label>
                    <?php endforeach; ?>
                </fieldset>
                <?php
                    endforeach;
                    endif;
                ?>
                <label class="check">
                    <input type="checkbox" value="1" name="stock" <?= isset($_GET['stock']) ? 'checked' : '' ?>>В наличии
                </label>
                <label>Сортировка<select name="sort">
                        <option value="">По умолчанию</option>
                        <option value="low" <?= $listing['sort'] === 'low' ? 'selected' : '' ?>>Сначала дешевле</option>
                        <option value="high" <?= $listing['sort'] === 'high' ? 'selected' : '' ?>>Сначала дороже</option>
                    </select>
                </label>
                <button class="btn">Применить</button>
                <a href="<?= esc(siteUrl($path)) ?>">Сбросить</a>
            </form>
        </details>
        <div>
            <div id="filter-status" role="status"></div>
            <?php if ( isset($_GET['option']) || isset($_GET['format']) ): ?>
            <p class="active-filters">Выбрано: <?= esc( implode( ' · ', array_filter(array_merge((array) ($_GET['option'] ?? []), (array) ($_GET['format'] ?? [])), 'is_string') ) ) ?></p>
            <?php endif; ?>

            <?php renderListing($listing, $path); ?>
        </div>
    </div>
</section>
