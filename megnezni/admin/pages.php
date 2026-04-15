<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message      = '';
$message_type = 'success';

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $system_id        = (int)($_POST['system_id'] ?? 0);
    $hero_title       = trim($_POST['hero_title']       ?? '');
    $hero_subtitle    = trim($_POST['hero_subtitle']    ?? '');
    $meta_title       = trim($_POST['meta_title']       ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords    = trim($_POST['meta_keywords']    ?? '');
    $schema_type      = trim($_POST['schema_type']      ?? 'SoftwareApplication');

    if (!$system_id) {
        $message      = 'Válassz rendszert!';
        $message_type = 'error';
    } else {

        // ── Content blocks ──
        $blocks     = [];
        $block_types  = $_POST['block_type']    ?? [];
        $block_titles = $_POST['block_title']   ?? [];
        $block_texts  = $_POST['block_text']    ?? [];
        $block_icons  = $_POST['block_icon']    ?? [];
        $block_colors = $_POST['block_color']   ?? [];

        foreach ($block_types as $i => $type) {
            if (empty($type)) continue;
            $blocks[] = [
                'type'  => $type,
                'title' => $block_titles[$i]  ?? '',
                'text'  => $block_texts[$i]   ?? '',
                'icon'  => $block_icons[$i]   ?? '',
                'color' => $block_colors[$i]  ?? '#c8a96e',
            ];
        }

        // ── Galéria képek ──
        $existing_gallery = json_decode($_POST['existing_gallery'] ?? '[]', true) ?: [];
        $new_images = [];

        if (!empty($_FILES['gallery_images']['name'][0])) {
            foreach ($_FILES['gallery_images']['tmp_name'] as $idx => $tmp) {
                if (!$tmp) continue;
                $ext     = strtolower(pathinfo($_FILES['gallery_images']['name'][$idx], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $allowed)) continue;
                if ($_FILES['gallery_images']['size'][$idx] > 5242880) continue;
                if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
                $filename = 'gallery_' . time() . '_' . $idx . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmp, UPLOAD_PATH . $filename)) {
                    $new_images[] = $filename;
                }
            }
        }

        $gallery = array_merge($existing_gallery, $new_images);

        // ── OG kép ──
        $og_image = null;
        if (!empty($_FILES['og_image']['tmp_name'])) {
            $og_image = upload_image('og_image', 'og');
            // Régi OG kép törlése
            $old_og = $pdo->prepare("SELECT og_image FROM system_pages WHERE system_id=?");
            $old_og->execute([$system_id]);
            $old_og = $old_og->fetchColumn();
            if ($old_og) delete_image($old_og);
        }

        $content_blocks_json = json_encode($blocks,   JSON_UNESCAPED_UNICODE);
        $gallery_json        = json_encode($gallery,  JSON_UNESCAPED_UNICODE);

        // ── Upsert ──
        $exists = $pdo->prepare("SELECT id, og_image FROM system_pages WHERE system_id=?");
        $exists->execute([$system_id]);
        $existing = $exists->fetch();

        if ($existing) {
            $final_og = $og_image ?? $existing['og_image'];
            $pdo->prepare("UPDATE system_pages SET
                hero_title=?, hero_subtitle=?, content_blocks=?, gallery=?,
                meta_title=?, meta_description=?, meta_keywords=?,
                og_image=?, schema_type=?
                WHERE system_id=?")
                ->execute([
                    $hero_title, $hero_subtitle, $content_blocks_json, $gallery_json,
                    $meta_title, $meta_description, $meta_keywords,
                    $final_og, $schema_type, $system_id
                ]);
        } else {
            $pdo->prepare("INSERT INTO system_pages
                (system_id, hero_title, hero_subtitle, content_blocks, gallery,
                 meta_title, meta_description, meta_keywords, og_image, schema_type)
                VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([
                    $system_id, $hero_title, $hero_subtitle,
                    $content_blocks_json, $gallery_json,
                    $meta_title, $meta_description, $meta_keywords,
                    $og_image, $schema_type
                ]);
        }

        $message = 'Oldal mentve!';
    }
}

// ── GALÉRIA KÉP TÖRLÉS ──
if (isset($_GET['del_img']) && isset($_GET['sys']) && csrf_verify()) {
    $sys_id   = (int)$_GET['sys'];
    $img_name = basename($_GET['del_img']);
    $page     = $pdo->prepare("SELECT gallery FROM system_pages WHERE system_id=?");
    $page->execute([$sys_id]);
    $page = $page->fetch();
    if ($page) {
        $gallery = json_decode($page['gallery'], true) ?: [];
        $gallery = array_values(array_filter($gallery, fn($g) => $g !== $img_name));
        delete_image($img_name);
        $pdo->prepare("UPDATE system_pages SET gallery=? WHERE system_id=?")
            ->execute([json_encode($gallery, JSON_UNESCAPED_UNICODE), $sys_id]);
        $message = 'Kép törölve!';
    }
    header('Location: pages.php?sys=' . $sys_id);
    exit;
}

// ── RENDSZEREK LISTA ──
$all_systems = $pdo->query("
    SELECT s.*, c.name as cat_name, c.icon as cat_icon, c.color as cat_color,
           sp.id as page_id, sp.updated_at as page_updated
    FROM systems s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN system_pages sp ON sp.system_id = s.id
    WHERE s.active = 1
    ORDER BY s.sort_order ASC, s.name ASC
")->fetchAll();

// ── AKTÍV RENDSZER ──
$active_sys_id = (int)($_GET['sys'] ?? 0);
$active_system = null;
$active_page   = null;

if ($active_sys_id) {
    foreach ($all_systems as $s) {
        if ($s['id'] == $active_sys_id) { $active_system = $s; break; }
    }
    $page_data = $pdo->prepare("SELECT * FROM system_pages WHERE system_id=?");
    $page_data->execute([$active_sys_id]);
    $active_page = $page_data->fetch();
}

$page_title = 'Oldalak kezelése';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-file-alt"></i> Oldalak kezelése</h2>
    <span style="color:var(--text-muted);font-size:13px;">
        Rendszer részletes oldalak szerkesztése
    </span>
</div>

<div class="pages-layout">

    <!-- ── BAL OLDAL: Rendszer lista ── -->
    <div class="pages-sidebar">
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;
                            letter-spacing:1px;color:var(--text-muted);">
                    Rendszerek
                </div>
            </div>
            <div class="sys-list">
                <?php foreach ($all_systems as $sys): ?>
                <a href="pages.php?sys=<?= $sys['id'] ?>"
                   class="sys-list-item <?= $active_sys_id == $sys['id'] ? 'active' : '' ?>">
                    <div class="sys-list-icon"
                         style="background:<?= e($sys['cat_color'] ?? '#c8a96e') ?>22;
                                color:<?= e($sys['cat_color'] ?? '#c8a96e') ?>;">
                        <i class="<?= e($sys['cat_icon'] ?? 'fas fa-calendar') ?>"></i>
                    </div>
                    <div class="sys-list-info">
                        <div class="sys-list-name"><?= e($sys['name']) ?></div>
                        <div class="sys-list-meta">
                            <?php if ($sys['page_id']): ?>
                            <span style="color:var(--green);font-size:10px;">
                                <i class="fas fa-check-circle"></i>
                                Szerkesztve: <?= date('m.d', strtotime($sys['page_updated'])) ?>
                            </span>
                            <?php else: ?>
                            <span style="color:var(--text-muted);font-size:10px;">
                                <i class="fas fa-circle"></i> Nincs oldal
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($active_sys_id == $sys['id']): ?>
                    <i class="fas fa-chevron-right" style="color:var(--gold);font-size:11px;"></i>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── JOB OLDAL: Szerkesztő ── -->
    <div class="pages-editor">
        <?php if (!$active_system): ?>
        <!-- Üres állapot -->
        <div class="card">
            <div class="empty-state" style="padding:80px 20px;">
                <i class="fas fa-file-alt" style="color:var(--gold);opacity:.4;"></i>
                <p>Válassz egy rendszert a bal oldali listából a szerkesztéshez.</p>
            </div>
        </div>

        <?php else: ?>
        <!-- Szerkesztő -->
        <form method="POST" action="pages.php?sys=<?= $active_sys_id ?>"
              enctype="multipart/form-data" id="pageForm">
            <input type="hidden" name="csrf_token"  value="<?= csrf_token() ?>">
            <input type="hidden" name="save_page"   value="1">
            <input type="hidden" name="system_id"   value="<?= $active_sys_id ?>">
            <input type="hidden" name="existing_gallery"
                   id="existingGallery"
                   value="<?= e(json_encode(json_decode($active_page['gallery'] ?? '[]'), JSON_UNESCAPED_UNICODE)) ?>">

            <!-- Fejléc -->
            <div style="display:flex;align-items:center;justify-content:space-between;
                        margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:10px;
                                background:<?= e($active_system['cat_color'] ?? '#c8a96e') ?>22;
                                color:<?= e($active_system['cat_color'] ?? '#c8a96e') ?>;
                                display:flex;align-items:center;justify-content:center;font-size:18px;">
                        <i class="<?= e($active_system['cat_icon'] ?? 'fas fa-calendar') ?>"></i>
                    </div>
                    <div>
                        <div style="font-size:17px;font-weight:700;color:var(--white);">
                            <?= e($active_system['name']) ?>
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);">
                            <?= e($active_system['cat_name'] ?? '') ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="<?= BASE_URL ?>/rendszer/<?= e($active_system['slug']) ?>"
                       target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fas fa-eye"></i> Előnézet
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save"></i> Mentés
                    </button>
                </div>
            </div>

            <!-- ── HERO SZEKCIÓ ── -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-image"></i> Hero szekció</h3>
                </div>
                <div class="form-group">
                    <label>Hero főcím</label>
                    <input type="text" name="hero_title"
                           value="<?= e($active_page['hero_title'] ?? $active_system['name']) ?>"
                           placeholder="<?= e($active_system['name']) ?>">
                    <small>Ha üres, a rendszer neve jelenik meg</small>
                </div>
                <div class="form-group">
                    <label>Hero alcím</label>
                    <textarea name="hero_subtitle" rows="2"
                              placeholder="Rövid, figyelemfelkeltő leírás..."><?= e($active_page['hero_subtitle'] ?? $active_system['tagline'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- ── CONTENT BLOCKS ── -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-th-list"></i> Tartalom blokkok</h3>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn btn-secondary btn-sm"
                                onclick="addBlock('text')">
                            <i class="fas fa-paragraph"></i> Szöveg
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm"
                                onclick="addBlock('feature')">
                            <i class="fas fa-star"></i> Feature
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm"
                                onclick="addBlock('cta')">
                            <i class="fas fa-bolt"></i> CTA
                        </button>
                    </div>
                </div>

                <div id="blocksContainer">
                    <?php
                    $blocks = json_decode($active_page['content_blocks'] ?? '[]', true) ?: [];
                    foreach ($blocks as $bi => $block):
                    ?>
                    <div class="block-item" data-index="<?= $bi ?>">
                        <?php include_once 'partials/block_row.php'; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($blocks)): ?>
                <div id="blocksEmpty" style="text-align:center;padding:32px;
                     color:var(--text-muted);font-size:13px;">
                    <i class="fas fa-plus-circle" style="font-size:28px;
                       color:var(--gold);opacity:.5;display:block;margin-bottom:10px;"></i>
                    Adj hozzá blokkokat a gombokkal fentebb!
                </div>
                <?php endif; ?>
            </div>

            <!-- ── GALÉRIA ── -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-images"></i> Galéria</h3>
                </div>

                <!-- Meglévő képek -->
                <?php
                $gallery = json_decode($active_page['gallery'] ?? '[]', true) ?: [];
                ?>
                <?php if ($gallery): ?>
                <div class="gallery-grid" id="galleryGrid">
                    <?php foreach ($gallery as $img): ?>
                    <div class="gallery-item" id="gimg-<?= e($img) ?>">
                        <img src="<?= UPLOAD_URL . e($img) ?>" alt="">
                        <a href="pages.php?sys=<?= $active_sys_id ?>&del_img=<?= e($img) ?>&csrf_token=<?= csrf_token() ?>"
                           class="gallery-del"
                           data-confirm="Biztosan törölni szeretnéd ezt a képet?">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Feltöltés -->
                <div class="upload-area" id="uploadArea"
                     onclick="document.getElementById('galleryInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Kattints vagy húzd ide a képeket</span>
                    <small>JPG, PNG, WebP – max 5MB/kép</small>
                </div>
                <input type="file" name="gallery_images[]" id="galleryInput"
                       multiple accept="image/*" style="display:none;"
                       onchange="previewGallery(this)">
                <div id="galleryPreview" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;"></div>
            </div>

            <!-- ── SEO ── -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> SEO beállítások</h3>
                </div>

                <div class="form-group">
                    <label>Meta title</label>
                    <input type="text" name="meta_title"
                           value="<?= e($active_page['meta_title'] ?? '') ?>"
                           placeholder="<?= e($active_system['name']) ?> – <?= e(get_setting('site_name')) ?>"
                           id="metaTitle"
                           oninput="updateSeoPreview()">
                    <div style="display:flex;justify-content:space-between;margin-top:4px;">
                        <small>Optimális: 50-60 karakter</small>
                        <small id="titleCount" style="color:var(--gold);">0 karakter</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Meta description</label>
                    <textarea name="meta_description" rows="2"
                              id="metaDesc"
                              placeholder="Rövid, vonzó leírás a keresőtalálatokhoz..."
                              oninput="updateSeoPreview()"><?= e($active_page['meta_description'] ?? '') ?></textarea>
                    <div style="display:flex;justify-content:space-between;margin-top:4px;">
                        <small>Optimális: 150-160 karakter</small>
                        <small id="descCount" style="color:var(--gold);">0 karakter</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Meta keywords</label>
                    <input type="text" name="meta_keywords"
                           value="<?= e($active_page['meta_keywords'] ?? '') ?>"
                           placeholder="foglalás, online időpont, borbély...">
                    <small>Vesszővel elválasztva</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Schema.org típus</label>
                        <select name="schema_type">
                            <?php
                            $schema_types = [
                                'SoftwareApplication' => 'SoftwareApplication (szoftver)',
                                'WebApplication'      => 'WebApplication (webalkalmazás)',
                                'Product'             => 'Product (termék)',
                                'Service'             => 'Service (szolgáltatás)',
                            ];
                            foreach ($schema_types as $val => $lbl):
                            ?>
                            <option value="<?= $val ?>"
                                    <?= ($active_page['schema_type'] ?? 'SoftwareApplication') === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>OG / Social kép</label>
                        <input type="file" name="og_image" accept="image/*"
                               onchange="previewOgImage(this)">
                        <?php if ($active_page['og_image'] ?? null): ?>
                        <div style="margin-top:8px;">
                            <img src="<?= UPLOAD_URL . e($active_page['og_image']) ?>"
                                 style="height:50px;border-radius:6px;object-fit:cover;">
                        </div>
                        <?php endif; ?>
                        <div id="ogPreview"></div>
                        <small>Ajánlott: 1200×630px</small>
                    </div>
                </div>

                <!-- SEO előnézet -->
                <div class="seo-preview">
                    <div style="font-size:11px;color:var(--text-muted);
                                text-transform:uppercase;letter-spacing:1px;
                                margin-bottom:10px;">Google előnézet</div>
                    <div class="seo-preview-url">
    <?= e(BASE_URL) ?>/rendszer/<?= e($active_system['slug']) ?>
</div>
                    <div class="seo-preview-title" id="seoPreviewTitle">
                        <?= e($active_page['meta_title'] ?? $active_system['name'] . ' – ' . get_setting('site_name')) ?>
                    </div>
                    <div class="seo-preview-desc" id="seoPreviewDesc">
                        <?= e($active_page['meta_description'] ?? $active_system['tagline'] ?? '') ?>
                    </div>
                </div>
            </div>

            <!-- Mentés gomb -->
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:40px;">
                <a href="<?= BASE_URL ?>/rendszer/<?= e($active_system['slug']) ?>"
                   target="_blank" class="btn btn-secondary">
                    <i class="fas fa-eye"></i> Előnézet
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Oldal mentése
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

</div><!-- /.pages-layout -->

<!-- ── BLOCK TEMPLATE (JS-nek) ── -->
<template id="blockTemplate">
    <div class="block-item" data-index="__INDEX__">
        <div class="block-header">
            <div style="display:flex;align-items:center;gap:8px;">
                <i class="fas fa-grip-vertical block-drag" style="color:var(--text-muted);cursor:grab;"></i>
                <span class="block-type-label">Blokk</span>
            </div>
            <button type="button" class="action-btn delete" onclick="removeBlock(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <input type="hidden" name="block_type[]" class="block-type-input" value="">
        <div class="block-fields">
            <div class="form-row">
                <div class="form-group">
                    <label>Cím</label>
                    <input type="text" name="block_title[]" placeholder="Blokk címe">
                </div>
                <div class="form-group">
                    <label>Ikon (FA)</label>
                    <input type="text" name="block_icon[]" placeholder="fas fa-star">
                </div>
            </div>
            <div class="form-group">
                <label>Szöveg / Leírás</label>
                <textarea name="block_text[]" rows="3" placeholder="Blokk tartalma..."></textarea>
            </div>
            <div class="form-group">
                <label>Szín</label>
                <input type="color" name="block_color[]" value="#c8a96e"
                       style="width:50px;height:32px;padding:2px;border-radius:6px;cursor:pointer;">
            </div>
        </div>
    </div>
</template>

<style>
/* Layout */
.pages-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px;
    align-items: start;
}

/* Bal sidebar */
.pages-sidebar { position: sticky; top: calc(var(--topbar-h) + 20px); }
.sys-list { max-height: calc(100vh - 220px); overflow-y: auto; }
.sys-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    text-decoration: none;
    border-bottom: 1px solid var(--border);
    transition: var(--transition, all .2s);
    cursor: pointer;
}
.sys-list-item:last-child { border-bottom: none; }
.sys-list-item:hover  { background: rgba(255,255,255,.02); }
.sys-list-item.active { background: var(--gold-light); }
.sys-list-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.sys-list-info { flex: 1; min-width: 0; }
.sys-list-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--white);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sys-list-meta { font-size: 11px; margin-top: 2px; }

/* Block item */
.block-item {
    background: var(--dark-3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 12px;
    overflow: hidden;
}
.block-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: var(--dark-4);
    border-bottom: 1px solid var(--border);
}
.block-type-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--gold);
    text-transform: uppercase;
    letter-spacing: 1px;
}
.block-fields { padding: 16px; }

/* Galéria */
.gallery-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 16px;
}
.gallery-item {
    position: relative;
    width: 100px; height: 100px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border);
}
.gallery-item img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.gallery-del {
    position: absolute;
    top: 4px; right: 4px;
    width: 22px; height: 22px;
    background: rgba(239,68,68,.9);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 10px;
    text-decoration: none;
    transition: all .2s;
}
.gallery-del:hover { background: var(--red); transform: scale(1.1); }

/* Upload area */
.upload-area {
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    padding: 32px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    font-size: 14px;
}
.upload-area i    { font-size: 28px; color: var(--gold); opacity: .7; }
.upload-area small { font-size: 12px; }
.upload-area:hover {
    border-color: var(--gold);
    background: var(--gold-light);
    color: var(--text);
}

/* SEO preview */
.seo-preview {
    background: var(--dark-3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px;
    margin-top: 16px;
}
.seo-preview-url   { font-size: 13px; color: #4ade80; margin-bottom: 4px; }
.seo-preview-title {
    font-size: 17px;
    color: #93c5fd;
    font-weight: 600;
    margin-bottom: 6px;
    line-height: 1.3;
}
.seo-preview-desc { font-size: 13px; color: var(--text-muted); line-height: 1.6; }

@media(max-width: 1024px) {
    .pages-layout { grid-template-columns: 1fr; }
    .pages-sidebar { position: static; }
}
</style>

<script>
let blockIndex = <?= count($blocks ?? []) ?>;

const blockLabels = {
    text:    '📄 Szöveg blokk',
    feature: '⭐ Feature blokk',
    cta:     '⚡ CTA blokk',
};

function addBlock(type) {
    const empty = document.getElementById('blocksEmpty');
    if (empty) empty.remove();

    const template = document.getElementById('blockTemplate');
    const clone    = template.content.cloneNode(true);
    const item     = clone.querySelector('.block-item');

    item.dataset.index = blockIndex;
    item.innerHTML     = item.innerHTML.replace(/__INDEX__/g, blockIndex);
    item.querySelector('.block-type-input').value   = type;
    item.querySelector('.block-type-label').textContent = blockLabels[type] || type;

    // CTA blokkhoz nincs ikon mező
    if (type === 'cta') {
        const iconGroup = item.querySelectorAll('.form-group')[1];
        if (iconGroup) iconGroup.style.display = 'none';
    }

    document.getElementById('blocksContainer').appendChild(clone);
    blockIndex++;
}

function removeBlock(btn) {
    if (!confirm('Biztosan törlöd ezt a blokkot?')) return;
    btn.closest('.block-item').remove();
    if (!document.querySelectorAll('.block-item').length) {
        document.getElementById('blocksContainer').insertAdjacentHTML('afterbegin',
            `<div id="blocksEmpty" style="text-align:center;padding:32px;
             color:var(--text-muted);font-size:13px;">
                <i class="fas fa-plus-circle" style="font-size:28px;color:var(--gold);
                   opacity:.5;display:block;margin-bottom:10px;"></i>
                Adj hozzá blokkokat a gombokkal fentebb!
             </div>`
        );
    }
}

// Meglévő blokkok feltöltése
<?php if (!empty($blocks)): ?>
document.querySelectorAll('.block-item').forEach((item, i) => {
    const type  = <?= json_encode(array_column($blocks, 'type')) ?>[i];
    const label = item.querySelector('.block-type-label');
    if (label && type) label.textContent = blockLabels[type] || type;
});
<?php endif; ?>

// SEO preview
function updateSeoPreview() {
    const title = document.getElementById('metaTitle')?.value || '';
    const desc  = document.getElementById('metaDesc')?.value  || '';

    document.getElementById('seoPreviewTitle').textContent = title || '–';
    document.getElementById('seoPreviewDesc').textContent  = desc  || '–';
    document.getElementById('titleCount').textContent      = title.length + ' karakter';
    document.getElementById('descCount').textContent       = desc.length  + ' karakter';

    // Szín jelzés
    const tc = document.getElementById('titleCount');
    tc.style.color = title.length > 60  ? '#f87171' :
                     title.length >= 50 ? '#4ade80' : 'var(--gold)';
    const dc = document.getElementById('descCount');
    dc.style.color = desc.length > 160  ? '#f87171' :
                     desc.length >= 150 ? '#4ade80' : 'var(--gold)';
}
updateSeoPreview();

// Galéria előnézet
function previewGallery(input) {
    const preview = document.getElementById('galleryPreview');
    preview.innerHTML = '';
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            preview.insertAdjacentHTML('beforeend',
                `<div style="width:80px;height:80px;border-radius:8px;overflow:hidden;
                             border:1px solid var(--border);">
                    <img src="${e.target.result}"
                         style="width:100%;height:100%;object-fit:cover;">
                </div>`
            );
        };
        reader.readAsDataURL(file);
    });
}

// OG kép előnézet
function previewOgImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('ogPreview').innerHTML =
                `<img src="${e.target.result}"
                      style="height:60px;border-radius:6px;
                             object-fit:cover;margin-top:8px;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Drag & Drop feltöltés
const uploadArea = document.getElementById('uploadArea');
if (uploadArea) {
    uploadArea.addEventListener('dragover', e => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--gold)';
        uploadArea.style.background  = 'var(--gold-light)';
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '';
        uploadArea.style.background  = '';
    });
    uploadArea.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.style.borderColor = '';
        uploadArea.style.background  = '';
        const input = document.getElementById('galleryInput');
        input.files = e.dataTransfer.files;
        previewGallery(input);
    });
}
</script>

<?php require_once 'partials/footer.php'; ?>