<?php
/**
 * Sidebar Navigation SIMAYA
 * Berdasarkan hak akses 6 role
 */
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user']['role'] ?? '';
?>

<aside class="simaya-sidebar">
    <div class="sidebar-heading">Menu Utama</div>
    <ul class="sidebar-menu">
        <li class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php">
                <span class="menu-icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>

    <!-- MENU ROLE: PASIEN -->
    <?php if ($role === 'pasien'): ?>
        <div class="sidebar-heading">Layanan Pasien</div>
        <ul class="sidebar-menu">
            <li class="<?= $current_page === 'pasien_persetujuan.php' ? 'active' : '' ?>">
                <a href="pasien_persetujuan.php">
                    <span class="menu-icon">✍️</span>
                    <span>Persetujuan Data Medis</span>
                </a>
            </li>
            <li class="<?= $current_page === 'rujukan_daftar.php' ? 'active' : '' ?>">
                <a href="rujukan_daftar.php">
                    <span class="menu-icon">📋</span>
                    <span>Riwayat Rujukan Saya</span>
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <!-- MENU ROLE: DOKTER PENGIRIM (FKTP) -->
    <?php if ($role === 'dokter_pengirim'): ?>
        <div class="sidebar-heading">Layanan Dokter FKTP</div>
        <ul class="sidebar-menu">
            <li class="<?= $current_page === 'pasien_daftar.php' ? 'active' : '' ?>">
                <a href="pasien_daftar.php">
                    <span class="menu-icon">👥</span>
                    <span>Daftar Pasien Berobat</span>
                </a>
            </li>
            <li class="<?= $current_page === 'rujukan_buat.php' ? 'active' : '' ?>">
                <a href="rujukan_buat.php">
                    <span class="menu-icon">➕</span>
                    <span>Buat Rujukan & RME</span>
                </a>
            </li>
            <li class="<?= $current_page === 'rujukan_daftar.php' ? 'active' : '' ?>">
                <a href="rujukan_daftar.php">
                    <span class="menu-icon">📤</span>
                    <span>Monitoring Rujukan Keluar</span>
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <!-- MENU ROLE: PETUGAS REKAM MEDIS (RM) -->
    <?php if ($role === 'petugas_rm'): ?>
        <div class="sidebar-heading">Perekam Medis</div>
        <ul class="sidebar-menu">
            <li class="<?= $current_page === 'pasien_daftar.php' ? 'active' : '' ?>">
                <a href="pasien_daftar.php">
                    <span class="menu-icon">📁</span>
                    <span>Master Pasien & No. RM</span>
                </a>
            </li>
            <li class="<?= $current_page === 'rujukan_daftar.php' ? 'active' : '' ?>">
                <a href="rujukan_daftar.php">
                    <span class="menu-icon">📑</span>
                    <span>Kelola Berkas Rujukan</span>
                </a>
            </li>
            <li class="<?= $current_page === 'verifikasi_rujukan.php' ? 'active' : '' ?>">
                <a href="verifikasi_rujukan.php">
                    <span class="menu-icon">🔍</span>
                    <span>Verifikasi Keaslian RME</span>
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <!-- MENU ROLE: DOKTER PENERIMA (RS / FKRTL) -->
    <?php if ($role === 'dokter_penerima'): ?>
        <div class="sidebar-heading">Layanan Dokter RS</div>
        <ul class="sidebar-menu">
            <li class="<?= $current_page === 'rujukan_daftar.php' ? 'active' : '' ?>">
                <a href="rujukan_daftar.php">
                    <span class="menu-icon">📥</span>
                    <span>Daftar Pasien Rujukan Masuk</span>
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <!-- MENU ROLE: SECURITY OFFICER -->
    <?php if ($role === 'security'): ?>
        <div class="sidebar-heading">Keamanan & Integritas</div>
        <ul class="sidebar-menu">
            <li class="<?= $current_page === 'audit_log.php' ? 'active' : '' ?>">
                <a href="audit_log.php">
                    <span class="menu-icon">🛡️</span>
                    <span>Audit Trail & Log Akses</span>
                </a>
            </li>
            <li class="<?= $current_page === 'verifikasi_rujukan.php' ? 'active' : '' ?>">
                <a href="verifikasi_rujukan.php">
                    <span class="menu-icon">🔒</span>
                    <span>Verifikasi Keaslian & Hash</span>
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <!-- MENU ROLE: ADMIN SISTEM -->
    <?php if ($role === 'admin'): ?>
        <div class="sidebar-heading">Administrasi Sistem</div>
        <ul class="sidebar-menu">
            <li class="<?= $current_page === 'manajemen_user.php' ? 'active' : '' ?>">
                <a href="manajemen_user.php">
                    <span class="menu-icon">⚙️</span>
                    <span>Manajemen Pengguna</span>
                </a>
            </li>
            <li class="<?= $current_page === 'pasien_daftar.php' ? 'active' : '' ?>">
                <a href="pasien_daftar.php">
                    <span class="menu-icon">👥</span>
                    <span>Data Pasien</span>
                </a>
            </li>
            <li class="<?= $current_page === 'rujukan_daftar.php' ? 'active' : '' ?>">
                <a href="rujukan_daftar.php">
                    <span class="menu-icon">📑</span>
                    <span>Semua Data Rujukan</span>
                </a>
            </li>
            <li class="<?= $current_page === 'verifikasi_rujukan.php' ? 'active' : '' ?>">
                <a href="verifikasi_rujukan.php">
                    <span class="menu-icon">🔍</span>
                    <span>Uji Keaslian Dokumen</span>
                </a>
            </li>
            <li class="<?= $current_page === 'audit_log.php' ? 'active' : '' ?>">
                <a href="audit_log.php">
                    <span class="menu-icon">🛡️</span>
                    <span>Audit Log Sistem</span>
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <div style="margin-top: auto; padding: 1rem 1.25rem; font-size: 0.75rem; color: #94A3B8; border-top: 1px solid var(--abu-border);">
        <strong>SIMAYA v1.0</strong><br>
        Kasus 5 Rujukan Medis FKTP - FKRTL
    </div>
</aside>

