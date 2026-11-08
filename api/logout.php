<?php
/**
 * SIMAYA - Logout Handler dengan Audit Trail
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

if (isset($_SESSION['user'])) {
    $nama = $_SESSION['user']['nama_lengkap'];
    $role = $_SESSION['user']['role'];
    logAudit($pdo, 'LOGOUT', "Pengguna {$nama} ({$role}) telah logout dari sistem SIMAYA.", 'NORMAL');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: login.php");
exit;

