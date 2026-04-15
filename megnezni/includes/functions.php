<?php

// ── Beállítás lekérése ──
function get_setting(string $key, string $default = ''): string {
    global $pdo;
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key`=?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    $cache[$key] = ($val !== false) ? $val : $default;
    return $cache[$key];
}

// ── Beállítás mentése ──
function set_setting(string $key, string $value): void {
    global $pdo;
    $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?)
                   ON DUPLICATE KEY UPDATE `value`=?")
        ->execute([$key, $value, $value]);
}

// ── XSS védelem ──
function e(?string $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

// ── CSRF token ──
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ── Slug generálás ──
function generate_slug(string $str): string {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $str = str_replace(
        ['á','é','í','ó','ö','ő','ú','ü','ű','Á','É','Í','Ó','Ö','Ő','Ú','Ü','Ű'],
        ['a','e','i','o','o','o','u','u','u','a','e','i','o','o','o','u','u','u'],
        $str
    );
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

// ── Fájlfeltöltés ──
function upload_image(string $input_name, string $prefix = 'img'): ?string {
    if (empty($_FILES[$input_name]['name'])) return null;
    $ext     = strtolower(pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif','svg'];
    if (!in_array($ext, $allowed))           return null;
    if ($_FILES[$input_name]['size'] > 5242880) return null; // 5MB
    if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
    $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($_FILES[$input_name]['tmp_name'], UPLOAD_PATH . $filename)) {
        return $filename;
    }
    return null;
}

// ── Kép törlés ──
function delete_image(?string $filename): void {
    if ($filename && file_exists(UPLOAD_PATH . $filename)) {
        unlink(UPLOAD_PATH . $filename);
    }
}

// ── Formázások ──
function format_price(float $price): string {
    return number_format($price, 0, ',', ' ') . ' Ft';
}

function format_date(string $date): string {
    return date('Y. m. d.', strtotime($date));
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'most';
    if ($diff < 3600)   return floor($diff/60)   . ' perce';
    if ($diff < 86400)  return floor($diff/3600)  . ' órája';
    if ($diff < 604800) return floor($diff/86400) . ' napja';
    return date('Y. m. d.', strtotime($datetime));
}

// ── JSON features kezelés ──
function decode_features(?string $json): array {
    if (!$json) return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// ── Statisztika rögzítés ──
function record_stat(string $event, ?int $system_id = null): void {
    global $pdo;
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $pdo->prepare("INSERT INTO stats (system_id, event, ip) VALUES (?,?,?)")
        ->execute([$system_id, $event, $ip]);
}

// ── Email küldés ──
function send_mail(string $to, string $subject, string $body): bool {
    $site_name  = get_setting('site_name',       'Foglalas.hu');
    $from_email = get_setting('smtp_from_email', 'info@foglalas.hu');

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$site_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";

    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

// ── Email sablon ──
function email_template(string $title, string $content): string {
    $site_name = get_setting('site_name', 'Foglalas.hu');
    $year      = date('Y');
    $base_url  = BASE_URL;

    return <<<HTML
    <!DOCTYPE html>
    <html lang="hu">
    <head>
        <meta charset="UTF-8">
        <style>
            body        { margin:0; padding:0; background:#0f0f0f; font-family:'Segoe UI',Arial,sans-serif; }
            .wrap       { max-width:600px; margin:0 auto; padding:32px 16px; }
            .header     { text-align:center; padding:24px 0; }
            .logo       { font-size:26px; font-weight:800; color:#c8a96e; letter-spacing:2px; }
            .card       { background:#1a1a1a; border-radius:16px; border:1px solid #2a2a2a; overflow:hidden; }
            .card-title { background:linear-gradient(135deg,#c8a96e,#d4b887); padding:22px 28px; }
            .card-title h2 { margin:0; color:#0f0f0f; font-size:20px; font-weight:700; }
            .card-body  { padding:28px; color:#ccc; font-size:15px; line-height:1.7; }
            .btn        { display:inline-block; padding:13px 30px; background:#c8a96e; color:#0f0f0f !important; border-radius:8px; text-decoration:none; font-weight:700; margin:16px 0; }
            .footer     { text-align:center; padding:20px; color:#444; font-size:12px; }
            .footer a   { color:#c8a96e; text-decoration:none; }
            hr          { border:none; border-top:1px solid #2a2a2a; margin:20px 0; }
        </style>
    </head>
    <body>
        <div class="wrap">
            <div class="header">
                <div class="logo">✦ {$site_name}</div>
            </div>
            <div class="card">
                <div class="card-title"><h2>{$title}</h2></div>
                <div class="card-body">{$content}</div>
            </div>
            <div class="footer">
                &copy; {$year} <a href="{$base_url}">{$site_name}</a>
            </div>
        </div>
    </body>
    </html>
    HTML;
}