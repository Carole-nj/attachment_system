<?php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    die('<h3>Database connection failed</h3><p>Start <b>Apache</b> and <b>MySQL</b> in the XAMPP Control Panel, then import <code>database.sql</code> in phpMyAdmin.</p>');
}

// ---------- helpers ----------
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function q($sql, $types = '', ...$params) {
    global $db;
    $st = $db->prepare($sql);
    if ($types !== '') {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    return $st;
}
function rows($sql, $types = '', ...$params) {
    return q($sql, $types, ...$params)->get_result()->fetch_all(MYSQLI_ASSOC);
}
function row($sql, $types = '', ...$params) {
    $r = rows($sql, $types, ...$params);
    return $r ? $r[0] : null;
}
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// ---------- flash messages ----------
function flash($msg, $type = 'success') {
    $_SESSION['flash'][] = [$type, $msg];
}
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $f) {
            echo '<div class="flash ' . e($f[0]) . '">' . e($f[1]) . '</div>';
        }
        unset($_SESSION['flash']);
    }
}

// ---------- CSRF protection (checked automatically on every POST) ----------
function csrf_field() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(400);
        die('Invalid form token. Please go back, refresh the page and try again.');
    }
}

// ---------- authentication ----------
function current_user() {
    return $_SESSION['user'] ?? null;
}
function require_login(array $roles = []) {
    $u = current_user();
    if (!$u) {
        redirect('login.php');
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied.');
    }
    return $u;
}

require_once __DIR__ . '/functions.php';
