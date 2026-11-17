<?php
declare(strict_types=1);
function pageLink(string $path, array $query): string
{
    return siteUrl($path) . ($query ? '?' . http_build_query($query) : '');
}
function icon(string $name): string
{
    $paths = [
        'bag' => '<path d="M4 8h16l-1 13H5L4 8Z"/><path d="M8 9V7a4 4 0 0 1 8 0v2"/>',
        'user' => '<circle cx="12" cy="8" r="3.7"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/>',
        'search' => '<circle cx="10" cy="10" r="6.5"/><path d="m15 15 5 5"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'ne' => '<path d="M5 19 19 5M7 5h12v12"/>',
        'logo' => '<path d="m2 8 10-5 10 5-10 5L2 8Zm0 5 10 5 10-5M2 18l10 5 10-5"/>'
    ];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round">' .
        $paths[$name] .
        '</svg>';
}

function productCard(array $v): void
{
?>
<a class="product-card" href="<?= esc(siteUrl(itemPath($v))) ?>">
    <div class="card-image">
        <img loading="lazy" width="600" height="450" src="<?= esc( imageUrl($v['image']) ) ?>" alt="<?= esc($v['name']) ?>">
    </div>
    <div class="card-data">
        <small><?= esc( $v['category_name'] ) ?> / <?= esc($v['option_name']) ?> · <?= esc($v['format_name']) ?></small>
        <h3><?= esc($v['name']) ?></h3>
        <div class="card-bottom">
            <span><?= money( $v['price'] ) ?> <em>/ <?= esc($v['unit']) ?></em>
            </span>
            <span class="circle"><?= icon('ne') ?></span>
        </div>
    </div>
</a>
<?php
}
function renderListing(array $listing, string $path): void
{
?>
<div id="listing" aria-live="polite">
    <p class="resultcount">Найдено товаров: <?= $listing['count'] ?></p>
    <div class="product-grid">
        <?php
            foreach ($listing['items'] as $v) {
                productCard($v);
            }
        ?>
    </div>

    <?php if ( !$listing['count'] ): ?>
    <div class="empty">
        <h2>Ничего не найдено</h2>
        <p>Измените запрос или снимите фильтры.</p>
        <a class="btn" href="<?= esc( siteUrl($path) ) ?>">Сбросить фильтры</a>
    </div>
    <?php endif; ?>

    <?php if ($listing['pages'] > 1): ?>
    <nav class="pagination" aria-label="Страницы каталога">
        <?php
            for (
                $i = 1;
                $i <= $listing['pages'];
                $i++
            ):
            
                $params = $_GET;
                unset($params['page']);
                if ($i > 1) {
                    $params['page'] = $i;
                }
        ?>
        <a <?= $listing['page'] === $i ? 'aria-current="page"' : '' ?> href="<?= esc( pageLink($path, $params) ) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
<?php } ?>
