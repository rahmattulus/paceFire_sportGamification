# Development Roadmap & Implementation Plan — PaceFire

Dokumen ini merupakan panduan langkah demi langkah (*step-by-step*) untuk membangun aplikasi web **PaceFire** menggunakan PHP Native, MySQL/MariaDB, HTML5, CSS3, dan Vanilla JavaScript. Pengerjaan dibagi menjadi **6 Fase** berdasarkan ketergantungan (*dependency*) fitur agar aplikasi selalu dalam kondisi berjalan (*working state*) di setiap akhir fase.

---

## Fase 1: Setup Environment, Database & Fondasi Inti
**Target:** Struktur folder siap, database ter-import dengan data awal, dan sistem autentikasi (Register, Login, Logout, Middleware Role) berjalan aman.

### Checklist Pengerjaan:
- [x] **1.1 Inisialisasi Struktur Folder:** Buat seluruh direktori sesuai spesifikasi pada Bagian 7 PRD (`config/`, `includes/`, `assets/css/`, `assets/js/`, `admin/`, `database/`).
- [x] **1.2 Database & Seeding (`database/schema.sql`):**
  - Buat database `pacefire_db` di phpMyAdmin / MySQL.
  - Jalankan DDL untuk tabel: `users`, `routes`, `activities`, `challenges`, `challenge_participants`, `badges`, dan `user_badges` (pastikan kolom `tingkat_kesulitan` dan `bonus_poin_pemenang` pada tabel `challenges` sesuai dengan PRD).
  - Isi data *seeder* awal untuk 7 master `badges` dan 3–5 contoh `routes` aktif.
  - Buat 1 akun `admin` secara manual (gunakan script PHP sementara dengan `password_hash('admin123', PASSWORD_DEFAULT)` lalu simpan ke tabel `users`).
- [x] **1.3 Konfigurasi & Helper Dasar:**
  - `config/database.php`: Koneksi `PDO` dengan mode error `PDO::ERRMODE_EXCEPTION` dan `date_default_timezone_set('Asia/Jakarta')`.
  - `config/constants.php`: Definisikan konstanta tarif poin, batas jarak/kecepatan/*pace*, batas 500 poin/hari, dan tier bonus *challenge*.
  - `includes/functions.php`: Buat fungsi `e()` (sanitasi XSS `htmlspecialchars`), `set_flash()` / `get_flash()`, generator/validator CSRF token, dan konversi format *pace* (`format_pace()`).
- [x] **1.4 Sistem Autentikasi & Layout Dasar:**
  - `includes/auth_check.php`: Fungsi `require_login()`, `require_user()`, dan `require_admin()`.
  - `register.php`, `login.php`, `logout.php`: Implementasi registrasi (`password_hash`), login (`password_verify` + `session_regenerate_id(true)`), dan redirect berbasis `role`.
  - `includes/header.php`, `includes/footer.php`, `assets/css/style.css`, `assets/css/auth.css`: Kerangka navigasi dinamis (Guest, User, Admin) dan sistem grid/kartu CSS murni.

**Kriteria Selesai (Definition of Done):** User baru dapat mendaftar dan login ke dashboard kosong; Admin dapat login dan masuk ke halaman `admin/index.php`; akses halaman terproteksi tanpa login otomatis dialihkan ke `login.php`.

---

## Fase 2: Manajemen Jalur (Admin) & Mesin Gamifikasi Aktivitas (Core)
**Target:** Admin dapat mengelola jalur olahraga, dan User dapat mencatat aktivitas lengkap dengan validasi anti-curang, kalkulasi poin, pembaruan *streak*, dan *badge*.

### Checklist Pengerjaan:
- [x] **2.1 Modul Admin Jalur (`admin/routes.php`):**
  - Form tambah jalur baru (nama, jenis olahraga, jarak resmi, deskripsi, tingkat kesulitan).
  - Tabel daftar jalur dengan fitur edit dan tombol *toggle* `is_active` (aktif/nonaktif sebagai pengganti hapus permanen).
- [x] **2.2 Mesin Gamifikasi Backend (`includes/gamification.php`):**
  - `validate_activity()`: Validasi jarak min/maks, *pace* lari (`3:00`–`15:00` /km) atau kecepatan sepeda (`8`–`45` km/jam), serta cek total poin user hari ini tidak melebihi $500\text{ poin}$.
  - `calculate_points()`: Hitung `poin_dasar`, pengali *streak* aktif, dan bonus $+5\text{ poin}$ jika aktivitas pertama hari ini.
  - `calculate_streak()`: Logika pembaruan `current_streak`, `longest_streak`, dan `last_active_date`.
  - `evaluate_badges()`: Pengecekan otomatis 7 syarat *badge* dan pemberian `bonus_poin` ke tabel `user_badges`.
- [x] **2.3 Halaman Catat Aktivitas (`activity_create.php` & `assets/js/activity_form.js`):**
  - Interaktivitas JS: Saat jenis olahraga dipilih, filter *dropdown* jalur yang aktif; saat jalur dipilih, isi otomatis input jarak dan set atribut `readonly`.
  - Proses simpan menggunakan **PDO Transaction** (`beginTransaction` -> simpan aktivitas -> update streak & poin user -> cek badge -> `commit`).
- [x] **2.4 Riwayat & Katalog Jalur User:**
  - `activity_history.php`: Tabel riwayat aktivitas user dengan filter jenis olahraga dan tanggal.
  - `routes.php` & `route_detail.php`: Daftar jalur aktif, tampilan *Personal Best (PB)* user pada jalur tersebut, *Leaderboard* 10 waktu tercepat jalur, dan tombol pintasan ke `activity_create.php?route_id=X`.

**Kriteria Selesai:** Input curang (misal lari 10 km dalam 15 menit) ditolak dengan pesan error jelas; input valid menambah poin, menaikkan streak, membuka badge pertama ("Langkah Pertama"), dan terkunci jaraknya jika memilih jalur.

---

## Fase 3: Sistem Challenge & Leaderboard Kompetitif
**Target:** User dapat membuat/mengikuti *challenge*, progres terhitung otomatis sejak `joined_at`, dan penutupan otomatis berjalan untuk target jarak maupun batas waktu.

### Checklist Pengerjaan:
- [ ] **3.1 Logika Evaluasi Challenge (`includes/gamification.php`):**
  - Fungsi `evaluate_user_challenges($user_id)`: Dipanggil di dalam transaksi saat user menyimpan aktivitas baru. Mengecek seluruh *challenge* aktif bertipe `jarak` yang diikuti user; jika akumulasi jarak $\ge$ `nilai_target`, langsung tutup *challenge* (`status = 'selesai'`), hitung peringkat akhir seluruh peserta, dan berikan `bonus_poin_pemenang` ke Peringkat 1.
  - Fungsi `sync_expired_challenges()`: Dipanggil saat halaman daftar/detail *challenge* dimuat untuk menutup *challenge* yang telah melewati `tanggal_selesai` dan menetapkan pemenangnya.
- [ ] **3.2 Halaman Challenge (`challenges.php`, `challenge_create.php`, `challenge_detail.php`):**
  - `challenge_create.php`: Form pembuatan *challenge* (judul, jenis olahraga, tipe target jarak/poin, nilai target, tingkat kesulitan `easy/medium/hard` yang otomatis menentukan bonus juara, serta tanggal mulai & selesai) + otomatis mendaftarkan pembuat ke `challenge_participants`.
  - `challenges.php`: Navigasi tab (**Tersedia**, **Diikuti**, **Selesai**) dengan progress bar CSS murni.
  - `challenge_detail.php`: Informasi aturan, tombol "Gabung Challenge", progres pribadi, dan *Leaderboard* peserta (dihitung dari `MAX(tanggal_mulai, DATE(joined_at))`).
- [ ] **3.3 Leaderboard Global (`leaderboard.php`):**
  - Peringkat seluruh pengguna berdasarkan `total_poin` tertinggi, menampilkan lencana peringkat 1–3, streak terpanjang, dan jumlah badge.

**Kriteria Selesai:** Dua akun berbeda dapat bergabung ke *challenge* yang sama; aktivitas yang dicatat sebelum `joined_at` tidak dihitung; saat salah satu user mencapai target jarak, status *challenge* otomatis berubah menjadi `selesai` dan bonus poin masuk ke pemenang.

---

## Fase 4: Dashboard Visual (HTML5 Canvas), Profil & Beranda
**Target:** Antarmuka utama kaya akan umpan balik visual tanpa menggunakan *library* grafik eksternal.

### Checklist Pengerjaan:
- [ ] **4.1 Grafik Mingguan Vanilla JS (`assets/js/dashboard_chart.js`):**
  - Siapkan data agregasi jarak/poin 7 hari terakhir dari PHP dalam format JSON ke atribut data HTML / variabel JS.
  - Gambar grafik batang (*bar chart*) responsif menggunakan HTML5 `<canvas>` 2D context lengkap dengan label hari, nilai poin/jarak, dan *hover tooltip* sederhana.
- [ ] **4.2 Dashboard Pengguna (`dashboard.php`):**
  - Kartu metrik utama: Total Poin, Status Api Streak (menyala jika `last_active_date` hari ini/kemarin, padam/0 jika terlewat), dan Peringkat Global saat ini.
  - Integrasi grafik `<canvas>`, daftar *challenge* aktif yang diikuti, dan 3 *badge* terbaru.
- [ ] **4.3 Halaman Profil (`profile.php`) & Beranda (`index.php`):**
  - `profile.php`: Ringkasan total jarak lari & sepeda, rekor `longest_streak`, grid etalase semua *badge* (visual berbeda antara yang sudah terbuka dan masih terkunci), serta form ganti password.
  - `index.php`: Halaman *landing* publik yang menjelaskan mekanika gamifikasi PaceFire beserta cuplikan Top 5 *Leaderboard* Global.

**Kriteria Selesai:** Grafik mingguan tergambar rapi di `<canvas>` tanpa error konsol; tampilan *streak* otomatis reset menjadi 0 jika tanggal terakhir aktif lebih lama dari kemarin.

---

## Fase 5: Panel Moderasi Admin
**Target:** Admin memiliki kendali penuh untuk menjaga integritas data dan memoderasi kecurangan.

### Checklist Pengerjaan:
- [ ] **5.1 Dashboard Admin (`admin/index.php`):**
  - Menampilkan kartu statistik: Total Pengguna, Total Aktivitas Hari Ini, Jumlah Jalur Aktif, dan Jumlah *Challenge* Aktif.
- [ ] **5.2 Moderasi Aktivitas (`admin/activities.php`):**
  - Daftar seluruh aktivitas terbaru dari semua pengguna dengan penanda visual untuk aktivitas dengan *pace*/kecepatan mendekati batas atas.
  - Fitur **Hapus Aktivitas**: Ketika admin menghapus aktivitas mencurigakan, sistem menjalankan transaksi untuk menghapus baris aktivitas, mengurangi/menghitung ulang `total_poin` user, dan merekalkulasi ulang status `streak` user dari riwayat aktivitas valid yang tersisa.
- [ ] **5.3 Moderasi Challenge (`admin/challenges.php`):**
  - Daftar seluruh *challenge* dan tombol untuk mengubah status menjadi `dibatalkan` jika ditemukan pelanggaran aturan.

**Kriteria Selesai:** Penghapusan aktivitas oleh admin secara akurat memperbarui ulang total poin dan status *streak* pengguna yang bersangkutan tanpa merusak konsistensi database.

---

## Fase 6: Pengujian End-to-End, Keamanan & Finalisasi Laporan
**Target:** Seluruh skenario kasus uji lolos dan kode siap dipresentasikan/dikumpulkan.

### Checklist Pengerjaan:
- [ ] **6.1 Pengujian Skenario Edge-Case:**
  - Uji batas poin harian ($500\text{ poin}$/hari) dengan beberapa kali input di hari yang sama.
  - Uji perubahan hari pada *streak* (simulasi dengan mengubah `last_active_date` sementara di database).
  - Uji *soft-delete* jalur yang sudah pernah dipakai di riwayat aktivitas (memastikan halaman riwayat tidak error).
- [ ] **6.2 Audit Keamanan & Kode:**
  - Pastikan semua input form dibersihkan saat ditampilkan (`e()`), seluruh query menggunakan PDO *Prepared Statements*, dan token CSRF aktif di setiap form `POST`.
  - Pastikan tidak ada satu pun file CSS/JS pihak ketiga yang tertinggal di folder `assets/`.
- [ ] **6.3 Dokumentasi Pengumpulan:**
  - Ekspor ulang file `database/schema.sql` terbaru yang sudah berisi data *dummy* untuk keperluan demo.

---

## Urutan Eksekusi File yang Disarankan
Jika kita mulai menulis kode secara bertahap, urutan pembuatan file yang paling efisien adalah:
1. `database/schema.sql`
2. `config/database.php` & `config/constants.php`
3. `includes/functions.php` & `includes/auth_check.php`
4. `assets/css/style.css`, `includes/header.php`, `includes/footer.php`
5. `register.php`, `login.php`, `logout.php`
6. `admin/routes.php`
7. `includes/gamification.php` & `activity_create.php` (+ `assets/js/activity_form.js`)
8. `activity_history.php`, `routes.php`, `route_detail.php`
9. `challenge_create.php`, `challenges.php`, `challenge_detail.php`, `leaderboard.php`
10. `dashboard.php` (+ `assets/js/dashboard_chart.js`), `profile.php`, `index.php`
11. `admin/index.php`, `admin/activities.php`, `admin/challenges.php`