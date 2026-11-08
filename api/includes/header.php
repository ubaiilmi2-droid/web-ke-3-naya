<?php
/**
 * Header Template SIMAYA
 */
if (!defined('APP_NAME')) {
    define('APP_NAME', 'SIMAYA');
}
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' . APP_NAME : APP_NAME . ' | Sistem Rujukan Medis' ?></title>
    <link rel="stylesheet" href="assets/css/simaya.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="simaya-navbar">
    <div class="brand-wrapper">
        <div class="brand-logo-icon">S</div>
        <div class="brand-text">
            <h1>SIMAYA</h1>
            <p>Sistem Rujukan & Resume Medis Elektronik Terintegrasi</p>
        </div>
    </div>
    <div class="navbar-user">
        <?php if ($user): ?>
            <div class="user-badge">
                <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong> 
                <span>(<?= htmlspecialchars(getRoleName($user['role'])) ?>)</span>
            </div>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?')">Keluar</a>
        <?php endif; ?>
    </div>
</nav>

<div class="simaya-layout">
    <?php if ($user): ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>
    <main class="simaya-content">
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success">
                <span>✓</span> <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger">
                <span>⚠</span> <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

