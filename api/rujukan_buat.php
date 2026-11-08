<?php
/**
 * SIMAYA - Pembuatan Rujukan & Resume Medis Elektronik (RME) oleh Dokter FKTP
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireRole($pdo, ['dokter_pengirim', 'admin']);

$page_title = 'Buat Rujukan & RME';
$user = $_SESSION['user'];

$selected_pasien_id = intval($_GET['pasien_id'] ?? 0);

// Ambil daftar pasien untuk dropdown
$pasien_list = $pdo->query("SELECT id, no_rm, nama_pasien, nik FROM pasien ORDER BY nama_pasien ASC")->fetchAll();

// Ambil daftar faskes tujuan (FKRTL)
$rs_list = $pdo->query("SELECT * FROM faskes WHERE tingkat = 'FKRTL'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pasien_id = intval($_POST['pasien_id'] ?? 0);
    $faskes_asal = $user['faskes_asal'] ?? 'Puskesmas Sehat Melati';
    $faskes_tujuan = trim($_POST['faskes_tujuan'] ?? 'RSUD Sejahtera Husada');
    $poli_tujuan = trim($_POST['poli_tujuan'] ?? '');
    $alasan_rujukan = trim($_POST['alasan_rujukan'] ?? '');
    
    // RME Fields
    $keluhan_utama = trim($_POST['keluhan_utama'] ?? '');
    $riwayat_penyakit = trim($_POST['riwayat_penyakit'] ?? '');
    $tanda_vital = trim($_POST['tanda_vital'] ?? '');
    $diagnosis_primer = trim($_POST['diagnosis_primer'] ?? '');
    $diagnosis_sekunder = trim($_POST['diagnosis_sekunder'] ?? '-');
    $tindakan_terapi = trim($_POST['tindakan_terapi'] ?? '');
    $obat_diberikan = trim($_POST['obat_diberikan'] ?? '');
    $hasil_penunjang = trim($_POST['hasil_penunjang'] ?? '-');

    if (empty($pasien_id) || empty($poli_tujuan) || empty($diagnosis_primer) || empty($keluhan_utama)) {
        $error = "Mohon lengkapi kolom yang wajib diisi (*)";
    } else {
        // Generate Nomor Rujukan Unik
        $count = $pdo->query("SELECT COUNT(*) FROM rujukan")->fetchColumn() + 1;
        $no_rujukan = 'RUJ-' . date('Ym') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        // Hitung SHA-256 Digital Hash untuk Integritas Data Medis Elektronik
        $data_for_hash = [
            'no_rujukan' => $no_rujukan,
            'pasien_id' => $pasien_id,
            'diagnosis_primer' => $diagnosis_primer,
            'keluhan_utama' => $keluhan_utama,
            'tanda_vital' => $tanda_vital,
            'tindakan_terapi' => $tindakan_terapi,
            'obat_diberikan' => $obat_diberikan,
            'faskes_asal' => $faskes_asal,
            'faskes_tujuan' => $faskes_tujuan
        ];
        $digital_hash = generateMedicalHash($data_for_hash);

        try {
            $stmt = $pdo->prepare("INSERT INTO rujukan (
                no_rujukan, pasien_id, faskes_asal, faskes_tujuan, poli_tujuan,
                dokter_pengirim_id, alasan_rujukan, keluhan_utama, riwayat_penyakit,
                tanda_vital, diagnosis_primer, diagnosis_sekunder, tindakan_terapi,
                obat_diberikan, hasil_penunjang, digital_hash, status_consent,
                status_verifikasi, status_rujukan
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Persetujuan', 'Belum Diverifikasi', 'Menunggu Persetujuan Pasien')");

            $stmt->execute([
                $no_rujukan, $pasien_id, $faskes_asal, $faskes_tujuan, $poli_tujuan,
                $user['id'], $alasan_rujukan, $keluhan_utama, $riwayat_penyakit,
                $tanda_vital, $diagnosis_primer, $diagnosis_sekunder, $tindakan_terapi,
                $obat_diberikan, $hasil_penunjang, $digital_hash
            ]);

            $new_id = $pdo->lastInsertId();

            // Log Audit
            logAudit($pdo, 'BUAT_RUJUKAN', "Dokter {$user['nama_lengkap']} membuat rujukan {$no_rujukan} dan Resume Medis Elektronik. Hash SHA-256: {$digital_hash}. Menunggu persetujuan pasien.", 'NORMAL');

            $_SESSION['flash_success'] = "Rujukan dan Resume Medis Elektronik ({$no_rujukan}) berhasil dibuat! Menunggu persetujuan dari pasien.";
            header("Location: rujukan_detail.php?id=" . $new_id);
            exit;
        } catch (Exception $e) {
            $error = "Gagal membuat rujukan: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <h2 style="color: var(--hijau-tua); margin: 0;">➕ Pembuatan Rujukan & Resume Medis Elektronik (RME)</h2>
    <p style="color: var(--abu-text); margin: 0;">Pengisian data klinis pasien di Fasilitas Kesehatan Tingkat Pertama (FKTP) untuk dirujuk ke Rumah Sakit</p>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="rujukan_buat.php">
    <!-- BAGIAN 1: DATA PASIEN & TUJUAN RUJUKAN -->
    <div class="card">
        <div class="card-header header-green">
            <h3 class="card-title">1. Data Pasien & Faskes Rujukan Lanjutan</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Pilih Pasien Berobat*</label>
                    <select name="pasien_id" class="form-control" required>
                        <option value="">-- Pilih Pasien --</option>
                        <?php foreach ($pasien_list as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $selected_pasien_id === intval($p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama_pasien']) ?> (No. RM: <?= htmlspecialchars($p['no_rm']) ?> - NIK: <?= htmlspecialchars($p['nik']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rumah Sakit Tujuan (FKRTL)*</label>
                    <select name="faskes_tujuan" class="form-control" required>
                        <option value="RSUD Sejahtera Husada">RSUD Sejahtera Husada (Tipe B)</option>
                        <option value="RSUP Dr. Sardjito">RSUP Dr. Sardjito (Tipe A)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Poli / Spesialisasi Tujuan*</label>
                    <select name="poli_tujuan" class="form-control" required>
                        <option value="">-- Pilih Poli Spesialis --</option>
                        <option value="Spesialis Penyakit Dalam">Spesialis Penyakit Dalam (Internis)</option>
                        <option value="Spesialis Anak">Spesialis Anak (Pediatri)</option>
                        <option value="Spesialis Jantung & Pembuluh Darah">Spesialis Jantung & Pembuluh Darah</option>
                        <option value="Spesialis Bedah Umum">Spesialis Bedah Umum</option>
                        <option value="Spesialis Mata">Spesialis Mata</option>
                        <option value="Spesialis Saraf (Neurologi)">Spesialis Saraf (Neurologi)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Alasan Merujuk ke Faskes Lanjutan*</label>
                <textarea name="alasan_rujukan" class="form-control" placeholder="Contoh: Pasien memerlukan evaluasi dan terapi lanjutan dokter spesialis dikarenakan fasilitas di faskes primer terbatas." required>Pasien memerlukan evaluasi lanjutan dari dokter spesialis dikarenakan kondisi klinis tidak menunjukkan perbaikan setelah penanganan di faskes tingkat pertama.</textarea>
            </div>
        </div>
    </div>

    <!-- BAGIAN 2: RESUME MEDIS ELEKTRONIK (RME) -->
    <div class="card">
        <div class="card-header header-green">
            <h3 class="card-title">2. Formulir Resume Medis Elektronik (RME)</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Keluhan Utama*</label>
                    <textarea name="keluhan_utama" class="form-control" placeholder="Keluhan yang dirasakan pasien saat datang ke FKTP" required>Nyeri dada sebelah kiri tembus ke punggung disertai sesak napas dan keringat dingin sejak pagi hari.</textarea>
                </div>
                <div class="form-group">
                    <label>Riwayat Penyakit & Pengobatan Dahulu*</label>
                    <textarea name="riwayat_penyakit" class="form-control" placeholder="Riwayat hipertensi, diabetes, operasi sebelumnya, dll" required>Riwayat Hipertensi kronis 5 tahun tidak terkontrol. Riwayat merokok aktif 1 bungkus per hari.</textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Pemeriksaan Tanda-tanda Vital*</label>
                    <input type="text" name="tanda_vital" class="form-control" placeholder="TD, Nadi, Suhu, RR" value="TD: 150/95 mmHg, Nadi: 96x/mnt, Suhu: 36.8 C, RR: 24x/mnt, SpO2: 95%" required>
                </div>
                <div class="form-group">
                    <label>Diagnosis Primer (Kode ICD-10)*</label>
                    <input type="text" name="diagnosis_primer" class="form-control" placeholder="Contoh: I20.9 - Angina pectoris, unspecified" value="I20.9 - Angina pectoris, unspecified" required>
                </div>
                <div class="form-group">
                    <label>Diagnosis Sekunder (Komorbiditas)</label>
                    <input type="text" name="diagnosis_sekunder" class="form-control" placeholder="Contoh: I10 - Essential (primary) hypertension" value="I10 - Essential (primary) hypertension">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tindakan / Terapi di FKTP*</label>
                    <textarea name="tindakan_terapi" class="form-control" placeholder="Oksigenasi, tirah baring, EKG 12 lead, dll" required>Oksigen nasal kanul 3 Lpm, pemasangan infus NaCl 0.9% 12 tpm, pemeriksaan EKG 12 sandapan.</textarea>
                </div>
                <div class="form-group">
                    <label>Obat yang Diberikan di FKTP*</label>
                    <textarea name="obat_diberikan" class="form-control" placeholder="Nama obat, dosis, dan rute pemberian" required>ISDN 5 mg sublingual 1 tablet, Asetosal 160 mg dikunyah, Captopril 12.5 mg.</textarea>
                </div>
            </div>

            <div class="form-group">
                <label>Hasil Pemeriksaan Penunjang (Lab / Radiologi / EKG)</label>
                <textarea name="hasil_penunjang" class="form-control" placeholder="Hasil pemeriksaan laboratorium sederhana atau interpretasi EKG">EKG: ST-Depresi di lead V4-V6. GDS: 130 mg/dL, Kolesterol: 210 mg/dL.</textarea>
            </div>
        </div>
    </div>

    <!-- SECURITY NOTICE -->
    <div class="alert alert-info">
        <span>🛡️</span>
        <div>
            <strong>Keamanan & Integritas Data Medis:</strong> Saat Anda mengklik "Kirim Rujukan & Terbitkan RME", sistem SIMAYA akan secara otomatis menghitung <strong>Digital Hash SHA-256</strong> untuk menjamin keaslian data medis. Data rujukan selanjutnya memerlukan <strong>Informed Consent (Persetujuan Pasien)</strong> sebelum diverifikasi oleh Rumah Sakit.
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-bottom: 2rem;">
        <a href="rujukan_daftar.php" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.5rem; font-weight: 600;">
            🚀 Simpan & Terbitkan Rujukan Elektronik
        </button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>

