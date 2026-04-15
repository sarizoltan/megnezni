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
    $pdo->prepare("DELETE FROM faqs WHERE id=?")->execute([$_GET['delete']]);
    $message = 'GYIK törölve!';
}

// ── TOGGLE ──
if (isset($_GET['toggle']) && is_numeric($_GET['toggle']) && csrf_verify()) {
    $pdo->prepare("UPDATE faqs SET active = 1 - active WHERE id=?")
        ->execute([$_GET['toggle']]);
    $message = 'Státusz frissítve!';
}

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faq'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id         = (int)($_POST['id']        ?? 0);
    $system_id  = (int)($_POST['system_id'] ?? 0) ?: null;
    $question   = trim($_POST['question']   ?? '');
    $answer     = trim($_POST['answer']     ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $active     = isset($_POST['active']) ? 1 : 0;

    if (!$question || !$answer) {
        $message      = 'A kérdés és a válasz megadása kötelező!';
        $message_type = 'error';
    } else {
        if ($id) {
            $pdo->prepare("UPDATE faqs SET
                system_id=?, question=?, answer=?, sort_order=?, active=?
                WHERE id=?")
                ->execute([$system_id, $question, $answer, $sort_order, $active, $id]);
            $message = 'GYIK frissítve!';
        } else {
            $pdo->prepare("INSERT INTO faqs
                (system_id, question, answer, sort_order, active)
                VALUES (?,?,?,?,?)")
                ->execute([$system_id, $question, $answer, $sort_order, $active]);
            $message = 'GYIK létrehozva!';
        }
    }
}

// ── SZŰRŐK ──
$filter_system = (int)($_GET['sys'] ?? 0);
$where  = ['1=1'];
$params = [];

if ($filter_system) {
    $where[]  = 'f.system_id = ?';
    $params[] = $filter_system;
} else if (isset($_GET['sys']) && $_GET['sys'] === '0') {
    $where[]  = 'f.system_id IS NULL';
}

$faqs = $pdo->prepare("
    SELECT f.*, s.name as system_name
    FROM faqs f
    LEFT JOIN systems s ON f.system_id = s.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY f.sort_order ASC, f.id ASC
");
$faqs->execute($params);
$faqs = $faqs->fetchAll();

$all_systems = $pdo->query("SELECT id, name FROM systems WHERE active=1 ORDER BY name")->fetchAll();

$page_title = 'GYIK';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-question-circle"></i> GYIK kezelése</h2>
    <button class="btn btn-primary" onclick="openFaqModal()">
        <i class="fas fa-plus"></i> Új kérdés
    </button>
</div>

<!-- Szűrő -->
<div class="card" style="padding:16px 20px;margin-bottom:20px;">
    <form method="GET" action="faqs.php"
          style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <select name="sys" style="max-width:220px;">
            <option value="">Minden FAQ</option>
            <option value="0" <?= isset($_GET['sys']) && $_GET['sys']==='0' ? 'selected':'' ?>>
                Általános (rendszer nélkül)
            </option>
            <?php foreach ($all_systems as $sys): ?>
            <option value="<?= $sys['id'] ?>"
                    <?= $filter_system == $sys['id'] ? 'selected' : '' ?>>
                <?= e($sys['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-filter"></i> Szűrés
        </button>
        <a href="faqs.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
        <span style="margin-left:auto;color:var(--text-muted);font-size:13px;">
            <?= count($faqs) ?> kérdés
        </span>
    </form>
</div>

<!-- FAQ lista -->
<div class="card">
    <?php if ($faqs): ?>
    <div class="faq-list">
        <?php foreach ($faqs as $faq): ?>
        <div class="faq-item <?= !$faq['active'] ? 'inactive' : '' ?>">
            <div class="faq-header">
                <div class="faq-q-icon">
                    <i class="fas fa-question"></i>
                </div>
                <div class="faq-question">
                    <?= e($faq['question']) ?>
                    <?php if ($faq['system_name']): ?>
                    <span class="faq-system-badge">
                        <i class="fas fa-link"></i> <?= e($faq['system_name']) ?>
                    </span>
                    <?php else: ?>
                    <span class="faq-system-badge general">
                        <i class="fas fa-globe"></i> Általános
                    </span>
                    <?php endif; ?>
                </div>
                <div class="faq-item-actions">
                    <a href="?toggle=<?= $faq['id'] ?>&csrf_token=<?= csrf_token() ?>">
                        <span class="badge <?= $faq['active'] ? 'badge-active' : 'badge-inactive' ?>"
                              style="cursor:pointer;font-size:10px;">
                            <?= $faq['active'] ? '●' : '○' ?>
                        </span>
                    </a>
                    <button class="action-btn edit" title="Szerkesztés"
                        onclick="editFaq(<?= htmlspecialchars(json_encode($faq), ENT_QUOTES) ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="?delete=<?= $faq['id'] ?>&csrf_token=<?= csrf_token() ?>"
                       class="action-btn delete" title="Törlés"
                       data-confirm="Biztosan törölni szeretnéd ezt a kérdést?">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <div class="faq-answer">
                <i class="fas fa-reply" style="color:var(--gold);margin-right:8px;font-size:12px;"></i>
                <?= e($faq['answer']) ?>
            </div>
            <div class="faq-footer">
                <span style="color:var(--text-muted);font-size:11px;">
                    Sorrend: <?= $faq['sort_order'] ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-question-circle"></i>
        <p>Nincs GYIK a megadott szűrővel.</p>
        <button class="btn btn-primary btn-sm" onclick="openFaqModal()">
            <i class="fas fa-plus"></i> Első kérdés hozzáadása
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- ── MODAL ── -->
<div class="modal-overlay" id="faqModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3 id="faqModalTitle">
                <i class="fas fa-question-circle"></i> Új kérdés
            </h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="faqs.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_faq"   value="1">
            <input type="hidden" name="id" id="faq_id" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label>Kapcsolódó rendszer</label>
                    <select name="system_id" id="faq_system">
                        <option value="">– Általános GYIK –</option>
                        <?php foreach ($all_systems as $sys): ?>
                        <option value="<?= $sys['id'] ?>"><?= e($sys['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Ha üres, az összes oldalon megjelenik (általános FAQ)</small>
                </div>

                <div class="form-group">
                    <label>Kérdés *</label>
                    <input type="text" name="question" id="faq_question"
                           required placeholder="pl. Hogyan tudok időpontot foglalni?">
                </div>

                <div class="form-group">
                    <label>Válasz *</label>
                    <textarea name="answer" id="faq_answer" rows="4" required
                              placeholder="A kérdés részletes megválaszolása..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Sorrend</label>
                        <input type="number" name="sort_order" id="faq_sort"
                               value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Aktív</label>
                        <label class="toggle-switch" style="margin-top:8px;">
                            <input type="checkbox" name="active" id="faq_active" checked>
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
.faq-list { display: flex; flex-direction: column; gap: 0; }
.faq-item {
    border-bottom: 1px solid var(--border);
    padding: 16px 0;
    transition: background .2s;
}
.faq-item:last-child { border-bottom: none; }
.faq-item.inactive { opacity: .5; }
.faq-item:hover { background: rgba(255,255,255,.01); }

.faq-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 10px;
}
.faq-q-icon {
    width: 28px; height: 28px;
    background: var(--gold-light);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: var(--gold);
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 1px;
}
.faq-question {
    flex: 1;
    font-size: 14px;
    font-weight: 600;
    color: var(--white);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.faq-system-badge {
    font-size: 10px;
    font-weight: 600;
    background: var(--gold-light);
    color: var(--gold);
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.faq-system-badge.general {
    background: rgba(107,114,128,.15);
    color: var(--text-muted);
}
.faq-item-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.faq-answer {
    font-size: 13px;
    color: var(--text-light);
    line-height: 1.7;
    padding: 10px 14px;
    background: var(--dark-3);
    border-radius: 8px;
    margin-left: 40px;
}
.faq-footer {
    margin-left: 40px;
    margin-top: 8px;
}
</style>

<script>
function openFaqModal() {
    document.getElementById('faqModalTitle').innerHTML =
        '<i class="fas fa-question-circle"></i> Új kérdés';
    document.getElementById('faq_id').value       = '';
    document.getElementById('faq_system').value   = '';
    document.getElementById('faq_question').value = '';
    document.getElementById('faq_answer').value   = '';
    document.getElementById('faq_sort').value     = '0';
    document.getElementById('faq_active').checked = true;
    document.getElementById('faqModal').classList.add('open');
}

function editFaq(faq) {
    document.getElementById('faqModalTitle').innerHTML =
        '<i class="fas fa-edit"></i> Kérdés szerkesztése';
    document.getElementById('faq_id').value       = faq.id;
    document.getElementById('faq_system').value   = faq.system_id  || '';
    document.getElementById('faq_question').value = faq.question   || '';
    document.getElementById('faq_answer').value   = faq.answer     || '';
    document.getElementById('faq_sort').value     = faq.sort_order || 0;
    document.getElementById('faq_active').checked = faq.active == 1;
    document.getElementById('faqModal').classList.add('open');
}

<?php if (isset($_GET['new'])): ?>
openFaqModal();
<?php endif; ?>
</script>

<?php require_once 'partials/footer.php'; ?>