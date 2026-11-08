<?php
/**
 * SIMAYA - Dashboard Utama Terintegrasi
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$page_title = 'Dashboard';

// Ambil Statistik
$total_pasien = $pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
$total_rujukan = $pdo->query("SELECT COUNT(*) FROM rujukan")->fetchColumn();
$rujukan_menunggu_consent = $pdo->query("SELECT COUNT(*) FROM rujukan WHERE status_consent = 'Menunggu Persetujuan'")->fetchColumn();
$rujukan_menunggu_verifikasi = $pdo->query("SELECT COUNT(*) FROM rujukan WHERE status_consent = 'Disetujui' AND status_verifikasi = 'Belum Diverifikasi'")->fetchColumn();
$total_audit = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

// Ambil data rujukan terbaru
$stmtLatest = $pdo->prepare("
    SELECT r.*, p.nama_pasien, p.no_rm 
    FROM rujukan r 
    JOIN pasien p ON r.pasien_id = p.id 
    ORDER BY r.id DESC 
    LIMIT 5
");
$stmtLatest->execute();
$latest_rujukan = $stmtLatest->fetchAll();

// Jika login sebagai pasien, cari rujukan khusus pasien tersebut
$pasien_rujukan = [];
if ($role === 'pasien') {
    $stmtMyRujukan = $pdo->prepare("
        SELECT r.*, p.nama_pasien, p.no_rm 
        FROM rujukan r 
        JOIN pasien p ON r.pasien_id = p.id 
        WHERE p.user_id = ? 
        ORDER BY r.id DESC
    ");
    $stmtMyRujukan->execute([$user['id']]);
    $pasien_rujukan = $stmtMyRujukan->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<!-- BANNER SELAMAT DATANG -->
<div class="card" style="border-left: 5px solid var(--hijau-tua); background: linear-gradient(to right, #FFFFFF, var(--hijau-pastel));">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="color: var(--hijau-tua); margin-bottom: 0.25rem;">Selamat Datang, <?= htmlspecialchars($user['nama_lengkap']) ?>!</h2>
            <p style="color: var(--abu-text); margin: 0;">
                Anda masuk sebagai <strong style="color: var(--hijau-sedang);"><?= htmlspecialchars(getRoleName($role)) ?></strong> 
                di <strong><?= htmlspecialchars($user['faskes_asal']) ?></strong>.
            </p>
        </div>
        <div>
            <span class="badge badge-success" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                🟢 Sistem Operasional & Terintegrasi
            </span>
        </div>
    </div>
</div>

<!-- DIAGRAM ALUR KASUS 5 -->
<div class="card">
    <div class="card-header header-green">
        <h3 class="card-title">📌 Alur Proses Kasus 5: Rujukan & Pertukaran Data Medis Elektronik</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem; text-align: center;">
            <div style="flex: 1; min-width: 140px; background: #F8FAFC; border: 1px solid var(--abu-border); padding: 0.75rem; border-radius: 8px;">
                <div style="font-size: 1.5rem;">🏥</div>
                <strong style="font-size: 0.85rem; color: var(--hijau-tua);">1. FKTP Puskesmas</strong>
                <p style="font-size: 0.75rem; color: #64748B; margin: 0;">Pemeriksaan pasien & buat RME</p>
            </div>
            <div style="font-weight: bold; color: var(--hijau-sedang);">➔</div>
            <div style="flex: 1; min-width: 140px; background: #F8FAFC; border: 1px solid var(--abu-border); padding: 0.75rem; border-radius: 8px;">
                <div style="font-size: 1.5rem;">✍️</div>
                <strong style="font-size: 0.85rem; color: var(--hijau-tua);">2. Persetujuan Pasien</strong>
                <p style="font-size: 0.75rem; color: #64748B; margin: 0;">Informed consent kirim data</p>
            </div>
            <div style="font-weight: bold; color: var(--hijau-sedang);">➔</div>
            <div style="flex: 1; min-width: 140px; background: #F8FAFC; border: 1px solid var(--abu-border); padding: 0.75rem; border-radius: 8px;">
                <div style="font-size: 1.5rem;">🔒</div>
                <strong style="font-size: 0.85rem; color: var(--hijau-tua);">3. Verifikasi RS</strong>
                <p style="font-size: 0.75rem; color: #64748B; margin: 0;">Validasi keaslian & SHA-256</p>
            </div>
            <div style="font-weight: bold; color: var(--hijau-sedang);">➔</div>
            <div style="flex: 1; min-width: 140px; background: #F8FAFC; border: 1px solid var(--abu-border); padding: 0.75rem; border-radius: 8px;">
                <div style="font-size: 1.5rem;">🩺</div>
                <strong style="font-size: 0.85rem; color: var(--hijau-tua);">4. Dokter RS</strong>
                <p style="font-size: 0.75rem; color: #64748B; margin: 0;">Buka RME sesuai hak akses poli</p>
            </div>
            <div style="font-weight: bold; color: var(--hijau-sedang);">➔</div>
            <div style="flex: 1; min-width: 140px; background: #F8FAFC; border: 1px solid var(--abu-border); padding: 0.75rem; border-radius: 8px;">
                <div style="font-size: 1.5rem;">🛡️</div>
                <strong style="font-size: 0.85rem; color: var(--hijau-tua);">5. Audit Trail</strong>
                <p style="font-size: 0.75rem; color: #64748B; margin: 0;">Semua aktivitas tercatat rapi</p>
            </div>
        </div>
    </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3><?= $total_pasien ?></h3>
            <p>Total Pasien Terdaftar</p>
        </div>
        <div class="stat-icon">👥</div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3><?= $total_rujukan ?></h3>
            <p>Total Rujukan Elektronik</p>
        </div>
        <div class="stat-icon">📑</div>
    </div>
    <div class="stat-card" style="border-left-color: var(--kuning);">
        <div class="stat-info">
            <h3 style="color: var(--kuning);"><?= $rujukan_menunggu_consent ?></h3>
            <p>Menunggu Consent Pasien</p>
        </div>
        <div class="stat-icon">⏳</div>
    </div>
    <div class="stat-card" style="border-left-color: var(--biru);">
        <div class="stat-info">
            <h3 style="color: var(--biru);"><?= $rujukan_menunggu_verifikasi ?></h3>
            <p>Siap Diverifikasi RS</p>
        </div>
        <div class="stat-icon">🔍</div>
    </div>
    <div class="stat-card" style="border-left-color: #8B5CF6;">
        <div class="stat-info">
            <h3 style="color: #8B5CF6;"><?= $total_audit ?></h3>
            <p>Total Catatan Audit</p>
        </div>
        <div class="stat-icon">🛡️</div>
    </div>
</div>

<!-- ROLE SPECIFIC ACTION ALERT -->
<?php if ($role === 'pasien'): ?>
    <?php 
    $needs_consent = false;
    foreach ($pasien_rujukan as $pr) {
        if ($pr['status_consent'] === 'Menunggu Persetujuan') {
            $needs_consent = true;
            break;
        }
    }
    ?>
    <?php if ($needs_consent): ?>
        <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>⚠️ Perhatian Pasien:</strong> Dokter Puskesmas telah merujuk Anda ke RS. Mohon berikan persetujuan (Informed Consent) transfer data medis Anda agar dapat diproses oleh Rumah Sakit.
            </div>
            <a href="pasien_persetujuan.php" class="btn btn-primary btn-sm">Buka Persetujuan Medis</a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- RECENT REFERRALS TABLE -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Data Rujukan Medis Terkini</h3>
        <div>
            <?php if ($role === 'dokter_pengirim' || $role === 'petugas_rm' || $role === 'admin'): ?>
                <a href="rujukan_buat.php" class="btn btn-primary btn-sm">➕ Buat Rujukan Baru</a>
            <?php endif; ?>
            <a href="rujukan_daftar.php" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="simaya-table">
                <thead>
                    <tr>
                        <th>No. Rujukan</th>
                        <th>Pasien & No. RM</th>
                        <th>Faskes Asal ➔ Tujuan</th>
                        <th>Poli Tujuan</th>
                        <th>Informed Consent</th>
                        <th>Verifikasi Keaslian</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($latest_rujukan)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94A3B8; padding: 2rem;">Belum ada data rujukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($latest_rujukan as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['no_rujukan']) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($r['nama_pasien']) ?><br>
                                    <small style="color: #64748B;"><?= htmlspecialchars($r['no_rm']) ?></small>
                                </td>
                                <td>
                                    <span style="font-size: 0.85rem;"><?= htmlspecialchars($r['faskes_asal']) ?></span><br>
                                    <span style="color: var(--hijau-sedang); font-size: 0.8rem;">➔ <?= htmlspecialchars($r['faskes_tujuan']) ?></span>
                                </td>
                                <td><span class="badge badge-info"><?= htmlspecialchars($r['poli_tujuan']) ?></span></td>
                                <td>
                                    <?php if ($r['status_consent'] === 'Disetujui'): ?>
                                        <span class="badge badge-success">✓ Disetujui Pasien</span>
                                    <?php elseif ($r['status_consent'] === 'Ditolak'): ?>
                                        <span class="badge badge-danger">✗ Ditolak Pasien</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⏳ Menunggu Consent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['status_verifikasi'] === 'Asli & Terverifikasi'): ?>
                                        <span class="badge badge-success">🛡️ Asli & Valid</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Belum Diverifikasi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($r['status_rujukan']) ?></span>
                                </td>
                                <td>
                                    <a href="rujukan_detail.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">Detail</a>
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

