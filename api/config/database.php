<?php
/**
 * SIMAYA - Sistem Informasi Rujukan Medis Elektronik Terintegrasi
 * Konfigurasi Database (Mendukung SQLite otomatis di Local & Vercel /tmp, serta MySQL XAMPP)
 */

define('APP_NAME', 'SIMAYA');
define('APP_TAGLINE', 'Sistem Rujukan Medis & Resume Medis Elektronik Terintegrasi');

// Driver: 'sqlite' (otomatis tanpa setup XAMPP) atau 'mysql' (menggunakan XAMPP/MySQL)
$db_driver = 'sqlite'; 

// Konfigurasi MySQL jika ingin memakai phpMyAdmin / XAMPP:
$mysql_host = 'localhost';
$mysql_user = 'root';
$mysql_pass = '';
$mysql_name = 'simaya_db';

try {
    if ($db_driver === 'sqlite') {
        // Deteksi apakah sedang berjalan di cloud/serverless Vercel
        $is_vercel = !empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || getenv('VERCEL');

        if ($is_vercel) {
            // Pada Vercel, filesystem bersifat read-only KECUALI direktori /tmp
            $db_path = '/tmp/simaya.sqlite';
            $bundled_db = __DIR__ . '/../database/simaya.sqlite';
            if (!file_exists($db_path) && file_exists($bundled_db)) {
                @copy($bundled_db, $db_path);
            }
        } else {
            // Localhost / XAMPP / PHP CLI
            $db_path = __DIR__ . '/../database/simaya.sqlite';
            if (!is_dir(__DIR__ . '/../database')) {
                @mkdir(__DIR__ . '/../database', 0777, true);
            }
        }

        $needs_init = !file_exists($db_path);
        
        $pdo = new PDO("sqlite:" . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($needs_init) {
            require_once __DIR__ . '/../database/init_db.php';
            initialize_database($pdo);
        }
    } else {
        $pdo = new PDO("mysql:host=$mysql_host;dbname=$mysql_name;charset=utf8mb4", $mysql_user, $mysql_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage() . "<br><small>Pastikan SQLite atau XAMPP MySQL aktif.</small>");
}

return $pdo;
