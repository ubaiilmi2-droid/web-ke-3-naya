<?php
/**
 * SIMAYA - Manajemen Pengguna Sistem (Admin Panel)
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireRole($pdo, ['admin']);

$user = $_SESSION['user'];
$page_title = 'Manajemen Pengguna';

// Tambah User Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password_raw = trim($_POST['password'] ?? 'password123');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $role_input = trim($_POST['role'] ?? '');
    $faskes_asal = trim($_POST['faskes_asal'] ?? '');
    $spesialisasi = trim($_POST['spesialisasi'] ?? '-');
    $nik = trim($_POST['nik'] ?? '-');
    $no_hp = trim($_POST['no_hp'] ?? '-');

    if (empty($username) || empty($nama_lengkap) || empty($role_input)) {
        $error = "Semua kolom bertanda * wajib diisi!";
    } else {
        $password_hash = password_hash($password_raw, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, faskes_asal, spesialisasi, nik, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $nama_lengkap, $role_input, $faskes_asal, $spesialisasi, $nik, $no_hp]);

            logAudit($pdo, 'TAMBAH_USER', "Admin {$user['nama_lengkap']} membuat akun pengguna baru: {$username} ({$role_input}).", 'NORMAL');

            $_SESSION['flash_success'] = "Pengguna baru '{$username}' berhasil ditambahkan!";
            header("Location: manajemen_user.php");
            exit;
        } catch (Exception $e) {
            $error = "Gagal menambahkan pengguna: Username sudah digunakan.";
        }
    }
}

// Ambil daftar users
$users_list = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="color: var(--hijau-tua); margin: 0;">⚙️ Manajemen Pengguna SIMAYA</h2>
        <p style="color: var(--abu-text); margin: 0;">Kelola hak akses dan akun untuk 6 Role Pengguna Kasus 5</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('formUser').style.display = document.getElementById('formUser').style.display === 'none' ? 'block' : 'none'">
        ➕ Tambah Pengguna Baru
    </button>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- FORM TAMBAH USER -->
<div class="card" id="formUser" style="display: none; border-left: 4px solid var(--hijau-muda);">
    <div class="card-header header-green">
        <h3 class="card-title">Formulir Tambah Akun Pengguna</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="manajemen_user.php">
            <input type="hidden" name="tambah_user" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label>Username*</label>
                    <input type="text" name="username" class="form-control" placeholder="Contoh: dr.joko" required>
                </div>
                <div class="form-group">
                    <label>Password Awal*</label>
                    <input type="text" name="password" class="form-control" value="password123" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap & Gelar*</label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: dr. Joko Widodo, Sp.B" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Role / Peran Pengguna*</label>
                    <select name="role" class="form-control" required>
                        <option value="dokter_pengirim">Dokter Pengirim (FKTP / Puskesmas)</option>
                        <option value="dokter_penerima">Dokter Penerima (FKRTL / Rumah Sakit)</option>
                        <option value="petugas_rm">Petugas Rekam Medis (RM)</option>
                        <option value="pasien">Pasien Rujukan</option>
                        <option value="security">Petugas Keamanan Sistem (Auditor)</option>
                        <option value="admin">Admin Sistem</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Spesialisasi / Poli (Wajib untuk Dokter)*</label>
                    <input type="text" name="spesialisasi" class="form-control" placeholder="Contoh: Spesialis Penyakit Dalam, Spesialis Anak, atau -">
                </div>
                <div class="form-group">
                    <label>Faskes Asal*</label>
                    <input type="text" name="faskes_asal" class="form-control" placeholder="Contoh: Puskesmas Sehat Melati atau RSUD Sejahtera Husada" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nomor Induk Kependudukan (NIK)</label>
                    <input type="text" name="nik" class="form-control" placeholder="16 Digit NIK KTP">
                </div>
                <div class="form-group">
                    <label>Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('formUser').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- TABEL DAFTAR PENGGUNA -->
<div class="card">
    <div class="card-header header-green">
        <h3 class="card-title">Daftar Akun Pengguna Terdaftar (6 Role)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="simaya-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role Akses</th>
                        <th>Fasilitas Kesehatan</th>
                        <th>Spesialisasi / Jabatan</th>
                        <th>Kontak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_list as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars(getRoleName($u['role'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars($u['faskes_asal']) ?></td>
                            <td><?= htmlspecialchars($u['spesialisasi']) ?></td>
                            <td><small><?= htmlspecialchars($u['no_hp']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

