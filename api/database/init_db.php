<?php
/**
 * Inisialisasi Database SQLite SIMAYA
 * Membuat tabel dan mengisi data awal (seeding) otomatis
 */

function initialize_database($pdo) {
    // 1. Tabel Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        nama_lengkap TEXT NOT NULL,
        role TEXT NOT NULL, -- admin, petugas_rm, dokter_pengirim, dokter_penerima, pasien, security
        faskes_asal TEXT NOT NULL,
        spesialisasi TEXT DEFAULT '-',
        nik TEXT DEFAULT '-',
        no_hp TEXT DEFAULT '-',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Tabel Faskes
    $pdo->exec("CREATE TABLE IF NOT EXISTS faskes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_faskes TEXT UNIQUE NOT NULL,
        nama_faskes TEXT NOT NULL,
        tingkat TEXT NOT NULL, -- FKTP / FKRTL
        alamat TEXT NOT NULL,
        telepon TEXT NOT NULL
    )");

    // 3. Tabel Pasien
    $pdo->exec("CREATE TABLE IF NOT EXISTS pasien (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        no_rm TEXT UNIQUE NOT NULL,
        nik TEXT UNIQUE NOT NULL,
        nama_pasien TEXT NOT NULL,
        tgl_lahir DATE NOT NULL,
        jenis_kelamin TEXT NOT NULL, -- L / P
        alamat TEXT NOT NULL,
        no_hp TEXT NOT NULL,
        golongan_darah TEXT DEFAULT '-',
        alergi TEXT DEFAULT 'Tidak Ada',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // 4. Tabel Rujukan & Resume Medis Elektronik (RME)
    $pdo->exec("CREATE TABLE IF NOT EXISTS rujukan (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        no_rujukan TEXT UNIQUE NOT NULL,
        pasien_id INTEGER NOT NULL,
        faskes_asal TEXT NOT NULL,
        faskes_tujuan TEXT NOT NULL,
        poli_tujuan TEXT NOT NULL,
        dokter_pengirim_id INTEGER NOT NULL,
        dokter_penerima_id INTEGER NULL,
        tgl_rujukan DATETIME DEFAULT CURRENT_TIMESTAMP,
        alasan_rujukan TEXT NOT NULL,
        
        -- Bagian Resume Medis Elektronik (RME)
        keluhan_utama TEXT NOT NULL,
        riwayat_penyakit TEXT NOT NULL,
        tanda_vital TEXT NOT NULL, -- Tekanan Darah, Nadi, Suhu, RR
        diagnosis_primer TEXT NOT NULL, -- Kode ICD-10 & Nama
        diagnosis_sekunder TEXT DEFAULT '-',
        tindakan_terapi TEXT NOT NULL,
        obat_diberikan TEXT NOT NULL,
        hasil_penunjang TEXT DEFAULT '-',
        
        -- Keamanan, Persetujuan (Consent), & Verifikasi Keaslian
        digital_hash TEXT NOT NULL, -- Hash SHA-256 integritas data RME
        status_consent TEXT DEFAULT 'Menunggu Persetujuan', -- Menunggu Persetujuan, Disetujui, Ditolak
        tgl_consent DATETIME NULL,
        catatan_consent TEXT NULL,
        
        status_verifikasi TEXT DEFAULT 'Belum Diverifikasi', -- Belum Diverifikasi, Asli & Terverifikasi, Tidak Valid
        tgl_verifikasi DATETIME NULL,
        petugas_verifikasi TEXT NULL,
        
        status_rujukan TEXT DEFAULT 'Menunggu Persetujuan Pasien', -- Menunggu Persetujuan Pasien, Terkirim ke RS, Terverifikasi RS, Selesai Diperiksa
        catatan_dokter_rs TEXT NULL,
        
        FOREIGN KEY (pasien_id) REFERENCES pasien(id),
        FOREIGN KEY (dokter_pengirim_id) REFERENCES users(id),
        FOREIGN KEY (dokter_penerima_id) REFERENCES users(id)
    )");

    // 5. Tabel Audit Log
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        username TEXT NOT NULL,
        role TEXT NOT NULL,
        action TEXT NOT NULL, -- LOGIN, LOGOUT, BUAT_RUJUKAN, CONSENT_SETUJU, VERIFIKASI_RME, AKSES_RME, AKSES_DITOLAK, dll
        description TEXT NOT NULL,
        ip_address TEXT DEFAULT '127.0.0.1',
        status_keamanan TEXT DEFAULT 'NORMAL', -- NORMAL, PERINGATAN, ANOMALI
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // ==========================================
    // SEED DATA AWAL
    // ==========================================

    // Master Faskes
    $stmtFaskes = $pdo->prepare("INSERT INTO faskes (kode_faskes, nama_faskes, tingkat, alamat, telepon) VALUES (?, ?, ?, ?, ?)");
    $stmtFaskes->execute(['PUSK-001', 'Puskesmas Sehat Melati', 'FKTP', 'Jl. Melati Indah No. 12, Sleman', '0274-123456']);
    $stmtFaskes->execute(['RSUD-001', 'RSUD Sejahtera Husada', 'FKRTL', 'Jl. Kesehatan Raya No. 45, Yogyakarta', '0274-987654']);

    // Master Users (Semua role)
    // Password menggunakan hash MD5/SHA256 sederhana untuk keperluan edukasi dan kemudahan login demo
    $password_default = password_hash('password123', PASSWORD_BCRYPT);

    $users = [
        ['admin', $password_default, 'Naya Putri, S.Kom (Admin)', 'admin', 'Pusat Operasional SIMAYA', 'Administrator', '3404010101990001', '081234567890'],
        ['petugas.rm', $password_default, 'Rina Marlina, A.Md.RMIK', 'petugas_rm', 'Puskesmas Sehat Melati', 'Perekam Medis', '3404010202980002', '081234567891'],
        ['dr.andi', $password_default, 'dr. Andi Kurniawan', 'dokter_pengirim', 'Puskesmas Sehat Melati', 'Dokter Umum FKTP', '3404010303850003', '081234567892'],
        ['dr.budi', $password_default, 'dr. Budi Santoso, Sp.PD', 'dokter_penerima', 'RSUD Sejahtera Husada', 'Spesialis Penyakit Dalam', '3404010404780004', '081234567893'],
        ['dr.siti', $password_default, 'dr. Siti Rahmawati, Sp.A', 'dokter_penerima', 'RSUD Sejahtera Husada', 'Spesialis Anak', '3404010505800005', '081234567894'],
        ['pasien.bambang', $password_default, 'Bambang Wijaya', 'pasien', 'Puskesmas Sehat Melati', 'Pasien', '3404011212880006', '081398765432'],
        ['security', $password_default, 'Ahmad Fauzi, S.Tr.Kom (CISO)', 'security', 'Pusat Keamanan Siber RSUD', 'Keamanan Sistem', '3404010707900007', '081234567895']
    ];

    $stmtUser = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, faskes_asal, spesialisasi, nik, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $stmtUser->execute($u);
    }

    // Master Pasien
    $stmtPasien = $pdo->prepare("INSERT INTO pasien (user_id, no_rm, nik, nama_pasien, tgl_lahir, jenis_kelamin, alamat, no_hp, golongan_darah, alergi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtPasien->execute([
        6, // user_id Bambang
        'RM-PKM-2026-0042',
        '3404011212880006',
        'Bambang Wijaya',
        '1988-12-12',
        'L',
        'Kec. Depok, Sleman, D.I. Yogyakarta',
        '081398765432',
        'B+',
        'Amoxicillin'
    ]);
    $stmtPasien->execute([
        NULL,
        'RM-PKM-2026-0043',
        '3404015504950002',
        'Dewi Anggraini',
        '1995-04-15',
        'P',
        'Kec. Mlati, Sleman, D.I. Yogyakarta',
        '081567891234',
        'O+',
        'Tidak Ada'
    ]);

    // Contoh 1 Rujukan Awal yang sudah dibuat dan menunggu consent
    $data_medis_hash = hash('sha256', 'RM-PKM-2026-0042|E11.9|Diabetes Mellitus Tipe 2|Puskesmas Sehat Melati|RSUD Sejahtera Husada|2026-11-08');
    
    $stmtRujukan = $pdo->prepare("INSERT INTO rujukan (
        no_rujukan, pasien_id, faskes_asal, faskes_tujuan, poli_tujuan,
        dokter_pengirim_id, alasan_rujukan, keluhan_utama, riwayat_penyakit,
        tanda_vital, diagnosis_primer, diagnosis_sekunder, tindakan_terapi,
        obat_diberikan, hasil_penunjang, digital_hash, status_consent,
        status_verifikasi, status_rujukan
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmtRujukan->execute([
        'RUJ-2026-001',
        1, // Bambang
        'Puskesmas Sehat Melati',
        'RSUD Sejahtera Husada',
        'Spesialis Penyakit Dalam',
        3, // dr. Andi Kurniawan
        'Perlu penanganan spesialis lanjutan terkait gula darah tidak terkontrol dan neuropati perifer.',
        'Sering kesemutan pada kedua kaki, lemas, haus berlebih sejak 2 minggu terakhir.',
        'Riwayat Diabetes Mellitus Tipe 2 sejak 3 tahun lalu, riwayat konsumsi obat tidak teratur.',
        'TD: 135/85 mmHg, Nadi: 82x/mnt, Suhu: 36.7 C, RR: 20x/mnt, BB: 68 kg',
        'E11.9 - Type 2 diabetes mellitus without complications',
        'G63.2 - Diabetic polyneuropathy',
        'Edukasi diet DM, pemeriksaan GDS, rujukan ke dokter spesialis penyakit dalam.',
        'Metformin 500mg 3x1 tablet, Vitamin B Kompleks 1x1 tablet',
        'GDS: 285 mg/dL, HbA1c: 9.2%, Kolesterol Total: 220 mg/dL',
        $data_medis_hash,
        'Menunggu Persetujuan',
        'Belum Diverifikasi',
        'Menunggu Persetujuan Pasien'
    ]);

    // Log Awal
    $stmtLog = $pdo->prepare("INSERT INTO audit_logs (user_id, username, role, action, description, status_keamanan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtLog->execute([1, 'admin', 'admin', 'SYSTEM_INIT', 'Sistem SIMAYA dan Database berhasil diinisialisasi otomatis.', 'NORMAL']);
    $stmtLog->execute([3, 'dr.andi', 'dokter_pengirim', 'BUAT_RUJUKAN', 'Membuat rujukan RUJ-2026-001 untuk pasien Bambang Wijaya ke RSUD Sejahtera Husada (Poli Penyakit Dalam).', 'NORMAL']);
}

