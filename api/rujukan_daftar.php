<?php
/**
 * SIMAYA - Daftar Rujukan Medis Elektronik
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$page_title = 'Daftar Rujukan Medis';

// Filter berdasarkan role
$sql = "
    SELECT r.*, p.nama_pasien, p.no_rm, p.nik, p.user_id as pasien_user_id, u.nama_lengkap as nama_dokter_pengirim
    FROM rujukan r
    JOIN pasien p ON r.pasien_id = p.id
    JOIN users u ON r.dokter_pengirim_id = u.id
";

$params = [];

if ($role === 'pasien') {
    $sql .= " WHERE p.user_id = ?";
    $params[] = $user['id'];
} elseif ($role === 'dokter_penerima') {
    // Dokter spesialis RS melihat rujukan ke RS mereka
    $sql .= " WHERE r.faskes_tujuan = ?";
    $params[] = $user['faskes_asal'];
} elseif ($role === 'dokter_pengirim') {
    $sql .= " WHERE r.faskes_asal = ?";
    $params[] = $user['faskes_asal'];
}

$sql .= " ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rujukan_list = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="color: var(--hijau-tua); margin: 0;">📋 Daftar Rujukan Medis Elektronik</h2>
        <p style="color: var(--abu-text); margin: 0;">Monitoring status pertukaran data rujukan dan resume medis antar faskes</p>
    </div>
    <?php if ($role === 'dokter_pengirim' || $role === 'admin'): ?>
        <a href="rujukan_buat.php" class="btn btn-primary">➕ Buat Rujukan Baru</a>
    <?php endif; ?>
</div>

<!-- INFORMASI STATUS KHUSUS ROLE -->
<?php if ($role === 'pasien'): ?>
    <div class="alert alert-info">
        <span>ℹ️</span>
        <div>Berikut adalah daftar riwayat rujukan Anda. Jika ada status <strong>"Menunggu Persetujuan"</strong>, silakan klik tombol <strong>"Beri Persetujuan"</strong> agar data medis Anda dapat ditindaklanjuti oleh Rumah Sakit.</div>
    </div>
<?php elseif ($role === 'dokter_penerima'): ?>
    <div class="alert alert-info">
        <span>🏥</span>
        <div>Anda masuk sebagai <strong><?= htmlspecialchars($user['spesialisasi']) ?></strong>. Sesuai prinsip <strong>Role-Based Access Control (RBAC)</strong>, Anda hanya berwenang membuka Resume Medis Elektronik yang ditujukan ke spesialisasi poli Anda.</div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header header-green">
        <h3 class="card-title">Tabel Rujukan Medis Terintegrasi</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="simaya-table">
                <thead>
                    <tr>
                        <th>No. Rujukan</th>
                        <th>Data Pasien</th>
                        <th>Alur Faskes</th>
                        <th>Poli Tujuan</th>
                        <th>Informed Consent</th>
                        <th>Verifikasi Keaslian</th>
                        <th>Status Rujukan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rujukan_list)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94A3B8; padding: 2.5rem;">
                                Belum ada data rujukan yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rujukan_list as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r['no_rujukan']) ?></strong><br>
                                    <small style="color: #64748B;"><?= date('d M Y H:i', strtotime($r['tgl_rujukan'])) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($r['nama_pasien']) ?></strong><br>
                                    <small style="color: #64748B;">No. RM: <?= htmlspecialchars($r['no_rm']) ?></small>
                                </td>
                                <td>
                                    <span style="font-size: 0.85rem; font-weight: 500;"><?= htmlspecialchars($r['faskes_asal']) ?></span><br>
                                    <span style="color: var(--hijau-sedang); font-size: 0.8rem; font-weight: 600;">➔ <?= htmlspecialchars($r['faskes_tujuan']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($r['poli_tujuan']) ?></span>
                                </td>
                                <td>
                                    <?php if ($r['status_consent'] === 'Disetujui'): ?>
                                        <span class="badge badge-success">✓ Disetujui Pasien</span>
                                        <?php if (!empty($r['tgl_consent'])): ?>
                                            <br><small style="font-size: 0.7rem; color: #166534;"><?= date('d/m/Y H:i', strtotime($r['tgl_consent'])) ?></small>
                                        <?php endif; ?>
                                    <?php elseif ($r['status_consent'] === 'Ditolak'): ?>
                                        <span class="badge badge-danger">✗ Ditolak Pasien</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⏳ Menunggu Persetujuan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['status_verifikasi'] === 'Asli & Terverifikasi'): ?>
                                        <span class="badge badge-success">🛡️ Terverifikasi Asli</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Belum Diverifikasi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($r['status_rujukan']) ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                        <a href="rujukan_detail.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">
                                            🔍 Detail
                                        </a>

                                        <!-- Aksi Pasien Beri Consent -->
                                        <?php if ($role === 'pasien' && $r['status_consent'] === 'Menunggu Persetujuan'): ?>
                                            <a href="pasien_persetujuan.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm">
                                                ✍️ Persetujuan
                                            </a>
                                        <?php endif; ?>

                                        <!-- Aksi RS Verifikasi Keaslian -->
                                        <?php if (($role === 'petugas_rm' || $role === 'security' || $role === 'admin') && $r['status_consent'] === 'Disetujui' && $r['status_verifikasi'] !== 'Asli & Terverifikasi'): ?>
                                            <a href="verifikasi_rujukan.php?id=<?= $r['id'] ?>" class="btn btn-success btn-sm">
                                                🛡️ Verifikasi RS
                                            </a>
                                        <?php endif; ?>

                                        <!-- Aksi Dokter RS Buka RME -->
                                        <?php if ($role === 'dokter_penerima'): ?>
                                            <a href="baca_rme.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm">
                                                🩺 Buka RME
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

