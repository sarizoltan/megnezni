<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

// ── STATISZTIKÁK ──
$total_systems      = $pdo->query("SELECT COUNT(*) FROM systems WHERE active=1")->fetchColumn();
$total_categories   = $pdo->query("SELECT COUNT(*) FROM categories WHERE active=1")->fetchColumn();
$total_inquiries    = $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$new_inquiries      = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
$total_testimonials = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE active=1")->fetchColumn();
$total_views        = $pdo->query("SELECT COUNT(*) FROM stats WHERE event='view'")->fetchColumn();
$total_demo_clicks  = $pdo->query("SELECT COUNT(*) FROM stats WHERE event='demo_click'")->fetchColumn();

// ── LEGUTÓBBI ÉRDEKLŐDÉSEK ──
$recent_inquiries = $pdo->query("
    SELECT i.*, s.name as system_name
    FROM inquiries i
    LEFT JOIN systems s ON i.system_id = s.id
    ORDER BY i.created_at DESC
    LIMIT 5
")->fetchAll();

// ── LEGUTÓBBI RENDSZEREK ──
$recent_systems = $pdo->query("
    SELECT s.*, c.name as category_name, c.icon as category_icon, c.color as category_color
    FROM systems s
    LEFT JOIN categories c ON s.category_id = c.id
    ORDER BY s.created_at DESC
    LIMIT 5
")->fetchAll();

// ── HETI STATISZTIKA ──
$weekly_stats = $pdo->query("
    SELECT
        DATE(created_at) as day,
        SUM(event='view')       as views,
        SUM(event='demo_click') as demos,
        SUM(event='inquiry')    as inquiries
    FROM stats
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll();

// ── TOP RENDSZEREK (legtöbb megtekintés) ──
$top_systems = $pdo->query("
    SELECT s.name, s.thumbnail, c.color as cat_color, c.icon as cat_icon,
           COUNT(st.id) as view_count
    FROM systems s
    LEFT JOIN stats st ON st.system_id = s.id AND st.event = 'view'
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE s.active = 1
    GROUP BY s.id
    ORDER BY view_count DESC
    LIMIT 5
")->fetchAll();

$page_title = 'Dashboard';
require_once 'partials/header.php';
?>

<!-- ── STAT KÁRTYÁK ── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(200,169,110,.15);color:var(--gold);">
            <i class="fas fa-th-large"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= $total_systems ?></div>
            <div class="stat-label">Aktív rendszer</div>
        </div>
        <a href="systems.php" class="stat-link">Kezelés <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(59,130,246,.15);color:var(--blue);">
            <i class="fas fa-envelope"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value">
                <?= $total_inquiries ?>
                <?php if ($new_inquiries > 0): ?>
                <span class="stat-badge"><?= $new_inquiries ?> új</span>
                <?php endif; ?>
            </div>
            <div class="stat-label">Érdeklődés</div>
        </div>
        <a href="inquiries.php" class="stat-link">Kezelés <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(34,197,94,.15);color:var(--green);">
            <i class="fas fa-eye"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= number_format($total_views, 0, ',', ' ') ?></div>
            <div class="stat-label">Összes megtekintés</div>
        </div>
        <span class="stat-link" style="cursor:default;">Összesen</span>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(139,92,246,.15);color:var(--purple);">
            <i class="fas fa-mouse-pointer"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= number_format($total_demo_clicks, 0, ',', ' ') ?></div>
            <div class="stat-label">Demo kattintás</div>
        </div>
        <span class="stat-link" style="cursor:default;">Összesen</span>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245,158,11,.15);color:var(--orange);">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= $total_testimonials ?></div>
            <div class="stat-label">Vélemény</div>
        </div>
        <a href="testimonials.php" class="stat-link">Kezelés <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(236,72,153,.15);color:#ec4899;">
            <i class="fas fa-folder"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= $total_categories ?></div>
            <div class="stat-label">Kategória</div>
        </div>
        <a href="categories.php" class="stat-link">Kezelés <i class="fas fa-arrow-right"></i></a>
    </div>
</div>

<!-- ── CHART + TOP RENDSZEREK ── -->
<div class="dashboard-grid">

    <!-- Heti statisztika -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Heti aktivitás</h3>
            <span style="font-size:12px;color:var(--text-muted);">Elmúlt 7 nap</span>
        </div>
        <canvas id="weeklyChart" height="200"></canvas>
    </div>

    <!-- Top rendszerek -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-trophy"></i> Top rendszerek</h3>
            <a href="systems.php" class="btn btn-secondary btn-sm">Összes</a>
        </div>
        <?php if ($top_systems): ?>
        <div class="top-list">
            <?php foreach ($top_systems as $i => $sys): ?>
            <div class="top-item">
                <div class="top-rank"><?= $i + 1 ?></div>
                <div class="top-icon" style="background:<?= e($sys['cat_color'] ?? '#c8a96e') ?>22;color:<?= e($sys['cat_color'] ?? '#c8a96e') ?>;">
                    <i class="<?= e($sys['cat_icon'] ?? 'fas fa-calendar') ?>"></i>
                </div>
                <div class="top-info">
                    <div class="top-name"><?= e($sys['name']) ?></div>
                    <div class="top-meta"><?= $sys['view_count'] ?> megtekintés</div>
                </div>
                <div class="top-bar-wrap">
                    <?php $max = $top_systems[0]['view_count'] ?: 1; ?>
                    <div class="top-bar">
                        <div class="top-bar-fill" style="width:<?= round($sys['view_count'] / $max * 100) ?>%;background:<?= e($sys['cat_color'] ?? '#c8a96e') ?>;"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-bar"></i>
            <p>Még nincs statisztika.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── LEGUTÓBBI ÉRDEKLŐDÉSEK + GYORS MŰVELETEK ── -->
<div class="dashboard-grid">

    <!-- Legutóbbi érdeklődések -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-envelope"></i> Legutóbbi érdeklődések</h3>
            <a href="inquiries.php" class="btn btn-secondary btn-sm">Összes</a>
        </div>
        <?php if ($recent_inquiries): ?>
        <div class="inquiry-list">
            <?php foreach ($recent_inquiries as $inq): ?>
            <div class="inquiry-item <?= $inq['status'] === 'new' ? 'is-new' : '' ?>">
                <div class="inquiry-avatar">
                    <?= strtoupper(substr($inq['name'], 0, 1)) ?>
                </div>
                <div class="inquiry-info">
                    <div class="inquiry-name">
                        <?= e($inq['name']) ?>
                        <?php if ($inq['status'] === 'new'): ?>
                        <span class="badge badge-new" style="font-size:10px;">Új</span>
                        <?php endif; ?>
                    </div>
                    <div class="inquiry-meta">
                        <?= e($inq['email']) ?>
                        <?php if ($inq['system_name']): ?>
                        · <span style="color:var(--gold);"><?= e($inq['system_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="inquiry-preview"><?= e(mb_substr($inq['message'], 0, 80)) ?>...</div>
                </div>
                <div class="inquiry-time"><?= time_ago($inq['created_at']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-envelope-open"></i>
            <p>Nincs érdeklődés még.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Gyors műveletek + legutóbbi rendszerek -->
    <div>
        <!-- Gyors műveletek -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Gyors műveletek</h3>
            </div>
            <div class="quick-actions">
                <a href="systems.php?new=1" class="quick-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Új rendszer</span>
                </a>
                <a href="categories.php?new=1" class="quick-btn">
                    <i class="fas fa-folder-plus"></i>
                    <span>Új kategória</span>
                </a>
                <a href="testimonials.php?new=1" class="quick-btn">
                    <i class="fas fa-star"></i>
                    <span>Új vélemény</span>
                </a>
                <a href="faqs.php?new=1" class="quick-btn">
                    <i class="fas fa-question-circle"></i>
                    <span>Új GYIK</span>
                </a>
                <a href="settings.php" class="quick-btn">
                    <i class="fas fa-cog"></i>
                    <span>Beállítások</span>
                </a>
                <a href="<?= BASE_URL ?>" target="_blank" class="quick-btn">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Weboldal</span>
                </a>
            </div>
        </div>

        <!-- Legutóbbi rendszerek -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-th-large"></i> Legutóbbi rendszerek</h3>
                <a href="systems.php" class="btn btn-secondary btn-sm">Összes</a>
            </div>
            <?php if ($recent_systems): ?>
            <div class="recent-systems">
                <?php foreach ($recent_systems as $sys): ?>
                <div class="recent-system-item">
                    <div class="rs-icon" style="background:<?= e($sys['category_color'] ?? '#c8a96e') ?>22;color:<?= e($sys['category_color'] ?? '#c8a96e') ?>;">
                        <i class="<?= e($sys['category_icon'] ?? 'fas fa-calendar') ?>"></i>
                    </div>
                    <div class="rs-info">
                        <div class="rs-name"><?= e($sys['name']) ?></div>
                        <div class="rs-cat"><?= e($sys['category_name'] ?? '–') ?></div>
                    </div>
                    <div class="rs-status">
                        <span class="badge <?= $sys['active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $sys['active'] ? 'Aktív' : 'Inaktív' ?>
                        </span>
                    </div>
                    <a href="systems.php?edit=<?= $sys['id'] ?>" class="action-btn edit">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-th-large"></i>
                <p>Még nincs rendszer.</p>
                <a href="systems.php?new=1" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Létrehozás
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Stat kártyák */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--dark-2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: border-color .2s, transform .2s;
}
.stat-card:hover {
    border-color: var(--gold);
    transform: translateY(-2px);
}
.stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.stat-body { flex: 1; }
.stat-value {
    font-size: 26px;
    font-weight: 800;
    color: var(--white);
    line-height: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}
.stat-badge {
    font-size: 11px;
    font-weight: 700;
    background: rgba(239,68,68,.2);
    color: #fca5a5;
    padding: 2px 8px;
    border-radius: 20px;
}
.stat-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.stat-link {
    font-size: 11px;
    color: var(--gold);
    text-decoration: none;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 4px;
}
.stat-link:hover { opacity: .8; }

/* Dashboard grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

/* Top lista */
.top-list { display: flex; flex-direction: column; gap: 12px; }
.top-item {
    display: flex;
    align-items: center;
    gap: 12px;
}
.top-rank {
    width: 24px;
    font-size: 13px;
    font-weight: 800;
    color: var(--text-muted);
    text-align: center;
    flex-shrink: 0;
}
.top-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.top-info { flex: 1; min-width: 0; }
.top-name { font-size: 13px; font-weight: 600; color: var(--white); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-meta { font-size: 11px; color: var(--text-muted); }
.top-bar-wrap { width: 80px; flex-shrink: 0; }
.top-bar { height: 6px; background: var(--dark-3); border-radius: 3px; overflow: hidden; }
.top-bar-fill { height: 100%; border-radius: 3px; transition: width .5s; }

/* Érdeklődések */
.inquiry-list { display: flex; flex-direction: column; gap: 0; }
.inquiry-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}
.inquiry-item:last-child { border-bottom: none; }
.inquiry-item.is-new { background: rgba(200,169,110,.03); margin: 0 -24px; padding: 14px 24px; }
.inquiry-avatar {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--gold), var(--gold-2));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: var(--dark);
    font-size: 14px;
    font-weight: 700;
    flex-shrink: 0;
}
.inquiry-info { flex: 1; min-width: 0; }
.inquiry-name { font-size: 13px; font-weight: 600; color: var(--white); display: flex; align-items: center; gap: 6px; }
.inquiry-meta { font-size: 11px; color: var(--text-muted); margin: 2px 0; }
.inquiry-preview { font-size: 12px; color: var(--text-light); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.inquiry-time { font-size: 11px; color: var(--text-muted); white-space: nowrap; flex-shrink: 0; }

/* Gyors műveletek */
.quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}
.quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 10px;
    background: var(--dark-3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text-muted);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    transition: all .2s;
}
.quick-btn i { font-size: 20px; color: var(--gold); }
.quick-btn:hover {
    border-color: var(--gold);
    color: var(--white);
    background: var(--gold-light);
    transform: translateY(-2px);
}

/* Legutóbbi rendszerek */
.recent-systems { display: flex; flex-direction: column; gap: 0; }
.recent-system-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}
.recent-system-item:last-child { border-bottom: none; }
.rs-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.rs-info { flex: 1; min-width: 0; }
.rs-name { font-size: 13px; font-weight: 600; color: var(--white); }
.rs-cat  { font-size: 11px; color: var(--text-muted); }
.rs-status { flex-shrink: 0; }

@media(max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media(max-width: 768px) {
    .stats-grid { grid-template-columns: 1fr; }
    .dashboard-grid { grid-template-columns: 1fr; }
    .quick-actions { grid-template-columns: 1fr 1fr; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ── Heti chart ──
const weeklyData = <?= json_encode($weekly_stats) ?>;

const labels = weeklyData.map(d => {
    const date = new Date(d.day);
    return date.toLocaleDateString('hu-HU', { month:'short', day:'numeric' });
});

const ctx = document.getElementById('weeklyChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels.length ? labels : ['Nincs adat'],
        datasets: [
            {
                label: 'Megtekintés',
                data: weeklyData.map(d => d.views),
                backgroundColor: 'rgba(200,169,110,.7)',
                borderRadius: 6,
            },
            {
                label: 'Demo kattintás',
                data: weeklyData.map(d => d.demos),
                backgroundColor: 'rgba(59,130,246,.7)',
                borderRadius: 6,
            },
            {
                label: 'Érdeklődés',
                data: weeklyData.map(d => d.inquiries),
                backgroundColor: 'rgba(34,197,94,.7)',
                borderRadius: 6,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: { color: '#888', font: { size: 12 } }
            }
        },
        scales: {
            x: {
                ticks: { color: '#888' },
                grid:  { color: 'rgba(255,255,255,.05)' }
            },
            y: {
                ticks: { color: '#888' },
                grid:  { color: 'rgba(255,255,255,.05)' },
                beginAtZero: true
            }
        }
    }
});
</script>

<?php require_once 'partials/footer.php'; ?>