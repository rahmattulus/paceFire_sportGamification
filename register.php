<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$nama = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (strlen($nama) < 3 || strlen($nama) > 100) {
            $errors[] = 'Nama harus terdiri dari 3 sampai 100 karakter.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errors[] = 'Username harus 3-50 karakter dan hanya boleh huruf, angka, atau underscore.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }
        if (!hash_equals($password, $passwordConfirmation)) {
            $errors[] = 'Konfirmasi password tidak sama.';
        }

        if (!$errors) {
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $errors[] = 'Username atau email sudah digunakan.';
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO users (nama, username, email, password_hash, role) VALUES (?, ?, ?, ?, \'user\')'
                );
                $insert->execute([$nama, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                $_SESSION['nama'] = $nama;
                $_SESSION['role'] = 'user';
                $_SESSION['username'] = $username;
                set_flash('success', 'Akun berhasil dibuat. Selamat datang di PaceFire!');
                redirect('/pacefire/dashboard.php');
            }
        }
    }
}

$pageTitle = 'Daftar - PaceFire';
$authPage = true;
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <aside class="auth-promo">
        <span class="auth-promo-art" aria-hidden="true">
            <svg viewBox="0 0 120 90"><path d="M25 67c-17-12-10-37 11-43 16-5 24 6 35 4 17-3 32 6 32 21 0 20-20 30-42 28-14-1-26-2-36-10Z" fill="currentColor" opacity=".22"/><path d="M55 64c-10-8-7-21 2-29 1 11 12 12 11 21 5-4 7-10 5-16 13 10 15 24 5 31-7 6-17 5-23-1Z" fill="currentColor"/><circle cx="60" cy="58" r="2" fill="#fff"/><circle cx="70" cy="58" r="2" fill="#fff"/></svg>
        </span>
        <h2>Mulai dari satu langkah.</h2>
        <p>Buat akun, catat aktivitas, lalu bangun kebiasaan bergerak dengan cara yang terasa ringan.</p>
    </aside>
    <div class="auth-card">
        <p class="eyebrow">Mulai bergerak</p>
        <h1>Buat Akun</h1>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>
        <form method="post" class="stack-form" novalidate>
            <?= csrf_field() ?>
            <label>Nama
                <input type="text" name="nama" value="<?= e($nama) ?>" maxlength="100" required>
            </label>
            <label>Username
                <input type="text" name="username" value="<?= e($username) ?>" maxlength="50" required>
            </label>
            <label>Email
                <input type="email" name="email" value="<?= e($email) ?>" maxlength="100" required>
            </label>
            <label>Password
                <input type="password" name="password" minlength="8" required>
            </label>
            <label>Konfirmasi Password
                <input type="password" name="password_confirmation" minlength="8" required>
            </label>
            <button class="btn btn-volt" type="submit">Daftar Sekarang</button>
        </form>
        <p class="auth-switch">Sudah punya akun? <a href="/pacefire/login.php">Masuk</a></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
