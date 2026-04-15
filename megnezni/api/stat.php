<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false]);
    exit;
}

$allowed   = ['view', 'demo_click', 'inquiry'];
$event     = $data['event']     ?? '';
$system_id = (int)($data['system_id'] ?? 0) ?: null;

if (!in_array($event, $allowed)) {
    echo json_encode(['success' => false]);
    exit;
}

// System id ellenőrzés
if ($system_id) {
    $check = $pdo->prepare("SELECT id FROM systems WHERE id=? AND active=1");
    $check->execute([$system_id]);
    if (!$check->fetch()) $system_id = null;
}

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

// Demo click esetén ne rögzítsük 1 percen belül ugyanarról az IP-ről
if ($event === 'demo_click' && $system_id) {
    $recent = $pdo->prepare("
        SELECT id FROM stats
        WHERE system_id=? AND event='demo_click' AND ip=?
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $recent->execute([$system_id, $ip]);
    if ($recent->fetch()) {
        echo json_encode(['success' => true, 'skipped' => true]);
        exit;
    }
}

try {
    $pdo->prepare("INSERT INTO stats (system_id, event, ip) VALUES (?,?,?)")
        ->execute([$system_id, $event, $ip]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Stat rögzítés hiba: ' . $e->getMessage());
    echo json_encode(['success' => false]);
}