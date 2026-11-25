# Design System Specification — PaceFire

**Versi Dokumen:** 1.0  
**Target Platform:** Web Application (Mobile-First, Responsive Tablet & Desktop)  
**Implementasi:** Vanilla CSS3 (CSS Custom Properties, Flexbox, CSS Grid) & Vanilla JS (Tanpa Framework)  

---

## 1. Filosofi Desain & Sintesis Referensi

**PaceFire** memadukan atmosfer stadion malam yang tegas dan fokus pada performa olahraga dengan mekanika umpan balik gamifikasi yang memuaskan secara taktil serta struktur tata letak informasi yang bersih:

1. **Nike Run Club (Pondasi Warna & Tipografi Performa):**
   * Menggunakan kanvas gelap pekat (`#000000`) sebagai panggung utama agar angka statistik olahraga (jarak, *pace*, kecepatan, poin) tampil sangat kontras dan tajam.
   * Menggunakan **Nike Volt (`#CCFF00`)** untuk aksi utama, *progress ring*, dan pencapaian aktif, serta **Nike Red (`#FA5400`)** untuk nyala api *daily streak* dan momen perayaan.
   * Mengadopsi tipografi *condensed* berenergi tinggi untuk judul/angka *hero* dengan `font-variant-numeric: tabular-nums` agar kolom angka pada *leaderboard* dan statistik tidak bergeser saat berubah.

2. **Duolingo (Komponen Gamifikasi Taktil):**
   * Mengadaptasi tombol **3D Ledge** (tombol dengan "bibir" bayangan padat di bagian bawah yang tertekan ke bawah saat diklik) untuk aksi utama seperti *"CATAT AKTIVITAS"* dan *"GABUNG CHALLENGE"*.
   * Mengadopsi tampilan **Streak Flame Card**, **XP/Target Progress Bar** dengan kilau atas (*top highlight*), serta **Lencana (Badges)** berbingkai geometris yang ramah dan memberikan rasa pencapaian.

3. **Airbnb (Arsitektur Layout Peta/Jalur & Responsive Grid):**
   * Pada halaman detail jalur (`route_detail.php`) dan eksplorasi rute, mengadopsi pola **Visual-First + Floating Bottom Sheet** pada tampilan *mobile* (panel detail yang dapat ditarik ke atas/bawah di atas visual jalur) yang otomatis bertransformasi menjadi **2-Column Sticky Rail Layout** pada layar *desktop* (visual jalur & *leaderboard* di kiri, panel aksi/PB menempel di kanan).
   * Menggunakan sudut membulat (*soft rounded corners*) dan hierarki kartu yang rapi agar kepadatan informasi tetap nyaman dibaca.

---

## 2. Palet Warna (Nike Run Club Core + Semantic Gamification)

Seluruh warna didefinisikan sebagai variabel global di `:root` pada `assets/css/style.css`.

### 2.1 Kanvas & Permukaan (Dark-First Stadium Canvas)
| Token CSS | Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--bg-canvas` | `#000000` | Latar belakang utama seluruh halaman (*True Black*). |
| `--bg-charcoal` | `#0A0A0A` | Latar belakang sekunder / *section* kontras halus. |
| `--bg-surface-1` | `#1A1A1A` | Permukaan kartu utama (*activity card*, *challenge card*, *bottom sheet*). |
| `--bg-surface-2` | `#2B2B2B` | Permukaan *input field*, tombol sekunder, dan *hover state* kartu. |
| `--border-divider` | `#333333` | Garis pemisah tipis (*hairline*), *border* kartu, dan jalur *progress track*. |

### 2.2 Warna Aksen Utama & Ledge 3D
| Token CSS | Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--accent-volt` | `#CCFF00` | **Aksen Utama:** Tombol CTA utama, *progress bar* aktif, *tab* aktif, ikon jalur terpilih. |
| `--accent-volt-ledge` | `#8AA800` | *3D Ledge* (bayangan padat 4px di bawah tombol Volt). |
| `--accent-volt-glow` | `rgba(204, 255, 0, 0.25)` | Efek pendaran halus pada *progress ring* dan CTA *hero*. |
| `--accent-fire` | `#FA5400` | **Aksen Intensitas & Streak:** Ikon api *daily streak*, label "ON FIRE", kategori *Challenge Sulit*. |
| `--accent-fire-ledge` | `#B83D00` | *3D Ledge* untuk tombol/kartu bertema *streak* atau *challenge* spesial. |
| `--accent-gold` | `#FFC800` | Peringkat 1 *Leaderboard*, mahkota rekor *Personal Best (PB)*, dan *badge* terbuka. |

### 2.3 Teks & Semantik
| Token CSS | Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--text-primary` | `#FFFFFF` | Teks utama, angka *hero*, label tombol gelap. |
| `--text-on-volt` | `#000000` | Teks di atas tombol/lencana berwarna `--accent-volt`. |
| `--text-secondary` | `#B3B3B3` | Label unit (`MIN/KM`, `KM/JAM`, `POIN`), subjudul kartu. |
| `--text-muted` | `#8E8E8E` | *Placeholder* input, ikon navigasi tidak aktif, waktu relatif. |
| `--text-disabled` | `#5C5C5C` | Teks *badge* terkunci dan elemen nonaktif. |
| `--semantic-success` | `#CCFF00` | Notifikasi aktivitas tersimpan & syarat terpenuhi. |
| `--semantic-warning` | `#FFC400` | Peringatan *streak* hampir putus (belum aktivitas hari ini) & kategori *Challenge Sedang*. |
| `--semantic-error` | `#E50916` | Validasi gagal (*pace* tidak masuk akal), tombol hapus admin, *streak* padam (`0`). |
| `--semantic-error-ledge` | `#9E060F` | *3D Ledge* untuk tombol destruktif (Admin hapus aktivitas). |

---

## 3. Tipografi (Condensed Display + Tabular Numerals)

Menggunakan Google Fonts yang ringan dan tajam untuk mereplikasi karakter *Trade Gothic Condensed* (Nike) dan keterbacaan UI modern:

* **Display & Angka Hero (`--font-display`):** `'Oswald', 'Arial Narrow', sans-serif` (Weight: `700`, `UPPERCASE`, `letter-spacing: 0.04em`).
* **Body, Form & UI (`--font-sans`):** `'Inter', -apple-system, BlinkMacSystemFont, sans-serif` (Weight: `400`, `500`, `600`, `700`).

### 3.1 Skala Tipografi Responsif (`clamp()`)
| Peran | Font Family | Ukuran (`clamp`) | Weight | Line Height | Aturan Khusus |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Hero Stat Number** | `--font-display` | `clamp(2.75rem, 6vw, 4.25rem)` | `700` | `0.95` | `font-variant-numeric: tabular-nums;` (Jarak, Poin, Streak) |
| **Page Title** | `--font-display` | `clamp(1.75rem, 3.5vw, 2.5rem)` | `700` | `1.05` | `text-transform: uppercase; letter-spacing: 0.03em;` |
| **Section Header** | `--font-display` | `clamp(1.25rem, 2.5vw, 1.5rem)` | `700` | `1.15` | `text-transform: uppercase; letter-spacing: 0.04em;` |
| **Stat Unit / Label** | `--font-display` | `0.75rem` (12px) | `700` | `1.0` | `uppercase; letter-spacing: 0.08em; color: var(--text-secondary);` |
| **Card Title** | `--font-sans` | `1.0625rem` (17px) | `700` | `1.3` | Mixed case (Judul Challenge, Nama Jalur) |
| **Body Text** | `--font-sans` | `0.9375rem` (15px) | `400` | `1.5` | Deskripsi jalur, aturan challenge |
| **Button Label** | `--font-display` | `1rem` (16px) | `700` | `1.0` | `uppercase; letter-spacing: 0.05em;` |
| **Table / Leaderboard** | `--font-sans` | `0.875rem` (14px) | `600` | `1.3` | Wajib `font-variant-numeric: tabular-nums;` pada kolom skor |

---

## 4. Sistem Komponen & Gaya Visual

### 4.1 Tombol Taktil 3D Ledge (Adaptasi Duolingo × Warna Nike)
Setiap tombol aksi utama memiliki ketebalan fisik `4px` di bagian bawah. Saat ditekan (`:active`), tombol bergeser turun `4px` dan bayangan bawah menghilang, memberikan sensasi mekanis yang memuaskan.

```css
.btn-tactile {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 48px;
  padding: 12px 24px;
  border: none;
  border-radius: 14px;
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: transform 80ms linear, box-shadow 80ms linear, background-color 150ms ease;
}

/* Primary Volt CTA */
.btn-volt {
  background-color: var(--accent-volt);
  color: var(--text-on-volt);
  box-shadow: 0 4px 0 var(--accent-volt-ledge);
}
.btn-volt:active {
  transform: translateY(4px);
  box-shadow: 0 0 0 var(--accent-volt-ledge);
}

/* Secondary Dark Slab CTA */
.btn-secondary {
  background-color: var(--bg-surface-2);
  color: var(--text-primary);
  border: 1px solid var(--border-divider);
  box-shadow: 0 4px 0 #141414;
}
.btn-secondary:active {
  transform: translateY(4px);
  box-shadow: 0 0 0 #141414;
}
```

### 4.2 Kartu Daily Streak & Gamification HUD (Adaptasi Duolingo)
* **Tampilan Menyala (Aktif):** Latar belakang `--bg-surface-1` dengan border `1px solid rgba(250, 84, 0, 0.4)`. Ikon api SVG berwarna `--accent-fire` (`#FA5400`) dengan animasi denyut lembut (`scale(1) -> scale(1.06)`). Angka streak ditampilkan dalam `--font-display` ukuran besar.
* **Tampilan Waspada (Belum Olahraga Hari Ini):** Ikon api berkedip pelan dengan lencana kecil `"JAGA API HARI INI!"` berwarna `--semantic-warning`.
* **Tampilan Padam (`0` Hari):** Ikon api berubah monokrom `--text-disabled` (`#5C5C5C`) dengan pesan motivasi singkat.

### 4.3 Progress Bar XP & Target Challenge (Adaptasi Duolingo)
* Tinggi `14px`, sudut membulat penuh (`border-radius: 999px`), latar trek `--bg-surface-2`.
* Bagian terisi menggunakan `--accent-volt` (atau `--accent-fire` untuk *challenge* tingkat *Hard*) dengan garis kilau horizontal `3px` berwarna `rgba(255, 255, 255, 0.35)` di sisi atas untuk memberikan efek dimensi segar ala Duolingo.

### 4.4 Lencana Pencapaian / Badges (Sintesis Hexagonal Nike × Keramahan Duolingo)
* Menggunakan siluet **Hexagon (Segi Enam)** dengan ukuran `88px × 88px` (mobile) hingga `104px × 104px` (desktop) yang dibuat menggunakan `clip-path: polygon(50% 0%, 95% 25%, 95% 75%, 50% 100%, 5% 75%, 5% 25%)`.
* **Status Terbuka (Unlocked):** Gradien diagonal `linear-gradient(135deg, #CCFF00 0%, #FA5400 100%)` dengan ikon putih/hitam kontras di tengah dan nama badge dalam huruf kapital di bawahnya.
* **Status Terkunci (Locked):** Warna solid `--bg-surface-2` dengan ikon gembok `--text-disabled` dan keterangan syarat pencapaian di bawahnya.

### 4.5 Pola Layout Jalur: Floating Bottom Sheet & Sticky Split-Rail (Adaptasi Airbnb)
Diterapkan pada halaman `route_detail.php` (Detail Jalur & Leaderboard Tercepat):
* **Visual Header / Peta Skematik Jalur:** Bagian atas layar menampilkan kartu ilustrasi rute/elevasi skematik SVG berlatar gelap dengan garis rute berwarna `--accent-volt` bercahaya.
* **Pada Mobile (`< 768px`) — Floating Bottom Sheet:**
  * Panel detail informasi jalur, *Personal Best (PB)* pengguna, tombol *"CATAT AKTIVITAS DI JALUR INI"*, dan daftar *Leaderboard* jalur tampil sebagai *sheet* mengambang (`border-radius: 24px 24px 0 0`, latar `--bg-surface-1`, garis pegangan *drag-handle* `40px × 4px` di tengah atas).
  * Pengguna dapat menarik (*swipe/tap*) pegangan *sheet* antara **Peek Mode** (menampilkan info jalur & tombol CTA utama sementara visual rute di atas tetap terlihat luas) dan **Expanded Mode** (menarik panel ke atas hingga `85vh` untuk menelusuri seluruh tabel *leaderboard* jalur).
* **Pada Tablet & Desktop (`>= 768px`) — Airbnb 2-Column Sticky Rail:**
  * *Bottom sheet* otomatis berubah menjadi tata letak **2 kolom berdampingan** (`grid-template-columns: 1.5fr 1fr`, `gap: 24px`).
  * **Kolom Kiri:** Visual rute penuh + tabel *Leaderboard* 10 waktu tercepat di jalur tersebut.
  * **Kolom Kanan (Sticky `top: 96px`):** Kartu aksi permanen berisi spesifikasi jarak resmi, tingkat kesulitan, rekor *Personal Best* pengguna, dan tombol CTA 3D *"CATAT AKTIVITAS DI JALUR INI"*.

---

## 5. Sistem Responsif: Mobile-First ke Tablet & Desktop

Pendekatan penulisan CSS dimulai dari tata letak layar ponsel kecil (`360px–430px`) sebagai *default*, lalu diperluas menggunakan dua *breakpoint* utama:

| Breakpoint | Dimensi Layar | Pola Navigasi | Tata Letak Grid Utama |
| :--- | :--- | :--- | :--- |
| **Mobile (Default)** | `< 768px` | **Top Bar Ringkas** (Logo + Chip Streak & Poin) + **Fixed Bottom Tab Bar** (`64px` tinggi, 4 menu utama + 1 tombol tengah *Quick Log* menonjol). | 1 Kolom penuh (`padding: 0 16px`). Kartu statistik 2 kolom sejajar. |
| **Tablet** | `768px – 1023px` | **Top Navigation Bar** penuh (`72px` tinggi) menggantikan *Bottom Tab Bar*. | Kontainer maks `720px` terpusat. Kartu *Challenge* dan *Routes* menjadi **Grid 2 Kolom**. |
| **Desktop** | `>= 1024px` | **Top Navigation Bar** (`76px` tinggi) dengan indikator garis bawah Volt (`3px`) pada menu aktif & HUD Poin/Streak di kanan. | Kontainer maks `1200px` terpusat. Dashboard menggunakan **Asymmetric Bento Grid** (Statistik & Grafik `<canvas>` di kiri `65%`, Streak & Challenge aktif di kanan `35%`). Kartu Jalur/Challenge menjadi **Grid 3 Kolom**. |

### 5.1 Aturan Touch Target & Ergonomi
* Semua tombol interaktif, *tab*, dan baris *dropdown* memiliki area sentuh minimal **`48px × 48px`** agar mudah ditekan dengan satu ibu jari setelah berlari/bersepeda.
* Pada tampilan *mobile*, kontainer utama diberi `padding-bottom: 88px` agar konten terbawah tidak tertutup oleh *Fixed Bottom Tab Bar*.

---

## 6. Spesifikasi Grafik HTML5 `<canvas>` (`dashboard_chart.js`)

Agar grafik aktivitas 7 hari terakhir menyatu dengan estetika Nike Run Club × Duolingo:
* **Latar Kanvas:** Transparan di atas kartu `--bg-surface-1` (`#1A1A1A`).
* **Garis Grid Horizontal:** Tipis `1px`, warna `--border-divider` (`#333333`) dengan label angka `tabular-nums` berwarna `--text-muted`.
* **Batang Grafik (Bars):** Sudut atas membulat (`border-radius: 6px 6px 0 0`). Hari biasa berwarna `--bg-surface-2` dengan garis atas Volt; **hari ini** atau hari dengan poin tertinggi disorot penuh dengan warna `--accent-volt` (`#CCFF00`).
* **Interaktivitas Vanilla JS:** Mendukung *hover* (desktop) dan *tap* (mobile) pada setiap batang untuk menampilkan *floating tooltip* kecil berlatar `#000000` dengan border `--accent-volt` berisi detail tanggal, total jarak (`km`), dan total poin.

---

## 7. Checklist Konsistensi Implementasi CSS

1. **Zero External CSS/JS:** Tidak menggunakan Bootstrap, Tailwind, FontAwesome, atau Chart.js. Semua ikon UI (api streak, sepeda, sepatu lari, trofi, gembok) menggunakan **inline SVG** ringan.
2. **Tabular Numerals Wajib:** Setiap elemen dengan class `.stat-number`, `.pace-value`, `.points-badge`, dan kolom skor tabel wajib memiliki `font-variant-numeric: tabular-nums;`.
3. **Kontras Aksesibilitas:** Teks di atas tombol atau lencana `--accent-volt` (`#CCFF00`) **wajib** menggunakan warna hitam pekat (`#000000`), tidak pernah putih.
4. **No Heavy Drop Shadows:** Gunakan permukaan bertingkat (`#000000` $\rightarrow$ `#1A1A1A` $\rightarrow$ `#2B2B2B`) dan *border* `1px solid #333333` untuk pemisahan kartu; sisakan bayangan padat (`box-shadow: 0 4px 0`) khusus untuk tombol taktil 3D.