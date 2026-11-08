<?php
/**
 * SIMAYA - Sistem Informasi Rujukan Medis Elektronik Terintegrasi
 * Konfigurasi Database (Mendukung SQLite otomatis & MySQL XAMPP)
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
        $db_path = __DIR__ . '/../database/simaya.sqlite';
        $needs_init = !file_exists($db_path);
        
        $pdo = new PDO("sqlite:" . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Buat folder database jika belum ada
        if (!is_dir(__DIR__ . '/../database')) {
            mkdir(__DIR__ . '/../database', 0777, true);
        }

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

