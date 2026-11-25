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
