<?php
/**
 * SIMAYA - Halaman Login dengan Fitur Demo 6 Role
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Silakan masukkan username dan password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Validasi password (password_verify atau password default)
        if ($user && (password_verify($password, $user['password']) || $password === 'password123')) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role' => $user['role'],
                'faskes_asal' => $user['faskes_asal'],
                'spesialisasi' => $user['spesialisasi']
            ];

            // Catat ke Audit Log
            logAudit($pdo, 'LOGIN', "Pengguna {$user['nama_lengkap']} ({$user['role']}) berhasil login ke sistem SIMAYA.", 'NORMAL');

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Username atau password salah!";
            // Catat kegagalan login ke Audit Log
            logAudit($pdo, 'LOGIN_GAGAL', "Percobaan login gagal untuk username: '{$username}'.", 'PERINGATAN');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMAYA (Sistem Rujukan Medis Terintegrasi)</title>
    <link rel="stylesheet" href="/assets/css/simaya.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-logo">
        <div class="icon">S</div>
        <h2 style="color: var(--hijau-tua); margin: 0; font-size: 1.5rem;">SIMAYA</h2>
        <p style="color: var(--abu-text); font-size: 0.85rem; margin-top: 4px;">
            Sistem Informasi Rujukan Medis & RME Terintegrasi
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="padding: 0.65rem 1rem; font-size: 0.85rem;">
            <span>⚠️</span> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.7rem; font-weight: 600;">
            Masuk ke Sistem SIMAYA
        </button>
    </form>

    <!-- QUICK DEMO SWITCHER FOR 6 ROLES -->
    <div class="quick-roles">
        <p style="font-size: 0.75rem; color: #64748B; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">
            ⚡ Demo Cepat (1-Klik Login Sesuai Kasus 5):
        </p>
        <div class="quick-roles-grid">
            <button type="button" class="btn-quick-role" onclick="quickLogin('dr.andi', 'password123')">
                <strong>1. Dokter Pengirim</strong>
                <small>dr. Andi (Puskesmas)</small>
            </button>
            <button type="button" class="btn-quick-role" onclick="quickLogin('pasien.bambang', 'password123')">
                <strong>2. Pasien Rujukan</strong>
                <small>Bambang Wijaya (Consent)</small>
            </button>
            <button type="button" class="btn-quick-role" onclick="quickLogin('petugas.rm', 'password123')">
                <strong>3. Petugas RM</strong>
                <small>Rina Marlina (Kelola RM)</small>
            </button>
            <button type="button" class="btn-quick-role" onclick="quickLogin('dr.budi', 'password123')">
                <strong>4. Dokter Penerima RS</strong>
                <small>dr. Budi, Sp.PD (Penyakit Dalam)</small>
            </button>
            <button type="button" class="btn-quick-role" onclick="quickLogin('security', 'password123')">
                <strong>5. Petugas Keamanan</strong>
                <small>Ahmad Fauzi (Audit Log)</small>
            </button>
            <button type="button" class="btn-quick-role" onclick="quickLogin('admin', 'password123')">
                <strong>6. Admin Sistem</strong>
                <small>Naya Putri (Full Akses)</small>
            </button>
        </div>
        <div style="margin-top: 0.5rem; text-align: center;">
            <button type="button" class="btn-quick-role" style="display: inline-block; width: 100%; text-align: center; border-color: #FCA5A5; background: #FFF5F5;" onclick="quickLogin('dr.siti', 'password123')">
                <span style="color: #991B1B; font-weight: 600;">Test Role Dokter Lain (Spesialis Anak):</span> 
                <small style="color: #64748B;">dr. Siti (Uji Hak Akses Tertolak RME)</small>
            </button>
        </div>
    </div>
</div>

<script src="/assets/js/main.js"></script>
</body>
</html>

