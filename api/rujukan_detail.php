<?php
/**
 * SIMAYA - Detail Rujukan & Lembar Resume Medis Elektronik (RME)
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$page_title = 'Detail Rujukan Medis';

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.*, p.nama_pasien, p.no_rm, p.nik, p.tgl_lahir, p.jenis_kelamin, p.alamat, p.no_hp, p.golongan_darah, p.alergi,
           u_kirim.nama_lengkap as nama_dokter_pengirim,
           u_terima.nama_lengkap as nama_dokter_penerima
    FROM rujukan r
    JOIN pasien p ON r.pasien_id = p.id
    JOIN users u_kirim ON r.dokter_pengirim_id = u_kirim.id
    LEFT JOIN users u_terima ON r.dokter_penerima_id = u_terima.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$rujukan = $stmt->fetch();

if (!$rujukan) {
    $_SESSION['flash_error'] = "Data rujukan tidak ditemukan.";
    header("Location: rujukan_daftar.php");
    exit;
}

// Simulasi Interoperabilitas: Payload JSON Pertukaran Data Faskes (Puskesmas -> RSUD)
$exchange_payload = [
    'standard' => 'SATUSEHAT / HL7 FHIR Indonesia Compatible',
    'resourceType' => 'ServiceRequest & ClinicalImpression',
    'identifier' => $rujukan['no_rujukan'],
    'status' => $rujukan['status_rujukan'],
    'subject' => [
        'reference' => 'Patient/' . $rujukan['no_rm'],
        'name' => $rujukan['nama_pasien'],
        'nik' => $rujukan['nik']
    ],
    'sender' => [
        'facility' => $rujukan['faskes_asal'],
        'practitioner' => $rujukan['nama_dokter_pengirim']
    ],
    'recipient' => [
        'facility' => $rujukan['faskes_tujuan'],
        'specialty' => $rujukan['poli_tujuan']
    ],
    'clinicalSummary' => [
        'chiefComplaint' => $rujukan['keluhan_utama'],
        'vitalSigns' => $rujukan['tanda_vital'],
        'primaryDiagnosis' => $rujukan['diagnosis_primer'],
        'secondaryDiagnosis' => $rujukan['diagnosis_sekunder'],
        'medication' => $rujukan['obat_diberikan']
    ],
    'security' => [
        'patientConsent' => $rujukan['status_consent'],
        'consentTimestamp' => $rujukan['tgl_consent'],
        'sha256Hash' => $rujukan['digital_hash'],
        'hospitalVerification' => $rujukan['status_verifikasi']
    ]
];

include __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="color: var(--hijau-tua); margin: 0;">📑 Berkas Rujukan: <?= htmlspecialchars($rujukan['no_rujukan']) ?></h2>
        <p style="color: var(--abu-text); margin: 0;">Integrasi Resume Medis Elektronik (FKTP ➔ FKRTL)</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button onclick="window.print()" class="btn btn-outline">🖨️ Cetak Berkas</button>
        <a href="rujukan_daftar.php" class="btn btn-primary">Kembali</a>
    </div>
</div>

<!-- TRACKER STATUS ALUR KASUS 5 -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; text-align: center;">
            <div style="padding: 0.75rem; border-radius: 6px; background: #DCFCE7; border: 1px solid #86EFAC;">
                <strong style="color: #166534; font-size: 0.85rem;">1. RME Diterbitkan</strong>
                <p style="margin: 0; font-size: 0.75rem; color: #14532D;"><?= date('d/m/Y H:i', strtotime($rujukan['tgl_rujukan'])) ?></p>
                <span class="badge badge-success">✓ Selesai</span>
            </div>

            <div style="padding: 0.75rem; border-radius: 6px; background: <?= $rujukan['status_consent'] === 'Disetujui' ? '#DCFCE7' : '#FEF3C7' ?>; border: 1px solid <?= $rujukan['status_consent'] === 'Disetujui' ? '#86EFAC' : '#FDE68A' ?>;">
                <strong style="color: #92400E; font-size: 0.85rem;">2. Informed Consent Pasien</strong>
                <p style="margin: 0; font-size: 0.75rem;"><?= htmlspecialchars($rujukan['status_consent']) ?></p>
                <?php if ($rujukan['status_consent'] === 'Disetujui'): ?>
                    <span class="badge badge-success">✓ Disetujui</span>
                <?php else: ?>
                    <span class="badge badge-warning">⏳ Menunggu</span>
                <?php endif; ?>
            </div>

            <div style="padding: 0.75rem; border-radius: 6px; background: <?= $rujukan['status_verifikasi'] === 'Asli & Terverifikasi' ? '#DCFCE7' : '#F1F5F9' ?>; border: 1px solid #CBD5E1;">
                <strong style="font-size: 0.85rem;">3. Verifikasi Keaslian RS</strong>
                <p style="margin: 0; font-size: 0.75rem;"><?= htmlspecialchars($rujukan['status_verifikasi']) ?></p>
                <?php if ($rujukan['status_verifikasi'] === 'Asli & Terverifikasi'): ?>
                    <span class="badge badge-success">🛡️ Terverifikasi</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Belum Verifikasi</span>
                <?php endif; ?>
            </div>

            <div style="padding: 0.75rem; border-radius: 6px; background: <?= !empty($rujukan['catatan_dokter_rs']) ? '#DCFCE7' : '#F1F5F9' ?>; border: 1px solid #CBD5E1;">
                <strong style="font-size: 0.85rem;">4. Pemeriksaan Dokter RS</strong>
                <p style="margin: 0; font-size: 0.75rem;"><?= !empty($rujukan['catatan_dokter_rs']) ? 'Sudah Diperiksa' : 'Belum Ditindaklanjuti' ?></p>
                <?php if (!empty($rujukan['catatan_dokter_rs'])): ?>
                    <span class="badge badge-success">✓ Selesai</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Menunggu Dokter</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- TOMBOL TINDAKAN CEPAT SESUAI ROLE -->
<div style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <?php if ($role === 'pasien' && $rujukan['status_consent'] === 'Menunggu Persetujuan'): ?>
        <a href="pasien_persetujuan.php?id=<?= $rujukan['id'] ?>" class="btn btn-primary" style="padding: 0.75rem 1.25rem;">
            ✍️ Berikan Persetujuan (Informed Consent)
        </a>
    <?php endif; ?>

    <?php if (($role === 'petugas_rm' || $role === 'security' || $role === 'admin') && $rujukan['status_consent'] === 'Disetujui' && $rujukan['status_verifikasi'] !== 'Asli & Terverifikasi'): ?>
        <a href="verifikasi_rujukan.php?id=<?= $rujukan['id'] ?>" class="btn btn-success" style="padding: 0.75rem 1.25rem;">
            🛡️ Uji & Verifikasi Keaslian Dokumen RS
        </a>
    <?php endif; ?>

    <?php if ($role === 'dokter_penerima'): ?>
        <a href="baca_rme.php?id=<?= $rujukan['id'] ?>" class="btn btn-primary" style="padding: 0.75rem 1.25rem;">
            🩺 Buka Resume Medis Elektronik (RME)
        </a>
    <?php endif; ?>
</div>

<!-- LEMBAR UTAMA RESUME MEDIS -->
<div class="rme-container card">
    <div class="rme-header">
        <div>
            <h3 style="color: var(--hijau-tua); margin: 0;">SURAT RUJUKAN & RESUME MEDIS ELEKTRONIK</h3>
            <p style="margin: 0; color: #64748B; font-size: 0.85rem;">Fasilitas Pelayanan Kesehatan Tingkat Pertama ke Lanjutan</p>
        </div>
        <div style="text-align: right;">
            <strong style="color: var(--hijau-tua); font-size: 1.1rem;"><?= htmlspecialchars($rujukan['no_rujukan']) ?></strong><br>
            <small style="color: #64748B;">Tanggal: <?= date('d F Y', strtotime($rujukan['tgl_rujukan'])) ?></small>
        </div>
    </div>

    <!-- IDENTITAS -->
    <div class="form-row" style="margin-bottom: 1.25rem; font-size: 0.9rem;">
        <div>
            <small style="color: #64748B;">Faskes Perujuk (FKTP):</small><br>
            <strong><?= htmlspecialchars($rujukan['faskes_asal']) ?></strong><br>
            <small>Dokter: dr. <?= htmlspecialchars($rujukan['nama_dokter_pengirim']) ?></small>
        </div>
        <div>
            <small style="color: #64748B;">Faskes Tujuan (FKRTL):</small><br>
            <strong><?= htmlspecialchars($rujukan['faskes_tujuan']) ?></strong><br>
            <small>Poli: <span class="badge badge-info"><?= htmlspecialchars($rujukan['poli_tujuan']) ?></span></small>
        </div>
        <div>
            <small style="color: #64748B;">Identitas Pasien:</small><br>
            <strong><?= htmlspecialchars($rujukan['nama_pasien']) ?></strong> (<?= $rujukan['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>)<br>
            <small>No. RM: <?= htmlspecialchars($rujukan['no_rm']) ?> | NIK: <?= htmlspecialchars($rujukan['nik']) ?></small>
        </div>
    </div>

    <!-- KLINIS -->
    <div style="border-top: 1px dashed var(--abu-border); padding-top: 1rem; margin-bottom: 1rem;">
        <span class="rme-section-title">RINGKASAN KLINIS (RESUME MEDIS)</span>
        
        <div style="margin-top: 0.75rem;">
            <strong>Keluhan Utama:</strong>
            <p style="margin: 0.25rem 0;"><?= nl2br(htmlspecialchars($rujukan['keluhan_utama'])) ?></p>
        </div>

        <div style="margin-top: 0.75rem;">
            <strong>Riwayat Penyakit:</strong>
            <p style="margin: 0.25rem 0;"><?= nl2br(htmlspecialchars($rujukan['riwayat_penyakit'])) ?></p>
        </div>

        <div style="margin-top: 0.75rem;">
            <strong>Pemeriksaan Tanda-tanda Vital:</strong>
            <p style="margin: 0.25rem 0; font-weight: 600; color: #1E293B;"><?= htmlspecialchars($rujukan['tanda_vital']) ?></p>
        </div>

        <div class="form-row" style="margin-top: 0.75rem;">
            <div>
                <strong>Diagnosis Primer (ICD-10):</strong><br>
                <span style="color: var(--hijau-tua); font-weight: 600;"><?= htmlspecialchars($rujukan['diagnosis_primer']) ?></span>
            </div>
            <div>
                <strong>Diagnosis Sekunder:</strong><br>
                <span><?= htmlspecialchars($rujukan['diagnosis_sekunder']) ?></span>
            </div>
        </div>

        <div style="margin-top: 0.75rem;">
            <strong>Tindakan / Terapi di FKTP:</strong>
            <p style="margin: 0.25rem 0;"><?= nl2br(htmlspecialchars($rujukan['tindakan_terapi'])) ?></p>
        </div>

        <div style="margin-top: 0.75rem;">
            <strong>Obat-obatan yang Diberikan:</strong>
            <p style="margin: 0.25rem 0; color: #166534; font-weight: 500;"><?= nl2br(htmlspecialchars($rujukan['obat_diberikan'])) ?></p>
        </div>

        <div style="margin-top: 0.75rem;">
            <strong>Hasil Penunjang:</strong>
            <p style="margin: 0.25rem 0;"><?= nl2br(htmlspecialchars($rujukan['hasil_penunjang'])) ?></p>
        </div>
    </div>

    <!-- CATATAN DOKTER RS -->
    <?php if (!empty($rujukan['catatan_dokter_rs'])): ?>
        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 6px; padding: 1rem; margin-top: 1rem;">
            <strong style="color: var(--hijau-tua);">🩺 Catatan & Tindak Lanjut Dokter RS (<?= htmlspecialchars($rujukan['nama_dokter_penerima'] ?? 'Dokter Pemeriksa') ?>):</strong>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;"><?= nl2br(htmlspecialchars($rujukan['catatan_dokter_rs'])) ?></p>
        </div>
    <?php endif; ?>

    <!-- TANDA TANGAN & TOKEN HASH -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 2rem; border-top: 2px solid var(--abu-border); padding-top: 1.5rem; flex-wrap: wrap; gap: 1.5rem;">
        <div style="max-width: 480px;">
            <small style="color: #64748B; font-weight: 600; text-transform: uppercase;">Kode Keamanan Kriptografis (Digital Hash SHA-256):</small>
            <div class="hash-token-box" style="margin-top: 4px; font-size: 0.75rem;">
                <?= htmlspecialchars($rujukan['digital_hash']) ?>
            </div>
            <small style="color: #64748B; display: block; margin-top: 4px;">
                Token ini mengamankan integritas berkas dari perubahan data tanpa izin saat pengiriman data antar faskes.
            </small>
        </div>

        <div style="text-align: center; min-width: 180px;">
            <div style="display: inline-block; padding: 0.5rem; border: 1px solid var(--abu-border); border-radius: 6px; background: white; margin-bottom: 0.5rem;">
                <div style="width: 70px; height: 70px; background: #1E293B; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; text-align: center; border-radius: 4px;">
                    QR CODE VALID
                </div>
            </div>
            <div style="font-size: 0.8rem; font-weight: 600;">dr. <?= htmlspecialchars($rujukan['nama_dokter_pengirim']) ?></div>
            <small style="color: #64748B;">Dokter Pemeriksa FKTP</small>
        </div>
    </div>
</div>

<!-- PAYLOAD PERTUKARAN DATA OTOMATIS (INTEROPERABILITAS JSON) -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">🌐 Payload Pertukaran Data Otomatis Antar Sistem Faskes (JSON Interoperability)</h3>
        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('jsonPayload').style.display = document.getElementById('jsonPayload').style.display === 'none' ? 'block' : 'none'">
            Lihat Format JSON
        </button>
    </div>
    <div class="card-body" id="jsonPayload" style="display: none;">
        <p style="font-size: 0.85rem; color: #64748B;">
            Format data di bawah ini adalah representasi paket data elektronik (HL7 FHIR / JSON) yang dikirimkan secara otomatis dari sistem Puskesmas ke sistem Rumah Sakit saat rujukan dibuat dan disetujui pasien:
        </p>
        <pre style="background: #1E293B; color: #38BDF8; padding: 1rem; border-radius: 6px; overflow-x: auto; font-size: 0.8rem;"><?= htmlspecialchars(json_encode($exchange_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

