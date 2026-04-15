<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message      = '';
$message_type = 'success';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && csrf_verify()) {
    $sys = $pdo->prepare("SELECT thumbnail FROM systems WHERE id=?");
    $sys->execute([$_GET['delete']]);
    $sys = $sys->fetch();
    if ($sys) {
        delete_image($sys['thumbnail']);
        $pdo->prepare("DELETE FROM systems WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Rendszer törölve!';
    }
}

// ── AKTÍV/INAKTÍV TOGGLE ──
if (isset($_GET['toggle']) && is_numeric($_GET['toggle']) && csrf_verify()) {
    $pdo->prepare("UPDATE systems SET active = 1 - active WHERE id=?")
        ->execute([$_GET['toggle']]);
    $message = 'Státusz frissítve!';
}

// ── MENTÉS (ÚJ / SZERKESZTÉS) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_system'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id          = (int)($_POST['id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $name        = trim($_POST['name']        ?? '');
    $slug        = trim($_POST['slug']        ?? '');
    $tagline     = trim($_POST['tagline']     ?? '');
    $description = trim($_POST['description'] ?? '');
    $demo_url    = trim($_POST['demo_url']    ?? '');
    $price_one   = $_POST['price_one_time'] !== '' ? (float)$_POST['price_one_time'] : null;
    $price_month = $_POST['price_monthly']  !== '' ? (float)$_POST['price_monthly']  : null;
    $price_year  = $_POST['price_yearly']   !== '' ? (float)$_POST['price_yearly']   : null;
    $badge       = trim($_POST['badge']       ?? '');
    $badge_color = trim($_POST['badge_color'] ?? '#c8a96e');
    $sort_order  = (int)($_POST['sort_order'] ?? 0);
    $active      = isset($_POST['active']) ? 1 : 0;

    // Features JSON
    $features_raw  = array_filter(array_map('trim', explode("\n", $_POST['features'] ?? '')));
    $features_json = $features_raw ? json_encode(array_values($features_raw), JSON_UNESCAPED_UNICODE) : null;

    // Slug generálás ha üres
    if (!$slug) $slug = generate_slug($name);

    // Slug egyediség
    $slug_check = $pdo->prepare("SELECT id FROM systems WHERE slug=? AND id != ?");
    $slug_check->execute([$slug, $id]);
    if ($slug_check->fetch()) {
        $slug .= '-' . time();
    }

    if (!$name) {
        $message      = 'A név megadása kötelező!';
        $message_type = 'error';
    } else {
        // Kép feltöltés
        $thumbnail = upload_image('thumbnail', 'sys');

        if ($id) {
            // ── SZERKESZTÉS ──
            if ($thumbnail) {
                // Régi kép törlése
                $old = $pdo->prepare("SELECT thumbnail FROM systems WHERE id=?");
                $old->execute([$id]);
                delete_image($old->fetchColumn());
            }

            $update_params = [
                $category_id, $name, $slug, $tagline, $description,
                $features_json, $demo_url, $price_one, $price_month,
                $price_year, $badge, $badge_color, $sort_order, $active
            ];
            $update_sql = "UPDATE systems SET
                category_id=?, name=?, slug=?, tagline=?, description=?,
                features=?, demo_url=?, price_one_time=?, price_monthly=?,
                price_yearly=?, badge=?, badge_color=?, sort_order=?, active=?";

            if ($thumbnail) {
                $update_sql    .= ", thumbnail=?";
                $update_params[] = $thumbnail;
            }
            $update_sql    .= " WHERE id=?";
            $update_params[] = $id;

            $pdo->prepare($update_sql)->execute($update_params);
            $message = 'Rendszer frissítve!';

        } else {
            // ── ÚJ RENDSZER ──
            $pdo->prepare("INSERT INTO systems
                (category_id, name, slug, tagline, description, features,
                 demo_url, price_one_time, price_monthly, price_yearly,
                 badge, badge_color, thumbnail, sort_order, active)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([
                    $category_id, $name, $slug, $tagline, $description,
                    $features_json, $demo_url, $price_one, $price_month,
                    $price_year, $badge, $badge_color, $thumbnail,
                    $sort_order, $active
                ]);
            $message = 'Rendszer létrehozva!';
        }
    }
}

// ── SZERKESZTENDŐ RENDSZER ──
$edit_system = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM systems WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_system = $stmt->fetch();
}

// ── SZŰRŐK ──
$filter_cat    = (int)($_GET['cat']    ?? 0);
$filter_search = trim($_GET['search'] ?? '');
$filter_active = $_GET['active'] ?? '';

$where  = ['1=1'];
$params = [];

if ($filter_cat) {
    $where[]  = 's.category_id = ?';
    $params[] = $filter_cat;
}
if ($filter_search) {
    $where[]  = '(s.name LIKE ? OR s.tagline LIKE ?)';
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
}
if ($filter_active !== '') {
    $where[]  = 's.active = ?';
    $params[] = (int)$filter_active;
}

$systems = $pdo->prepare("
    SELECT s.*, c.name as cat_name, c.icon as cat_icon, c.color as cat_color
    FROM systems s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY s.sort_order ASC, s.created_at DESC
");
$systems->execute($params);
$systems = $systems->fetchAll();

$all_categories = $pdo->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order")->fetchAll();

$page_title = 'Rendszerek';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-th-large"></i> Rendszerek kezelése</h2>
    <button class="btn btn-primary" onclick="openModal('systemModal')">
        <i class="fas fa-plus"></i> Új rendszer
    </button>
</div>

<!-- Szűrők -->
<div class="card" style="margin-bottom:20px;padding:16px 20px;">
    <form method="GET" action="systems.php" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <select name="cat" style="max-width:180px;">
            <option value="">Minden kategória</option>
            <?php foreach ($all_categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $filter_cat == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="active" style="max-width:140px;">
            <option value="">Minden státusz</option>
            <option value="1" <?= $filter_active === '1' ? 'selected' : '' ?>>Aktív</option>
            <option value="0" <?= $filter_active === '0' ? 'selected' : '' ?>>Inaktív</option>
        </select>
        <input type="text" name="search" value="<?= e($filter_search) ?>"
               placeholder="Keresés..." style="max-width:220px;">
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-filter"></i> Szűrés
        </button>
        <a href="systems.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
    </form>
</div>

<!-- Rendszerek táblázat -->
<div class="card">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:60px;">Kép</th>
                <th>Név / Leírás</th>
                <th>Kategória</th>
                <th>Árak</th>
                <th>Badge</th>
                <th>Sorrend</th>
                <th>Státusz</th>
                <th>Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($systems): ?>
            <?php foreach ($systems as $sys): ?>
            <tr>
                <td>
                    <?php if ($sys['thumbnail']): ?>
                    <img src="<?= UPLOAD_URL . e($sys['thumbnail']) ?>"
                         style="width:48px;height:48px;border-radius:8px;object-fit:cover;">
                    <?php else: ?>
                    <div style="width:48px;height:48px;border-radius:8px;background:var(--dark-3);
                                display:flex;align-items:center;justify-content:center;
                                color:var(--text-muted);font-size:18px;">
                        <i class="<?= e($sys['cat_icon'] ?? 'fas fa-calendar') ?>"></i>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong style="color:var(--white);"><?= e($sys['name']) ?></strong><br>
                    <small style="color:var(--text-muted);"><?= e(mb_substr($sys['tagline'] ?? '', 0, 60)) ?></small><br>
                    <small style="color:var(--border);font-family:monospace;">/<?= e($sys['slug']) ?></small>
                </td>
                <td>
                    <?php if ($sys['cat_name']): ?>
                    <span style="display:inline-flex;align-items:center;gap:6px;
                                 background:<?= e($sys['cat_color']) ?>22;
                                 color:<?= e($sys['cat_color']) ?>;
                                 padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                        <i class="<?= e($sys['cat_icon']) ?>"></i>
                        <?= e($sys['cat_name']) ?>
                    </span>
                    <?php else: ?>
                    <span style="color:var(--text-muted);">–</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;">
                    <?php if ($sys['price_one_time']): ?>
                    <div style="color:var(--gold);">
                        <i class="fas fa-infinity" title="Egyszeri"></i>
                        <?= format_price($sys['price_one_time']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($sys['price_monthly']): ?>
                    <div style="color:var(--text-light);">
                        <i class="fas fa-calendar-alt" title="Havi"></i>
                        <?= format_price($sys['price_monthly']) ?>/hó
                    </div>
                    <?php endif; ?>
                    <?php if (!$sys['price_one_time'] && !$sys['price_monthly']): ?>
                    <span style="color:var(--text-muted);">–</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($sys['badge']): ?>
                    <span style="background:<?= e($sys['badge_color']) ?>33;
                                 color:<?= e($sys['badge_color']) ?>;
                                 padding:3px 10px;border-radius:20px;
                                 font-size:11px;font-weight:700;">
                        <?= e($sys['badge']) ?>
                    </span>
                    <?php else: ?>
                    <span style="color:var(--text-muted);">–</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:13px;">
                    <?= $sys['sort_order'] ?>
                </td>
                <td>
                    <a href="?toggle=<?= $sys['id'] ?>&csrf_token=<?= csrf_token() ?>"
                       title="Státusz váltás">
                        <span class="badge <?= $sys['active'] ? 'badge-active' : 'badge-inactive' ?>"
                              style="cursor:pointer;">
                            <?= $sys['active'] ? '● Aktív' : '○ Inaktív' ?>
                        </span>
                    </a>
                </td>
                <td>
                    <div class="table-actions">
                        <?php if ($sys['demo_url']): ?>
                        <a href="<?= e($sys['demo_url']) ?>" target="_blank"
                           class="action-btn view" title="Demo megtekintése">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <?php endif; ?>
                        <button class="action-btn edit" title="Szerkesztés"
                            onclick="editSystem(<?= htmlspecialchars(json_encode($sys), ENT_QUOTES) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?= $sys['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="action-btn delete" title="Törlés"
                           data-confirm="Biztosan törölni szeretnéd ezt a rendszert?">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <i class="fas fa-th-large"></i>
                        <p>Nincs rendszer a megadott szűrőkkel.</p>
                        <button class="btn btn-primary btn-sm" onclick="openModal('systemModal')">
                            <i class="fas fa-plus"></i> Új rendszer létrehozása
                        </button>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ── MODAL: Rendszer szerkesztő ── -->
<div class="modal-overlay" id="systemModal">
    <div class="modal" style="max-width:760px;">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus-circle"></i> Új rendszer</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="systems.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_system" value="1">
            <input type="hidden" name="id" id="sys_id" value="">

            <div class="modal-body">
                <!-- Alapadatok -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Név *</label>
                        <input type="text" name="name" id="sys_name" required
                               placeholder="pl. Barber Shop Rendszer"
                               oninput="generateSlug(this.value)">
                    </div>
                    <div class="form-group">
                        <label>Slug (URL)</label>
                        <input type="text" name="slug" id="sys_slug"
                               placeholder="auto-generalt-slug">
                        <small>Üresen hagyva automatikusan generálódik</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategória</label>
                        <select name="category_id" id="sys_cat">
                            <option value="">– Válassz kategóriát –</option>
                            <?php foreach ($all_categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>">
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Szlogen / Tagline</label>
                        <input type="text" name="tagline" id="sys_tagline"
                               placeholder="Rövid, figyelemfelkeltő mondat">
                    </div>
                </div>

                <div class="form-group">
                    <label>Leírás</label>
                    <textarea name="description" id="sys_desc" rows="3"
                              placeholder="A rendszer részletes leírása..."></textarea>
                </div>

                <div class="form-group">
                    <label>Funkciók / Features
                        <small style="text-transform:none;font-weight:400;">
                            (soronként egy funkció)
                        </small>
                    </label>
                    <textarea name="features" id="sys_features" rows="5"
                              placeholder="Online időpontfoglalás&#10;Email értesítők&#10;Admin felület&#10;Mobilbarát dizájn"></textarea>
                </div>

                <!-- Árak -->
                <div class="section-divider">
                    <span>💰 Árazás</span>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Egyszeri ár (Ft)</label>
                        <input type="number" name="price_one_time" id="sys_price_one"
                               placeholder="0" min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label>Havi ár (Ft)</label>
                        <input type="number" name="price_monthly" id="sys_price_month"
                               placeholder="0" min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label>Éves ár (Ft)</label>
                        <input type="number" name="price_yearly" id="sys_price_year"
                               placeholder="0" min="0" step="1000">
                    </div>
                </div>

                <!-- Badge + Demo -->
                <div class="section-divider"><span>🏷️ Badge & Demo</span></div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Badge szöveg</label>
                        <input type="text" name="badge" id="sys_badge"
                               placeholder="pl. Népszerű, Új, Akció">
                    </div>
                    <div class="form-group">
                        <label>Badge szín</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" name="badge_color" id="sys_badge_color"
                                   value="#c8a96e"
                                   style="width:50px;height:38px;padding:2px;border-radius:6px;cursor:pointer;">
                            <input type="text" id="sys_badge_color_text" value="#c8a96e"
                                   style="flex:1;"
                                   oninput="document.getElementById('sys_badge_color').value=this.value">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Demo URL</label>
                        <input type="url" name="demo_url" id="sys_demo"
                               placeholder="https://demo.foglalas.hu/barbershop">
                    </div>
                    <div class="form-group">
                        <label>Sorrend</label>
                        <input type="number" name="sort_order" id="sys_sort"
                               value="0" min="0">
                    </div>
                </div>

                <!-- Kép + Aktív -->
                <div class="section-divider"><span>🖼️ Kép & Beállítások</span></div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Thumbnail kép</label>
                        <input type="file" name="thumbnail" accept="image/*"
                               onchange="previewImage(this)">
                        <div id="imgPreview" style="margin-top:8px;"></div>
                    </div>
                    <div class="form-group">
                        <label>Aktív</label>
                        <label class="toggle-switch" style="margin-top:8px;">
                            <input type="checkbox" name="active" id="sys_active" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <small>Ha ki van kapcsolva, nem jelenik meg a weboldalon</small>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Mégse</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.section-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0 16px;
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.section-divider::before,
.section-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}
</style>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}

function generateSlug(val) {
    const id = document.getElementById('sys_id').value;
    if (id) return; // szerkesztésnél ne írja felül
    let slug = val.toLowerCase()
        .replace(/[áàä]/g,'a').replace(/[éè]/g,'e').replace(/[íì]/g,'i')
        .replace(/[óöőò]/g,'o').replace(/[úüűù]/g,'u')
        .replace(/[^a-z0-9\s-]/g,'')
        .replace(/[\s]+/g,'-')
        .replace(/-+/g,'-')
        .replace(/^-|-$/g,'');
    document.getElementById('sys_slug').value = slug;
}

function editSystem(sys) {
    document.getElementById('modalTitle').innerHTML =
        '<i class="fas fa-edit"></i> Rendszer szerkesztése';
    document.getElementById('sys_id').value          = sys.id;
    document.getElementById('sys_name').value        = sys.name        || '';
    document.getElementById('sys_slug').value        = sys.slug        || '';
    document.getElementById('sys_tagline').value     = sys.tagline     || '';
    document.getElementById('sys_desc').value        = sys.description || '';
    document.getElementById('sys_demo').value        = sys.demo_url    || '';
    document.getElementById('sys_price_one').value   = sys.price_one_time || '';
    document.getElementById('sys_price_month').value = sys.price_monthly  || '';
    document.getElementById('sys_price_year').value  = sys.price_yearly   || '';
    document.getElementById('sys_badge').value       = sys.badge       || '';
    document.getElementById('sys_badge_color').value = sys.badge_color || '#c8a96e';
    document.getElementById('sys_badge_color_text').value = sys.badge_color || '#c8a96e';
    document.getElementById('sys_sort').value        = sys.sort_order  || 0;
    document.getElementById('sys_active').checked    = sys.active == 1;

    // Kategória
    const catSel = document.getElementById('sys_cat');
    catSel.value = sys.category_id || '';

    // Features
    try {
        const features = JSON.parse(sys.features || '[]');
        document.getElementById('sys_features').value = features.join('\n');
    } catch(e) {
        document.getElementById('sys_features').value = '';
    }

    // Kép előnézet
    if (sys.thumbnail) {
        document.getElementById('imgPreview').innerHTML =
            `<img src="<?= UPLOAD_URL ?>${sys.thumbnail}"
                  style="width:80px;height:80px;border-radius:8px;object-fit:cover;">`;
    } else {
        document.getElementById('imgPreview').innerHTML = '';
    }

    openModal('systemModal');
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreview').innerHTML =
                `<img src="${e.target.result}"
                      style="width:80px;height:80px;border-radius:8px;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Badge color sync
document.getElementById('sys_badge_color')?.addEventListener('input', function() {
    document.getElementById('sys_badge_color_text').value = this.value;
});

// Új rendszer gomb (ha ?new=1)
<?php if (isset($_GET['new'])): ?>
openModal('systemModal');
<?php endif; ?>

// Szerkesztés (ha ?edit=X)
<?php if ($edit_system): ?>
editSystem(<?= json_encode($edit_system) ?>);
<?php endif; ?>
</script>

<?php require_once 'partials/footer.php'; ?>