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
    $t = $pdo->prepare("SELECT avatar FROM testimonials WHERE id=?");
    $t->execute([$_GET['delete']]);
    $t = $t->fetch();
    if ($t) {
        delete_image($t['avatar']);
        $pdo->prepare("DELETE FROM testimonials WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Vélemény törölve!';
    }
}

// ── TOGGLE ──
if (isset($_GET['toggle']) && is_numeric($_GET['toggle']) && csrf_verify()) {
    $pdo->prepare("UPDATE testimonials SET active = 1 - active WHERE id=?")
        ->execute([$_GET['toggle']]);
    $message = 'Státusz frissítve!';
}

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonial'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id         = (int)($_POST['id']        ?? 0);
    $system_id  = (int)($_POST['system_id'] ?? 0) ?: null;
    $name       = trim($_POST['name']       ?? '');
    $position   = trim($_POST['position']   ?? '');
    $company    = trim($_POST['company']    ?? '');
    $content    = trim($_POST['content']    ?? '');
    $rating     = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $active     = isset($_POST['active']) ? 1 : 0;

    if (!$name || !$content) {
        $message      = 'A név és a vélemény szövege kötelező!';
        $message_type = 'error';
    } else {
        $avatar = upload_image('avatar', 'testi');

        if ($id) {
            if ($avatar) {
                $old = $pdo->prepare("SELECT avatar FROM testimonials WHERE id=?");
                $old->execute([$id]);
                delete_image($old->fetchColumn());
            }
            $sql    = "UPDATE testimonials SET
                        system_id=?, name=?, position=?, company=?,
                        content=?, rating=?, sort_order=?, active=?
                        " . ($avatar ? ", avatar=?" : "") . "
                        WHERE id=?";
            $params = [$system_id, $name, $position, $company,
                       $content, $rating, $sort_order, $active];
            if ($avatar) $params[] = $avatar;
            $params[] = $id;
            $pdo->prepare($sql)->execute($params);
            $message = 'Vélemény frissítve!';
        } else {
            $pdo->prepare("INSERT INTO testimonials
                (system_id, name, position, company, content, rating, avatar, sort_order, active)
                VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$system_id, $name, $position, $company,
                           $content, $rating, $avatar, $sort_order, $active]);
            $message = 'Vélemény létrehozva!';
        }
    }
}

// ── LISTA ──
$testimonials = $pdo->query("
    SELECT t.*, s.name as system_name
    FROM testimonials t
    LEFT JOIN systems s ON t.system_id = s.id
    ORDER BY t.sort_order ASC, t.created_at DESC
")->fetchAll();

$all_systems = $pdo->query("SELECT id, name FROM systems WHERE active=1 ORDER BY name")->fetchAll();

$page_title = 'Vélemények';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-star"></i> Vélemények kezelése</h2>
    <button class="btn btn-primary" onclick="openTestiModal()">
        <i class="fas fa-plus"></i> Új vélemény
    </button>
</div>

<!-- Vélemény kártyák -->
<?php if ($testimonials): ?>
<div class="testi-grid">
    <?php foreach ($testimonials as $t): ?>
    <div class="testi-card <?= !$t['active'] ? 'inactive' : '' ?>">
        <div class="testi-header">
            <div class="testi-avatar">
                <?php if ($t['avatar']): ?>
                <img src="<?= UPLOAD_URL . e($t['avatar']) ?>" alt="<?= e($t['name']) ?>">
                <?php else: ?>
                <div class="testi-avatar-placeholder">
                    <?= strtoupper(substr($t['name'], 0, 1)) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="testi-meta">
                <div class="testi-name"><?= e($t['name']) ?></div>
                <?php if ($t['position'] || $t['company']): ?>
                <div class="testi-pos">
                    <?= e($t['position']) ?>
                    <?= ($t['position'] && $t['company']) ? ' · ' : '' ?>
                    <?= e($t['company']) ?>
                </div>
                <?php endif; ?>
                <div class="testi-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star" style="color:<?= $i <= $t['rating'] ? '#f59e0b' : 'var(--border)' ?>;font-size:11px;"></i>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="testi-actions-top">
                <a href="?toggle=<?= $t['id'] ?>&csrf_token=<?= csrf_token() ?>">
                    <span class="badge <?= $t['active'] ? 'badge-active' : 'badge-inactive' ?>"
                          style="cursor:pointer;font-size:10px;">
                        <?= $t['active'] ? '● Aktív' : '○ Inaktív' ?>
                    </span>
                </a>
            </div>
        </div>

        <div class="testi-content">
            "<?= e(mb_substr($t['content'], 0, 120)) ?><?= mb_strlen($t['content']) > 120 ? '...' : '' ?>"
        </div>

        <?php if ($t['system_name']): ?>
        <div class="testi-system">
            <i class="fas fa-link"></i> <?= e($t['system_name']) ?>
        </div>
        <?php endif; ?>

        <div class="testi-footer">
            <span style="color:var(--text-muted);font-size:11px;">
                Sorrend: <?= $t['sort_order'] ?>
            </span>
            <div style="display:flex;gap:6px;">
                <button class="action-btn edit" title="Szerkesztés"
                    onclick="editTesti(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)">
                    <i class="fas fa-edit"></i>
                </button>
                <a href="?delete=<?= $t['id'] ?>&csrf_token=<?= csrf_token() ?>"
                   class="action-btn delete" title="Törlés"
                   data-confirm="Biztosan törölni szeretnéd ezt a véleményt?">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-star"></i>
        <p>Még nincs vélemény.</p>
        <button class="btn btn-primary btn-sm" onclick="openTestiModal()">
            <i class="fas fa-plus"></i> Első vélemény hozzáadása
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ── MODAL ── -->
<div class="modal-overlay" id="testiModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3 id="testiModalTitle"><i class="fas fa-star"></i> Új vélemény</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="testimonials.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"       value="<?= csrf_token() ?>">
            <input type="hidden" name="save_testimonial" value="1">
            <input type="hidden" name="id" id="testi_id" value="">

            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Név *</label>
                        <input type="text" name="name" id="testi_name"
                               required placeholder="pl. Kovács János">
                    </div>
                    <div class="form-group">
                        <label>Pozíció / Foglalkozás</label>
                        <input type="text" name="position" id="testi_pos"
                               placeholder="pl. Fodrász mester">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Cég / Vállalkozás</label>
                        <input type="text" name="company" id="testi_company"
                               placeholder="pl. Kovács Fodrászat">
                    </div>
                    <div class="form-group">
                        <label>Kapcsolódó rendszer</label>
                        <select name="system_id" id="testi_system">
                            <option value="">– Általános vélemény –</option>
                            <?php foreach ($all_systems as $sys): ?>
                            <option value="<?= $sys['id'] ?>"><?= e($sys['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Vélemény szövege *</label>
                    <textarea name="content" id="testi_content" rows="4" required
                              placeholder="Az ügyfél véleménye..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Értékelés</label>
                        <div class="star-rating" id="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="star-btn <?= $i <= 5 ? 'active' : '' ?>"
                                    data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)">
                                <i class="fas fa-star"></i>
                            </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="testi_rating" value="5">
                    </div>
                    <div class="form-group">
                        <label>Profilkép</label>
                        <input type="file" name="avatar" accept="image/*"
                               onchange="previewAvatar(this)">
                        <div id="avatarPreview" style="margin-top:8px;"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Sorrend</label>
                        <input type="number" name="sort_order" id="testi_sort"
                               value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Aktív</label>
                        <label class="toggle-switch" style="margin-top:8px;">
                            <input type="checkbox" name="active" id="testi_active" checked>
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
.testi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.testi-card {
    background: var(--dark-2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    transition: border-color .2s, transform .2s;
}
.testi-card:hover { border-color: var(--gold); transform: translateY(-2px); }
.testi-card.inactive { opacity: .6; }

.testi-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.testi-avatar {
    width: 48px; height: 48px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
}
.testi-avatar img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.testi-avatar-placeholder {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, var(--gold), var(--gold-2));
    display: flex; align-items: center; justify-content: center;
    color: var(--dark);
    font-size: 18px;
    font-weight: 700;
}
.testi-meta { flex: 1; }
.testi-name { font-size: 14px; font-weight: 700; color: var(--white); }
.testi-pos  { font-size: 11px; color: var(--text-muted); margin: 2px 0; }
.testi-stars { display: flex; gap: 2px; margin-top: 4px; }
.testi-actions-top { flex-shrink: 0; }

.testi-content {
    font-size: 13px;
    color: var(--text-light);
    line-height: 1.7;
    font-style: italic;
    padding: 12px 16px;
    background: var(--dark-3);
    border-radius: 8px;
    border-left: 3px solid var(--gold);
}
.testi-system {
    font-size: 11px;
    color: var(--gold);
    display: flex;
    align-items: center;
    gap: 5px;
}
.testi-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10px;
    border-top: 1px solid var(--border);
}

/* Csillag értékelő */
.star-rating {
    display: flex;
    gap: 4px;
    margin-top: 4px;
}
.star-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 22px;
    color: var(--border);
    padding: 0;
    transition: color .15s, transform .15s;
}
.star-btn.active { color: #f59e0b; }
.star-btn:hover  { transform: scale(1.2); }
</style>

<script>
function openTestiModal() {
    document.getElementById('testiModalTitle').innerHTML =
        '<i class="fas fa-star"></i> Új vélemény';
    document.getElementById('testi_id').value      = '';
    document.getElementById('testi_name').value    = '';
    document.getElementById('testi_pos').value     = '';
    document.getElementById('testi_company').value = '';
    document.getElementById('testi_content').value = '';
    document.getElementById('testi_sort').value    = '0';
    document.getElementById('testi_active').checked = true;
    document.getElementById('testi_system').value  = '';
    document.getElementById('avatarPreview').innerHTML = '';
    setRating(5);
    document.getElementById('testiModal').classList.add('open');
}

function editTesti(t) {
    document.getElementById('testiModalTitle').innerHTML =
        '<i class="fas fa-edit"></i> Vélemény szerkesztése';
    document.getElementById('testi_id').value      = t.id;
    document.getElementById('testi_name').value    = t.name     || '';
    document.getElementById('testi_pos').value     = t.position || '';
    document.getElementById('testi_company').value = t.company  || '';
    document.getElementById('testi_content').value = t.content  || '';
    document.getElementById('testi_sort').value    = t.sort_order || 0;
    document.getElementById('testi_active').checked = t.active == 1;
    document.getElementById('testi_system').value  = t.system_id || '';
    setRating(parseInt(t.rating) || 5);

    if (t.avatar) {
        document.getElementById('avatarPreview').innerHTML =
            `<img src="<?= UPLOAD_URL ?>${t.avatar}"
                  style="width:48px;height:48px;border-radius:10px;object-fit:cover;">`;
    } else {
        document.getElementById('avatarPreview').innerHTML = '';
    }
    document.getElementById('testiModal').classList.add('open');
}

function setRating(val) {
    document.getElementById('testi_rating').value = val;
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.dataset.val) <= val);
    });
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('avatarPreview').innerHTML =
                `<img src="${e.target.result}"
                      style="width:48px;height:48px;border-radius:10px;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

<?php if (isset($_GET['new'])): ?>
openTestiModal();
<?php endif; ?>
</script>

<?php require_once 'partials/footer.php'; ?>