<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message      = '';
$message_type = 'success';

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && csrf_verify()) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE category_id=?");
    $check->execute([$_GET['delete']]);
    if ($check->fetchColumn() > 0) {
        $message      = 'Ez a kategória nem törölhető, mert rendszerek tartoznak hozzá!';
        $message_type = 'error';
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Kategória törölve!';
    }
}

// ── TOGGLE ──
if (isset($_GET['toggle']) && is_numeric($_GET['toggle']) && csrf_verify()) {
    $pdo->prepare("UPDATE categories SET active = 1 - active WHERE id=?")
        ->execute([$_GET['toggle']]);
    $message = 'Státusz frissítve!';
}

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name']        ?? '');
    $slug        = trim($_POST['slug']        ?? '');
    $icon        = trim($_POST['icon']        ?? 'fas fa-calendar');
    $description = trim($_POST['description'] ?? '');
    $color       = trim($_POST['color']       ?? '#c8a96e');
    $sort_order  = (int)($_POST['sort_order'] ?? 0);
    $active      = isset($_POST['active']) ? 1 : 0;

    if (!$slug) $slug = generate_slug($name);

    // Slug egyediség
    $slug_check = $pdo->prepare("SELECT id FROM categories WHERE slug=? AND id != ?");
    $slug_check->execute([$slug, $id]);
    if ($slug_check->fetch()) $slug .= '-' . time();

    if (!$name) {
        $message      = 'A név megadása kötelező!';
        $message_type = 'error';
    } else {
        if ($id) {
            $pdo->prepare("UPDATE categories SET
                name=?, slug=?, icon=?, description=?, color=?, sort_order=?, active=?
                WHERE id=?")
                ->execute([$name, $slug, $icon, $description, $color, $sort_order, $active, $id]);
            $message = 'Kategória frissítve!';
        } else {
            $pdo->prepare("INSERT INTO categories
                (name, slug, icon, description, color, sort_order, active)
                VALUES (?,?,?,?,?,?,?)")
                ->execute([$name, $slug, $icon, $description, $color, $sort_order, $active]);
            $message = 'Kategória létrehozva!';
        }
    }
}

// ── LISTA ──
$categories = $pdo->query("
    SELECT c.*, COUNT(s.id) as system_count
    FROM categories c
    LEFT JOIN systems s ON s.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.name ASC
")->fetchAll();

$page_title = 'Kategóriák';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-folder"></i> Kategóriák kezelése</h2>
    <button class="btn btn-primary" onclick="openCatModal()">
        <i class="fas fa-plus"></i> Új kategória
    </button>
</div>

<!-- Kategória kártyák -->
<div class="cat-grid">
    <?php foreach ($categories as $cat): ?>
    <div class="cat-card <?= !$cat['active'] ? 'inactive' : '' ?>">
        <div class="cat-card-header" style="background:<?= e($cat['color']) ?>22;border-color:<?= e($cat['color']) ?>44;">
            <div class="cat-icon" style="background:<?= e($cat['color']) ?>33;color:<?= e($cat['color']) ?>;">
                <i class="<?= e($cat['icon']) ?>"></i>
            </div>
            <div class="cat-status">
                <a href="?toggle=<?= $cat['id'] ?>&csrf_token=<?= csrf_token() ?>">
                    <span class="badge <?= $cat['active'] ? 'badge-active' : 'badge-inactive' ?>"
                          style="cursor:pointer;font-size:10px;">
                        <?= $cat['active'] ? '● Aktív' : '○ Inaktív' ?>
                    </span>
                </a>
            </div>
        </div>
        <div class="cat-card-body">
            <h4><?= e($cat['name']) ?></h4>
            <p class="cat-slug"><code>/<?= e($cat['slug']) ?></code></p>
            <?php if ($cat['description']): ?>
            <p class="cat-desc"><?= e(mb_substr($cat['description'], 0, 80)) ?>...</p>
            <?php endif; ?>
            <div class="cat-meta">
                <span style="color:<?= e($cat['color']) ?>;font-size:12px;font-weight:600;">
                    <i class="fas fa-th-large"></i> <?= $cat['system_count'] ?> rendszer
                </span>
                <span style="color:var(--text-muted);font-size:12px;">
                    Sorrend: <?= $cat['sort_order'] ?>
                </span>
            </div>
        </div>
        <div class="cat-card-footer">
            <button class="btn btn-secondary btn-sm" style="flex:1;"
                onclick="editCat(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>)">
                <i class="fas fa-edit"></i> Szerkesztés
            </button>
            <?php if ($cat['system_count'] == 0): ?>
            <a href="?delete=<?= $cat['id'] ?>&csrf_token=<?= csrf_token() ?>"
               class="btn btn-danger btn-sm"
               data-confirm="Biztosan törölni szeretnéd ezt a kategóriát?">
                <i class="fas fa-trash"></i>
            </a>
            <?php else: ?>
            <button class="btn btn-secondary btn-sm" disabled title="Nem törölhető, mert rendszerek tartoznak hozzá">
                <i class="fas fa-lock"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Új kategória kártya -->
    <div class="cat-card cat-card-new" onclick="openCatModal()">
        <div class="cat-new-inner">
            <i class="fas fa-plus-circle"></i>
            <span>Új kategória hozzáadása</span>
        </div>
    </div>
</div>

<!-- ── MODAL ── -->
<div class="modal-overlay" id="catModal">
    <div class="modal" style="max-width:580px;">
        <div class="modal-header">
            <h3 id="catModalTitle"><i class="fas fa-folder-plus"></i> Új kategória</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="categories.php">
            <input type="hidden" name="csrf_token"    value="<?= csrf_token() ?>">
            <input type="hidden" name="save_category" value="1">
            <input type="hidden" name="id" id="cat_id" value="">

            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Név *</label>
                        <input type="text" name="name" id="cat_name" required
                               placeholder="pl. Fogorvos"
                               oninput="catGenerateSlug(this.value)">
                    </div>
                    <div class="form-group">
                        <label>Slug (URL)</label>
                        <input type="text" name="slug" id="cat_slug"
                               placeholder="auto-generalt">
                        <small>Üresen hagyva automatikusan generálódik</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Leírás</label>
                    <textarea name="description" id="cat_desc" rows="2"
                              placeholder="Rövid leírás a kategóriáról..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            Font Awesome ikon
                            <a href="https://fontawesome.com/icons" target="_blank"
                               style="color:var(--gold);font-size:11px;margin-left:6px;">
                                <i class="fas fa-external-link-alt"></i> Ikonok
                            </a>
                        </label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="text" name="icon" id="cat_icon"
                                   placeholder="fas fa-tooth" style="flex:1;"
                                   oninput="updateIconPreview(this.value)">
                            <div id="iconPreview"
                                 style="width:38px;height:38px;background:var(--dark-3);
                                        border:1px solid var(--border);border-radius:8px;
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:16px;color:var(--gold);flex-shrink:0;">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                        <small>Pl: fas fa-tooth · fas fa-heartbeat · fas fa-utensils · fas fa-bed</small>
                    </div>
                    <div class="form-group">
                        <label>Szín</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" name="color" id="cat_color"
                                   value="#c8a96e"
                                   style="width:50px;height:38px;padding:2px;
                                          border-radius:6px;cursor:pointer;
                                          border:1px solid var(--border);background:var(--dark-3);">
                            <input type="text" id="cat_color_text" value="#c8a96e"
                                   style="flex:1;"
                                   oninput="document.getElementById('cat_color').value=this.value">
                        </div>
                    </div>
                </div>

                <!-- Gyors ikon választó -->
                <div class="form-group">
                    <label>Gyors ikon választó</label>
                    <div class="icon-picker">
                        <?php
                        $quick_icons = [
                            'fas fa-tooth'         => 'Fogorvos',
                            'fas fa-heartbeat'     => 'Orvos',
                            'fas fa-utensils'      => 'Étterem',
                            'fas fa-bed'           => 'Szállás',
                            'fas fa-cut'           => 'Borbély',
                            'fas fa-spa'           => 'Szépség',
                            'fas fa-car'           => 'Autó',
                            'fas fa-dumbbell'      => 'Edzőterem',
                            'fas fa-graduation-cap'=> 'Oktatás',
                            'fas fa-paw'           => 'Állatorvos',
                            'fas fa-camera'        => 'Fotós',
                            'fas fa-music'         => 'Zene',
                            'fas fa-home'          => 'Ingatlan',
                            'fas fa-briefcase'     => 'Üzlet',
                            'fas fa-calendar-check'=> 'Egyéb',
                        ];
                        foreach ($quick_icons as $ico => $label): ?>
                        <button type="button" class="icon-pick-btn" title="<?= $label ?>"
                                onclick="pickIcon('<?= $ico ?>')">
                            <i class="<?= $ico ?>"></i>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Sorrend</label>
                        <input type="number" name="sort_order" id="cat_sort"
                               value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Aktív</label>
                        <label class="toggle-switch" style="margin-top:8px;">
                            <input type="checkbox" name="active" id="cat_active" checked>
                            <span class="toggle-slider"></span>
                        </label>
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
/* Kategória grid */
.cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.cat-card {
    background: var(--dark-2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: border-color .2s, transform .2s;
    display: flex;
    flex-direction: column;
}
.cat-card:hover { border-color: var(--gold); transform: translateY(-2px); }
.cat-card.inactive { opacity: .6; }

.cat-card-header {
    padding: 20px;
    border-bottom: 1px solid;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.cat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
}
.cat-card-body { padding: 16px 20px; flex: 1; }
.cat-card-body h4 {
    font-size: 15px;
    font-weight: 700;
    color: var(--white);
    margin-bottom: 4px;
}
.cat-slug { font-size: 11px; color: var(--text-muted); margin-bottom: 8px; }
.cat-slug code { background: var(--dark-3); padding: 2px 6px; border-radius: 4px; }
.cat-desc { font-size: 12px; color: var(--text-muted); line-height: 1.5; margin-bottom: 10px; }
.cat-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}
.cat-card-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 8px;
}

/* Új kategória kártya */
.cat-card-new {
    border: 2px dashed var(--border);
    background: transparent;
    cursor: pointer;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color .2s, background .2s;
}
.cat-card-new:hover {
    border-color: var(--gold);
    background: var(--gold-light);
    transform: translateY(-2px);
}
.cat-new-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 600;
}
.cat-new-inner i { font-size: 32px; color: var(--gold); opacity: .7; }
.cat-card-new:hover .cat-new-inner { color: var(--gold); }

/* Ikon picker */
.icon-picker {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.icon-pick-btn {
    width: 36px; height: 36px;
    background: var(--dark-3);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
}
.icon-pick-btn:hover,
.icon-pick-btn.selected {
    background: var(--gold-light);
    border-color: var(--gold);
    color: var(--gold);
}
</style>

<script>
function openCatModal() {
    // Reset form
    document.getElementById('catModalTitle').innerHTML =
        '<i class="fas fa-folder-plus"></i> Új kategória';
    document.getElementById('cat_id').value    = '';
    document.getElementById('cat_name').value  = '';
    document.getElementById('cat_slug').value  = '';
    document.getElementById('cat_desc').value  = '';
    document.getElementById('cat_icon').value  = 'fas fa-calendar';
    document.getElementById('cat_color').value = '#c8a96e';
    document.getElementById('cat_color_text').value = '#c8a96e';
    document.getElementById('cat_sort').value  = '0';
    document.getElementById('cat_active').checked = true;
    updateIconPreview('fas fa-calendar');
    document.getElementById('catModal').classList.add('open');
}

function editCat(cat) {
    document.getElementById('catModalTitle').innerHTML =
        '<i class="fas fa-edit"></i> Kategória szerkesztése';
    document.getElementById('cat_id').value    = cat.id;
    document.getElementById('cat_name').value  = cat.name        || '';
    document.getElementById('cat_slug').value  = cat.slug        || '';
    document.getElementById('cat_desc').value  = cat.description || '';
    document.getElementById('cat_icon').value  = cat.icon        || 'fas fa-calendar';
    document.getElementById('cat_color').value = cat.color       || '#c8a96e';
    document.getElementById('cat_color_text').value = cat.color  || '#c8a96e';
    document.getElementById('cat_sort').value  = cat.sort_order  || 0;
    document.getElementById('cat_active').checked = cat.active == 1;
    updateIconPreview(cat.icon || 'fas fa-calendar');
    document.getElementById('catModal').classList.add('open');
}

function catGenerateSlug(val) {
    if (document.getElementById('cat_id').value) return;
    let slug = val.toLowerCase()
        .replace(/[áàä]/g,'a').replace(/[éè]/g,'e').replace(/[íì]/g,'i')
        .replace(/[óöőò]/g,'o').replace(/[úüűù]/g,'u')
        .replace(/[^a-z0-9\s-]/g,'')
        .replace(/[\s]+/g,'-')
        .replace(/-+/g,'-')
        .replace(/^-|-$/g,'');
    document.getElementById('cat_slug').value = slug;
}

function updateIconPreview(val) {
    const preview = document.getElementById('iconPreview');
    preview.innerHTML = `<i class="${val}"></i>`;
}

function pickIcon(icon) {
    document.getElementById('cat_icon').value = icon;
    updateIconPreview(icon);
    document.querySelectorAll('.icon-pick-btn').forEach(b => b.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
}

// Color sync
document.getElementById('cat_color')?.addEventListener('input', function() {
    document.getElementById('cat_color_text').value = this.value;
});

<?php if (isset($_GET['new'])): ?>
openCatModal();
<?php endif; ?>
</script>

<?php require_once 'partials/footer.php'; ?>