<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message      = '';
$message_type = 'success';

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {

    // Jelszó változtatás
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) {
            $message      = 'A jelszónak legalább 6 karakter hosszúnak kell lennie!';
            $message_type = 'error';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $message      = 'A két jelszó nem egyezik!';
            $message_type = 'error';
        } else {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admins SET password=? WHERE id=?")
                ->execute([$hash, $_SESSION['admin_id']]);
            $message = 'Jelszó sikeresen megváltoztatva!';
        }
    }

    // Beállítások mentése
    if ($message_type !== 'error') {
        $settings_to_save = [
            'site_name', 'site_tagline', 'site_email', 'site_phone', 'site_address',
            'hero_title', 'hero_subtitle', 'hero_cta_text', 'hero_cta_url',
            'social_facebook', 'social_instagram', 'social_linkedin',
            'smtp_from_name', 'smtp_from_email',
            'google_analytics', 'meta_description',
        ];
        foreach ($settings_to_save as $key) {
            if (isset($_POST[$key])) {
                set_setting($key, trim($_POST[$key]));
            }
        }
        if (empty($_POST['new_password'])) {
            $message = 'Beállítások mentve!';
        }
    }
}

// ── BEÁLLÍTÁSOK BETÖLTÉSE ──
$s = [];
$rows = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll();
foreach ($rows as $row) {
    $s[$row['key']] = $row['value'];
}
$sv = fn(string $key, string $default = '') => e($s[$key] ?? $default);

$page_title = 'Beállítások';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-cog"></i> Beállítások</h2>
</div>

<form method="POST" action="settings.php">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="settings-grid">

        <!-- ── Általános ── -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-globe"></i> Általános beállítások</h3>
            </div>
            <div class="form-group">
                <label>Weboldal neve</label>
                <input type="text" name="site_name"
                       value="<?= $sv('site_name', 'Foglalas.hu') ?>"
                       placeholder="Foglalas.hu">
            </div>
            <div class="form-group">
                <label>Szlogen</label>
                <input type="text" name="site_tagline"
                       value="<?= $sv('site_tagline') ?>"
                       placeholder="Okos foglalási rendszerek minden vállalkozásnak">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email cím</label>
                    <input type="email" name="site_email"
                           value="<?= $sv('site_email') ?>"
                           placeholder="info@foglalas.hu">
                </div>
                <div class="form-group">
                    <label>Telefonszám</label>
                    <input type="text" name="site_phone"
                           value="<?= $sv('site_phone') ?>"
                           placeholder="+36 30 123 4567">
                </div>
            </div>
            <div class="form-group">
                <label>Cím</label>
                <input type="text" name="site_address"
                       value="<?= $sv('site_address') ?>"
                       placeholder="1234 Budapest, Példa utca 1.">
            </div>
            <div class="form-group">
                <label>Meta leírás (SEO)</label>
                <textarea name="meta_description" rows="2"
                          placeholder="Rövid leírás a keresőknek..."><?= $sv('meta_description') ?></textarea>
            </div>
            <div class="form-group">
                <label>Google Analytics ID</label>
                <input type="text" name="google_analytics"
                       value="<?= $sv('google_analytics') ?>"
                       placeholder="G-XXXXXXXXXX">
            </div>
        </div>

        <!-- ── Hero szekció ── -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-image"></i> Hero szekció</h3>
            </div>
            <div class="form-group">
                <label>Főcím</label>
                <textarea name="hero_title" rows="2"
                          placeholder="Okos foglalási rendszer&#10;vállalkozásodnak"><?= $sv('hero_title') ?></textarea>
                <small>Sortöréshez használj új sort</small>
            </div>
            <div class="form-group">
                <label>Alcím</label>
                <textarea name="hero_subtitle" rows="2"
                          placeholder="Egyszerű, gyors, megbízható..."><?= $sv('hero_subtitle') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>CTA gomb szövege</label>
                    <input type="text" name="hero_cta_text"
                           value="<?= $sv('hero_cta_text', 'Rendszerek megtekintése') ?>"
                           placeholder="Rendszerek megtekintése">
                </div>
                <div class="form-group">
                    <label>CTA gomb link</label>
                    <input type="text" name="hero_cta_url"
                           value="<?= $sv('hero_cta_url', '#systems') ?>"
                           placeholder="#systems">
                </div>
            </div>
        </div>

        <!-- ── Közösségi média ── -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-share-alt"></i> Közösségi média</h3>
            </div>
            <div class="form-group">
                <label><i class="fab fa-facebook" style="color:#1877f2;"></i> Facebook</label>
                <input type="url" name="social_facebook"
                       value="<?= $sv('social_facebook') ?>"
                       placeholder="https://facebook.com/...">
            </div>
            <div class="form-group">
                <label><i class="fab fa-instagram" style="color:#e1306c;"></i> Instagram</label>
                <input type="url" name="social_instagram"
                       value="<?= $sv('social_instagram') ?>"
                       placeholder="https://instagram.com/...">
            </div>
            <div class="form-group">
                <label><i class="fab fa-linkedin" style="color:#0077b5;"></i> LinkedIn</label>
                <input type="url" name="social_linkedin"
                       value="<?= $sv('social_linkedin') ?>"
                       placeholder="https://linkedin.com/...">
            </div>
        </div>

        <!-- ── Email beállítások ── -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-envelope"></i> Email beállítások</h3>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Feladó neve</label>
                    <input type="text" name="smtp_from_name"
                           value="<?= $sv('smtp_from_name', 'Foglalas.hu') ?>"
                           placeholder="Foglalas.hu">
                </div>
                <div class="form-group">
                    <label>Feladó email</label>
                    <input type="email" name="smtp_from_email"
                           value="<?= $sv('smtp_from_email') ?>"
                           placeholder="info@foglalas.hu">
                </div>
            </div>
            <div class="setting-info">
                <i class="fas fa-info-circle"></i>
                XAMPP-on a PHP <code>mail()</code> funkcióhoz a
                <code>C:\xampp\php\php.ini</code> fájlban az
                <code>SMTP</code> és <code>smtp_port</code> értékeket kell beállítani.
            </div>
        </div>

        <!-- ── Admin fiók ── -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-shield"></i> Admin fiók</h3>
            </div>
            <?php $admin = current_admin(); ?>
            <div class="form-group">
                <label>Név</label>
                <input type="text" value="<?= e($admin['name'] ?? '') ?>"
                       disabled style="opacity:.5;cursor:not-allowed;">
                <small>A név módosításához vedd fel a kapcsolatot a rendszergazdával.</small>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="text" value="<?= e($admin['email'] ?? '') ?>"
                       disabled style="opacity:.5;cursor:not-allowed;">
            </div>

            <div class="section-sep">Jelszó megváltoztatása</div>

            <div class="form-group">
                <label>Új jelszó</label>
                <input type="password" name="new_password"
                       placeholder="Minimum 6 karakter"
                       autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Jelszó megerősítése</label>
                <input type="password" name="confirm_password"
                       placeholder="Ismételd meg az új jelszót"
                       autocomplete="new-password">
            </div>
            <small style="color:var(--text-muted);">
                Ha nem szeretnéd megváltoztatni a jelszót, hagyd üresen!
            </small>
        </div>

        <!-- ── Veszélyzóna ── -->
        <div class="card" style="border-color:rgba(239,68,68,.3);">
            <div class="card-header">
                <h3 style="color:#fca5a5;"><i class="fas fa-exclamation-triangle"></i> Veszélyzóna</h3>
            </div>
            <div class="setting-info danger">
                <i class="fas fa-exclamation-triangle"></i>
                Az alábbi műveletek visszafordíthatatlanok! Légy óvatos.
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
                <a href="?clear_stats=1&csrf_token=<?= csrf_token() ?>"
                   class="btn btn-danger btn-sm"
                   data-confirm="Biztosan törlöd az összes statisztikát?">
                    <i class="fas fa-chart-bar"></i> Statisztikák törlése
                </a>
            </div>
        </div>

    </div><!-- /.settings-grid -->

    <!-- Mentés gomb -->
    <div class="settings-save-bar">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Beállítások mentése
        </button>
    </div>

</form>

<?php
// Statisztika törlés
if (isset($_GET['clear_stats']) && csrf_verify()) {
    $pdo->exec("DELETE FROM stats");
    $message      = 'Statisztikák törölve!';
    $message_type = 'success';
    echo '<script>
        document.querySelector(".alert")?.remove();
        const a = document.createElement("div");
        a.className = "alert alert-success";
        a.innerHTML = "<i class=\'fas fa-check-circle\'></i> Statisztikák törölve!";
        document.querySelector(".page-header").after(a);
    </script>';
}
?>

<style>
.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 80px;
}
.setting-info {
    background: rgba(59,130,246,.1);
    border: 1px solid rgba(59,130,246,.2);
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13px;
    color: #93c5fd;
    line-height: 1.6;
    margin-top: 8px;
}
.setting-info code {
    background: rgba(59,130,246,.2);
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 12px;
}
.setting-info.danger {
    background: rgba(239,68,68,.1);
    border-color: rgba(239,68,68,.2);
    color: #fca5a5;
}
.section-sep {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
    padding: 12px 0 8px;
    border-top: 1px solid var(--border);
    margin-top: 8px;
}
.settings-save-bar {
    position: fixed;
    bottom: 0; left: var(--sidebar-w); right: 0;
    background: var(--dark-2);
    border-top: 1px solid var(--border);
    padding: 16px 28px;
    display: flex;
    justify-content: flex-end;
    z-index: 90;
    box-shadow: 0 -4px 20px rgba(0,0,0,.3);
}

@media(max-width: 1024px) {
    .settings-grid { grid-template-columns: 1fr; }
    .settings-save-bar { left: 0; }
}
</style>

<?php require_once 'partials/footer.php'; ?>