-- =====================================================================
-- Skema database: Web Gamifikasi Olahraga (lari & bersepeda)
-- Target: MySQL 8.0.16+ / MariaDB 10.2.1+ (CHECK constraint aktif)
-- Engine: InnoDB, charset utf8mb4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS pacefire_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pacefire_db;

-- Urutan drop (anak dulu, induk belakangan) agar skrip bisa dijalankan ulang
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS challenge_participants;
DROP TABLE IF EXISTS challenges;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS routes;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- 1. users
--    total_poin dan kolom streak adalah data turunan (cache) yang
--    di-update oleh PHP setiap ada aktivitas valid disimpan.
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama              VARCHAR(100) NOT NULL,
  username          VARCHAR(50)  NOT NULL,
  email             VARCHAR(100) NOT NULL,
  password_hash     VARCHAR(255) NOT NULL,           -- hasil password_hash() PHP
  role              ENUM('user','admin') NOT NULL DEFAULT 'user',
  total_poin        INT UNSIGNED NOT NULL DEFAULT 0,
  current_streak    INT UNSIGNED NOT NULL DEFAULT 0,
  longest_streak    INT UNSIGNED NOT NULL DEFAULT 0,
  last_active_date  DATE NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. routes (jalur) - hanya dikelola admin
--    Jangan dihapus permanen jika sudah dipakai; nonaktifkan lewat is_active.
-- ---------------------------------------------------------------------
CREATE TABLE routes (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_jalur          VARCHAR(100) NOT NULL,
  jenis_olahraga      ENUM('lari','sepeda') NOT NULL,
  jarak_km            DECIMAL(6,2) NOT NULL,
  lokasi              VARCHAR(150) NULL,
  deskripsi           TEXT NULL,
  tingkat_kesulitan   ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'sedang',
  is_active           TINYINT(1) NOT NULL DEFAULT 1,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_routes_jenis_aktif (jenis_olahraga, is_active),
  CONSTRAINT chk_routes_jarak CHECK (jarak_km > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. activities
--    route_id boleh NULL (user tidak memakai jalur).
--    Poin disimpan sebagai snapshot agar perubahan tarif tidak
--    mengubah riwayat lama.
-- ---------------------------------------------------------------------
CREATE TABLE activities (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  route_id      INT UNSIGNED NULL,
  jenis_olahraga ENUM('lari','sepeda') NOT NULL,
  jarak_km      DECIMAL(6,2) NOT NULL,
  durasi_menit  DECIMAL(7,2) NOT NULL,
  tanggal       DATE NOT NULL,                       -- diisi otomatis oleh PHP (hari saat dicatat, zona Asia/Jakarta), bukan input user
  poin_dasar    INT UNSIGNED NOT NULL DEFAULT 0,
  poin_bonus    INT UNSIGNED NOT NULL DEFAULT 0,
  poin_total    INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_act_user_tanggal (user_id, tanggal),
  KEY idx_act_tanggal (tanggal),
  KEY idx_act_route (route_id, durasi_menit),
  CONSTRAINT fk_act_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_act_route FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE RESTRICT,
  CONSTRAINT chk_act_jarak  CHECK (jarak_km > 0),
  CONSTRAINT chk_act_durasi CHECK (durasi_menit > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. challenges
--    status diperbarui saat halaman dimuat (bandingkan dengan tanggal),
--    atau dibatalkan manual oleh pembuat/admin.
-- ---------------------------------------------------------------------
CREATE TABLE challenges (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  creator_id           INT UNSIGNED NOT NULL,
  judul                VARCHAR(120) NOT NULL,
  deskripsi            TEXT NULL,
  jenis_olahraga       ENUM('lari','sepeda','semua') NOT NULL DEFAULT 'semua',
  tipe_target          ENUM('jarak','poin') NOT NULL,
  nilai_target         DECIMAL(10,2) NOT NULL,           -- km jika 'jarak', poin jika 'poin'
  tingkat_kesulitan    ENUM('easy','medium','hard') NOT NULL,
  bonus_poin_pemenang  INT UNSIGNED NOT NULL,
  tanggal_mulai        DATE NOT NULL,
  tanggal_selesai      DATE NOT NULL,
  status               ENUM('aktif','selesai','dibatalkan') NOT NULL DEFAULT 'aktif',
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_chal_status_tanggal (status, tanggal_mulai, tanggal_selesai),
  CONSTRAINT fk_chal_creator FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_chal_target   CHECK (nilai_target > 0),
  CONSTRAINT chk_chal_bonus    CHECK (bonus_poin_pemenang >= 0),
  CONSTRAINT chk_chal_bonus_tier CHECK (
    (tingkat_kesulitan = 'easy' AND bonus_poin_pemenang = 50)
    OR (tingkat_kesulitan = 'medium' AND bonus_poin_pemenang = 100)
    OR (tingkat_kesulitan = 'hard' AND bonus_poin_pemenang = 200)
  ),
  CONSTRAINT chk_chal_periode  CHECK (tanggal_selesai >= tanggal_mulai)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. challenge_participants
--    Skor leaderboard TIDAK disimpan di sini; dihitung dari activities
--    pada periode challenge. bonus_poin dicatat terpisah dan hanya
--    terisi untuk pemenang setelah challenge selesai.
-- ---------------------------------------------------------------------
CREATE TABLE challenge_participants (
  challenge_id    INT UNSIGNED NOT NULL,
  user_id         INT UNSIGNED NOT NULL,
  joined_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  peringkat_akhir INT UNSIGNED NULL,
  bonus_poin_didapat INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (challenge_id, user_id),
  KEY idx_part_user (user_id),
  CONSTRAINT fk_part_challenge FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
  CONSTRAINT fk_part_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. badges & user_badges
--    Badge dicek otomatis setiap ada aktivitas baru. Bonus milestone
--    (mis. streak 7/30 hari) dimodelkan sebagai badge ber-bonus_poin,
--    sehingga otomatis "sekali seumur hidup" lewat PK user_badges.
-- ---------------------------------------------------------------------
CREATE TABLE badges (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_badge     VARCHAR(50)  NOT NULL,
  nama_badge     VARCHAR(100) NOT NULL,
  deskripsi      VARCHAR(255) NULL,
  kriteria_tipe  ENUM('aktivitas','streak','jarak_lari','jarak_sepeda','eksplorasi_jalur') NOT NULL,
  kriteria_nilai DECIMAL(10,2) NOT NULL,
  bonus_poin     INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_badges_kode (kode_badge)
) ENGINE=InnoDB;

CREATE TABLE user_badges (
  user_id      INT UNSIGNED NOT NULL,
  badge_id     INT UNSIGNED NOT NULL,
  achieved_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, badge_id),
  CONSTRAINT fk_ub_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- DATA AWAL (seed)
-- =====================================================================

-- Akun admin: buat lewat PHP, jangan tulis hash manual. Contoh:
--   echo password_hash('passwordAdmin', PASSWORD_DEFAULT);
-- lalu:
--   INSERT INTO users (nama, username, email, password_hash, role)
--   VALUES ('Admin', 'admin', 'admin@example.com', '<hash di atas>', 'admin');

INSERT INTO badges (kode_badge, nama_badge, deskripsi, kriteria_tipe, kriteria_nilai, bonus_poin) VALUES
  ('LANGKAH_PERTAMA',       'Langkah Pertama',          'Menyelesaikan 1 aktivitas valid pertama',           'aktivitas',       1,   10),
  ('API_MENYALA_7_HARI',    'Api Menyala (7 Hari)',     'Mencapai streak sebesar 7 hari berturut-turut',     'streak',          7,   50),
  ('TAK_TERHENTIKAN_30_HARI','Tak Terhentikan (30 Hari)','Mencapai streak sebesar 30 hari berturut-turut',   'streak',         30,  200),
  ('PELARI_SETENGAH_MARATON','Pelari Setengah Maraton', 'Mengumpulkan total jarak lari minimal 21 km',       'jarak_lari',     21,   50),
  ('PENAKLUK_MARATON',      'Penakluk Maraton',         'Mengumpulkan total jarak lari minimal 42 km',       'jarak_lari',     42,  100),
  ('GOWES_CENTURY',         'Gowes Century',             'Mengumpulkan total jarak sepeda minimal 100 km',    'jarak_sepeda',  100,   75),
  ('PENJELAJAH_JALUR',      'Penjelajah Jalur',          'Menyelesaikan minimal 3 jalur resmi yang berbeda',  'eksplorasi_jalur', 3, 40);

INSERT INTO routes (nama_jalur, jenis_olahraga, jarak_km, lokasi, deskripsi, tingkat_kesulitan) VALUES
  ('Sirkuit Stadion 5K',      'lari',    5.00, 'Stadion Kota',         'Jalur lari pendek untuk pemanasan dan interval', 'mudah'),
  ('Rute Sungai 10K',         'lari',   10.00, 'Tepi Sungai',          'Jalur lari datar mengikuti lintasan sungai',      'sedang'),
  ('Setengah Maraton Kota',   'lari',   21.00, 'Area Kota',            'Jalur lari menengah dengan lintasan perkotaan',   'sulit'),
  ('Gowes Taman 20K',         'sepeda', 20.00, 'Taman Kota',           'Jalur sepeda santai mengelilingi taman kota',     'mudah'),
  ('Circuit Sepeda 100K',     'sepeda',100.00, 'Ring Road',            'Jalur sepeda jarak jauh untuk tantangan century', 'sulit');

-- =====================================================================
-- CONTOH QUERY PENTING (bukan bagian skema, hanya referensi)
-- =====================================================================

-- Leaderboard sebuah challenge (target poin), dengan filter jenis olahraga:
--   SELECT u.id, u.nama, SUM(a.poin_total) AS skor
--   FROM challenge_participants cp
--   JOIN challenges c ON c.id = cp.challenge_id
--   JOIN users u      ON u.id = cp.user_id
--   LEFT JOIN activities a
--          ON a.user_id = cp.user_id
--         AND a.tanggal >= GREATEST(c.tanggal_mulai, DATE(cp.joined_at))
--         AND a.tanggal <= c.tanggal_selesai
--         AND (c.jenis_olahraga = 'semua' OR a.jenis_olahraga = c.jenis_olahraga)
--   WHERE cp.challenge_id = ?
--   GROUP BY u.id, u.nama
--   ORDER BY skor DESC;
--   (untuk target jarak, ganti SUM(a.poin_total) menjadi SUM(a.jarak_km))

-- Leaderboard sebuah jalur (waktu tercepat per user):
--   SELECT u.nama, MIN(a.durasi_menit) AS waktu_terbaik
--   FROM activities a JOIN users u ON u.id = a.user_id
--   WHERE a.route_id = ?
--   GROUP BY u.id, u.nama
--   ORDER BY waktu_terbaik ASC;

-- Total poin aktivitas hari ini (untuk batas 500 poin/hari):
--   SELECT COALESCE(SUM(poin_total), 0) FROM activities
--   WHERE user_id = ? AND tanggal = CURDATE();
