<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$identifier = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $identifier = trim((string) ($_POST['identifier'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $statement = $pdo->prepare(
            'SELECT id, nama, username, email, password_hash, role
             FROM users
             WHERE email = ? OR username = ?
             LIMIT 1'
        );
        $statement->execute([$identifier, $identifier]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Email/username atau password salah.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            set_flash('success', 'Login berhasil.');
            redirect($user['role'] === 'admin'
                ? '/pacefire/admin/index.php'
                : '/pacefire/dashboard.php');
        }
    }
}

$pageTitle = 'Masuk - PaceFire';
$authPage = true;
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <aside class="auth-promo">
        <span class="auth-promo-art" aria-hidden="true">
            <svg viewBox="0 0 120 90"><path d="M25 67c-17-12-10-37 11-43 16-5 24 6 35 4 17-3 32 6 32 21 0 20-20 30-42 28-14-1-26-2-36-10Z" fill="currentColor" opacity=".22"/><path d="M55 64c-10-8-7-21 2-29 1 11 12 12 11 21 5-4 7-10 5-16 13 10 15 24 5 31-7 6-17 5-23-1Z" fill="currentColor"/><circle cx="60" cy="58" r="2" fill="#fff"/><circle cx="70" cy="58" r="2" fill="#fff"/></svg>
        </span>
        <h2>Selamat datang kembali.</h2>
        <p>Catat langkahmu, jaga streak, dan rayakan progres kecil yang kamu buat setiap hari.</p>
    </aside>
    <div class="auth-card">
        <p class="eyebrow">Area atlet</p>
        <h1>Masuk</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <label>Email atau Username
                <input type="text" name="identifier" value="<?= e($identifier) ?>" required autocomplete="username">
            </label>
            <label>Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button class="btn btn-volt" type="submit">Masuk</button>
        </form>
        <p class="auth-switch">Belum punya akun? <a href="/pacefire/register.php">Daftar</a></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
