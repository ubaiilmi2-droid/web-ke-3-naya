<?php
/**
 * SIMAYA - Persetujuan Pasien (Informed Consent Elektronik)
 * Pasien memberikan persetujuan pengiriman data medis ke RS rujukan
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireRole($pdo, ['pasien', 'admin']);

$user = $_SESSION['user'];
$page_title = 'Persetujuan Transfer Data Medis';

// Cari rujukan yang membutuhkan consent
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, p.nama_pasien, p.no_rm, p.nik, p.tgl_lahir, p.alamat, u.nama_lengkap as nama_dokter_pengirim
        FROM rujukan r
        JOIN pasien p ON r.pasien_id = p.id
        JOIN users u ON r.dokter_pengirim_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $rujukan = $stmt->fetch();
} else {
    // Cari rujukan terbaru pasien ini yang status consent-nya masih menunggu
    $stmt = $pdo->prepare("
        SELECT r.*, p.nama_pasien, p.no_rm, p.nik, p.tgl_lahir, p.alamat, u.nama_lengkap as nama_dokter_pengirim
        FROM rujukan r
        JOIN pasien p ON r.pasien_id = p.id
        JOIN users u ON r.dokter_pengirim_id = u.id
        WHERE p.user_id = ? AND r.status_consent = 'Menunggu Persetujuan'
        ORDER BY r.id DESC
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $rujukan = $stmt->fetch();
}

// Proses Persetujuan (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_consent'])) {
    $rujukan_id = intval($_POST['rujukan_id']);
    $keputusan = $_POST['keputusan'] ?? ''; // 'setuju' atau 'tolak'
    $catatan = trim($_POST['catatan_consent'] ?? '');

    if ($keputusan === 'setuju') {
        $status_consent = 'Disetujui';
        $status_rujukan = 'Terkirim ke RS';
        $action_audit = 'CONSENT_SETUJU';
        $deskripsi = "Pasien {$user['nama_lengkap']} MENYETUJUI pengiriman data medis rujukan ID: {$rujukan_id} ke Rumah Sakit tujuan.";
    } else {
        $status_consent = 'Ditolak';
        $status_rujukan = 'Dibatalkan Pasien';
        $action_audit = 'CONSENT_DITOLAK';
        $deskripsi = "Pasien {$user['nama_lengkap']} MENOLAK pengiriman data medis rujukan ID: {$rujukan_id}. Alasan: {$catatan}.";
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE rujukan 
        SET status_consent = ?, tgl_consent = datetime('now', 'localtime'), catatan_consent = ?, status_rujukan = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$status_consent, $catatan, $status_rujukan, $rujukan_id]);

    logAudit($pdo, $action_audit, $deskripsi, $keputusan === 'setuju' ? 'NORMAL' : 'PERINGATAN');

    $_SESSION['flash_success'] = "Keputusan persetujuan Anda berhasil disimpan dan dicatat dalam audit log!";
    header("Location: rujukan_detail.php?id=" . $rujukan_id);
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <h2 style="color: var(--hijau-tua); margin: 0;">✍️ Formulir Persetujuan Elektronik Pasien (Informed Consent)</h2>
    <p style="color: var(--abu-text); margin: 0;">Hak pasien untuk mengizinkan atau menolak pertukaran data medis antar fasilitas kesehatan</p>
</div>

<?php if (!$rujukan): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem;">🎉</div>
            <h3 style="color: var(--hijau-tua); margin-top: 0.5rem;">Tidak Ada Rujukan yang Menunggu Persetujuan</h3>
            <p style="color: var(--abu-text);">Semua rujukan medis Anda telah diproses atau belum ada rujukan baru yang diterbitkan.</p>
            <a href="rujukan_daftar.php" class="btn btn-primary" style="margin-top: 1rem;">Lihat Riwayat Rujukan</a>
        </div>
    </div>
<?php else: ?>
    <!-- KARTU INFORMASI PERSETUJUAN -->
    <div class="card" style="border-top: 5px solid var(--hijau-tua);">
        <div class="card-header header-green">
            <h3 class="card-title">Permohonan Persetujuan Rujukan: <?= htmlspecialchars($rujukan['no_rujukan']) ?></h3>
            <span class="badge badge-warning"><?= htmlspecialchars($rujukan['status_consent']) ?></span>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <span>ℹ️</span>
                <div>
                    <strong>Pemberitahuan Hak Pasien (UU Kesehatan & Permenkes RME):</strong> Data rekam medis adalah data pribadi yang bersifat rahasia. Puskesmas memerlukan izin tertulis/elektronik Anda sebelum data medis dan riwayat diagnosis dikirimkan secara otomatis ke Rumah Sakit rujukan.
                </div>
            </div>

            <!-- RINGKASAN DATA YANG AKAN DIKIRIM -->
            <div style="background: #F8FAFC; border: 1px solid var(--abu-border); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <h4 style="color: var(--hijau-tua); margin-top: 0; margin-bottom: 1rem;">📋 Ringkasan Data Rujukan & Resume Medis:</h4>
                <div class="form-row">
                    <div>
                        <small style="color: #64748B;">Nama Pasien</small><br>
                        <strong><?= htmlspecialchars($rujukan['nama_pasien']) ?></strong> (No. RM: <?= htmlspecialchars($rujukan['no_rm']) ?>)
                    </div>
                    <div>
                        <small style="color: #64748B;">Faskes Pengirim</small><br>
                        <strong><?= htmlspecialchars($rujukan['faskes_asal']) ?></strong> (dr. <?= htmlspecialchars($rujukan['nama_dokter_pengirim']) ?>)
                    </div>
                    <div>
                        <small style="color: #64748B;">Rumah Sakit Tujuan</small><br>
                        <strong style="color: var(--hijau-tua);"><?= htmlspecialchars($rujukan['faskes_tujuan']) ?></strong>
                    </div>
                    <div>
                        <small style="color: #64748B;">Poli Spesialis Tujuan</small><br>
                        <span class="badge badge-info"><?= htmlspecialchars($rujukan['poli_tujuan']) ?></span>
                    </div>
                </div>

                <div style="margin-top: 1rem; border-top: 1px solid var(--abu-border); padding-top: 0.75rem;">
                    <small style="color: #64748B;">Diagnosis Terduga</small><br>
                    <strong><?= htmlspecialchars($rujukan['diagnosis_primer']) ?></strong>
                </div>

                <div style="margin-top: 0.5rem;">
                    <small style="color: #64748B;">Alasan Rujukan</small><br>
                    <p style="margin: 0; font-size: 0.9rem;"><?= htmlspecialchars($rujukan['alasan_rujukan']) ?></p>
                </div>
            </div>

            <!-- FORM PERNYATAAN PERSETUJUAN -->
            <?php if ($rujukan['status_consent'] === 'Menunggu Persetujuan'): ?>
                <form method="POST" action="pasien_persetujuan.php">
                    <input type="hidden" name="rujukan_id" value="<?= $rujukan['id'] ?>">
                    <input type="hidden" name="submit_consent" value="1">

                    <div style="background: var(--hijau-pastel); border: 2px dashed var(--hijau-sedang); padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <h4 style="color: var(--hijau-tua); margin-top: 0; margin-bottom: 0.5rem;">📜 Pernyataan Persetujuan (Informed Consent):</h4>
                        <p style="font-size: 0.9rem; color: #14532D; line-height: 1.6; margin: 0;">
                            <em>"Saya menyatakan telah memahami alasan rujukan ini dan <strong>memberikan persetujuan penuh</strong> kepada <strong><?= htmlspecialchars($rujukan['faskes_asal']) ?></strong> untuk mentransfer data Resume Medis Elektronik (RME) saya ke <strong><?= htmlspecialchars($rujukan['faskes_tujuan']) ?></strong> melalui sistem terintegrasi SIMAYA demi kesinambungan perawatan saya."</em>
                        </p>
                    </div>

                    <div class="form-group">
                        <label>Catatan Tambahan Pasien (Opsional):</label>
                        <input type="text" name="catatan_consent" class="form-control" placeholder="Contoh: Saya bersedia dirujuk dan meminta jadwal konsultasi pagi hari.">
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                        <button type="submit" name="keputusan" value="tolak" class="btn btn-outline" style="color: #DC2626; border-color: #DC2626;" onclick="return confirm('Apakah Anda yakin ingin MENOLAK pengiriman data medis ini? Rujukan tidak akan diteruskan ke Rumah Sakit.')">
                            ✗ Tolak Pengiriman Data
                        </button>
                        <button type="submit" name="keputusan" value="setuju" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-weight: 600;" onclick="return confirm('Apakah Anda yakin ingin MEMBERIKAN PERSETUJUAN transfer data medis ini?')">
                            ✓ Ya, Saya Setuju & Kirim Data ke RS
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert <?= $rujukan['status_consent'] === 'Disetujui' ? 'alert-success' : 'alert-danger' ?>">
                    <span><?= $rujukan['status_consent'] === 'Disetujui' ? '✓' : '✗' ?></span>
                    <div>
                        <strong>Persetujuan Telah Diproses:</strong> Anda telah <strong><?= htmlspecialchars($rujukan['status_consent']) ?></strong> pengiriman data ini pada tanggal <strong><?= htmlspecialchars($rujukan['tgl_consent']) ?></strong>.
                        <?php if (!empty($rujukan['catatan_consent'])): ?>
                            <br><small>Catatan: <?= htmlspecialchars($rujukan['catatan_consent']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <a href="rujukan_detail.php?id=<?= $rujukan['id'] ?>" class="btn btn-primary">Lihat Lembar RME Lengkap</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

