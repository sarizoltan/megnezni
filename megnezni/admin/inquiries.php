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
    $pdo->prepare("DELETE FROM inquiries WHERE id=?")->execute([$_GET['delete']]);
    $message = 'Érdeklődés törölve!';
}

// ── STÁTUSZ VÁLTOZTATÁS ──
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id']) && csrf_verify()) {
    $allowed = ['new', 'read', 'replied'];
    if (in_array($_GET['status'], $allowed)) {
        $pdo->prepare("UPDATE inquiries SET status=? WHERE id=?")
            ->execute([$_GET['status'], (int)$_GET['id']]);
        $message = 'Státusz frissítve!';
    }
}

// ── TÖMEGES TÖRLÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) && csrf_verify()) {
    $ids = array_filter(array_map('intval', $_POST['selected'] ?? []));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM inquiries WHERE id IN ($placeholders)")->execute($ids);
        $message = count($ids) . ' érdeklődés törölve!';
    }
}

// ── SZŰRŐK ──
$filter_status = $_GET['fstatus'] ?? '';
$filter_system = (int)($_GET['sys'] ?? 0);
$filter_search = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filter_status) {
    $where[]  = 'i.status = ?';
    $params[] = $filter_status;
}
if ($filter_system) {
    $where[]  = 'i.system_id = ?';
    $params[] = $filter_system;
}
if ($filter_search) {
    $where[]  = '(i.name LIKE ? OR i.email LIKE ? OR i.message LIKE ?)';
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
}

$inquiries = $pdo->prepare("
    SELECT i.*, s.name as system_name
    FROM inquiries i
    LEFT JOIN systems s ON i.system_id = s.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY i.created_at DESC
");
$inquiries->execute($params);
$inquiries = $inquiries->fetchAll();

// Összesítő
$summary = $pdo->query("SELECT status, COUNT(*) as cnt FROM inquiries GROUP BY status")
               ->fetchAll(PDO::FETCH_KEY_PAIR);

$all_systems = $pdo->query("SELECT id, name FROM systems WHERE active=1 ORDER BY name")->fetchAll();

$page_title = 'Érdeklődések';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-envelope"></i> Érdeklődések</h2>
    <span style="color:var(--text-muted);font-size:13px;">
        Összes: <strong style="color:var(--white);"><?= array_sum($summary) ?></strong>
    </span>
</div>

<!-- Összesítő -->
<div class="inq-summary">
    <a href="?fstatus=" class="inq-sum-item <?= $filter_status === '' ? 'active' : '' ?>">
        <span class="inq-sum-val"><?= array_sum($summary) ?></span>
        <span class="inq-sum-label">Összes</span>
    </a>
    <a href="?fstatus=new" class="inq-sum-item <?= $filter_status === 'new' ? 'active' : '' ?>">
        <span class="inq-sum-val" style="color:var(--blue);"><?= $summary['new'] ?? 0 ?></span>
        <span class="inq-sum-label">🔵 Új</span>
    </a>
    <a href="?fstatus=read" class="inq-sum-item <?= $filter_status === 'read' ? 'active' : '' ?>">
        <span class="inq-sum-val" style="color:var(--text-muted);"><?= $summary['read'] ?? 0 ?></span>
        <span class="inq-sum-label">⚪ Olvasott</span>
    </a>
    <a href="?fstatus=replied" class="inq-sum-item <?= $filter_status === 'replied' ? 'active' : '' ?>">
        <span class="inq-sum-val" style="color:var(--green);"><?= $summary['replied'] ?? 0 ?></span>
        <span class="inq-sum-label">🟢 Megválaszolt</span>
    </a>
</div>

<!-- Szűrők + tömeges törlés -->
<div class="card" style="padding:16px 20px;margin-bottom:20px;">
    <form method="GET" action="inquiries.php"
          style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <select name="sys" style="max-width:200px;">
            <option value="">Minden rendszer</option>
            <?php foreach ($all_systems as $sys): ?>
            <option value="<?= $sys['id'] ?>" <?= $filter_system == $sys['id'] ? 'selected':'' ?>>
                <?= e($sys['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="fstatus" style="max-width:160px;">
            <option value="">Minden státusz</option>
            <option value="new"     <?= $filter_status==='new'     ? 'selected':'' ?>>Új</option>
            <option value="read"    <?= $filter_status==='read'    ? 'selected':'' ?>>Olvasott</option>
            <option value="replied" <?= $filter_status==='replied' ? 'selected':'' ?>>Megválaszolt</option>
        </select>
        <input type="text" name="search" value="<?= e($filter_search) ?>"
               placeholder="Keresés..." style="max-width:220px;">
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-filter"></i> Szűrés
        </button>
        <a href="inquiries.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
    </form>
</div>

<!-- Lista -->
<form method="POST" action="inquiries.php" id="bulkForm">
    <input type="hidden" name="csrf_token"  value="<?= csrf_token() ?>">
    <input type="hidden" name="bulk_delete" value="1">

    <div class="card">
        <!-- Bulk actions -->
        <div class="bulk-bar" id="bulkBar" style="display:none;">
            <span id="selectedCount" style="color:var(--gold);font-weight:600;font-size:13px;"></span>
            <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Biztosan törölni szeretnéd a kijelölt érdeklődéseket?">
                <i class="fas fa-trash"></i> Kijelöltek törlése
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                Mégsem
            </button>
        </div>

        <?php if ($inquiries): ?>
        <div class="inq-list">
            <?php foreach ($inquiries as $inq): ?>
            <div class="inq-item status-<?= $inq['status'] ?>" id="inq-<?= $inq['id'] ?>">
                <div class="inq-check">
                    <input type="checkbox" name="selected[]"
                           value="<?= $inq['id'] ?>" class="inq-checkbox"
                           onchange="updateBulkBar()">
                </div>

                <div class="inq-avatar">
                    <?= strtoupper(substr($inq['name'], 0, 1)) ?>
                </div>

                <div class="inq-body" onclick="openInqDetail(<?= htmlspecialchars(json_encode($inq), ENT_QUOTES) ?>)"
                     style="cursor:pointer;">
                    <div class="inq-top">
                        <span class="inq-name"><?= e($inq['name']) ?></span>
                        <?php if ($inq['status'] === 'new'): ?>
                        <span class="badge badge-new" style="font-size:10px;">Új</span>
                        <?php elseif ($inq['status'] === 'replied'): ?>
                        <span class="badge badge-replied" style="font-size:10px;">Megválaszolt</span>
                        <?php endif; ?>
                        <?php if ($inq['system_name']): ?>
                        <span class="badge badge-gold" style="font-size:10px;">
                            <?= e($inq['system_name']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="inq-email">
                        <i class="fas fa-envelope"></i> <?= e($inq['email']) ?>
                        <?php if ($inq['phone']): ?>
                        · <i class="fas fa-phone"></i> <?= e($inq['phone']) ?>
                        <?php endif; ?>
                        <?php if ($inq['company']): ?>
                        · <i class="fas fa-building"></i> <?= e($inq['company']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="inq-preview">
                        <?= e(mb_substr($inq['message'], 0, 100)) ?>...
                    </div>
                </div>

                <div class="inq-side">
                    <div class="inq-time"><?= time_ago($inq['created_at']) ?></div>
                    <div class="inq-actions">
                        <!-- Státusz váltó -->
                        <div class="status-dropdown">
                            <button type="button" class="action-btn view" title="Státusz">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <div class="status-menu" style="right:0;left:auto;min-width:160px;">
                                <?php foreach (['new'=>'🔵 Új','read'=>'⚪ Olvasott','replied'=>'🟢 Megválaszolt'] as $st => $lbl): ?>
                                <?php if ($st !== $inq['status']): ?>
                                <a href="?id=<?= $inq['id'] ?>&status=<?= $st ?>&csrf_token=<?= csrf_token() ?>">
                                    <?= $lbl ?>
                                </a>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <a href="mailto:<?= e($inq['email']) ?>" class="action-btn edit" title="Email küldés">
                            <i class="fas fa-reply"></i>
                        </a>
                        <a href="?delete=<?= $inq['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="action-btn delete" title="Törlés"
                           data-confirm="Biztosan törölni szeretnéd ezt az érdeklődést?">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-envelope-open"></i>
            <p>Nincs érdeklődés a megadott szűrőkkel.</p>
        </div>
        <?php endif; ?>
    </div>
</form>

<!-- ── DETAIL MODAL ── -->
<div class="modal-overlay" id="inqDetailModal">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <h3><i class="fas fa-envelope-open"></i> Érdeklődés részletei</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="inqDetailBody"></div>
        <div class="modal-footer" id="inqDetailFooter"></div>
    </div>
</div>

<style>
/* Összesítő */
.inq-summary {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.inq-sum-item {
    background: var(--dark-2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 14px 20px;
    text-align: center;
    text-decoration: none;
    transition: all .2s;
    min-width: 100px;
}
.inq-sum-item:hover,
.inq-sum-item.active {
    border-color: var(--gold);
    background: var(--gold-light);
}
.inq-sum-val   { display: block; font-size: 24px; font-weight: 800; color: var(--white); }
.inq-sum-label { display: block; font-size: 11px; color: var(--text-muted); margin-top: 3px; }

/* Bulk bar */
.bulk-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--gold-light);
    border-radius: var(--radius);
    margin-bottom: 16px;
    border: 1px solid rgba(200,169,110,.3);
}

/* Érdeklődés lista */
.inq-list { display: flex; flex-direction: column; }
.inq-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px;
    border-bottom: 1px solid var(--border);
    transition: background .2s;
    border-radius: 8px;
    margin: 2px 0;
}
.inq-item:last-child { border-bottom: none; }
.inq-item:hover { background: rgba(255,255,255,.02); }
.inq-item.status-new { border-left: 3px solid var(--blue); }
.inq-item.status-replied { border-left: 3px solid var(--green); }
.inq-item.status-read { border-left: 3px solid var(--border); }

.inq-check { padding-top: 3px; }
.inq-check input { width: 16px; height: 16px; cursor: pointer; accent-color: var(--gold); }

.inq-avatar {
    width: 42px; height: 42px;
    background: linear-gradient(135deg, var(--gold), var(--gold-2));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: var(--dark);
    font-size: 16px;
    font-weight: 700;
    flex-shrink: 0;
}
.inq-body  { flex: 1; min-width: 0; }
.inq-top   { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px; }
.inq-name  { font-size: 14px; font-weight: 700; color: var(--white); }
.inq-email { font-size: 12px; color: var(--text-muted); margin-bottom: 5px; }
.inq-email i { color: var(--gold); font-size: 11px; }
.inq-preview { font-size: 13px; color: var(--text-light); line-height: 1.5; }

.inq-side  { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
.inq-time  { font-size: 11px; color: var(--text-muted); white-space: nowrap; }
.inq-actions { display: flex; gap: 6px; }

/* Státusz dropdown */
.status-dropdown { position: relative; display: inline-block; }
.status-menu {
    display: none;
    position: absolute;
    top: 100%; right: 0;
    background: var(--dark-2);
    border: 1px solid var(--border);
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    z-index: 10;
    min-width: 160px;
    padding: 6px;
    margin-top: 4px;
}
.status-dropdown:hover .status-menu { display: block; }
.status-menu a {
    display: block;
    padding: 8px 12px;
    text-decoration: none;
    color: var(--text);
    font-size: 13px;
    border-radius: 6px;
    transition: background .15s;
}
.status-menu a:hover { background: var(--dark-3); }

/* Detail modal */
.detail-row {
    display: flex;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
    gap: 16px;
}
.detail-row:last-child { border-bottom: none; }
.detail-label { color: var(--text-muted); min-width: 100px; flex-shrink: 0; }
.detail-value { color: var(--text); font-weight: 500; flex: 1; }
.message-box {
    background: var(--dark-3);
    border-radius: 8px;
    padding: 16px;
    font-size: 14px;
    color: var(--text);
    line-height: 1.8;
    margin-top: 12px;
    border-left: 3px solid var(--gold);
    white-space: pre-wrap;
}
</style>

<script>
function updateBulkBar() {
    const checked = document.querySelectorAll('.inq-checkbox:checked');
    const bar     = document.getElementById('bulkBar');
    const count   = document.getElementById('selectedCount');
    if (checked.length > 0) {
        bar.style.display = 'flex';
        count.textContent = checked.length + ' kijelölve';
    } else {
        bar.style.display = 'none';
    }
}

function clearSelection() {
    document.querySelectorAll('.inq-checkbox').forEach(cb => cb.checked = false);
    updateBulkBar();
}

function openInqDetail(inq) {
    const statusMap = { new: '🔵 Új', read: '⚪ Olvasott', replied: '🟢 Megválaszolt' };

    document.getElementById('inqDetailBody').innerHTML = `
        <div class="detail-row">
            <span class="detail-label">Név</span>
            <span class="detail-value">${inq.name}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value">
                <a href="mailto:${inq.email}" style="color:var(--gold);">${inq.email}</a>
            </span>
        </div>
        ${inq.phone ? `
        <div class="detail-row">
            <span class="detail-label">Telefon</span>
            <span class="detail-value">
                <a href="tel:${inq.phone}" style="color:var(--gold);">${inq.phone}</a>
            </span>
        </div>` : ''}
        ${inq.company ? `
        <div class="detail-row">
            <span class="detail-label">Cég</span>
            <span class="detail-value">${inq.company}</span>
        </div>` : ''}
        ${inq.system_name ? `
        <div class="detail-row">
            <span class="detail-label">Rendszer</span>
            <span class="detail-value" style="color:var(--gold);">${inq.system_name}</span>
        </div>` : ''}
        <div class="detail-row">
            <span class="detail-label">Státusz</span>
            <span class="detail-value">${statusMap[inq.status] || inq.status}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Beérkezett</span>
            <span class="detail-value">${inq.created_at}</span>
        </div>
        <div class="message-box">${inq.message}</div>
    `;

    document.getElementById('inqDetailFooter').innerHTML = `
        <a href="?id=${inq.id}&status=replied&csrf_token=<?= csrf_token() ?>"
           class="btn btn-success btn-sm">
            <i class="fas fa-check"></i> Megválaszoltnak jelöl
        </a>
        <a href="mailto:${inq.email}?subject=RE: Érdeklődés – Foglalas.hu"
           class="btn btn-primary btn-sm">
            <i class="fas fa-reply"></i> Email válasz
        </a>
        <button class="btn btn-secondary btn-sm modal-close">Bezárás</button>
    `;

    document.getElementById('inqDetailModal').classList.add('open');

    // Ha új, automatikusan olvasottra állítja
    if (inq.status === 'new') {
        fetch(`inquiries.php?id=${inq.id}&status=read&csrf_token=<?= csrf_token() ?>`);
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>