<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ── Slug beolvasása (.htaccess rewrite + GET fallback) ──
$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    $path  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    $slug  = end($parts);
}
if (!$slug) {
    header('Location: ' . BASE_URL);
    exit;
}

// ── Rendszer lekérése ──
$stmt = $pdo->prepare("
    SELECT s.*, c.name as cat_name, c.slug as cat_slug,
           c.icon as cat_icon, c.color as cat_color
    FROM systems s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE s.slug = ? AND s.active = 1
");
$stmt->execute([$slug]);
$system = $stmt->fetch();

if (!$system) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// ── Részletes oldal adatok ──
$page = $pdo->prepare("SELECT * FROM system_pages WHERE system_id=?");
$page->execute([$system['id']]);
$page = $page->fetch();

// ── Kapcsolódó rendszerek ──
$related = $pdo->prepare("
    SELECT s.*, c.name as cat_name, c.icon as cat_icon, c.color as cat_color
    FROM systems s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE s.category_id = ? AND s.id != ? AND s.active = 1
    ORDER BY s.sort_order ASC
    LIMIT 3
");
$related->execute([$system['category_id'], $system['id']]);
$related = $related->fetchAll();

// ── Vélemények ──
$testimonials = $pdo->prepare("
    SELECT * FROM testimonials
    WHERE (system_id = ? OR system_id IS NULL) AND active = 1
    ORDER BY sort_order ASC
    LIMIT 4
");
$testimonials->execute([$system['id']]);
$testimonials = $testimonials->fetchAll();

// ── FAQ ──
$faqs = $pdo->prepare("
    SELECT * FROM faqs
    WHERE (system_id = ? OR system_id IS NULL) AND active = 1
    ORDER BY sort_order ASC
    LIMIT 6
");
$faqs->execute([$system['id']]);
$faqs = $faqs->fetchAll();

// ── SEO adatok ──
$site_name    = get_setting('site_name',        'Foglalas.hu');
$site_url     = get_setting('seo_canonical_url', BASE_URL);
$title_suffix = get_setting('seo_title_suffix',  ' – ' . $site_name);
$twitter_site = get_setting('seo_twitter_site',  '');
$org_name     = get_setting('schema_org_name',   $site_name);
$org_phone    = get_setting('schema_org_phone',  get_setting('site_phone', ''));
$org_address  = get_setting('schema_org_address',get_setting('site_address', ''));
$site_email   = get_setting('site_email',        '');

$meta_title  = $page['meta_title']       ?? ($system['name'] . $title_suffix);
$meta_desc   = $page['meta_description'] ?? ($system['tagline'] ?? $system['name']);
$meta_kw     = $page['meta_keywords']    ?? '';
$og_image    = $page['og_image']         ?? $system['thumbnail'] ?? '';
$og_image_url= $og_image
    ? UPLOAD_URL . $og_image
    : $site_url . '/assets/img/og-default.jpg';
$canon_url   = $site_url . '/rendszer/' . urlencode($slug);
$schema_type = $page['schema_type'] ?? 'SoftwareApplication';

// ── Content blocks, galéria, funkciók ──
$blocks   = json_decode($page['content_blocks'] ?? '[]', true) ?: [];
$gallery  = json_decode($page['gallery']        ?? '[]', true) ?: [];
$features = decode_features($system['features']);

// ── Statisztika ──
record_stat('view', $system['id']);

// ── Árazás ──
$pricing_plans = $pdo->prepare("
    SELECT * FROM pricing_plans
    WHERE system_id = ?
    ORDER BY sort_order ASC
");
$pricing_plans->execute([$system['id']]);
$pricing_plans = $pricing_plans->fetchAll();

// ── Footer adatok ──
$soc_fb             = get_setting('social_facebook',  '');
$soc_ig             = get_setting('social_instagram', '');
$soc_li             = get_setting('social_linkedin',  '');
$site_phone         = get_setting('site_phone',        '');
$site_address       = get_setting('site_address',      '');
$all_systems_footer = $pdo->query("
    SELECT name, slug FROM systems
    WHERE active = 1
    ORDER BY sort_order ASC
    LIMIT 5
")->fetchAll();


// ── Biztonsági fallback-ek ──
$site_url  = $site_url  ?: BASE_URL;
$org_name  = $org_name  ?: $site_name;
$site_name = $site_name ?: 'Foglalási Rendszer';


// ── Schema.org JSON-LD ──



// ── aggregateRating számítás testimonials alapján ──
$rating_data = $pdo->prepare("
    SELECT COUNT(*) as cnt, AVG(rating) as avg_rating
    FROM testimonials
    WHERE (system_id = ? OR system_id IS NULL)
    AND active = 1
");
$rating_data->execute([$system['id']]);
$rating_data = $rating_data->fetch();

$rating_count = (int)($rating_data['cnt'] ?? 0);
$rating_value = $rating_data['avg_rating']
    ? round((float)$rating_data['avg_rating'], 1)
    : 5.0;

// ── Schema.org JSON-LD ──
$schema = [
    '@context'            => 'https://schema.org',
    '@type'               => $schema_type,
    'name'                => $system['name'],
    'description'         => $meta_desc,
    'url'                 => $canon_url,
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem'     => 'Web, Android, iOS',
    'provider' => [
        '@type' => 'Organization',
        'name'  => $org_name,
        'url'   => $site_url,
    ],
    // ── Értékelés ──
    'aggregateRating' => [
        '@type'       => 'AggregateRating',
        'ratingValue' => (string)$rating_value,
        'ratingCount' => (string)max($rating_count, 1), // min. 1
        'bestRating'  => '5',
        'worstRating' => '1',
    ],
    // ── Egyedi vélemények ──
    'review' => array_map(fn($t) => [
        '@type'         => 'Review',
        'author'        => [
            '@type' => 'Person',
            'name'  => $t['name'],
        ],
        'reviewRating'  => [
            '@type'       => 'Rating',
            'ratingValue' => (string)$t['rating'],
            'bestRating'  => '5',
            'worstRating' => '1',
        ],
        'reviewBody'    => $t['content'],
        'datePublished' => date('Y-m-d', strtotime($t['created_at'] ?? 'now')),
    ], $testimonials ?: []),
];

// ── Ár ──
if ($system['price_one_time'] || $system['price_monthly']) {
    $schema['offers'] = [
        '@type'         => 'Offer',
        'priceCurrency' => 'HUF',
        'price'         => number_format(
            (float)($system['price_one_time'] ?? $system['price_monthly']),
            2, '.', ''
        ),
        'availability'  => 'https://schema.org/InStock',
        'url'           => $canon_url,
    ];
} else {
    $schema['offers'] = [
        '@type'         => 'Offer',
        'priceCurrency' => 'HUF',
        'price'         => '0',
        'availability'  => 'https://schema.org/InStock',
        'description'   => 'Egyedi árajánlat – vedd fel velünk a kapcsolatot!',
        'url'           => $canon_url,
    ];
}

// ── Kép ──
if ($og_image) {
    $schema['image'] = $og_image_url;
}

// ── Ha nincs vélemény, töröljük az üres review tömböt ──
if (empty($schema['review'])) {
    unset($schema['review']);
}




// BreadcrumbList – csak ha van slug
$breadcrumb_schema = null;
if ($slug && $system['name']) {
    $breadcrumb_schema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => $site_name,
                'item'     => $site_url,
            ],
            [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $system['cat_name'] ?? 'Rendszerek',
                'item'     => $site_url . '/#systems',
            ],
            [
                '@type'    => 'ListItem',
                'position' => 3,
                'name'     => $system['name'],
                'item'     => $canon_url,
            ],
        ],
    ];
}



$org_schema = null;
if ($org_name && $site_url) {
    $org_schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => $org_name,
        'url'      => $site_url,
        'logo'     => get_setting('schema_org_logo', $site_url . '/assets/favicon/logo.svg'),
    ];
    if ($site_email)  $org_schema['email']     = $site_email;
    if ($org_phone)   $org_schema['telephone'] = $org_phone;
    if ($org_address) $org_schema['address']   = [
        '@type'         => 'PostalAddress',
        'streetAddress' => $org_address,
    ];
}

if ($system['price_one_time'] || $system['price_monthly']) {
    $schema['offers'] = [
        '@type'         => 'Offer',
        'priceCurrency' => 'HUF',
        'price'         => $system['price_one_time'] ?? $system['price_monthly'],
        'availability'  => 'https://schema.org/InStock',
        'url'           => $canon_url,
    ];
} else {
    // Ha nincs ár, akkor is kell egy offers hogy ne legyen kritikus hiba
    $schema['offers'] = [
        '@type'         => 'Offer',
        'priceCurrency' => 'HUF',
        'price'         => '0',
        'availability'  => 'https://schema.org/InStock',
        'description'   => 'Egyedi árajánlat – vedd fel velünk a kapcsolatot!',
        'url'           => $canon_url,
    ];
}

if ($og_image) {
    $schema['image'] = $og_image_url;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- ── Favicon ── -->
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/favicon/favicon.svg">
<link rel="icon" type="image/x-icon"  href="<?= BASE_URL ?>/assets/favicon/favicon.ico">
<meta name="theme-color" content="#1e8a8a">

    <!-- ── Alap SEO ── -->
    <title><?= e($meta_title) ?></title>
    <meta name="description" content="<?= e($meta_desc) ?>">
    <?php if ($meta_kw): ?>
    <meta name="keywords" content="<?= e($meta_kw) ?>">
    <?php endif; ?>
    <meta name="robots"   content="index, follow">
    <link rel="canonical" href="<?= e($canon_url) ?>">

    <!-- ── Open Graph ── -->
    <meta property="og:type"         content="website">
    <meta property="og:url"          content="<?= e($canon_url) ?>">
    <meta property="og:title"        content="<?= e($meta_title) ?>">
    <meta property="og:description"  content="<?= e($meta_desc) ?>">
    <meta property="og:image"        content="<?= e($og_image_url) ?>">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name"    content="<?= e($site_name) ?>">
    <meta property="og:locale"       content="hu_HU">

    <!-- ── Twitter Card ── -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= e($meta_title) ?>">
    <meta name="twitter:description" content="<?= e($meta_desc) ?>">
    <meta name="twitter:image"       content="<?= e($og_image_url) ?>">
    <?php if ($twitter_site): ?>
    <meta name="twitter:site"        content="<?= e($twitter_site) ?>">
    <?php endif; ?>

    <!-- ── Schema.org JSON-LD ── -->
    <script type="application/ld+json">
        <?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <script type="application/ld+json">
        <?= json_encode($breadcrumb_schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <script type="application/ld+json">
        <?= json_encode($org_schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <?php if (!empty($faqs) && $faq_schema !== null): ?>
<script type="application/ld+json">
    <?= json_encode($faq_schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/system.css">
</head>
<body>

<!-- ══════════════════════════════════════
     NAVBAR
══════════════════════════════════════ -->
<nav class="navbar" id="navbar">
    <div class="container">
        <div class="nav-inner">
            <a href="<?= BASE_URL ?>" class="nav-logo">
                <div class="nav-logo-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <?= e($site_name) ?>
            </a>
            <div class="nav-links">
                <a href="<?= BASE_URL ?>/#systems"      class="nav-link">Rendszerek</a>
                <a href="<?= BASE_URL ?>/#how-it-works" class="nav-link">Hogyan működik</a>
                <a href="<?= BASE_URL ?>/#testimonials" class="nav-link">Vélemények</a>
                <a href="<?= BASE_URL ?>/#contact"      class="nav-link">Kapcsolat</a>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <a href="#contact-sys" class="btn btn-primary btn-sm nav-cta">
                    <i class="fas fa-paper-plane"></i> Érdekel!
                </a>
                <button class="nav-toggle" id="navToggle" aria-label="Menü">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile nav -->
<div class="nav-mobile" id="mobileNav">
    <a href="<?= BASE_URL ?>/#systems"      class="nav-link">Rendszerek</a>
    <a href="<?= BASE_URL ?>/#how-it-works" class="nav-link">Hogyan működik</a>
    <a href="<?= BASE_URL ?>/#testimonials" class="nav-link">Vélemények</a>
    <a href="<?= BASE_URL ?>/#contact"      class="nav-link">Kapcsolat</a>
    <a href="#contact-sys" class="btn btn-primary"
       style="margin-top:8px;justify-content:center;">
        <i class="fas fa-paper-plane"></i> Érdekel!
    </a>
</div>

<!-- ══════════════════════════════════════
     BREADCRUMB
══════════════════════════════════════ -->
<div class="breadcrumb-bar">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">
                <i class="fas fa-home"></i> Főoldal
            </a>
            <i class="fas fa-chevron-right"></i>
            <a href="<?= BASE_URL ?>/#systems">Rendszerek</a>
            <?php if ($system['cat_name']): ?>
            <i class="fas fa-chevron-right"></i>
            <a href="<?= BASE_URL ?>/#systems"
               style="color:<?= e($system['cat_color']) ?>;">
                <?= e($system['cat_name']) ?>
            </a>
            <?php endif; ?>
            <i class="fas fa-chevron-right"></i>
            <span><?= e($system['name']) ?></span>
        </div>
    </div>
</div>

<!-- ══════════════════���═══════════════════
     HERO
══════════════════════════════════════ -->
<section class="sys-hero">
    <div class="sys-hero-bg">
        <div class="sys-hero-gradient"
             style="--cat-color:<?= e($system['cat_color'] ?? '#c8a96e') ?>;"></div>
        <div class="hero-grid"></div>
    </div>

    <div class="container">
        <div class="sys-hero-inner">
            <div class="sys-hero-content reveal">

                <?php if ($system['cat_name']): ?>
                <div class="sys-cat-badge"
                     style="background:<?= e($system['cat_color']) ?>22;
                            border-color:<?= e($system['cat_color']) ?>44;
                            color:<?= e($system['cat_color']) ?>;">
                    <i class="<?= e($system['cat_icon']) ?>"></i>
                    <?= e($system['cat_name']) ?>
                </div>
                <?php endif; ?>

                <h1 class="sys-hero-title">
                    <?= e($page['hero_title'] ?? $system['name']) ?>
                </h1>

                <p class="sys-hero-sub">
                    <?= e($page['hero_subtitle'] ?? $system['tagline'] ?? '') ?>
                </p>

                <!-- Ár -->
                <div class="sys-hero-price">
                    <?php if ($system['price_one_time']): ?>
                    <div class="price-box">
                        <span class="price-val">
                            <?= number_format($system['price_one_time'], 0, ',', ' ') ?>
                            <span>Ft</span>
                        </span>
                        <span class="price-label">egyszeri díj</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($system['price_monthly']): ?>
                    <div class="price-box">
                        <span class="price-val">
                            <?= number_format($system['price_monthly'], 0, ',', ' ') ?>
                            <span>Ft</span>
                        </span>
                        <span class="price-label">/ hónap</span>
                    </div>
                    <?php endif; ?>
                    <?php if (!$system['price_one_time'] && !$system['price_monthly']): ?>
                    <div class="price-box">
                        <span class="price-val" style="color:var(--gold);">
                            Egyedi árajánlat
                        </span>
                        <span class="price-label">vedd fel velünk a kapcsolatot</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="sys-hero-btns">
                    <a href="#contact-sys" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Érdekel, kérek ajánlatot!
                    </a>
                    <?php if ($system['demo_url']): ?>
                    <a href="<?= e($system['demo_url']) ?>" target="_blank"
                       class="btn btn-outline btn-lg"
                       onclick="recordDemoClick(<?= $system['id'] ?>)">
                        <i class="fas fa-external-link-alt"></i> Demo megtekintése
                    </a>
                    <?php endif; ?>
                </div>

                <?php if ($system['badge']): ?>
                <div style="margin-top:16px;">
                    <span style="background:<?= e($system['badge_color']) ?>;
                                 color:#fff;padding:5px 14px;border-radius:20px;
                                 font-size:12px;font-weight:700;">
                        <?= e($system['badge']) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Jobb oldal: kép vagy placeholder -->
            <div class="sys-hero-visual reveal reveal-delay-2">
                <?php if ($system['thumbnail']): ?>
                <div class="sys-hero-img">
                    <img src="<?= UPLOAD_URL . e($system['thumbnail']) ?>"
                         alt="<?= e($system['name']) ?>">
                </div>
                <?php else: ?>
                <div class="sys-hero-placeholder"
                     style="background:<?= e($system['cat_color'] ?? '#c8a96e') ?>11;">
                    <i class="<?= e($system['cat_icon'] ?? 'fas fa-calendar') ?>"
                       style="color:<?= e($system['cat_color'] ?? '#c8a96e') ?>;"></i>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═════════════════════════��════════════
     FUNKCIÓK
══════════════════════════════════════ -->
<?php if ($features): ?>
<section class="section sys-features-section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-check-circle"></i> Funkciók
            </div>
            <h2 class="section-title">
                Mit tartalmaz a <span>rendszer?</span>
            </h2>
        </div>

        <div class="features-grid">
            <?php foreach ($features as $i => $feat): ?>
            <div class="feature-item reveal reveal-delay-<?= ($i % 4) + 1 ?>">
                <div class="feature-icon"
                     style="background:<?= e($system['cat_color'] ?? '#c8a96e') ?>22;
                            color:<?= e($system['cat_color'] ?? '#c8a96e') ?>;">
                    <i class="fas fa-check"></i>
                </div>
                <span><?= e($feat) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     CONTENT BLOCKS
══════════════════════════════════════ -->
<?php if ($blocks): ?>
<section class="section sys-blocks-section">
    <div class="container">
        <?php foreach ($blocks as $i => $block):
            $type  = $block['type']  ?? 'text';
            $title = $block['title'] ?? '';
            $text  = $block['text']  ?? '';
            $icon  = $block['icon']  ?? 'fas fa-star';
            $color = $block['color'] ?? '#c8a96e';
        ?>

        <?php if ($type === 'text'): ?>
        <div class="content-block text-block reveal"
             style="--block-color:<?= e($color) ?>;">
            <?php if ($title): ?>
            <h3 class="block-title"><?= e($title) ?></h3>
            <?php endif; ?>
            <?php if ($text): ?>
            <div class="block-text"><?= nl2br(e($text)) ?></div>
            <?php endif; ?>
        </div>

        <?php elseif ($type === 'feature'): ?>
        <div class="content-block feature-block reveal reveal-delay-<?= ($i % 3) + 1 ?>">
            <div class="feature-block-icon"
                 style="background:<?= e($color) ?>22;color:<?= e($color) ?>;">
                <i class="<?= e($icon) ?>"></i>
            </div>
            <div class="feature-block-body">
                <?php if ($title): ?>
                <h3 class="feature-block-title"><?= e($title) ?></h3>
                <?php endif; ?>
                <?php if ($text): ?>
                <p class="feature-block-text"><?= nl2br(e($text)) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($type === 'cta'): ?>
        <div class="content-block cta-block reveal"
             style="border-color:<?= e($color) ?>44;
                    background:<?= e($color) ?>0a;">
            <div class="cta-block-inner">
                <?php if ($title): ?>
                <h3 style="color:var(--white);font-size:22px;margin-bottom:8px;">
                    <?= e($title) ?>
                </h3>
                <?php endif; ?>
                <?php if ($text): ?>
                <p style="color:var(--text-muted);margin-bottom:20px;">
                    <?= nl2br(e($text)) ?>
                </p>
                <?php endif; ?>
                <a href="#contact-sys" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Érdekel, kérek ajánlatot!
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     GALÉRIA
══════════════════════════════════════ -->
<?php if ($gallery): ?>
<section class="section sys-gallery-section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-images"></i> Galéria
            </div>
            <h2 class="section-title">
                Képek a <span>rendszerről</span>
            </h2>
        </div>

        <div class="gallery-masonry reveal">
            <?php foreach ($gallery as $i => $img): ?>
            <div class="gallery-masonry-item reveal reveal-delay-<?= ($i % 4) + 1 ?>"
                 onclick="openLightbox(<?= $i ?>)">
                <img src="<?= UPLOAD_URL . e($img) ?>"
                     alt="<?= e($system['name']) ?> – <?= $i + 1 ?>. kép"
                     loading="lazy">
                <div class="gallery-overlay">
                    <i class="fas fa-expand"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">
        <i class="fas fa-times"></i>
    </button>
    <button class="lightbox-prev"
            onclick="event.stopPropagation();lightboxNav(-1)">
        <i class="fas fa-chevron-left"></i>
    </button>
    <div class="lightbox-inner" onclick="event.stopPropagation()">
        <img id="lightboxImg" src="" alt="">
        <div id="lightboxCaption"></div>
    </div>
    <button class="lightbox-next"
            onclick="event.stopPropagation();lightboxNav(1)">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     ÁRAZÁS
══════════════════════════════════════ -->
<?php if ($pricing_plans): ?>
<section class="section sys-pricing-section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-tags"></i> Árazás
            </div>
            <h2 class="section-title">
                Válaszd ki a <span>megfelelő csomagot</span>
            </h2>
        </div>

        <div class="pricing-grid reveal">
            <?php foreach ($pricing_plans as $plan): ?>
            <div class="pricing-card <?= $plan['is_featured'] ? 'featured' : '' ?>">
                <?php if ($plan['is_featured']): ?>
                <div class="pricing-featured-badge">Ajánlott</div>
                <?php endif; ?>
                <div class="pricing-name"><?= e($plan['name']) ?></div>
                <div class="pricing-price">
                    <?= number_format($plan['price'], 0, ',', ' ') ?>
                    <span>Ft</span>
                </div>
                <div class="pricing-period">
                    <?php
                    $periods = [
                        'one_time' => 'egyszeri díj',
                        'monthly'  => '/ hónap',
                        'yearly'   => '/ év',
                    ];
                    echo $periods[$plan['period']] ?? '';
                    ?>
                </div>
                <?php if ($plan['description']): ?>
                <p class="pricing-desc"><?= e($plan['description']) ?></p>
                <?php endif; ?>
                <?php
                $plan_features = decode_features($plan['features']);
                if ($plan_features):
                ?>
                <ul class="pricing-features">
                    <?php foreach ($plan_features as $pf): ?>
                    <li>
                        <i class="fas fa-check-circle"
                           style="color:<?= e($system['cat_color'] ?? '#c8a96e') ?>;"></i>
                        <?= e($pf) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <a href="#contact-sys"
                   class="btn <?= $plan['is_featured'] ? 'btn-primary' : 'btn-outline' ?>"
                   style="width:100%;justify-content:center;">
                    <i class="fas fa-paper-plane"></i> Ezt választom
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     VÉLEMÉNYEK
══════════════════════════════════════ -->
<?php if ($testimonials): ?>
<section class="section testi-section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-star"></i> Vélemények
            </div>
            <h2 class="section-title">
                Mit mondanak <span>ügyfeleink?</span>
            </h2>
        </div>

        <div class="testi-slider reveal">
            <?php foreach ($testimonials as $i => $t): ?>
            <div class="testi-card reveal reveal-delay-<?= ($i % 3) + 1 ?>">
                <div class="testi-stars">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="<?= $s <= $t['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                    <?php endfor; ?>
                </div>
                <p class="testi-text"><?= e($t['content']) ?></p>
                <div class="testi-author">
                    <div class="testi-author-img">
                        <?php if ($t['avatar']): ?>
                        <img src="<?= UPLOAD_URL . e($t['avatar']) ?>"
                             alt="<?= e($t['name']) ?>">
                        <?php else: ?>
                        <div class="testi-author-placeholder">
                            <?= strtoupper(mb_substr($t['name'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="testi-author-name"><?= e($t['name']) ?></div>
                        <div class="testi-author-pos">
                            <?= e($t['position'] ?? '') ?>
                            <?= ($t['position'] && $t['company']) ? ' · ' : '' ?>
                            <?= e($t['company'] ?? '') ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     FAQ
══════════════════════════════════════ -->
<?php if ($faqs): ?>
<section class="section faq-section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-question-circle"></i> GYIK
            </div>
            <h2 class="section-title">
                Gyakran ismételt <span>kérdések</span>
            </h2>
        </div>

        <div class="faq-list reveal">
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <div class="faq-question">
                    <span><?= e($faq['question']) ?></span>
                    <div class="faq-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-inner"><?= e($faq['answer']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     KAPCSOLÓDÓ RENDSZEREK
══════════════════════════════════════ -->
<?php if ($related): ?>
<section class="section sys-related-section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-th-large"></i> Kapcsolódó
            </div>
            <h2 class="section-title">
                Hasonló <span>rendszerek</span>
            </h2>
        </div>

        <div class="related-grid reveal">
            <?php foreach ($related as $i => $rel): ?>
            <div class="sys-card reveal reveal-delay-<?= $i + 1 ?>">
                <div class="sys-card-img">
                    <?php if ($rel['thumbnail']): ?>
                    <img src="<?= UPLOAD_URL . e($rel['thumbnail']) ?>"
                         alt="<?= e($rel['name']) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="sys-card-img-placeholder"
                         style="background:<?= e($rel['cat_color'] ?? '#c8a96e') ?>11;">
                        <i class="<?= e($rel['cat_icon'] ?? 'fas fa-calendar') ?>"
                           style="color:<?= e($rel['cat_color'] ?? '#c8a96e') ?>;"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="sys-card-body">
                    <div class="sys-card-cat"
                         style="color:<?= e($rel['cat_color'] ?? '#c8a96e') ?>;">
                        <i class="<?= e($rel['cat_icon'] ?? '') ?>"></i>
                        <?= e($rel['cat_name'] ?? '') ?>
                    </div>
                    <h3 class="sys-card-name"><?= e($rel['name']) ?></h3>
                    <p class="sys-card-desc">
                        <?= e(mb_substr($rel['tagline'] ?? '', 0, 80)) ?>
                    </p>
                </div>
                <div class="sys-card-footer">
                    <div class="sys-card-price">
                        <?php if ($rel['price_one_time']): ?>
                        <span class="sys-card-price-val">
                            <?= number_format($rel['price_one_time'], 0, ',', ' ') ?>
                            <span>Ft</span>
                        </span>
                        <span class="sys-card-price-label">egyszeri díj</span>
                        <?php elseif ($rel['price_monthly']): ?>
                        <span class="sys-card-price-val">
                            <?= number_format($rel['price_monthly'], 0, ',', ' ') ?>
                            <span>Ft</span>
                        </span>
                        <span class="sys-card-price-label">/ hónap</span>
                        <?php else: ?>
                        <span class="sys-card-price-val" style="color:var(--gold);">
                            Egyedi ár
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="sys-card-btns">
                        <a href="<?= BASE_URL ?>/rendszer/<?= e($rel['slug']) ?>"
                           class="btn btn-ghost btn-sm">
                            <i class="fas fa-info-circle"></i> Részletek
                        </a>
                        <a href="#contact-sys" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane"></i> Érdekel
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     CONTACT
══════════════════════════════════════ -->
<section class="section cta-section" id="contact-sys">
    <div class="cta-bg"></div>
    <div class="container">
        <div class="cta-inner reveal">
            <div class="section-tag" style="margin-bottom:20px;">
                <i class="fas fa-paper-plane"></i> Érdeklődés
            </div>
            <h2 class="cta-title">
                Érdekel a <span><?= e($system['name']) ?>?</span>
            </h2>
            <p class="cta-sub">
                Töltsd ki az alábbi űrlapot és 24 órán belül
                személyre szabott ajánlattal keresünk meg!
            </p>

            <form class="contact-form" id="contactForm">
                <input type="hidden" name="csrf_token"
                       value="<?= csrf_token() ?>">
                <input type="hidden" name="system_id"
                       value="<?= $system['id'] ?>">

                <div class="form-row-2">
                    <div class="form-group">
                        <label>Neved *</label>
                        <input type="text" name="name" required
                               placeholder="Kovács János">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required
                               placeholder="email@example.com">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label>Telefonszám</label>
                        <input type="tel" name="phone"
                               placeholder="+36 30 123 4567">
                    </div>
                    <div class="form-group">
                        <label>Cég / Vállalkozás</label>
                        <input type="text" name="company"
                               placeholder="Kovács Borbélyüzlet">
                    </div>
                </div>
                <div class="form-group">
                    <label>Üzenet *</label>
                    <textarea name="message" rows="4" required
                              placeholder="Írj pár szót vállalkozásodról és igényeidről..."></textarea>
                </div>
                <div class="form-submit">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Küldés
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <?= e($site_name) ?>
                </div>
                <p class="footer-desc">
                    Professzionális foglalási rendszerek minden iparág számára.
                    Egyszerű, gyors, megbízható.
                </p>
                <div class="footer-socials">
                    <?php if ($soc_fb): ?>
                    <a href="<?= e($soc_fb) ?>" target="_blank" class="footer-social">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($soc_ig): ?>
                    <a href="<?= e($soc_ig) ?>" target="_blank" class="footer-social">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($soc_li): ?>
                    <a href="<?= e($soc_li) ?>" target="_blank" class="footer-social">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer-col">
                <h4>Rendszerek</h4>
                <div class="footer-links">
                    <?php foreach ($all_systems_footer as $fs): ?>
                    <a href="<?= BASE_URL ?>/rendszer/<?= e($fs['slug']) ?>"
                       class="footer-link">
                        <i class="fas fa-chevron-right"></i>
                        <?= e($fs['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="footer-col">
                <h4>Navigáció</h4>
                <div class="footer-links">
                    <a href="<?= BASE_URL ?>"
                       class="footer-link">
                        <i class="fas fa-chevron-right"></i> Főoldal
                    </a>
                    <a href="<?= BASE_URL ?>/#systems"
                       class="footer-link">
                        <i class="fas fa-chevron-right"></i> Rendszerek
                    </a>
                    <a href="<?= BASE_URL ?>/#how-it-works"
                       class="footer-link">
                        <i class="fas fa-chevron-right"></i> Hogyan működik
                    </a>
                    <a href="<?= BASE_URL ?>/#contact"
                       class="footer-link">
                        <i class="fas fa-chevron-right"></i> Kapcsolat
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Kapcsolat</h4>
                <?php if ($site_email): ?>
                <div class="footer-contact-item">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?= e($site_email) ?>"
                       style="color:var(--text-muted);">
                        <?= e($site_email) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($site_phone): ?>
                <div class="footer-contact-item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:<?= e($site_phone) ?>"
                       style="color:var(--text-muted);">
                        <?= e($site_phone) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($site_address): ?>
                <div class="footer-contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= e($site_address) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-copy">
                &copy; <?= date('Y') ?>
                <a href="<?= BASE_URL ?>"><?= e($site_name) ?></a>
                – Minden jog fenntartva.
            </div>
            <div class="footer-bottom-links">
                <a href="#">Adatvédelem</a>
                <a href="#">ÁSZF</a>
                <a href="<?= BASE_URL ?>/admin/">Admin</a>
            </div>
        </div>
    </div>
</footer>

<!-- ══════════════════════════════════════
     STICKY CTA (mobil) – footer UTÁN!
══════════════════════════════════════ -->
<div class="sticky-cta" id="stickyCta"
     style="transform:translateY(100%);transition:transform .3s ease;">
    <div class="sticky-cta-info">
        <div class="sticky-cta-name"><?= e($system['name']) ?></div>
        <div class="sticky-cta-price">
            <?php if ($system['price_one_time']): ?>
                <?= number_format($system['price_one_time'], 0, ',', ' ') ?> Ft – egyszeri
            <?php elseif ($system['price_monthly']): ?>
                <?= number_format($system['price_monthly'], 0, ',', ' ') ?> Ft / hó
            <?php else: ?>
                Egyedi árajánlat
            <?php endif; ?>
        </div>
    </div>
    <a href="#contact-sys" class="btn btn-primary btn-sm">
        <i class="fas fa-paper-plane"></i> Érdekel!
    </a>
</div>

<!-- Back to top -->
<button class="back-to-top" id="backToTop" aria-label="Vissza a tetejére">
    <i class="fas fa-chevron-up"></i>
</button>
<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// ── Lightbox ──
const galleryImages = <?= json_encode(
    array_map(fn($img) => UPLOAD_URL . $img, $gallery),
    JSON_UNESCAPED_UNICODE
) ?>;
let currentLightbox = 0;

function openLightbox(idx) {
    currentLightbox = idx;
    document.getElementById('lightboxImg').src = galleryImages[idx];
    document.getElementById('lightboxCaption').textContent =
        (idx + 1) + ' / ' + galleryImages.length;
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}
function lightboxNav(dir) {
    currentLightbox =
        (currentLightbox + dir + galleryImages.length) % galleryImages.length;
    document.getElementById('lightboxImg').src = galleryImages[currentLightbox];
    document.getElementById('lightboxCaption').textContent =
        (currentLightbox + 1) + ' / ' + galleryImages.length;
}
document.addEventListener('keydown', e => {
    if (!document.getElementById('lightbox')?.classList.contains('open')) return;
    if (e.key === 'ArrowLeft')  lightboxNav(-1);
    if (e.key === 'ArrowRight') lightboxNav(1);
    if (e.key === 'Escape')     closeLightbox();
});

// ── Demo kattintás rögzítése ──
function recordDemoClick(id) {
    fetch('<?= BASE_URL ?>/api/stat.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ system_id: id, event: 'demo_click' }),
    });
}
</script>
</body>
</html>