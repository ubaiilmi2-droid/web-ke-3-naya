<?php
/**
 * SIMAYA - Master Front Controller & Router
 * Menghandle routing semua halaman secara aman tanpa loop redirect
 * dan memenuhi batas 12 Serverless Function pada Vercel Hobby Plan (hanya 1 function).
 */

// Tangkap URI
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$parsed_path = parse_url($request_uri, PHP_URL_PATH);

// Hilangkan leading dan trailing slash
$path = trim($parsed_path, '/');

// Jika request ke assets tapi masuk ke PHP handler (fallback jika web server mengarahkannya ke index):
if (strpos($path, 'assets/') === 0) {
    $asset_file = __DIR__ . '/../' . $path;
    if (!file_exists($asset_file)) {
        $asset_file = __DIR__ . '/' . $path;
    }
    if (file_exists($asset_file)) {
        $ext = strtolower(pathinfo($asset_file, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css; charset=utf-8',
            'js'  => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg'=> 'image/jpeg',
            'svg' => 'image/svg+xml'
        ];
        if (isset($mimes[$ext])) {
            header("Content-Type: " . $mimes[$ext]);
        }
        readfile($asset_file);
        exit;
    }
}

// Ambil nama file target (contoh: 'api/login.php' -> 'login.php' atau 'login.php' -> 'login.php')
$page = basename($path);

// Jika kosong atau 'index.php', tentukan halaman default
if ($page === '' || $page === 'index.php') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user'])) {
        $page = 'dashboard.php';
    } else {
        $page = 'login.php';
    }
}

// Daftar halaman yang sah dalam SIMAYA
$allowed_pages = [
    'login.php',
    'logout.php',
    'dashboard.php',
    'pasien_daftar.php',
    'pasien_persetujuan.php',
    'rujukan_buat.php',
    'rujukan_daftar.php',
    'rujukan_detail.php',
    'verifikasi_rujukan.php',
    'baca_rme.php',
    'audit_log.php',
    'manajemen_user.php'
];

if (in_array($page, $allowed_pages)) {
    $target_file = __DIR__ . '/' . $page;
    if (file_exists($target_file)) {
        require $target_file;
        exit;
    }
}

// Jika halaman tidak ditemukan
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>404 - Halaman Tidak Ditemukan | SIMAYA</title>
    <link rel="stylesheet" href="/assets/css/simaya.css">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; text-align: center; padding: 4rem 1rem; background: #F8FAFC;">
    <h1 style="color: #14532D; font-size: 3rem; margin-bottom: 0.5rem;">404</h1>
    <h2 style="color: #334155;">Halaman Tidak Ditemukan</h2>
    <p style="color: #64748B; margin-bottom: 2rem;">URL yang Anda tuju (<code><?= htmlspecialchars($request_uri) ?></code>) tidak ditemukan pada sistem SIMAYA.</p>
    <a href="/login.php" style="background: #14532D; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">
        Kembali ke Halaman Login
    </a>
</body>
</html>

