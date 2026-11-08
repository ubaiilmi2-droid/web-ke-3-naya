<?php
/**
 * SIMAYA - Verifikasi Keaslian Rujukan & Resume Medis Elektronik (RME)
 * Rumah Sakit memverifikasi keaslian data menggunakan Digital Signature / Hash SHA-256
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireRole($pdo, ['petugas_rm', 'security', 'admin', 'dokter_penerima']);

$user = $_SESSION['user'];
$page_title = 'Verifikasi Keaslian RME';

$id = intval($_GET['id'] ?? 0);

// Proses Verifikasi jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_verifikasi'])) {
    $rujukan_id = intval($_POST['rujukan_id']);
    
    // Ambil data rujukan
    $stmt = $pdo->prepare("SELECT * FROM rujukan WHERE id = ?");
    $stmt->execute([$rujukan_id]);
    $r = $stmt->fetch();

    if ($r) {
        // Hitung ulang hash untuk uji integritas
        $data_current = [
            'no_rujukan' => $r['no_rujukan'],
            'pasien_id' => $r['pasien_id'],
            'diagnosis_primer' => $r['diagnosis_primer'],
            'keluhan_utama' => $r['keluhan_utama'],
            'tanda_vital' => $r['tanda_vital'],
            'tindakan_terapi' => $r['tindakan_terapi'],
            'obat_diberikan' => $r['obat_diberikan'],
            'faskes_asal' => $r['faskes_asal'],
            'faskes_tujuan' => $r['faskes_tujuan']
        ];
        $recalculated_hash = generateMedicalHash($data_current);

        if ($recalculated_hash === $r['digital_hash']) {
            // Hash Cocok -> ASLI & VALID
            $stmtUpdate = $pdo->prepare("
                UPDATE rujukan 
                SET status_verifikasi = 'Asli & Terverifikasi', 
                    tgl_verifikasi = datetime('now', 'localtime'), 
                    petugas_verifikasi = ?, 
                    status_rujukan = 'Terverifikasi RS'
                WHERE id = ?
            ");
            $stmtUpdate->execute([$user['nama_lengkap'], $rujukan_id]);

            logAudit($pdo, 'VERIFIKASI_RME_SUKSES', "Petugas {$user['nama_lengkap']} berhasil memverifikasi keaslian rujukan {$r['no_rujukan']}. Hash SHA-256 Valid dan Cocok 100%.", 'NORMAL');

            $_SESSION['flash_success'] = "Dokumen RME {$r['no_rujukan']} berhasil diverifikasi! Keaslian data terbukti sah dan tidak ada manipulasi data.";
        } else {
            // Hash Berbeda -> MANIPULASI TERDETEKSI
            $stmtUpdate = $pdo->prepare("
                UPDATE rujukan 
                SET status_verifikasi = 'Tidak Valid', 
                    tgl_verifikasi = datetime('now', 'localtime'), 
                    petugas_verifikasi = ?, 
                    status_rujukan = 'Ditolak - Integritas Rusak'
                WHERE id = ?
            ");
            $stmtUpdate->execute([$user['nama_lengkap'], $rujukan_id]);

            logAudit($pdo, 'VERIFIKASI_RME_GAGAL', "PERINGATAN: Integritas data rujukan {$r['no_rujukan']} rusak atau telah dimanipulasi! Hash tidak cocok.", 'ANOMALI');

            $_SESSION['flash_error'] = "PERINGATAN: Dokumen rujukan tidak valid! Hash SHA-256 tidak cocok dengan data asli.";
        }

        header("Location: rujukan_detail.php?id=" . $rujukan_id);
        exit;
    }
}

// Daftar rujukan yang perlu diverifikasi
$stmtPending = $pdo->query("
    SELECT r.*, p.nama_pasien, p.no_rm, u.nama_lengkap as nama_dokter_pengirim
    FROM rujukan r
    JOIN pasien p ON r.pasien_id = p.id
    JOIN users u ON r.dokter_pengirim_id = u.id
    WHERE r.status_consent = 'Disetujui'
    ORDER BY r.id DESC
");
$rujukan_list = $stmtPending->fetchAll();

// Jika ada ID rujukan spesifik dipilih
$detail_rujukan = null;
$hash_match = null;
if ($id > 0) {
    $stmtDetail = $pdo->prepare("
        SELECT r.*, p.nama_pasien, p.no_rm, p.nik, u.nama_lengkap as nama_dokter_pengirim
        FROM rujukan r
        JOIN pasien p ON r.pasien_id = p.id
        JOIN users u ON r.dokter_pengirim_id = u.id
        WHERE r.id = ?
    ");
    $stmtDetail->execute([$id]);
    $detail_rujukan = $stmtDetail->fetch();

    if ($detail_rujukan) {
        $data_current = [
            'no_rujukan' => $detail_rujukan['no_rujukan'],
            'pasien_id' => $detail_rujukan['pasien_id'],
            'diagnosis_primer' => $detail_rujukan['diagnosis_primer'],
            'keluhan_utama' => $detail_rujukan['keluhan_utama'],
            'tanda_vital' => $detail_rujukan['tanda_vital'],
            'tindakan_terapi' => $detail_rujukan['tindakan_terapi'],
            'obat_diberikan' => $detail_rujukan['obat_diberikan'],
            'faskes_asal' => $detail_rujukan['faskes_asal'],
            'faskes_tujuan' => $detail_rujukan['faskes_tujuan']
        ];
        $calculated_hash = generateMedicalHash($data_current);
        $hash_match = ($calculated_hash === $detail_rujukan['digital_hash']);
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <h2 style="color: var(--hijau-tua); margin: 0;">🛡️ Verifikasi Keaslian & Integritas Dokumen RME</h2>
    <p style="color: var(--abu-text); margin: 0;">Proses validasi kriptografis SHA-256 oleh Rumah Sakit untuk memastikan data rekam medis tidak mengalami manipulasi selama pengiriman data</p>
</div>

<?php if ($detail_rujukan): ?>
    <!-- PANEL VERIFIKASI DETAIL UNTUK RUJUKAN TERTENTU -->
    <div class="card" style="border-top: 5px solid var(--hijau-sedang);">
        <div class="card-header header-green">
            <h3 class="card-title">🔍 Pemeriksaan Kriptografis: <?= htmlspecialchars($detail_rujukan['no_rujukan']) ?></h3>
            <span class="badge badge-info"><?= htmlspecialchars($detail_rujukan['status_rujukan']) ?></span>
        </div>
        <div class="card-body">
            <div class="form-row" style="margin-bottom: 1rem;">
                <div>
                    <small style="color: #64748B;">Pasien</small><br>
                    <strong><?= htmlspecialchars($detail_rujukan['nama_pasien']) ?></strong> (RM: <?= htmlspecialchars($detail_rujukan['no_rm']) ?>)
                </div>
                <div>
                    <small style="color: #64748B;">Puskesmas Asal</small><br>
                    <strong><?= htmlspecialchars($detail_rujukan['faskes_asal']) ?></strong> (dr. <?= htmlspecialchars($detail_rujukan['nama_dokter_pengirim']) ?>)
                </div>
                <div>
                    <small style="color: #64748B;">Poli Tujuan RS</small><br>
                    <span class="badge badge-info"><?= htmlspecialchars($detail_rujukan['poli_tujuan']) ?></span>
                </div>
                <div>
                    <small style="color: #64748B;">Status Informed Consent Pasien</small><br>
                    <span class="badge badge-success">✓ <?= htmlspecialchars($detail_rujukan['status_consent']) ?></span>
                </div>
            </div>

            <!-- COMPARISON HASH BOX -->
            <div style="background: #F8FAFC; border: 1px solid var(--abu-border); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem;">
                <h4 style="color: var(--hijau-tua); margin-top: 0;">🔐 Hasil Uji Integritas Kriptografis (SHA-256):</h4>
                
                <div style="margin-bottom: 0.75rem;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #475569;">1. Hash Terdaftar Saat Dokumen Dibuat di Puskesmas:</label>
                    <div class="hash-token-box"><?= htmlspecialchars($detail_rujukan['digital_hash']) ?></div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #475569;">2. Hash Hasil Kalkulasi Ulang Saat Diterima di Rumah Sakit:</label>
                    <div class="hash-token-box"><?= htmlspecialchars($calculated_hash) ?></div>
                </div>

                <?php if ($hash_match): ?>
                    <div class="alert alert-success" style="margin: 0;">
                        <span>✓</span>
                        <div>
                            <strong>HASIL UJI INTEGRITAS: COCOK (MATCH 100%)</strong><br>
                            Dokumen Resume Medis Elektronik ini terbukti <strong>ASLI</strong>, utuh, dan tidak mengalami modifikasi liar saat proses pertukaran data dari Puskesmas ke Rumah Sakit.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger" style="margin: 0;">
                        <span>⚠️</span>
                        <div>
                            <strong>HASIL UJI INTEGRITAS: TIDAK COCOK (TAMPERED/CORRUPT)</strong><br>
                            PERINGATAN! Hash tidak cocok. Terdapat indikasi manipulasi isi resume medis selama transmisi data.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TOMBOL TINDAKAN VERIFIKASI -->
            <?php if ($detail_rujukan['status_verifikasi'] !== 'Asli & Terverifikasi'): ?>
                <form method="POST" action="verifikasi_rujukan.php">
                    <input type="hidden" name="rujukan_id" value="<?= $detail_rujukan['id'] ?>">
                    <input type="hidden" name="proses_verifikasi" value="1">
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <a href="verifikasi_rujukan.php" class="btn btn-outline">Kembali</a>
                        <button type="submit" class="btn btn-success" style="padding: 0.65rem 1.25rem; font-weight: 600;">
                            🛡️ Sahkan & Verifikasi Keaslian Dokumen
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-success">
                    <span>✓</span>
                    <div>
                        Dokumen ini telah disahkan keasliannya oleh <strong><?= htmlspecialchars($detail_rujukan['petugas_verifikasi']) ?></strong> pada tanggal <strong><?= htmlspecialchars($detail_rujukan['tgl_verifikasi']) ?></strong>.
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <a href="rujukan_detail.php?id=<?= $detail_rujukan['id'] ?>" class="btn btn-primary">Lihat Resume Medis</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- DAFTAR SEMUA RUJUKAN YANG SIAP DIVERIFIKASI RS -->
<div class="card">
    <div class="card-header header-green">
        <h3 class="card-title">Daftar Rujukan Masuk Siap Verifikasi</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="simaya-table">
                <thead>
                    <tr>
                        <th>No. Rujukan</th>
                        <th>Nama Pasien</th>
                        <th>Puskesmas Pengirim</th>
                        <th>Poli Tujuan</th>
                        <th>Status Consent Pasien</th>
                        <th>Status Verifikasi</th>
                        <th>Aksi Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rujukan_list)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #94A3B8; padding: 2.5rem;">
                                Tidak ada rujukan yang memerlukan verifikasi saat ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rujukan_list as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['no_rujukan']) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($row['nama_pasien']) ?><br>
                                    <small style="color: #64748B;">No. RM: <?= htmlspecialchars($row['no_rm']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['faskes_asal']) ?></td>
                                <td><span class="badge badge-info"><?= htmlspecialchars($row['poli_tujuan']) ?></span></td>
                                <td><span class="badge badge-success">✓ Disetujui</span></td>
                                <td>
                                    <?php if ($row['status_verifikasi'] === 'Asli & Terverifikasi'): ?>
                                        <span class="badge badge-success">🛡️ Terverifikasi</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⏳ Perlu Uji Hash</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="verifikasi_rujukan.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                        🔍 Uji Keaslian
                                    </a>
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

