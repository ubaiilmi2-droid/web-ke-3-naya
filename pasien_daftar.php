<?php
/**
 * SIMAYA - Data Pasien & Pendaftaran Pasien Baru
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireRole($pdo, ['admin', 'petugas_rm', 'dokter_pengirim', 'dokter_penerima']);

$page_title = 'Data Pasien';
$user = $_SESSION['user'];

// Proses Simpan Pasien Baru (Khusus Petugas RM & Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pasien'])) {
    if (!in_array($user['role'], ['admin', 'petugas_rm'])) {
        $_SESSION['flash_error'] = "Hanya Petugas RM atau Admin yang berhak mendaftarkan pasien baru.";
        header("Location: pasien_daftar.php");
        exit;
    }

    $no_rm = trim($_POST['no_rm'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $tgl_lahir = trim($_POST['tgl_lahir'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'L');
    $alamat = trim($_POST['alamat'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $golongan_darah = trim($_POST['golongan_darah'] ?? '-');
    $alergi = trim($_POST['alergi'] ?? 'Tidak Ada');

    try {
        $stmt = $pdo->prepare("INSERT INTO pasien (no_rm, nik, nama_pasien, tgl_lahir, jenis_kelamin, alamat, no_hp, golongan_darah, alergi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$no_rm, $nik, $nama_pasien, $tgl_lahir, $jenis_kelamin, $alamat, $no_hp, $golongan_darah, $alergi]);
        
        logAudit($pdo, 'TAMBAH_PASIEN', "Petugas RM menambahkan pasien baru: {$nama_pasien} (No. RM: {$no_rm}).", 'NORMAL');
        $_SESSION['flash_success'] = "Data pasien berhasil ditambahkan!";
        header("Location: pasien_daftar.php");
        exit;
    } catch (Exception $e) {
        $error = "Gagal menyimpan pasien: NIK atau No. RM mungkin sudah terdaftar.";
    }
}

// Ambil daftar pasien
$pasien_list = $pdo->query("SELECT * FROM pasien ORDER BY id DESC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
    <div>
        <h2 style="color: var(--hijau-tua); margin: 0;">👥 Data Pasien Faskes</h2>
        <p style="color: var(--abu-text); margin: 0;">Daftar pasien yang terdaftar di Fasilitas Kesehatan Tingkat Pertama (FKTP)</p>
    </div>
    <?php if (in_array($user['role'], ['admin', 'petugas_rm'])): ?>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('formTambahPasien').style.display = document.getElementById('formTambahPasien').style.display === 'none' ? 'block' : 'none';">
            ➕ Tambah Pasien Baru
        </button>
    <?php endif; ?>
</div>

<!-- FORM TAMBAH PASIEN (MODULAR ACCORDION) -->
<?php if (in_array($user['role'], ['admin', 'petugas_rm'])): ?>
<div class="card" id="formTambahPasien" style="display: none; border-left: 4px solid var(--hijau-muda);">
    <div class="card-header header-green">
        <h3 class="card-title">📝 Formulir Pendaftaran Pasien Baru</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="pasien_daftar.php">
            <input type="hidden" name="tambah_pasien" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label>Nomor Rekam Medis (No. RM)*</label>
                    <input type="text" name="no_rm" class="form-control" placeholder="Contoh: RM-PKM-2026-0045" value="RM-PKM-<?= date('Y') ?>-<?= str_pad(rand(50, 999), 4, '0', STR_PAD_LEFT) ?>" required>
                </div>
                <div class="form-group">
                    <label>Nomor Induk Kependudukan (NIK)*</label>
                    <input type="text" name="nik" class="form-control" placeholder="16 Digit NIK KTP" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap Pasien*</label>
                    <input type="text" name="nama_pasien" class="form-control" placeholder="Nama Pasien Sesuai KTP" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Lahir*</label>
                    <input type="date" name="tgl_lahir" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin*</label>
                    <select name="jenis_kelamin" class="form-control" required>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>No. HP / WhatsApp*</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Golongan Darah</label>
                    <select name="golongan_darah" class="form-control">
                        <option value="-">- Pilih -</option>
                        <option value="A+">A+</option>
                        <option value="B+">B+</option>
                        <option value="AB+">AB+</option>
                        <option value="O+">O+</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Riwayat Alergi Obat</label>
                    <input type="text" name="alergi" class="form-control" placeholder="Misal: Penisilin, Amoxicillin, atau Tidak Ada">
                </div>
                <div class="form-group">
                    <label>Alamat Domisili Lengkap*</label>
                    <input type="text" name="alamat" class="form-control" placeholder="Alamat Tempat Tinggal" required>
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('formTambahPasien').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pasien</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- TABEL PASIEN -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Pasien Terdaftar</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="simaya-table">
                <thead>
                    <tr>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>NIK</th>
                        <th>Tgl Lahir / Usia</th>
                        <th>L/P</th>
                        <th>Kontak & Alamat</th>
                        <th>Alergi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pasien_list)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94A3B8; padding: 2rem;">Belum ada data pasien.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pasien_list as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['no_rm']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['nama_pasien']) ?></strong>
                                    <?php if ($p['golongan_darah'] !== '-'): ?>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($p['golongan_darah']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['nik']) ?></td>
                                <td>
                                    <?= htmlspecialchars($p['tgl_lahir']) ?><br>
                                    <small style="color: #64748B;">
                                        (<?= date_diff(date_create($p['tgl_lahir']), date_create('today'))->y ?> tahun)
                                    </small>
                                </td>
                                <td><?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td>
                                    <?= htmlspecialchars($p['no_hp']) ?><br>
                                    <small style="color: #64748B;"><?= htmlspecialchars($p['alamat']) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $p['alergi'] !== 'Tidak Ada' ? 'badge-danger' : 'badge-success' ?>">
                                        <?= htmlspecialchars($p['alergi']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'dokter_pengirim' || $user['role'] === 'admin'): ?>
                                        <a href="rujukan_buat.php?pasien_id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">
                                            🩺 Rujuk Pasien
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #94A3B8; font-size: 0.8rem;">-</span>
                                    <?php endif; ?>
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

