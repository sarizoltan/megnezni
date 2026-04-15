<?php
// ── Alap konfiguráció ──
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL',    'https://foglalasi-rendszer.hu');
define('ADMIN_URL',   BASE_URL . '/admin');
define('SYSTEM_URL',  BASE_URL . '/rendszer/');

// ── Feltöltési útvonalak (dinamikusan BASE_PATH alapján) ──
define('UPLOAD_URL',  BASE_URL  . '/assets/uploads/');
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');

// ── Hibakezelés ──
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// ── Időzóna ──
date_default_timezone_set('Europe/Budapest');

// ── Session beállítások (session_start() ELŐTT kell legyen) ──
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// ── Adatbázis ──
define('DB_HOST', 'mysql.omega');
define('DB_PORT', '3306');
define('DB_NAME', 'macarena');
define('DB_USER', 'macarena');
define('DB_PASS', 'Misterminit1230?');