<?php
/**
 * SIMAYA - Audit Trail & Log Keamanan Sistem
 * Memantau seluruh aktivitas pengguna secara realtime sesuai Kasus 5
 */
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireRole($pdo, ['security', 'admin']);

$user = $_SESSION['user'];
$page_title = 'Audit Log Keamanan Sistem';

// Filter
$filter_action = trim($_GET['action'] ?? '');
$filter_status = trim($_GET['status_keamanan'] ?? '');

$sql = "SELECT * FROM audit_logs WHERE 1=1";
$params = [];

if (!empty($filter_action)) {
    $sql .= " AND action = ?";
    $params[] = $filter_action;
}

if (!empty($filter_status)) {
    $sql .= " AND status_keamanan = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY id DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Statistik Keamanan
$total_logs = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$total_normal = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE status_keamanan = 'NORMAL'")->fetchColumn();
$total_peringatan = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE status_keamanan = 'PERINGATAN'")->fetchColumn();
$total_anomali = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE status_keamanan = 'ANOMALI'")->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="color: var(--hijau-tua); margin: 0;">🛡️ Audit Trail & Log Keamanan Sistem (CISO Log)</h2>
        <p style="color: var(--abu-text); margin: 0;">Pencatatan menyeluruh setiap aktivitas akses rekam medis,
            verifikasi, otorisasi, dan deteksi anomali</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Cetak Laporan Audit</button>
    </div>
</div>

<!-- STATISTIK KEAMANAN -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3><?= $total_logs ?></h3>
            <p>Total Aktivitas Tercatat</p>
        </div>
        <div class="stat-icon">📊</div>
    </div>
    <div class="stat-card" style="border-left-color: var(--hijau-sedang);">
        <div class="stat-info">
            <h3 style="color: var(--hijau-sedang);"><?= $total_normal ?></h3>
            <p>Aktivitas Normal Sah</p>
        </div>
        <div class="stat-icon">✓</div>
    </div>
    <div class="stat-card" style="border-left-color: var(--kuning);">
        <div class="stat-info">
            <h3 style="color: var(--kuning);"><?= $total_peringatan ?></h3>
            <p>Peringatan Keamanan</p>
        </div>
        <div class="stat-icon">⚠️</div>
    </div>
    <div class="stat-card" style="border-left-color: var(--merah);">
        <div class="stat-info">
            <h3 style="color: var(--merah);"><?= $total_anomali ?></h3>
            <p>Anomali / Pelanggaran</p>
        </div>
        <div class="stat-icon">🛑</div>
    </div>
</div>

<!-- FILTER BAR -->
<div class="card" style="margin-bottom: 1.25rem;">
    <div class="card-body" style="padding: 1rem;">
        <form method="GET" action="audit_log.php"
            style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.25rem;">Filter
                    Jenis Tindakan (Action):</label>
                <select name="action" class="form-control">
                    <option value="">-- Semua Tindakan --</option>
                    <option value="LOGIN" <?= $filter_action === 'LOGIN' ? 'selected' : '' ?>>LOGIN (Masuk Sistem)</option>
                    <option value="LOGOUT" <?= $filter_action === 'LOGOUT' ? 'selected' : '' ?>>LOGOUT (Keluar Sistem)
                    </option>
                    <option value="BUAT_RUJUKAN" <?= $filter_action === 'BUAT_RUJUKAN' ? 'selected' : '' ?>>BUAT_RUJUKAN
                        (Dokter FKTP)</option>
                    <option value="CONSENT_SETUJU" <?= $filter_action === 'CONSENT_SETUJU' ? 'selected' : '' ?>>
                        CONSENT_SETUJU (Persetujuan Pasien)</option>
                    <option value="CONSENT_DITOLAK" <?= $filter_action === 'CONSENT_DITOLAK' ? 'selected' : '' ?>>
                        CONSENT_DITOLAK</option>
                    <option value="VERIFIKASI_RME_SUKSES" <?= $filter_action === 'VERIFIKASI_RME_SUKSES' ? 'selected' : '' ?>>VERIFIKASI_RME_SUKSES (RS Sahkan Dokumen)</option>
                    <option value="AKSES_RME_SUKSES" <?= $filter_action === 'AKSES_RME_SUKSES' ? 'selected' : '' ?>>
                        AKSES_RME_SUKSES (Dokter RS Buka RME)</option>
                    <option value="AKSES_RME_DITOLAK" <?= $filter_action === 'AKSES_RME_DITOLAK' ? 'selected' : '' ?>>
                        AKSES_RME_DITOLAK (Poli Tidak Cocok)</option>
                    <option value="AKSES_DITOLAK" <?= $filter_action === 'AKSES_DITOLAK' ? 'selected' : '' ?>>AKSES_DITOLAK
                        (Role Tidak Sah)</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 180px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.25rem;">Status
                    Keamanan:</label>
                <select name="status_keamanan" class="form-control">
                    <option value="">-- Semua Status --</option>
                    <option value="NORMAL" <?= $filter_status === 'NORMAL' ? 'selected' : '' ?>>NORMAL (Aktivitas Sah)
                    </option>
                    <option value="PERINGATAN" <?= $filter_status === 'PERINGATAN' ? 'selected' : '' ?>>PERINGATAN
                        (Warning)</option>
                    <option value="ANOMALI" <?= $filter_status === 'ANOMALI' ? 'selected' : '' ?>>ANOMALI
                        (Pelanggaran/Integritas Rusak)</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="audit_log.php" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- TABEL AUDIT LOG -->
<div class="card">
    <div class="card-header header-green">
        <h3 class="card-title">Catatan Kronologis Audit Trail (Terbaru)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="simaya-table">
                <thead>
                    <tr>
                        <th>Waktu (Timestamp)</th>
                        <th>Pengguna / Akun</th>
                        <th>Role Pengguna</th>
                        <th>Tindakan (Action)</th>
                        <th>Deskripsi Aktivitas</th>
                        <th>IP Address</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #94A3B8; padding: 2.5rem;">
                                Tidak ada log aktivitas yang sesuai filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td>
                                    <small style="font-family: monospace; font-size: 0.8rem; color: #475569;">
                                        <?= htmlspecialchars($l['created_at']) ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($l['username']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= htmlspecialchars(getRoleName($l['role'])) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $action_class = 'badge-info';
                                    if (strpos($l['action'], 'DITOLAK') !== false || strpos($l['action'], 'GAGAL') !== false) {
                                        $action_class = 'badge-danger';
                                    } elseif (strpos($l['action'], 'SUKSES') !== false || strpos($l['action'], 'SETUJU') !== false) {
                                        $action_class = 'badge-success';
                                    }
                                    ?>
                                    <span class="badge <?= $action_class ?>"><?= htmlspecialchars($l['action']) ?></span>
                                </td>
                                <td style="max-width: 380px; font-size: 0.85rem;">
                                    <?= htmlspecialchars($l['description']) ?>
                                </td>
                                <td>
                                    <code
                                        style="font-size: 0.75rem; background: #F1F5F9; padding: 2px 4px; border-radius: 4px;">
                                                <?= htmlspecialchars($l['ip_address']) ?>
                                            </code>
                                </td>
                                <td>
                                    <?php if ($l['status_keamanan'] === 'NORMAL'): ?>
                                        <span class="badge badge-success">✓ NORMAL</span>
                                    <?php elseif ($l['status_keamanan'] === 'PERINGATAN'): ?>
                                        <span class="badge badge-warning">⚠️ PERINGATAN</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">🛑 ANOMALI</span>
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