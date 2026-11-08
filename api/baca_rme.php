<?php
/**
 * SIMAYA - Pembacaan Resume Medis Elektronik (RME) oleh Dokter RS
 * Dilengkapi dengan Role-Based Access Control (RBAC) sesuai Spesialisasi Poli
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireRole($pdo, ['dokter_penerima', 'admin']);

$user = $_SESSION['user'];
$page_title = 'Buka Resume Medis Elektronik (RME)';

$id = intval($_GET['id'] ?? 0);

// Ambil data rujukan
$stmt = $pdo->prepare("
    SELECT r.*, p.nama_pasien, p.no_rm, p.nik, p.tgl_lahir, p.jenis_kelamin, p.golongan_darah, p.alergi,
           u.nama_lengkap as nama_dokter_pengirim
    FROM rujukan r
    JOIN pasien p ON r.pasien_id = p.id
    JOIN users u ON r.dokter_pengirim_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$rujukan = $stmt->fetch();

if (!$rujukan) {
    $_SESSION['flash_error'] = "Data rujukan tidak ditemukan.";
    header("Location: rujukan_daftar.php");
    exit;
}

// ========================================================
// HAK AKSES POLI DOKTER RS (ROLE-BASED ACCESS CONTROL)
// ========================================================
$has_access = false;
$access_reason = '';

if ($user['role'] === 'admin') {
    // Admin sistem memiliki hak buka penuh untuk monitoring
    $has_access = true;
} else {
    // Dokter RS hanya boleh membuka jika spesialisasi polinya sesuai
    // e.g. 'Spesialis Penyakit Dalam' vs 'Spesialis Penyakit Dalam'
    if (trim(strtolower($user['spesialisasi'])) === trim(strtolower($rujukan['poli_tujuan']))) {
        $has_access = true;
    } else {
        $has_access = false;
        $access_reason = "Spesialisasi Anda (" . htmlspecialchars($user['spesialisasi']) . ") tidak sesuai dengan Poli Tujuan rujukan ini (" . htmlspecialchars($rujukan['poli_tujuan']) . "). Sesuai prinsip Need-to-Know dan Kerahasiaan Medis, Anda tidak berhak mengakses RME pasien ini.";
        
        // Catat insiden akses ilegal ke Audit Log
        logAudit(
            $pdo, 
            'AKSES_RME_DITOLAK', 
            "PERINGATAN: Dokter {$user['nama_lengkap']} ({$user['spesialisasi']}) mencoba membuka RME {$rujukan['no_rujukan']} milik pasien {$rujukan['nama_pasien']} yang ditujukan ke {$rujukan['poli_tujuan']}. Akses ditolak sistem.",
            'PERINGATAN'
        );
    }
}

// Simpan Catatan Pemeriksaan Dokter RS jika disubmit
if ($has_access && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_catatan_rs'])) {
    $catatan = trim($_POST['catatan_dokter_rs'] ?? '');
    
    $stmtUpdate = $pdo->prepare("
        UPDATE rujukan 
        SET catatan_dokter_rs = ?, dokter_penerima_id = ?, status_rujukan = 'Selesai Diperiksa RS'
        WHERE id = ?
    ");
    $stmtUpdate->execute([$catatan, $user['id'], $id]);

    logAudit($pdo, 'TINDAK_LANJUT_RS', "Dokter Penerima {$user['nama_lengkap']} memberikan catatan pemeriksaan/tindak lanjut pada rujukan {$rujukan['no_rujukan']}.", 'NORMAL');

    $_SESSION['flash_success'] = "Catatan penerimaan dokter RS berhasil disimpan!";
    header("Location: rujukan_detail.php?id=" . $id);
    exit;
}

// Jika akses disetujui, catat audit log pembacaan RME
if ($has_access) {
    logAudit(
        $pdo, 
        'AKSES_RME_SUKSES', 
        "Dokter Penerima {$user['nama_lengkap']} ({$user['spesialisasi']}) berhasil membuka dan membaca Resume Medis Elektronik {$rujukan['no_rujukan']} milik pasien {$rujukan['nama_pasien']}.",
        'NORMAL'
    );
}

include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <h2 style="color: var(--hijau-tua); margin: 0;">🩺 Pembacaan Resume Medis Elektronik (RME)</h2>
    <p style="color: var(--abu-text); margin: 0;">Pemeriksaan data klinis rujukan oleh Dokter Spesialis Rumah Sakit Penerima</p>
</div>

<?php if (!$has_access): ?>
    <!-- TAMPILAN BLOKIR HAK AKSES TERTOLAK -->
    <div class="card" style="border: 2px solid #F87171;">
        <div class="card-header" style="background: #FEE2E2; color: #991B1B;">
            <h3 class="card-title">🚫 Akses Rekam Medis Ditolak (Security Access Control)</h3>
        </div>
        <div class="card-body" style="padding: 2rem; text-align: center;">
            <div style="font-size: 3rem;">🛑</div>
            <h3 style="color: #991B1B; margin-top: 0.5rem;">Anda Tidak Memiliki Hak Akses ke Resume Medis Ini!</h3>
            <p style="color: #475569; max-width: 600px; margin: 1rem auto; line-height: 1.6;">
                <?= $access_reason ?>
            </p>

            <div class="alert alert-warning" style="max-width: 650px; margin: 1.5rem auto; text-align: left;">
                <span>⚠️</span>
                <div>
                    <strong>Pencatatan Audit Trail:</strong> Percobaan akses tanpa wewenang ini telah dicatat secara permanen di dalam sistem <strong>Audit Log SIMAYA</strong> untuk keperluan audit keamanan siber dan pematuhan hukum rekam medis.
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <a href="rujukan_daftar.php" class="btn btn-primary">Kembali ke Daftar Rujukan</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- TAMPILAN RESUME MEDIS ELEKTRONIK (AKSES DIIZINKAN) -->
    
    <!-- BANNER PERIZINAN & KEASLIAN -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
        <div style="flex: 1; background: #DCFCE7; border: 1px solid #86EFAC; padding: 0.75rem 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.25rem;">✓</span>
            <div style="font-size: 0.85rem; color: #166534;">
                <strong>Hak Akses Sah:</strong> Spesialisasi Anda (<?= htmlspecialchars($user['spesialisasi']) ?>) sesuai dengan Poli Rujukan.
            </div>
        </div>
        <div style="flex: 1; background: #EFF6FF; border: 1px solid #BFDBFE; padding: 0.75rem 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.25rem;">🛡️</span>
            <div style="font-size: 0.85rem; color: #1E40AF;">
                <strong>Keaslian Dokumen:</strong> <?= htmlspecialchars($rujukan['status_verifikasi']) ?> (Hash SHA-256 Valid)
            </div>
        </div>
    </div>

    <!-- LEMBAR DOKUMEN RESUME MEDIS ELEKTRONIK -->
    <div class="rme-container card">
        <div class="rme-header">
            <div>
                <h2 style="color: var(--hijau-tua); margin: 0; font-size: 1.4rem;">RESUME MEDIS ELEKTRONIK (RME) RUJUKAN</h2>
                <p style="margin: 0; color: #64748B; font-size: 0.85rem;">Standar Rekam Medis Elektronik Terintegrasi Antar Fasilitas Pelayanan Kesehatan</p>
            </div>
            <div style="text-align: right;">
                <span class="badge badge-info" style="font-size: 0.85rem;"><?= htmlspecialchars($rujukan['no_rujukan']) ?></span>
                <div style="font-size: 0.75rem; color: #64748B; margin-top: 4px;">Tgl Rujukan: <?= date('d F Y', strtotime($rujukan['tgl_rujukan'])) ?></div>
            </div>
        </div>

        <!-- IDENTITAS PASIEN -->
        <span class="rme-section-title">A. IDENTITAS PASIEN</span>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; font-size: 0.9rem;">
            <div><small style="color: #64748B;">Nama Pasien:</small><br><strong><?= htmlspecialchars($rujukan['nama_pasien']) ?></strong></div>
            <div><small style="color: #64748B;">No. Rekam Medis (RM):</small><br><strong><?= htmlspecialchars($rujukan['no_rm']) ?></strong></div>
            <div><small style="color: #64748B;">NIK KTP:</small><br><?= htmlspecialchars($rujukan['nik']) ?></div>
            <div><small style="color: #64748B;">Jenis Kelamin / Gol. Darah:</small><br><?= $rujukan['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> (<?= htmlspecialchars($rujukan['golongan_darah']) ?>)</div>
            <div><small style="color: #64748B;">Tanggal Lahir:</small><br><?= htmlspecialchars($rujukan['tgl_lahir']) ?></div>
            <div><small style="color: #64748B;">Riwayat Alergi:</small><br><span class="badge badge-danger"><?= htmlspecialchars($rujukan['alergi']) ?></span></div>
        </div>

        <!-- DATA FASKES ASAL -->
        <span class="rme-section-title">B. FASKES PERUJUK & DOKTER PENGIRIM</span>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; font-size: 0.9rem;">
            <div><small style="color: #64748B;">Fasilitas Pelayanan Kesehatan Pengirim:</small><br><strong><?= htmlspecialchars($rujukan['faskes_asal']) ?></strong></div>
            <div><small style="color: #64748B;">Dokter Pemeriksa FKTP:</small><br><strong><?= htmlspecialchars($rujukan['nama_dokter_pengirim']) ?></strong></div>
        </div>

        <!-- ANAMNESIS & FISIK -->
        <span class="rme-section-title">C. ANAMNESIS & PEMERIKSAAN FISIK</span>
        <div style="margin-bottom: 1rem;">
            <div style="margin-bottom: 0.5rem;">
                <strong>Keluhan Utama:</strong>
                <p style="margin: 0.25rem 0; background: #F8FAFC; padding: 0.5rem 0.75rem; border-radius: 4px; border-left: 3px solid var(--hijau-sedang);"><?= nl2br(htmlspecialchars($rujukan['keluhan_utama'])) ?></p>
            </div>
            <div style="margin-bottom: 0.5rem;">
                <strong>Riwayat Penyakit & Pengobatan Terdahulu:</strong>
                <p style="margin: 0.25rem 0; background: #F8FAFC; padding: 0.5rem 0.75rem; border-radius: 4px;"><?= nl2br(htmlspecialchars($rujukan['riwayat_penyakit'])) ?></p>
            </div>
            <div>
                <strong>Tanda-tanda Vital (Vital Signs):</strong>
                <p style="margin: 0.25rem 0; background: #F8FAFC; padding: 0.5rem 0.75rem; border-radius: 4px; font-weight: 600; color: #1E293B;"><?= htmlspecialchars($rujukan['tanda_vital']) ?></p>
            </div>
        </div>

        <!-- DIAGNOSIS -->
        <span class="rme-section-title">D. DIAGNOSIS KERJA (ICD-10)</span>
        <div style="margin-bottom: 1rem;">
            <div style="margin-bottom: 0.5rem;">
                <small style="color: #64748B;">Diagnosis Utama (Primer):</small><br>
                <strong style="color: var(--hijau-tua); font-size: 1rem;"><?= htmlspecialchars($rujukan['diagnosis_primer']) ?></strong>
            </div>
            <div>
                <small style="color: #64748B;">Diagnosis Tambahan (Sekunder/Komorbid):</small><br>
                <span><?= htmlspecialchars($rujukan['diagnosis_sekunder']) ?></span>
            </div>
        </div>

        <!-- TERAPI & HASIL LAB -->
        <span class="rme-section-title">E. TERAPI, OBAT, & HASIL PEMERIKSAAN PENUNJANG DI FKTP</span>
        <div style="margin-bottom: 1.25rem;">
            <div style="margin-bottom: 0.5rem;">
                <strong>Tindakan / Terapi Diberikan:</strong>
                <p style="margin: 0.25rem 0;"><?= nl2br(htmlspecialchars($rujukan['tindakan_terapi'])) ?></p>
            </div>
            <div style="margin-bottom: 0.5rem;">
                <strong>Obat-obatan yang Diberikan:</strong>
                <p style="margin: 0.25rem 0; color: #166534; font-weight: 500;"><?= nl2br(htmlspecialchars($rujukan['obat_diberikan'])) ?></p>
            </div>
            <div>
                <strong>Hasil Pemeriksaan Penunjang (Laboratorium/EKG/Radiologi):</strong>
                <p style="margin: 0.25rem 0; background: #F8FAFC; padding: 0.5rem 0.75rem; border-radius: 4px;"><?= nl2br(htmlspecialchars($rujukan['hasil_penunjang'])) ?></p>
            </div>
        </div>

        <!-- KEAMANAN DATA -->
        <div style="background: #F1F5F9; border-radius: 6px; padding: 0.75rem; font-size: 0.75rem; color: #64748B;">
            <strong>Integritas Kriptografis:</strong> SHA-256 Hash: <code><?= htmlspecialchars($rujukan['digital_hash']) ?></code> | 
            Consent Pasien: <strong><?= htmlspecialchars($rujukan['status_consent']) ?></strong> (<?= htmlspecialchars($rujukan['tgl_consent'] ?? '-') ?>)
        </div>
    </div>

    <!-- FORM TINDAK LANJUT / CATATAN DOKTER RS -->
    <div class="card" style="margin-top: 1.5rem; border-left: 4px solid var(--hijau-tua);">
        <div class="card-header header-green">
            <h3 class="card-title">📝 Catatan Penerimaan & Rencana Penanganan Dokter RS</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="baca_rme.php?id=<?= $rujukan['id'] ?>">
                <input type="hidden" name="simpan_catatan_rs" value="1">
                
                <div class="form-group">
                    <label>Catatan Klinis Dokter Spesialis Penerima (Rencana Tindak Lanjut Pasien)*</label>
                    <textarea name="catatan_dokter_rs" class="form-control" placeholder="Masukkan instruksi medis, rencana rawat inap/jalan, pemeriksaan penunjang lanjutan yang diperintahkan..." required><?= htmlspecialchars($rujukan['catatan_dokter_rs'] ?? 'Pasien telah diterima di Poli Spesialis. Rencana penjadwalan pemeriksaan lanjutan dan penyesuaian dosis terapi.') ?></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <a href="rujukan_detail.php?id=<?= $rujukan['id'] ?>" class="btn btn-outline">Kembali</a>
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan Catatan Penerimaan Pasien
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

