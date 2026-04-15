<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ── Adatok betöltése ──
$site_name    = get_setting('site_name',    'Foglalas.hu');
$site_tagline = get_setting('site_tagline', 'Okos foglalási rendszerek minden vállalkozásnak');
$site_email   = get_setting('site_email',   'info@foglalas.hu');
$site_phone   = get_setting('site_phone',   '+36 30 123 4567');
$site_address = get_setting('site_address', 'Budapest');
$meta_desc    = get_setting('meta_description', 'Professzionális foglalási rendszerek');
$hero_title   = get_setting('hero_title',   "Okos foglalási rendszer\nvállalkozásodnak");
$hero_sub     = get_setting('hero_subtitle','Egyszerű, gyors, megbízható.');
$hero_cta     = get_setting('hero_cta_text','Rendszerek megtekintése');
$hero_cta_url = get_setting('hero_cta_url', '#systems');
$soc_fb       = get_setting('social_facebook',  '');
$soc_ig       = get_setting('social_instagram', '');
$soc_li       = get_setting('social_linkedin',  '');
$ga_id        = get_setting('google_analytics', '');

// ── Rendszerek ──
$systems = $pdo->query("
    SELECT s.*, c.name as cat_name, c.slug as cat_slug,
           c.icon as cat_icon, c.color as cat_color
    FROM systems s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE s.active = 1
    ORDER BY s.sort_order ASC, s.created_at DESC
")->fetchAll();

// ── Kategóriák ──
$categories = $pdo->query("
    SELECT c.*, COUNT(s.id) as sys_count
    FROM categories c
    INNER JOIN systems s ON s.category_id = c.id AND s.active = 1
    WHERE c.active = 1
    GROUP BY c.id
    HAVING sys_count > 0
    ORDER BY c.sort_order ASC
")->fetchAll();

// ── Vélemények ──
$testimonials = $pdo->query("
    SELECT t.*, s.name as system_name
    FROM testimonials t
    LEFT JOIN systems s ON t.system_id = s.id
    WHERE t.active = 1
    ORDER BY t.sort_order ASC
    LIMIT 6
")->fetchAll();

// ── FAQ ──
$faqs = $pdo->query("
    SELECT * FROM faqs
    WHERE active = 1
    ORDER BY sort_order ASC
    LIMIT 8
")->fetchAll();

// ── Statisztika ──
$total_systems  = $pdo->query("SELECT COUNT(*) FROM systems  WHERE active=1")->fetchColumn();
$total_cats     = $pdo->query("SELECT COUNT(*) FROM categories WHERE active=1")->fetchColumn();

// Statisztika rögzítés
record_stat('view');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="nZohSYf1xE2qqHsM8R9KOu7a_XjGoBo5CslV7hku28M">

    <!-- ── Alap SEO ── -->
    <title><?= e($site_name) ?> – <?= e($site_tagline) ?></title>
    <meta name="description" content="<?= e($meta_desc) ?>">
    <meta name="robots" content="index, follow">

    <!-- ── Open Graph ── -->
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= e($site_name) ?>">
    <meta property="og:description" content="<?= e($meta_desc) ?>">

    <!-- ── Favicon ── -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/favicon/favicon.svg">
    <link rel="icon" type="image/x-icon"  href="<?= BASE_URL ?>/assets/favicon/favicon.ico">
    <meta name="theme-color" content="#1e8a8a">

    <!-- ── Kritikus CSS inline ── -->
    <style>
        /* Alap reset – villódzás ellen */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, sans-serif; background: #080808; color: #fff; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; }
        .hero   { min-height: 100vh; display: flex; align-items: center; padding-top: 80px; }
        .hero h1 { font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 800; line-height: 1.1; }

        /* Font Awesome – csak a használt ikonok */
        @font-face {
            font-family: 'Font Awesome 6 Free';
            font-style: normal;
            font-weight: 900;
            font-display: swap;
            src: url('<?= BASE_URL ?>/assets/fonts/fa-solid-900.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Font Awesome 6 Brands';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('<?= BASE_URL ?>/assets/fonts/fa-brands-400.woff2') format('woff2');
        }
        .fas, .fab {
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
        }
        .fas { font-family: 'Font Awesome 6 Free';   font-weight: 900; }
        .fab { font-family: 'Font Awesome 6 Brands'; font-weight: 400; }

        /* Csak a ténylegesen használt ikonok */
        .fa-check::before               { content: "\f00c"; }
        .fa-calendar::before            { content: "\f133"; }
        .fa-star::before                { content: "\f005"; }
        .fa-arrow-right::before         { content: "\f061"; }
        .fa-arrow-up::before            { content: "\f062"; }
        .fa-bars::before                { content: "\f0c9"; }
        .fa-times::before               { content: "\f00d"; }
        .fa-spinner::before             { content: "\f110"; }
        .fa-check-circle::before        { content: "\f058"; }
        .fa-exclamation-circle::before  { content: "\f06a"; }
        .fa-envelope::before            { content: "\f0e0"; }
        .fa-phone::before               { content: "\f095"; }
        .fa-map-marker-alt::before      { content: "\f3c5"; }
        .fa-chevron-down::before        { content: "\f078"; }
        .fa-play-circle::before         { content: "\f144"; }
        .fa-search::before              { content: "\f002"; }
        .fa-paper-plane::before         { content: "\f1d8"; }
        .fa-rocket::before              { content: "\f135"; }
		.fa-chevron-up::before { content: "\f077"; }
.fa-th-large::before   { content: "\f009"; }
.fa-cut::before        { content: "\f0c4"; }
        /* Social – brands */
        .fa-facebook-f::before          { content: "\f39e"; }
        .fa-instagram::before           { content: "\f16d"; }
        .fa-tiktok::before              { content: "\e07b"; }
    </style>

    <!-- ── Saját CSS – nem blokkoló ── -->
    <link rel="preload" href="<?= BASE_URL ?>/assets/css/style.css?v=1.2"
          as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=1.2">
    </noscript>

    <!-- ── Google Fonts – nem blokkoló ── -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          as="style"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    </noscript>

    <!-- ── Google Analytics ── -->
    <?php if ($ga_id): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga_id) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= e($ga_id) ?>');
    </script>
    <?php endif; ?>
</head>
<body>

<!-- ══════════════════════════════════════
     NAVBAR
══════════════════════════════════════ -->
<nav class="navbar" id="navbar">
    <div class="container">
        <div class="nav-inner">
            <a href="<?= BASE_URL ?>" class="nav-logo">
    <img src="<?= BASE_URL ?>/assets/favicon/logo.svg"
         alt="<?= e($site_name) ?>"
         style="width:32px;height:32px;border-radius:8px;">
    <?= e($site_name) ?>
</a>

            <div class="nav-links">
                <a href="#systems"      class="nav-link">Rendszerek</a>
                <a href="#how-it-works" class="nav-link">Hogyan működik</a>
                <a href="#testimonials" class="nav-link">Vélemények</a>
                <a href="#faq"          class="nav-link">GYIK</a>
                <a href="#contact"      class="nav-link">Kapcsolat</a>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
                <a href="#contact" class="btn btn-primary btn-sm nav-cta">
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
    <a href="#systems"      class="nav-link">Rendszerek</a>
    <a href="#how-it-works" class="nav-link">Hogyan működik</a>
    <a href="#testimonials" class="nav-link">Vélemények</a>
    <a href="#faq"          class="nav-link">GYIK</a>
    <a href="#contact"      class="nav-link">Kapcsolat</a>
    <a href="#contact" class="btn btn-primary" style="margin-top:8px;justify-content:center;">
        <i class="fas fa-paper-plane"></i> Érdekel!
    </a>
</div>

<!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
<section class="hero" id="hero">
    <div class="hero-bg">
        <div class="hero-bg-gradient"></div>
        <div class="hero-grid"></div>
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
    </div>

    <div class="container">
        <div class="hero-inner">
            <!-- Bal oldal -->
            <div class="hero-content reveal">
                <div class="hero-badge">
                    <div class="hero-badge-dot">
                        <i class="fas fa-bolt" style="font-size:10px;"></i>
                    </div>
                    <span>Professzionális foglalási megoldások</span>
                </div>

                <h1 class="hero-title">
                    <?php
                    $lines = explode("\n", $hero_title);
                    foreach ($lines as $i => $line):
                        $line = trim($line);
                        if (!$line) continue;
                    ?>
                    <span class="line <?= $i === 1 ? 'highlight' : '' ?>">
                        <?= e($line) ?>
                    </span>
                    <?php endforeach; ?>
                </h1>

                <p class="hero-sub"><?= e($hero_sub) ?></p>

                <div class="hero-btns">
                    <a href="<?= e($hero_cta_url) ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-th-large"></i>
                        <?= e($hero_cta) ?>
                    </a>
                    <a href="#how-it-works" class="btn btn-outline btn-lg">
                        <i class="fas fa-play-circle"></i>
                        Hogyan működik?
                    </a>
                </div>

                <div class="hero-stats">
                    <div>
                        <span class="hero-stat-val">
                            <?= $total_systems ?><span>+</span>
                        </span>
                        <span class="hero-stat-label">Rendszer</span>
                    </div>
                    <div>
                        <span class="hero-stat-val">
                            <?= $total_cats ?><span>+</span>
                        </span>
                        <span class="hero-stat-label">Kategória</span>
                    </div>
                    <div>
                        <span class="hero-stat-val">100<span>%</span></span>
                        <span class="hero-stat-label">Elégedett ügyfél</span>
                    </div>
                    <div>
                        <span class="hero-stat-val">24<span>/7</span></span>
                        <span class="hero-stat-label">Elérhetőség</span>
                    </div>
                </div>
            </div>

            <!-- Jobb oldal – vizuális -->
            <div class="hero-visual reveal reveal-delay-2">
                <!-- Floating kártyák -->
                <div class="hero-float-card hero-float-1">
                    <div class="hero-float-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <div class="hero-float-text">Foglalás megerősítve!</div>
                        <div class="hero-float-sub">Kovács János · most</div>
                    </div>
                </div>
                <div class="hero-float-card hero-float-2">
                    <div class="hero-float-icon" style="background:rgba(34,197,94,.15);color:#4ade80;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <div class="hero-float-text">5 csillagos értékelés</div>
                        <div class="hero-float-sub">Nagy Éva · 2 perce</div>
                    </div>
                </div>

                <!-- Fő kártya -->
                <div class="hero-card-main">
                    <div class="hero-card-header">
                        <div class="hero-card-dots">
                            <span></span><span></span><span></span>
                        </div>
                        <span class="hero-card-title">foglalas.hu – rendszerek</span>
                    </div>

                    <div class="system-list">
                        <?php foreach (array_slice($systems, 0, 4) as $sys): ?>
                        <div class="system-item">
                            <div class="system-item-icon"
                                 style="background:<?= e($sys['cat_color'] ?? '#c8a96e') ?>22;
                                        color:<?= e($sys['cat_color'] ?? '#c8a96e') ?>;">
                                <i class="<?= e($sys['cat_icon'] ?? 'fas fa-calendar') ?>"></i>
                            </div>
                            <div class="system-item-info">
                                <div class="system-item-name"><?= e($sys['name']) ?></div>
                                <div class="system-item-meta"><?= e($sys['cat_name'] ?? '') ?></div>
                            </div>
                            <?php if ($sys['badge']): ?>
                            <span class="system-item-badge"
                                  style="background:<?= e($sys['badge_color']) ?>22;
                                         color:<?= e($sys['badge_color']) ?>;">
                                <?= e($sys['badge']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <?php if (empty($systems)): ?>
                        <?php
                        $demo = [
                            ['icon'=>'fas fa-tooth',    'color'=>'#3b82f6', 'name'=>'Fogorvos Rendszer',   'cat'=>'Fogorvos'],
                            ['icon'=>'fas fa-utensils', 'color'=>'#f59e0b', 'name'=>'Étterem Rendszer',    'cat'=>'Étterem'],
                            ['icon'=>'fas fa-bed',      'color'=>'#8b5cf6', 'name'=>'Szállás Rendszer',    'cat'=>'Szállás'],
                            ['icon'=>'fas fa-cut',      'color'=>'#c8a96e', 'name'=>'Borbély Rendszer',    'cat'=>'Borbély'],
                        ];
                        foreach ($demo as $d):
                        ?>
                        <div class="system-item">
                            <div class="system-item-icon"
                                 style="background:<?= $d['color'] ?>22;color:<?= $d['color'] ?>;">
                                <i class="<?= $d['icon'] ?>"></i>
                            </div>
                            <div class="system-item-info">
                                <div class="system-item-name"><?= $d['name'] ?></div>
                                <div class="system-item-meta"><?= $d['cat'] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     MARQUEE
══════════════════════════════════════ -->
<div class="marquee-section">
    <div class="marquee-track">
        <?php
        $marquee_items = [
            ['icon'=>'fas fa-tooth',       'text'=>'Fogorvos'],
            ['icon'=>'fas fa-heartbeat',   'text'=>'Nőgyógyász'],
            ['icon'=>'fas fa-utensils',    'text'=>'Étterem'],
            ['icon'=>'fas fa-bed',         'text'=>'Szállás'],
            ['icon'=>'fas fa-cut',         'text'=>'Borbély'],
            ['icon'=>'fas fa-spa',         'text'=>'Szépségszalon'],
            ['icon'=>'fas fa-car',         'text'=>'Autószerelő'],
            ['icon'=>'fas fa-dumbbell',    'text'=>'Edzőterem'],
            ['icon'=>'fas fa-paw',         'text'=>'Állatorvos'],
            ['icon'=>'fas fa-graduation-cap','text'=>'Oktatás'],
        ];
        // Duplikálva a végtelen scrollhoz
        $all_items = array_merge($marquee_items, $marquee_items);
        foreach ($all_items as $item):
        ?>
        <div class="marquee-item">
            <i class="<?= $item['icon'] ?>"></i>
            <span><?= $item['text'] ?></span>
        </div>
        <span class="marquee-sep">·</span>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════════════════════════════════
     STATS
══════════════════════════════════════ -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item reveal">
                <span class="stat-num">
                    <span data-count="<?= $total_systems ?>" data-suffix="+">0+</span>
                </span>
                <span class="stat-label">Elérhető rendszer</span>
            </div>
            <div class="stat-item reveal reveal-delay-1">
                <span class="stat-num">
                    <span data-count="<?= $total_cats ?>" data-suffix="">0</span>
                </span>
                <span class="stat-label">Iparági kategória</span>
            </div>
            <div class="stat-item reveal reveal-delay-2">
                <span class="stat-num">
                    <span data-count="100" data-suffix="%">0%</span>
                </span>
                <span class="stat-label">Elégedettségi arány</span>
            </div>
            <div class="stat-item reveal reveal-delay-3">
                <span class="stat-num">
                    <span data-count="24" data-suffix="/7">0/7</span>
                </span>
                <span class="stat-label">Rendelkezésre állás</span>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     RENDSZEREK
══════════════════════════════════════ -->
<section class="section systems-section" id="systems">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-th-large"></i> Rendszereink
            </div>
            <h2 class="section-title">
                Válaszd ki a <span>vállalkozásodnak</span><br>megfelelő rendszert
            </h2>
            <p class="section-sub">
                Minden iparágra szabott, könnyen kezelhető foglalási rendszerek,
                amelyek azonnal bevezethetők.
            </p>
        </div>

        <!-- Kategória szűrők -->
        <?php if ($categories): ?>
        <div class="category-tabs reveal">
            <button class="cat-tab active" data-cat="all">
                <i class="fas fa-border-all"></i> Összes
            </button>
            <?php foreach ($categories as $cat): ?>
            <button class="cat-tab" data-cat="<?= e($cat['slug']) ?>">
                <i class="<?= e($cat['icon']) ?>"
                   style="color:<?= e($cat['color']) ?>;"></i>
                <?= e($cat['name']) ?>
                <span style="opacity:.5;font-size:11px;">(<?= $cat['sys_count'] ?>)</span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Rendszer kártyák -->
        <?php if ($systems): ?>
        <div class="systems-grid">
            <?php foreach ($systems as $idx => $sys): ?>
            <div class="sys-card <?= $sys['badge'] ? 'featured' : '' ?> reveal reveal-delay-<?= ($idx % 3) + 1 ?>"
                 data-cat="<?= e($sys['cat_slug'] ?? 'egyeb') ?>"
                 style="transition-delay:<?= ($idx % 3) * .1 ?>s;">

                <!-- Kép -->
                <div class="sys-card-img">
                    <?php if ($sys['thumbnail']): ?>
                    <img src="<?= UPLOAD_URL . e($sys['thumbnail']) ?>"
     alt="<?= e($sys['name']) ?> – foglalási rendszer"
     loading="lazy">
                    <?php else: ?>
                    <div class="sys-card-img-placeholder"
                         style="background:<?= e($sys['cat_color'] ?? '#c8a96e') ?>11;">
                        <i class="<?= e($sys['cat_icon'] ?? 'fas fa-calendar') ?>"
                           style="color:<?= e($sys['cat_color'] ?? '#c8a96e') ?>;"></i>
                    </div>
                    <?php endif; ?>

                    <?php if ($sys['badge']): ?>
                    <span class="sys-card-badge"
                          style="background:<?= e($sys['badge_color']) ?>;
                                 color:<?= e(isLightColor($sys['badge_color'])) ? '#000' : '#fff' ?>;">
                        <?= e($sys['badge']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="sys-card-body">
                    <?php if ($sys['cat_name']): ?>
                    <div class="sys-card-cat"
                         style="color:<?= e($sys['cat_color'] ?? '#c8a96e') ?>;">
                        <i class="<?= e($sys['cat_icon'] ?? '') ?>"></i>
                        <?= e($sys['cat_name']) ?>
                    </div>
                    <?php endif; ?>

                    <h3 class="sys-card-name"><?= e($sys['name']) ?></h3>

                    <?php if ($sys['tagline']): ?>
                    <p class="sys-card-desc"><?= e($sys['tagline']) ?></p>
                    <?php elseif ($sys['description']): ?>
                    <p class="sys-card-desc">
                        <?= e(mb_substr($sys['description'], 0, 100)) ?>...
                    </p>
                    <?php endif; ?>

                    <!-- Funkciók -->
                    <?php
                    $features = decode_features($sys['features']);
                    if ($features):
                    ?>
                    <div class="sys-card-features">
                        <?php foreach (array_slice($features, 0, 4) as $feat): ?>
                        <div class="sys-card-feature">
                            <i class="fas fa-check-circle"></i>
                            <?= e($feat) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <div class="sys-card-footer">
    <!-- Ár felül -->
    <div class="sys-card-price">
        <?php if ($sys['price_one_time']): ?>
        <span class="sys-card-price-val">
            <?= number_format($sys['price_one_time'],0,',',' ') ?>
            <span>Ft</span>
        </span>
        <span class="sys-card-price-label">egyszeri díj</span>
        <?php elseif ($sys['price_monthly']): ?>
        <span class="sys-card-price-val">
            <?= number_format($sys['price_monthly'],0,',',' ') ?>
            <span>Ft</span>
        </span>
        <span class="sys-card-price-label">/ hónap</span>
        <?php else: ?>
        <span class="sys-card-price-val" style="color:var(--gold);">
            Egyedi árajánlat
        </span>
        <?php endif; ?>
    </div>
    <!-- Gombok alul -->
    <div class="sys-card-btns">
        <a href="<?= BASE_URL ?>/rendszer/<?= e($sys['slug']) ?>"
   class="btn btn-ghost btn-sm">
    <i class="fas fa-info-circle"></i> Részletek
</a>
        <?php if ($sys['demo_url']): ?>
        <a href="<?= e($sys['demo_url']) ?>" target="_blank"
           class="btn btn-ghost btn-sm"
           onclick="recordDemoClick(<?= $sys['id'] ?>)">
            <i class="fas fa-eye"></i> Demo
        </a>
        <?php endif; ?>
        <a href="#contact"
           class="btn btn-primary btn-sm"
           onclick="preselectSystem(<?= $sys['id'] ?>, '<?= e(addslashes($sys['name'])) ?>')">
            <i class="fas fa-paper-plane"></i> Érdekel
        </a>
    </div>
</div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div style="text-align:center;padding:60px 20px;color:var(--text-muted);">
            <i class="fas fa-th-large" style="font-size:48px;opacity:.3;display:block;margin-bottom:16px;"></i>
            <p>Hamarosan elérhetők lesznek a rendszerek.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════ -->
<section class="section how-section" id="how-it-works">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-route"></i> Folyamat
            </div>
            <h2 class="section-title">
                Hogyan <span>működik?</span>
            </h2>
            <p class="section-sub">
                Mindössze 3 egyszerű lépés, és vállalkozásod már
                online fogadhatja a foglalásokat.
            </p>
        </div>

        <div class="steps-grid">
            <div class="step-card reveal reveal-delay-1">
                <div class="step-num">1</div>
                <div class="step-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="step-title">Válaszd ki a rendszert</h3>
                <p class="step-desc">
                    Böngészd át a kategóriákat, nézd meg a demo verziókat,
                    és válaszd ki a vállalkozásodhoz legjobban illő foglalási rendszert.
                </p>
            </div>
            <div class="step-card reveal reveal-delay-2">
                <div class="step-num">2</div>
                <div class="step-icon">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <h3 class="step-title">Küldj érdeklődést</h3>
                <p class="step-desc">
                    Töltsd ki a kapcsolati űrlapot, és mi hamarosan
                    felvesszük Veled a kapcsolatot az egyedi ajánlattal
                    és a bevezetés részleteivel.
                </p>
            </div>
            <div class="step-card reveal reveal-delay-3">
                <div class="step-num">3</div>
                <div class="step-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3 class="step-title">Indulj el!</h3>
                <p class="step-desc">
                    Mi elvégzünk mindent – beállítjuk, testre szabjuk
                    és élesbe helyezzük a rendszert. Te csak fogadd
                    az online foglalásokat!
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════ -->
<?php if ($testimonials): ?>
<section class="section testi-section" id="testimonials">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-star"></i> Vélemények
            </div>
            <h2 class="section-title">
                Mit mondanak <span>ügyfeleink?</span>
            </h2>
            <p class="section-sub">
                Valódi vélemények valódi vállalkozóktól, akik már
                használják rendszereinket.
            </p>
        </div>

        <div class="testi-slider reveal">
            <?php foreach ($testimonials as $idx => $t): ?>
            <div class="testi-card reveal reveal-delay-<?= ($idx % 3) + 1 ?>">
                <!-- Csillagok -->
                <div class="testi-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= $t['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                    <?php endfor; ?>
                </div>

                <!-- Szöveg -->
                <p class="testi-text"><?= e($t['content']) ?></p>

                <!-- Szerző -->
                <div class="testi-author">
                    <div class="testi-author-img">
                        <?php if ($t['avatar']): ?>
                        <img src="<?= UPLOAD_URL . e($t['avatar']) ?>"
                             alt="<?= e($t['name']) ?>" loading="lazy">
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
                    <?php if ($t['system_name']): ?>
                    <span class="testi-system-tag"><?= e($t['system_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════��═══════
     FAQ
══════════════════════════════════════ -->
<?php if ($faqs): ?>
<section class="section faq-section" id="faq">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-tag">
                <i class="fas fa-question-circle"></i> GYIK
            </div>
            <h2 class="section-title">
                Gyakran ismételt <span>kérdések</span>
            </h2>
            <p class="section-sub">
                Ha nem találod a választ, írj nekünk bátran!
            </p>
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
                    <div class="faq-answer-inner">
                        <?= e($faq['answer']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     CTA / CONTACT
══════════════════════════════════════ -->
<section class="section cta-section" id="contact">
    <div class="cta-bg"></div>
    <div class="container">
        <div class="cta-inner reveal">
            <div class="section-tag" style="margin-bottom:20px;">
                <i class="fas fa-paper-plane"></i> Kapcsolat
            </div>
            <h2 class="cta-title">
                Készen állsz az <span>online foglalásra?</span>
            </h2>
            <p class="cta-sub">
                Töltsd ki az alábbi űrlapot és 24 órán belül
                személyre szabott ajánlattal keresünk meg!
            </p>

            <!-- Kapcsolat form -->
<form class="contact-form" id="contactForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="system_id" id="formSystemId" value="">

    <div class="form-row-2">
        <div class="form-group">
            <label for="contactName">Neved *</label>
            <input type="text"
                   id="contactName"
                   name="name"
                   required
                   placeholder="Kovács János"
                   autocomplete="name">
        </div>
        <div class="form-group">
            <label for="contactEmail">Email *</label>
            <input type="email"
                   id="contactEmail"
                   name="email"
                   required
                   placeholder="email@example.com"
                   autocomplete="email">
        </div>
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <label for="contactPhone">Telefonszám</label>
            <input type="tel"
                   id="contactPhone"
                   name="phone"
                   placeholder="+36 30 123 4567"
                   autocomplete="tel">
        </div>
        <div class="form-group">
            <label for="contactCompany">Cég / Vállalkozás neve</label>
            <input type="text"
                   id="contactCompany"
                   name="company"
                   placeholder="Pl. Kovács Fodrászat"
                   autocomplete="organization">
        </div>
    </div>

    <div class="form-group">
        <label for="formSystemSelect">Érdeklő rendszer</label>
        <select name="system_id"
                id="formSystemSelect"
                autocomplete="off">
            <option value="">– Válassz rendszert (opcionális) –</option>
            <?php foreach ($systems as $sys): ?>
            <option value="<?= $sys['id'] ?>">
                <?= e($sys['name']) ?>
                <?= $sys['cat_name'] ? ' (' . e($sys['cat_name']) . ')' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="contactMessage">Üzenet *</label>
        <textarea id="contactMessage"
                  name="message"
                  rows="4"
                  required
                  placeholder="Írj pár szót vállalkozásodról és igényeidről..."
                  autocomplete="off"></textarea>
    </div>

    <div class="form-submit">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-paper-plane"></i>
            Üzenet küldése
        </button>
    </div>
</form>

            <!-- Elérhetőségek -->
            <div style="display:flex;justify-content:center;gap:32px;
                        flex-wrap:wrap;margin-top:40px;
                        padding-top:32px;border-top:1px solid var(--border);">
                <?php if ($site_email): ?>
                <a href="mailto:<?= e($site_email) ?>"
                   style="display:flex;align-items:center;gap:8px;
                          color:var(--text-muted);font-size:14px;
                          transition:var(--transition);"
                   onmouseover="this.style.color='var(--gold)'"
                   onmouseout="this.style.color='var(--text-muted)'">
                    <i class="fas fa-envelope" style="color:var(--gold);"></i>
                    <?= e($site_email) ?>
                </a>
                <?php endif; ?>
                <?php if ($site_phone): ?>
                <a href="tel:<?= e($site_phone) ?>"
                   style="display:flex;align-items:center;gap:8px;
                          color:var(--text-muted);font-size:14px;
                          transition:var(--transition);"
                   onmouseover="this.style.color='var(--gold)'"
                   onmouseout="this.style.color='var(--text-muted)'">
                    <i class="fas fa-phone" style="color:var(--gold);"></i>
                    <?= e($site_phone) ?>
                </a>
                <?php endif; ?>
                <?php if ($site_address): ?>
                <span style="display:flex;align-items:center;gap:8px;
                             color:var(--text-muted);font-size:14px;">
                    <i class="fas fa-map-marker-alt" style="color:var(--gold);"></i>
                    <?= e($site_address) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand -->
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <?= e($site_name) ?>
                </div>
                <p class="footer-desc">
                    Professzionális foglalási rendszerek minden iparág számára.
                    Egyszerű, gyors, megbízható – azonnali bevezetéssel.
                </p>
                <div class="footer-socials">
                    <?php if ($soc_fb): ?>
                    <a href="<?= e($soc_fb) ?>" target="_blank" class="footer-social"
                       title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($soc_ig): ?>
                    <a href="<?= e($soc_ig) ?>" target="_blank" class="footer-social"
                       title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($soc_li): ?>
                    <a href="<?= e($soc_li) ?>" target="_blank" class="footer-social"
                       title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (!$soc_fb && !$soc_ig && !$soc_li): ?>
                    <a href="#" class="footer-social" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="footer-social" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="footer-social" title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rendszerek -->
            <div class="footer-col">
                <h4>Rendszerek</h4>
                <div class="footer-links">
                    <?php foreach (array_slice($systems, 0, 5) as $sys): ?>
                    <a href="#systems" class="footer-link"
                       onclick="preselectSystem(<?= $sys['id'] ?>, '<?= e(addslashes($sys['name'])) ?>')">
                        <i class="fas fa-chevron-right"></i>
                        <?= e($sys['name']) ?>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($systems)): ?>
                    <span class="footer-link" style="cursor:default;">Hamarosan...</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Navigáció -->
            <div class="footer-col">
                <h4>Navigáció</h4>
                <div class="footer-links">
                    <a href="#hero"          class="footer-link"><i class="fas fa-chevron-right"></i> Főoldal</a>
                    <a href="#systems"       class="footer-link"><i class="fas fa-chevron-right"></i> Rendszerek</a>
                    <a href="#how-it-works"  class="footer-link"><i class="fas fa-chevron-right"></i> Hogyan működik</a>
                    <a href="#testimonials"  class="footer-link"><i class="fas fa-chevron-right"></i> Vélemények</a>
                    <a href="#faq"           class="footer-link"><i class="fas fa-chevron-right"></i> GYIK</a>
                    <a href="#contact"       class="footer-link"><i class="fas fa-chevron-right"></i> Kapcsolat</a>
                </div>
            </div>

            <!-- Kapcsolat -->
            <div class="footer-col">
                <h4>Kapcsolat</h4>
                <?php if ($site_email): ?>
                <div class="footer-contact-item">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?= e($site_email) ?>"
                       style="color:var(--text-muted);transition:var(--transition);"
                       onmouseover="this.style.color='var(--gold)'"
                       onmouseout="this.style.color='var(--text-muted)'">
                        <?= e($site_email) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($site_phone): ?>
                <div class="footer-contact-item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:<?= e($site_phone) ?>"
                       style="color:var(--text-muted);transition:var(--transition);"
                       onmouseover="this.style.color='var(--gold)'"
                       onmouseout="this.style.color='var(--text-muted)'">
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

        <!-- Footer bottom -->
        <div class="footer-bottom">
            <div class="footer-copy">
                &copy; <?= date('Y') ?>
                <a href="#"><?= e($site_name) ?></a>
                – Minden jog fenntartva.
            </div>
            <div class="footer-bottom-links">
                <a href="#">Adatvédelem</a>
                <a href="#">ÁSZF</a>
                
            </div>
        </div>
    </div>
</footer>

<!-- Back to top -->
<button class="back-to-top" id="backToTop" aria-label="Vissza a tetejére">
    <i class="fas fa-chevron-up"></i>
</button>

<script src="assets/js/main.js"></script>
<script>
// Rendszer előválasztás (Érdekel gomb)
function preselectSystem(id, name) {
    const select = document.getElementById('formSystemSelect');
    if (select) select.value = id;

    // Scroll a contact szekcióhoz
    setTimeout(() => {
        const contact = document.getElementById('contact');
        if (contact) {
            contact.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 100);
}

// Demo kattintás rögzítése
function recordDemoClick(systemId) {
    fetch('api/stat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ system_id: systemId, event: 'demo_click' })
    });
}

// Badge szín – világos/sötét
function isLightColor(hex) {
    const c = hex.replace('#','');
    const r = parseInt(c.substr(0,2),16);
    const g = parseInt(c.substr(2,2),16);
    const b = parseInt(c.substr(4,2),16);
    return ((r*299 + g*587 + b*114) / 1000) > 128;
}
</script>

</body>
</html>

<?php
// Helper a badge színhez
function isLightColor(string $hex): bool {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    return (($r*299 + $g*587 + $b*114) / 1000) > 128;
}
?>