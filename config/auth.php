<?php
/**
 * SIMAYA - Manajemen Autentikasi, Hak Akses (RBAC), & Audit Log
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mencatat segala aktivitas ke dalam tabel audit_logs
 */
function logAudit($pdo, $action, $description, $status_keamanan = 'NORMAL')
{
    try {
        $user_id = $_SESSION['user']['id'] ?? NULL;
        $username = $_SESSION['user']['username'] ?? 'GUEST/ANONIM';
        $role = $_SESSION['user']['role'] ?? 'GUEST';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, username, role, action, description, ip_address, status_keamanan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))");
        $stmt->execute([$user_id, $username, $role, $action, $description, $ip_address, $status_keamanan]);
    } catch (Exception $e) {
        // Fallback jika error log agar proses utama tidak crash
        error_log("Gagal mencatat audit log: " . $e->getMessage());
    }
}

/**
 * Cek apakah user sudah login
 */
function requireLogin()
{
    if (!isset($_SESSION['user'])) {
        header("Location: login.php?msg=silakan_login");
        exit;
    }
}

/**
 * Cek hak akses role tertentu
 */
function requireRole($pdo, $allowed_roles = [])
{
    requireLogin();

    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    $current_role = $_SESSION['user']['role'];

    if (!in_array($current_role, $allowed_roles)) {
        // Catat insiden akses ditolak di Audit Log
        logAudit($pdo, 'AKSES_DITOLAK', 'Pengguna mencoba mengakses halaman terlarang tanpa hak akses role yang sesuai.', 'PERINGATAN');

        $_SESSION['flash_error'] = "Akses ditolak! Akun Anda (" . htmlspecialchars(getRoleName($current_role)) . ") tidak memiliki izin membuka halaman tersebut.";
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * Mengambil nama ramah dari role
 */
function getRoleName($role)
{
    $roles = [
        'admin' => 'Admin Sistem',
        'petugas_rm' => 'Petugas Rekam Medis (RM)',
        'dokter_pengirim' => 'Dokter Pengirim (FKTP / Puskesmas)',
        'dokter_penerima' => 'Dokter Penerima (FKRTL / RS)',
        'pasien' => 'Pasien Rujukan',
        'security' => 'Petugas Keamanan Sistem (Auditor)'
    ];
    return $roles[$role] ?? ucfirst($role);
}

/**
 * Menghitung Digital Signature / Hash SHA-256 untuk Integritas Data Medis
 */
function generateMedicalHash($data)
{
    // Merangkum data penting medis menjadi satu string unik
    $raw = sprintf(
        "%s|%s|%s|%s|%s|%s|%s|%s|%s",
        $data['no_rujukan'] ?? '',
        $data['pasien_id'] ?? '',
        $data['diagnosis_primer'] ?? '',
        $data['keluhan_utama'] ?? '',
        $data['tanda_vital'] ?? '',
        $data['tindakan_terapi'] ?? '',
        $data['obat_diberikan'] ?? '',
        $data['faskes_asal'] ?? '',
        $data['faskes_tujuan'] ?? ''
    );
    return hash('sha256', $raw);
}
