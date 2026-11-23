<?php
$visual = [
    'fanera' => ['hero', 'Листовые материалы', 'Слоистый торец фанеры', 'Выбрать фанеру', 'Толщина листа', '6–24 мм'],
    'plitka' => [
        'tile-travertine',
        'Отделочные материалы',
        'Матовая поверхность плитки под травертин',
        'Выбрать плитку',
        'Формат образца',
        '60 × 60 см'
    ],
    'klej' => [
        'glue-basic',
        'Сухие смеси',
        'Бумажный мешок базового плиточного клея',
        'Выбрать клей',
        'Масса мешка',
        '25 кг'
    ]
];
$first = $categories[0] ?? ['name' => 'Материалы', 'slug' => ''];
$hero = $visual[$first['slug']] ?? ['hero', 'Строительные материалы', 'Крупный план слоистого торца фанеры', 'Выбрать материалы', 'Подбор материалов', 'Для вашего проекта'];
$heroPath = $first['slug'] !== '' ? '/catalog/' . $first['slug'] . '/' : '/catalog/';
?>
<section class="hero-wrap">
    <div class="shell">
        <div class="hero" id="hero">
            <div class="hero-art">
                <img id="hero-image" src="<?= esc( imageUrl($hero[0]) ) ?>" fetchpriority="high" width="1062" height="458"
                    alt="<?= esc($hero[0] === 'hero' ? 'Крупный план слоистого торца фанеры' : $hero[2]) ?>">
            </div>
            <div class="hero-copy">
                <div class="eyebrow">
                    <i></i>
                    <span id="hero-kind"><?= esc($hero[1]) ?></span>
                </div>
                <h1 id="hero-title"><?= esc($first['name']) ?></h1>
                <a id="hero-cta" class="btn lime" href="<?= esc(siteUrl($heroPath)) ?>"><?= esc($hero[3]) ?> <?= icon( 'ne' ) ?></a>
            </div>
            <div class="hero-caption">
                <span id="hero-measure"><?= esc($hero[4]) ?></span>
                <b id="hero-value"><?= esc( $hero[5] ) ?></b>
            </div>
        </div>
        <div class="hero-strip">
            <span class="mono">Выберите материал</span>
            <div class="slide-nav" aria-label="Материалы на витрине">
                <?php
                    foreach (
                        $categories
                        as $i => $c
                    ):
                    
                        $g = $visual[$c['slug']] ?? null;
                        if (!$g) {
                            continue;
                        }
                ?>
                <a class="slide <?= !$i ? 'active' : '' ?>" aria-label="<?= esc($c['name']) ?>" href="<?= esc( siteUrl('/catalog/' . $c['slug'] . '/') ) ?>"
                    data-image="<?= esc(imageUrl($g[0])) ?>" data-title="<?= esc($c['name']) ?>" data-kind="<?= esc( $g[1] ) ?>"
                    data-alt="<?= esc($g[2]) ?>" data-cta="<?= esc($g[3]) ?>" data-measure="<?= esc($g[4]) ?>"
                    data-value="<?= esc( $g[5] ) ?>"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<section class="shell home-section">
    <div class="section-head">
        <div class="section-line">
            <h2>Каталог материалов</h2>
            <a class="textlink" href="<?= esc( siteUrl('/catalog/') ) ?>">Смотреть все <?= icon('ne') ?></a>
        </div>
    </div>
    <div class="category-grid">
        <?php
            foreach ($categories as $c):
            $im = ['fanera' => 'plywood-light', 'plitka' => 'tile-sage', 'klej' => 'glue-flex'][$c['slug']] ?? 'plywood';
        ?>
        <a class="category-card" href="<?= esc( siteUrl('/catalog/' . $c['slug'] . '/') ) ?>">
            <div class="cat-photo">
                <img loading="lazy" width="700" height="500" src="<?= esc(imageUrl($im)) ?>" alt="<?= esc( $c['name'] ) ?>">
            </div>
            <div class="cat-name">
                <h3><?= esc($c['name']) ?></h3>
                <span class="circle"><?= icon('ne') ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<section class="shell home-section">
    <div class="section-line">
        <h2>Материалы в наличии</h2>
    </div>
    <div class="product-grid">
        <?php
            foreach (
                rows(selectVariants() . ' WHERE v.visible=1 AND v.stock>0 AND v.id IN (1,5,7,9) ORDER BY v.id')
                as $v
            ) {
                productCard($v);
            }
        ?>
    </div>
</section>
