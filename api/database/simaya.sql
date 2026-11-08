-- ==============================================================
-- SIMAYA - SISTEM INFORMASI RUJUKAN MEDIS ELEKTRONIK TERINTEGRASI
-- Kasus 5: Pertukaran Data Puskesmas ke Rumah Sakit, RME,
--          Informed Consent Pasien, Verifikasi Keaslian, RBAC Dokter,
--          dan Audit Trail Lengkap.
-- Disusun untuk Mahasiswi Semester 7 Rekam Medis / Sistem Informasi
-- ==============================================================

CREATE DATABASE IF NOT EXISTS simaya_db;
USE simaya_db;

-- 1. TABEL PENGGUNA (USERS)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas_rm', 'dokter_pengirim', 'dokter_penerima', 'pasien', 'security') NOT NULL,
    faskes_asal VARCHAR(100) NOT NULL,
    spesialisasi VARCHAR(100) DEFAULT '-',
    nik VARCHAR(20) DEFAULT '-',
    no_hp VARCHAR(20) DEFAULT '-',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL FASKES
DROP TABLE IF EXISTS faskes;
CREATE TABLE faskes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_faskes VARCHAR(20) NOT NULL UNIQUE,
    nama_faskes VARCHAR(100) NOT NULL,
    tingkat ENUM('FKTP', 'FKRTL') NOT NULL,
    alamat TEXT NOT NULL,
    telepon VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL PASIEN
DROP TABLE IF EXISTS pasien;
CREATE TABLE pasien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    no_rm VARCHAR(30) NOT NULL UNIQUE,
    nik VARCHAR(20) NOT NULL UNIQUE,
    nama_pasien VARCHAR(100) NOT NULL,
    tgl_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    alamat TEXT NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    golongan_darah VARCHAR(5) DEFAULT '-',
    alergi TEXT DEFAULT 'Tidak Ada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABEL RUJUKAN & RESUME MEDIS ELEKTRONIK (RME)
DROP TABLE IF EXISTS rujukan;
CREATE TABLE rujukan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_rujukan VARCHAR(30) NOT NULL UNIQUE,
    pasien_id INT NOT NULL,
    faskes_asal VARCHAR(100) NOT NULL,
    faskes_tujuan VARCHAR(100) NOT NULL,
    poli_tujuan VARCHAR(100) NOT NULL,
    dokter_pengirim_id INT NOT NULL,
    dokter_penerima_id INT NULL,
    tgl_rujukan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    alasan_rujukan TEXT NOT NULL,
    
    -- Resume Medis Elektronik
    keluhan_utama TEXT NOT NULL,
    riwayat_penyakit TEXT NOT NULL,
    tanda_vital TEXT NOT NULL,
    diagnosis_primer VARCHAR(255) NOT NULL,
    diagnosis_sekunder VARCHAR(255) DEFAULT '-',
    tindakan_terapi TEXT NOT NULL,
    obat_diberikan TEXT NOT NULL,
    hasil_penunjang TEXT DEFAULT '-',
    
    -- Keamanan & Verifikasi
    digital_hash VARCHAR(64) NOT NULL,
    status_consent ENUM('Menunggu Persetujuan', 'Disetujui', 'Ditolak') DEFAULT 'Menunggu Persetujuan',
    tgl_consent DATETIME NULL,
    catatan_consent TEXT NULL,
    
    status_verifikasi ENUM('Belum Diverifikasi', 'Asli & Terverifikasi', 'Tidak Valid') DEFAULT 'Belum Diverifikasi',
    tgl_verifikasi DATETIME NULL,
    petugas_verifikasi VARCHAR(100) NULL,
    
    status_rujukan ENUM('Menunggu Persetujuan Pasien', 'Terkirim ke RS', 'Terverifikasi RS', 'Selesai Diperiksa') DEFAULT 'Menunggu Persetujuan Pasien',
    catatan_dokter_rs TEXT NULL,
    
    INDEX (pasien_id),
    INDEX (dokter_pengirim_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABEL AUDIT LOG
DROP TABLE IF EXISTS audit_logs;
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NOT NULL,
    role VARCHAR(30) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT '127.0.0.1',
    status_keamanan ENUM('NORMAL', 'PERINGATAN', 'ANOMALI') DEFAULT 'NORMAL',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA
INSERT INTO faskes (kode_faskes, nama_faskes, tingkat, alamat, telepon) VALUES
('PUSK-001', 'Puskesmas Sehat Melati', 'FKTP', 'Jl. Melati Indah No. 12, Sleman', '0274-123456'),
('RSUD-001', 'RSUD Sejahtera Husada', 'FKRTL', 'Jl. Kesehatan Raya No. 45, Yogyakarta', '0274-987654');

-- Password default: password123 ($2y$10$e846UqG0FvC5sO2Iu3.xjeGkW6W3P352k9kXwA99jR1LqFh0sJgeu)
INSERT INTO users (username, password, nama_lengkap, role, faskes_asal, spesialisasi, nik, no_hp) VALUES
('admin', '$2y$10$4B9bW6LzN7U6jD4xR5P9ue6kP/3YgqWz.88l1u3o3mB0P71e0n3Y.', 'Naya Putri, S.Kom (Admin)', 'admin', 'Pusat Operasional SIMAYA', 'Administrator', '3404010101990001', '081234567890'),
('petugas.rm', '$2y$10$4B9bW6LzN7U6jD4xR5P9ue6kP/3YgqWz.88l1u3o3mB0P71e0n3Y.', 'Rina Marlina, A.Md.RMIK', 'petugas_rm', 'Puskesmas Sehat Melati', 'Perekam Medis', '3404010202980002', '081234567891'),
('dr.andi', '$2y$10$4B9bW6LzN7U6jD4xR5P9ue6kP/3YgqWz.88l1u3o3mB0P71e0n3Y.', 'dr. Andi Kurniawan', 'dokter_pengirim', 'Puskesmas Sehat Melati', 'Dokter Umum FKTP', '3404010303850003', '081234567892'),
('dr.budi', '$2y$10$4B9bW6LzN7U6jD4xR5P9ue6kP/3YgqWz.88l1u3o3mB0P71e0n3Y.', 'dr. Budi Santoso, Sp.PD', 'dokter_penerima', 'RSUD Sejahtera Husada', 'Spesialis Penyakit Dalam', '3404010404780004', '081234567893'),
('dr.siti', '$2y$10$4B9bW6LzN7U6jD4xR5P9ue6kP/3YgqWz.88l1u3o3mB0P71e0n3Y.', 'dr. Siti Rahmawati, Sp.A', 'dokter_penerima', 'RSUD Sejahtera Husada', 'Spesialis Anak', '3404010505800005', '081234567894'),
('pasien.bambang', '$2y$10$4B9bW6LzN7U6jD4xR5P9ue6kP/3YgqWz.88l1u3o3mB0P71e0n3Y.', 'Bambang Wijaya', 'pasien', 'Puskesmas Sehat Melati', 'Pasien', '3404011212880006', '081398765432'),
('security', '$2y$10$4B9bW6LzN7U6jD4xR5P9ue6kP/3YgqWz.88l1u3o3mB0P71e0n3Y.', 'Ahmad Fauzi, S.Tr.Kom (CISO)', 'security', 'Pusat Keamanan Siber RSUD', 'Keamanan Sistem', '3404010707900007', '081234567895');

INSERT INTO pasien (user_id, no_rm, nik, nama_pasien, tgl_lahir, jenis_kelamin, alamat, no_hp, golongan_darah, alergi) VALUES
(6, 'RM-PKM-2026-0042', '3404011212880006', 'Bambang Wijaya', '1988-12-12', 'L', 'Kec. Depok, Sleman, D.I. Yogyakarta', '081398765432', 'B+', 'Amoxicillin'),
(NULL, 'RM-PKM-2026-0043', '3404015504950002', 'Dewi Anggraini', '1995-04-15', 'P', 'Kec. Mlati, Sleman, D.I. Yogyakarta', '081567891234', 'O+', 'Tidak Ada');

INSERT INTO rujukan (
    no_rujukan, pasien_id, faskes_asal, faskes_tujuan, poli_tujuan,
    dokter_pengirim_id, alasan_rujukan, keluhan_utama, riwayat_penyakit,
    tanda_vital, diagnosis_primer, diagnosis_sekunder, tindakan_terapi,
    obat_diberikan, hasil_penunjang, digital_hash, status_consent,
    status_verifikasi, status_rujukan
) VALUES (
    'RUJ-2026-001', 1, 'Puskesmas Sehat Melati', 'RSUD Sejahtera Husada', 'Spesialis Penyakit Dalam',
    3, 'Perlu penanganan spesialis lanjutan terkait gula darah tidak terkontrol dan neuropati perifer.',
    'Sering kesemutan pada kedua kaki, lemas, haus berlebih sejak 2 minggu terakhir.',
    'Riwayat Diabetes Mellitus Tipe 2 sejak 3 tahun lalu, riwayat konsumsi obat tidak teratur.',
    'TD: 135/85 mmHg, Nadi: 82x/mnt, Suhu: 36.7 C, RR: 20x/mnt, BB: 68 kg',
    'E11.9 - Type 2 diabetes mellitus without complications', 'G63.2 - Diabetic polyneuropathy',
    'Edukasi diet DM, pemeriksaan GDS, rujukan ke dokter spesialis penyakit dalam.',
    'Metformin 500mg 3x1 tablet, Vitamin B Kompleks 1x1 tablet',
    'GDS: 285 mg/dL, HbA1c: 9.2%, Kolesterol Total: 220 mg/dL',
    '4a7b9c8d1e2f3a4b5c6d7e8f90123456789abcdef0123456789abcdef0123456',
    'Menunggu Persetujuan', 'Belum Diverifikasi', 'Menunggu Persetujuan Pasien'
);

INSERT INTO audit_logs (user_id, username, role, action, description, status_keamanan) VALUES
(1, 'admin', 'admin', 'SYSTEM_INIT', 'Sistem SIMAYA dan Database berhasil diinisialisasi.', 'NORMAL'),
(3, 'dr.andi', 'dokter_pengirim', 'BUAT_RUJUKAN', 'Membuat rujukan RUJ-2026-001 untuk pasien Bambang Wijaya ke RSUD Sejahtera Husada.', 'NORMAL');

