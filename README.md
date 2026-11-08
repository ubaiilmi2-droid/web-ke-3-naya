# 🏥 SIMAYA - Sistem Informasi Rujukan & Resume Medis Elektronik Terintegrasi

**SIMAYA** adalah aplikasi web sistem informasi rujukan medis antar fasilitas kesehatan (Fasilitas Kesehatan Tingkat Pertama / FKTP ke Fasilitas Kesehatan Rujukan Tingkat Lanjutan / FKRTL) yang dibangun khusus untuk menyelesaikan studi **Kasus 5**. Dirancang dengan standar kompetensi **Mahasiswi Semester 7 Rekam Medis & Informasi Kesehatan (RMIK) / Sistem Informasi Kesehatan**, dengan arsitektur bersih, modular, dan mudah dipresentasikan.

---

## 🎨 Panduan Desain & Palet Warna Sederhana
Sesuai arahan, antarmuka SIMAYA dirancang bersih, profesional medis, dan sederhana dengan palet warna:
- **Hijau Tua** (`#14532D`): Warna utama navbar, header dokumen rekam medis, dan tombol utama. Memberikan kesan formal, kredibel, dan representasi faskes.
- **Hijau Muda** (`#22C55E` / `#DCFCE7`): Warna aksen, indikator status berhasil/valid, badge informed consent, dan highlight data. Memberikan kesan segar dan modern.
- **Putih** (`#FFFFFF`): Warna latar kartu, form klinis, lembar dokumen RME, dan tabel.

---

## 🔄 Pemetaan Alur Kasus 5 pada SIMAYA
1. **Pasien Berobat di FKTP (Puskesmas)**: Pasien datang ke Puskesmas, data rekam medis terdaftar, dan dokter melakukan pemeriksaan fisik, anamnesis, serta diagnosis ICD-10.
2. **Pembuatan RME & Rujukan ke RS**: Dokter FKTP membuat rujukan elektronik lengkap dengan Resume Medis Elektronik (RME). Sistem secara otomatis menghitung **Digital Hash SHA-256** untuk mengunci integritas data medis.
3. **Persetujuan Pasien (Informed Consent)**: Sebelum data medis dikirimkan ke Rumah Sakit, pasien login ke SIMAYA untuk membaca ringkasan data rujukan dan memberikan persetujuan (Setuju / Tolak) secara sadar sesuai prinsip perlindungan data pribadi medis.
4. **Pertukaran Data Otomatis & Verifikasi Keaslian oleh RS**: Rumah Sakit menerima data rujukan dan melakukan pengujian integritas kriptografis (membandingkan hash awal dengan hash saat ini) untuk membuktikan dokumen 100% asli dan bebas manipulasi liar.
5. **Dokter RS Membuka RME Sesuai Hak Akses (RBAC)**: Dokter RS (Dokter Penerima) hanya dapat membuka lembar RME jika spesialisasi polinya sesuai (misal: Poli Penyakit Dalam hanya untuk Sp.PD). Jika dokter dengan poli berbeda mencoba membuka, sistem memblokir akses (*Need-to-Know Principle*).
6. **Audit Trail Realtime**: Seluruh aktivitas (login, buat rujukan, persetujuan pasien, verifikasi keaslian, dokter membuka RME, hingga akses yang ditolak) secara otomatis dicatat dalam **Audit Log** dengan timestamp dan IP address.

---

## 👥 6 Role Pengguna & Akun Demo Cepat
Semua akun demo sudah siap pakai dengan password default: `password123`. Pada halaman login SIMAYA, telah disediakan tombol **1-Klik Demo Switcher** sehingga Anda tidak perlu mengetik username dan password saat presentasi di depan dosen!

| No | Role Pengguna | Username | Password | Deskripsi Tugas dalam Kasus 5 |
|:--:|:---|:---|:---|:---|
| 1 | **Admin Sistem** | `admin` | `password123` | Kelola master faskes, akun pengguna, dan pemantauan sistem secara menyeluruh. |
| 2 | **Petugas Rekam Medis (RM)** | `petugas.rm` | `password123` | Kelola master pasien, no rekam medis, dan validasi berkas rujukan masuk/keluar. |
| 3 | **Dokter Pengirim (FKTP)** | `dr.andi` | `password123` | Dokter Puskesmas yang memeriksa pasien, mengisi RME, dan membuat rujukan ke RS. |
| 4 | **Pasien Rujukan** | `pasien.bambang` | `password123` | Pasien yang melihat rujukan dan memberikan persetujuan transfer data medis. |
| 5 | **Dokter Penerima (RS)** | `dr.budi` | `password123` | Dokter Spesialis Penyakit Dalam di RS yang membuka RME dan memberi catatan tindak lanjut. |
| 6 | **Petugas Keamanan Sistem** | `security` | `password123` | Chief Information Security Officer (CISO) yang memantau Audit Trail dan integritas data. |
| *Bonus* | *Dokter Uji Hak Akses* | `dr.siti` | `password123` | Dokter Spesialis Anak (digunakan untuk demo hak akses tertolak saat membuka rujukan penyakit dalam). |

---

## 🛠️ Apa Saja yang Harus Disiapkan?

### 1. Perangkat Lunak (Software)
- **Web Browser**: Google Chrome, Mozilla Firefox, atau Microsoft Edge.
- **PHP**: Versi 8.0 ke atas (sudah terinstal pada sistem Anda: PHP 8.5).
- **Web Server (Pilih salah satu)**:
  - **Opsi A (Paling Cepat & Praktis)**: Menggunakan PHP Built-in Web Server bawaan (`php -S localhost:8000`). Database otomatis menggunakan SQLite bawaan tanpa instalasi apapun!
  - **Opsi B (Menggunakan XAMPP)**: Menggunakan Apache & MySQL dari XAMPP (`C:\xampp`), kemudian mengimpor berkas `database/simaya.sql` ke phpMyAdmin.

### 2. Konsep Akademis yang Perlu Dipahami (Semester 7)
- **Interoperabilitas Rekam Medis Elektronik (RME)**: Kemampuan sistem faskes primer (Puskesmas) untuk bertukar data secara mulus dengan faskes rujukan sekunder (Rumah Sakit) sesuai regulasi Permenkes No. 24 Tahun 2022 tentang Rekam Medis.
- **Informed Consent Elektronik**: Persetujuan tertulis secara digital dari pasien sebelum data medis pribadi ditransmisikan antar instansi guna mematuhi UU Perlindungan Data Pribadi (UU PDP).
- **Integritas Data Kriptografis (SHA-256 Hash)**: Membuktikan bahwa dokumen RME yang diterima oleh Rumah Sakit identik dan tidak pernah diubah oleh pihak ketiga di perjalanan jaringan.
- **Role-Based Access Control (RBAC) & Need-to-Know Principle**: Pembatasan hak akses dokter hanya pada resume medis pasien yang sesuai dengan bidang keahlian polinya.
- **Audit Trail / Audit Logging**: Rekaman jejak audit forensik digital untuk kepatuhan hukum, akuntabilitas faskes, dan investigasi insiden keamanan siber.

---

## 🚀 Tutorial Step-by-Step Menjalankan Aplikasi

### Opsi A: Menjalankan Langsung (Rekomendasi - Tanpa Setup XAMPP)
Aplikasi SIMAYA telah dilengkapi dengan auto-initializer SQLite. Cukup buka terminal PowerShell di folder proyek ini dan jalankan perintah:

```powershell
# 1. Pastikan berada di folder proyek:
cd "d:\Naya\WEB KE 3 NAYA"

# 2. Jalankan built-in web server PHP:
php -S localhost:8000
```

Buka browser Anda dan akses:
👉 **`http://localhost:8000`**

Aplikasi akan otomatis menginisialisasi database `simaya.sqlite` dan mengisi seluruh data awal untuk 6 role secara instan!

---

### Opsi B: Menjalankan Menggunakan XAMPP (MySQL)
Jika dosen mengharuskan presentasi menggunakan phpMyAdmin:
1. Buka **XAMPP Control Panel**, lalu klik **Start** pada Apache dan MySQL.
2. Buka browser ke `http://localhost/phpmyadmin`.
3. Buat database baru bernama `simaya_db`.
4. Klik tab **Import**, pilih file: `d:\Naya\WEB KE 3 NAYA\database\simaya.sql`, lalu klik **Import**.
5. Buka file `config/database.php`, lalu ubah:
   ```php
   $db_driver = 'mysql'; // Ganti dari 'sqlite' ke 'mysql'
   ```
6. Pindahkan atau buat symbolic link folder proyek ke dalam `C:\xampp\htdocs\simaya`, lalu akses di browser melalui: `http://localhost/simaya`.

---

## 🎬 Skenario Demonstrasi saat Ujian / Sidang Tugas

Berikut adalah urutan presentasi alur yang dijamin membuat dosen terkesan:

1. **Langkah 1: Login sebagai Dokter FKTP (`dr.andi`)**
   - Klik tombol demo *Dokter Pengirim*.
   - Buka menu *Buat Rujukan & RME*.
   - Pilih pasien (misal: *Bambang Wijaya*), tentukan faskes tujuan (RSUD Sejahtera Husada), poli tujuan (Poli Spesialis Penyakit Dalam), dan isi data klinis RME (Keluhan, Tanda Vital, Diagnosis ICD-10, Terapi, Obat).
   - Klik *Simpan & Terbitkan Rujukan*. Tunjukkan kepada dosen bahwa sistem menghasilkan token **Digital Hash SHA-256** dan status rujukan berada pada tahap **Menunggu Persetujuan Pasien**.

2. **Langkah 2: Login sebagai Pasien (`pasien.bambang`)**
   - Logout lalu klik tombol demo *Pasien Rujukan*.
   - Pasien melihat pemberitahuan rujukan dan membuka menu *Persetujuan Data Medis*.
   - Pasien membaca ringkasan data yang akan dikirim, lalu mengklik tombol **✓ Ya, Saya Setuju & Kirim Data ke RS**.
   - Status rujukan otomatis berubah menjadi **Terkirim ke RS**.

3. **Langkah 3: Login sebagai Petugas RS / Keamanan (`petugas.rm` atau `security`)**
   - Buka menu *Verifikasi Keaslian RME*.
   - Klik *Uji Keaslian* pada berkas rujukan pasien Bambang Wijaya.
   - Perlihatkan kepada dosen bahwa sistem membandingkan Hash asal Puskesmas dengan kalkulasi ulang di Rumah Sakit. Hasil uji menunjukkan: **COCOK (100% Valid & Asli)**.
   - Klik *Sahkan & Verifikasi Keaslian Dokumen*.

4. **Langkah 4: Demonstrasi Uji Keamanan Hak Akses Tertolak (RBAC)**
   - Login sebagai **dr. Siti** (Spesialis Anak).
   - Coba buka berkas rujukan pasien Bambang Wijaya (yang ditujukan untuk Poli Penyakit Dalam).
   - Sistem akan memunculkan layar merah: **🚫 Akses Rekam Medis Ditolak!** karena spesialisasi dokter tidak cocok dengan poli tujuan.

5. **Langkah 5: Buka RME oleh Dokter yang Sah (`dr.budi`)**
   - Login sebagai **dr. Budi, Sp.PD** (Spesialis Penyakit Dalam).
   - Buka menu *Daftar Pasien Rujukan Masuk* -> klik *Buka RME*.
   - Sistem mengizinkan akses penuh membaca Resume Medis Elektronik. Dokter RS dapat memasukkan *Catatan Penerimaan & Rencana Penanganan Lanjutan*.

6. **Langkah 6: Pembuktian Audit Trail (`security`)**
   - Login sebagai Petugas Keamanan Sistem (`security`).
   - Buka menu *Audit Trail & Log Akses*.
   - Perlihatkan kepada dosen seluruh kronologi lengkap dari pembuatan rujukan, persetujuan pasien, verifikasi SHA-256, percobaan akses ditolak oleh dr. Siti (bertanda kuning/merah), hingga pembacaan sah oleh dr. Budi.

