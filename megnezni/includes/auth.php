<?php
function require_login(): void {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

function current_admin(): ?array {
    global $pdo;
    if (!isset($_SESSION['admin_id'])) return null;
    static $admin = null;
    if ($admin) return $admin;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id=?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;
    return $admin;
}

function is_superadmin(): bool {
    $admin = current_admin();
    return $admin && $admin['role'] === 'superadmin';
}