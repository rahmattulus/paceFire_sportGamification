<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_user();

$errors = [];
$success = null;

$userStatement = $pdo->prepare(
    'SELECT nama, username, email, total_poin, current_streak, longest_streak, last_active_date
     FROM users
     WHERE id = ?'
);
$userStatement->execute([$_SESSION['user_id']]);
$user = $userStatement->fetch();

if ($user['last_active_date'] !== null) {
    $displayStreak = $user['last_active_date'] >= date('Y-m-d', strtotime('-1 day'))
        ? (int) $user['current_streak']
        : 0;
} else {
    $displayStreak = 0;
}

$distanceStatement = $pdo->prepare(
    'SELECT jenis_olahraga, COALESCE(SUM(jarak_km), 0) AS total_jarak
     FROM activities
     WHERE user_id = ?
     GROUP BY jenis_olahraga'
);
$distanceStatement->execute([$_SESSION['user_id']]);
$distanceRows = $distanceStatement->fetchAll();
$distance = ['lari' => 0.0, 'sepeda' => 0.0];
foreach ($distanceRows as $row) {
    $distance[$row['jenis_olahraga']] = (float) $row['total_jarak'];
}

$badgeStatement = $pdo->prepare(
    'SELECT b.*, ub.achieved_at
     FROM badges b
     LEFT JOIN user_badges ub
       ON ub.badge_id = b.id AND ub.user_id = ?
     ORDER BY b.id'
);
$badgeStatement->execute([$_SESSION['user_id']]);
$badges = $badgeStatement->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Sesi formulir tidak valid.';
    } else {
        $currentPassword = (string) ($_POST['password_lama'] ?? '');
        $newPassword = (string) ($_POST['password_baru'] ?? '');
        $confirmation = (string) ($_POST['konfirmasi_password'] ?? '');

        $passwordStatement = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $passwordStatement->execute([$_SESSION['user_id']]);
        $passwordHash = $passwordStatement->fetchColumn();

        if (!password_verify($currentPassword, $passwordHash)) {
            $errors[] = 'Password lama tidak sesuai.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'Password baru minimal 8 karakter.';
        } elseif (!hash_equals($newPassword, $confirmation)) {
            $errors[] = 'Konfirmasi password baru tidak sama.';
        } else {
            $pdo->prepare(
                'UPDATE users SET password_hash = ? WHERE id = ?'
            )->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            $success = 'Password berhasil diperbarui.';
        }
    }
}

$pageTitle = 'Profil - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Profil Atlet</p>
    <h1><?= e($user['nama']) ?></h1>
    <p><?= e($user['email']) ?></p>
</section>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>
<?php if ($success): ?>
    <div class="flash flash-success"><?= e($success) ?></div>
<?php endif; ?>

<section class="bento-grid">
    <article class="stat-card streak-ring-card">
        <div class="streak-ring" style="--progress: <?= min(100, ((int) $displayStreak / 30) * 100) ?>%">
            <div class="streak-ring-inner">
                <strong><?= $displayStreak ?></strong>
                <span>Hari</span>
            </div>
        </div>
    </article>
    <article class="stat-card">
        <span class="stat-label">Total Jarak Lari</span>
        <strong class="stat-number"><?= number_format($distance['lari'], 2) ?><small> km</small></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Total Jarak Sepeda</span>
        <strong class="stat-number"><?= number_format($distance['sepeda'], 2) ?><small> km</small></strong>
    </article>
    <article class="stat-card streak-card">
        <span class="stat-label">Streak Terpanjang</span>
        <strong class="stat-number"><?= (int) $user['longest_streak'] ?> Hari</strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Streak Saat Ini</span>
        <strong class="stat-number"><?= $displayStreak ?> Hari</strong>
    </article>
</section>

<section>
    <h2 class="section-title">Etalase Badge</h2>
    <div class="badge-grid">
        <?php foreach ($badges as $badge): ?>
            <?php $unlocked = $badge['achieved_at'] !== null; ?>
            <article class="badge-item <?= $unlocked ? 'unlocked' : 'locked' ?>">
                <div class="badge-hexagon">
                    <span><?= $unlocked ? e(mb_strtoupper(mb_substr($badge['nama_badge'], 0, 1, 'UTF-8'), 'UTF-8')) : '🔒' ?></span>
                </div>
                <h3><?= e($badge['nama_badge']) ?></h3>
                <p><?= e($badge['deskripsi']) ?></p>
                <?php if (!$unlocked): ?>
                    <small>Terkunci - +<?= (int) $badge['bonus_poin'] ?> poin</small>
                <?php else: ?>
                    <small>Terbuka - +<?= (int) $badge['bonus_poin'] ?> poin</small>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel-card password-card">
    <h2>Ganti Password</h2>
    <form method="post" class="stack-form">
        <?= csrf_field() ?>
        <label>Password Lama
            <input type="password" name="password_lama" required autocomplete="current-password">
        </label>
        <label>Password Baru
            <input type="password" name="password_baru" minlength="8" required autocomplete="new-password">
        </label>
        <label>Konfirmasi Password Baru
            <input type="password" name="konfirmasi_password" minlength="8" required autocomplete="new-password">
        </label>
        <button class="btn btn-volt" type="submit">Simpan Password</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
