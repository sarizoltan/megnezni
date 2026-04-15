<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Csak POST kérés engedélyezett.']]);
    exit;
}

// ── JSON body beolvasása ──
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    // Fallback: form-data
    $data = $_POST;
}

// ── CSRF ellenőrzés ──
session_start();
$token = $data['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'errors' => ['Érvénytelen CSRF token!']]);
    exit;
}

// ── Rate limiting (IP alapú) ──
$ip       = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$rate_key = 'contact_' . md5($ip);

if (!isset($_SESSION[$rate_key])) {
    $_SESSION[$rate_key] = ['count' => 0, 'time' => time()];
}

// 10 percen belül max 3 üzenet
if (time() - $_SESSION[$rate_key]['time'] < 600) {
    if ($_SESSION[$rate_key]['count'] >= 3) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'errors'  => ['Túl sok üzenetet küldtél! Kérjük várj 10 percet.']
        ]);
        exit;
    }
} else {
    $_SESSION[$rate_key] = ['count' => 0, 'time' => time()];
}

// ── Adatok kinyerése és sanitizálás ──
$name      = trim($data['name']      ?? '');
$email     = trim($data['email']     ?? '');
$phone     = trim($data['phone']     ?? '');
$company   = trim($data['company']   ?? '');
$message   = trim($data['message']   ?? '');
$system_id = (int)($data['system_id'] ?? 0) ?: null;

// ── Validáció ──
$errors = [];

if (empty($name) || mb_strlen($name) < 2) {
    $errors[] = 'A név megadása kötelező (minimum 2 karakter)!';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Érvényes email cím megadása kötelező!';
}
if (empty($message) || mb_strlen($message) < 10) {
    $errors[] = 'Az üzenet megadása kötelező (minimum 10 karakter)!';
}
if (mb_strlen($name)    > 150) $errors[] = 'A név túl hosszú!';
if (mb_strlen($message) > 2000) $errors[] = 'Az üzenet maximum 2000 karakter lehet!';

// Spam ellenőrzés
$spam_words = ['casino', 'viagra', 'loan', 'crypto', 'bitcoin', 'click here', 'free money'];
foreach ($spam_words as $word) {
    if (stripos($message, $word) !== false || stripos($name, $word) !== false) {
        $errors[] = 'Az üzenet spam tartalmat észleltünk!';
        break;
    }
}

// System id ellenőrzés
if ($system_id) {
    $sys_check = $pdo->prepare("SELECT id FROM systems WHERE id=? AND active=1");
    $sys_check->execute([$system_id]);
    if (!$sys_check->fetch()) $system_id = null;
}

if ($errors) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── Mentés DB-be ──
try {
    $pdo->prepare("INSERT INTO inquiries
        (system_id, name, email, phone, company, message, status)
        VALUES (?,?,?,?,?,?,'new')")
        ->execute([$system_id, $name, $email, $phone, $company, $message]);

    $inquiry_id = $pdo->lastInsertId();

    // Rate limit növelése
    $_SESSION[$rate_key]['count']++;

    // Statisztika
    $pdo->prepare("INSERT INTO stats (system_id, event, ip) VALUES (?,?,?)")
        ->execute([$system_id, 'inquiry', $ip]);

} catch (PDOException $e) {
    error_log('Contact form DB hiba: ' . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['Adatbázis hiba! Kérjük próbáld újra.']]);
    exit;
}

// ── Email az adminnak ──
send_admin_notification_contact($name, $email, $phone, $company, $message, $system_id);

// ── Auto-reply az ügyfélnek ──
send_client_autoreply($name, $email);

echo json_encode([
    'success' => true,
    'message' => 'Üzenet sikeresen elküldve!'
]);

/* ══════════════════════════════════════════
   EMAIL FÜGGVÉNYEK
══════════════════════════════════════════ */

function send_admin_notification_contact(
    string $name, string $email, string $phone,
    string $company, string $message, ?int $system_id
): void {
    $site_name   = get_setting('site_name',  'Foglalas.hu');
    $admin_email = get_setting('site_email', '');
    if (!$admin_email) return;

    // Rendszer neve
    $system_name = '–';
    if ($system_id) {
        global $pdo;
        $s = $pdo->prepare("SELECT name FROM systems WHERE id=?");
        $s->execute([$system_id]);
        $system_name = $s->fetchColumn() ?: '–';
    }

    $subject = "🔔 Új érdeklődés érkezett – {$site_name}";

    $body = email_template("🔔 Új érdeklődés érkezett", "
        <p style='font-size:15px;line-height:1.7;color:#ccc;margin-bottom:20px;'>
            Új érdeklődés érkezett a weboldalon keresztül.
        </p>

        <div style='background:#1a1a1a;border:1px solid #2a2a2a;border-radius:10px;
                    padding:20px;margin-bottom:20px;'>
            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                <tr>
                    <td style='padding:8px 0;color:#777;width:130px;'>Név:</td>
                    <td style='padding:8px 0;color:#e0e0e0;font-weight:600;'>{$name}</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#777;border-top:1px solid #2a2a2a;'>Email:</td>
                    <td style='padding:8px 0;color:#c8a96e;border-top:1px solid #2a2a2a;'>
                        <a href='mailto:{$email}' style='color:#c8a96e;'>{$email}</a>
                    </td>
                </tr>
                " . ($phone ? "
                <tr>
                    <td style='padding:8px 0;color:#777;border-top:1px solid #2a2a2a;'>Telefon:</td>
                    <td style='padding:8px 0;color:#e0e0e0;border-top:1px solid #2a2a2a;'>
                        <a href='tel:{$phone}' style='color:#c8a96e;'>{$phone}</a>
                    </td>
                </tr>" : "") . "
                " . ($company ? "
                <tr>
                    <td style='padding:8px 0;color:#777;border-top:1px solid #2a2a2a;'>Cég:</td>
                    <td style='padding:8px 0;color:#e0e0e0;border-top:1px solid #2a2a2a;'>{$company}</td>
                </tr>" : "") . "
                <tr>
                    <td style='padding:8px 0;color:#777;border-top:1px solid #2a2a2a;'>Rendszer:</td>
                    <td style='padding:8px 0;color:#e0e0e0;border-top:1px solid #2a2a2a;'>{$system_name}</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#777;border-top:1px solid #2a2a2a;'>Beérkezett:</td>
                    <td style='padding:8px 0;color:#e0e0e0;border-top:1px solid #2a2a2a;'>" . date('Y. m. d. H:i') . "</td>
                </tr>
            </table>
        </div>

        <div style='background:#1a1a1a;border-left:3px solid #c8a96e;border-radius:0 8px 8px 0;
                    padding:16px 20px;margin-bottom:24px;'>
            <div style='font-size:11px;color:#777;text-transform:uppercase;
                        letter-spacing:1px;margin-bottom:8px;'>Üzenet</div>
            <p style='font-size:14px;color:#ccc;line-height:1.8;margin:0;'>" . nl2br(htmlspecialchars($message)) . "</p>
        </div>

        <div style='text-align:center;'>
            <a href='mailto:{$email}?subject=RE: Érdeklődés – {$site_name}'
               style='display:inline-flex;align-items:center;gap:8px;
                      background:linear-gradient(135deg,#c8a96e,#d4b887);
                      color:#111;padding:13px 28px;border-radius:8px;
                      text-decoration:none;font-weight:700;font-size:14px;'>
                ✉️ Válasz küldése
            </a>
        </div>
    ");

    send_mail($admin_email, $subject, $body);
}

function send_client_autoreply(string $name, string $email): void {
    $site_name  = get_setting('site_name',  'Foglalas.hu');
    $site_email = get_setting('site_email', '');
    $site_phone = get_setting('site_phone', '');
    if (!$site_email) return;

    $subject = "✅ Megkaptuk üzeneted – {$site_name}";

    $body = email_template("✅ Köszönjük az érdeklődést!", "
        <p style='font-size:15px;line-height:1.7;color:#ccc;margin-bottom:20px;'>
            Kedves <strong style='color:#fff;'>{$name}</strong>!
        </p>
        <p style='font-size:15px;line-height:1.7;color:#ccc;margin-bottom:20px;'>
            Köszönjük az érdeklődést! Üzenetedet megkaptuk és
            <strong style='color:#c8a96e;'>24 órán belül</strong>
            személyre szabott ajánlattal keresünk meg.
        </p>

        <div style='background:#1a1a1a;border:1px solid #2a2a2a;border-radius:10px;
                    padding:20px 24px;margin:24px 0;text-align:center;'>
            <div style='font-size:36px;margin-bottom:8px;'>⏱️</div>
            <div style='font-size:16px;font-weight:700;color:#fff;'>
                Várható válaszidő: <span style='color:#c8a96e;'>24 óra</span>
            </div>
            <div style='font-size:13px;color:#777;margin-top:6px;'>
                Munkanapokon 8:00–18:00 között
            </div>
        </div>

        <p style='font-size:14px;color:#777;line-height:1.8;margin-bottom:20px;'>
            Ha sürgős a kérdésed, keress minket közvetlenül:
        </p>

        <div style='display:flex;gap:16px;flex-wrap:wrap;justify-content:center;margin-bottom:24px;'>
            " . ($site_email ? "
            <a href='mailto:{$site_email}'
               style='display:inline-flex;align-items:center;gap:8px;
                      color:#c8a96e;font-size:14px;text-decoration:none;'>
                ✉️ {$site_email}
            </a>" : "") . "
            " . ($site_phone ? "
            <a href='tel:{$site_phone}'
               style='display:inline-flex;align-items:center;gap:8px;
                      color:#c8a96e;font-size:14px;text-decoration:none;'>
                📞 {$site_phone}
            </a>" : "") . "
        </div>

        <div style='text-align:center;'>
            <a href='" . BASE_URL . "'
               style='display:inline-flex;align-items:center;gap:8px;
                      background:linear-gradient(135deg,#c8a96e,#d4b887);
                      color:#111;padding:13px 28px;border-radius:8px;
                      text-decoration:none;font-weight:700;font-size:14px;'>
                🌐 Vissza a weboldalra
            </a>
        </div>
    ");

    send_mail($email, $subject, $body);
}