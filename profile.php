<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_user();

$errors = [];
$success = null;

$userStatement = $pdo->prepare(
    'SELECT nama, username, email, total_poin, current_streak, longest_streak, last_active_date, created_at
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

$activitySummaryStatement = $pdo->prepare(
    'SELECT COUNT(*) AS total_aktivitas,
            COALESCE(SUM(jarak_km), 0) AS total_jarak,
            COALESCE(SUM(durasi_menit), 0) AS total_durasi
     FROM activities
     WHERE user_id = ?'
);
$activitySummaryStatement->execute([$_SESSION['user_id']]);
$activitySummary = $activitySummaryStatement->fetch();

$badgeStatement = $pdo->prepare(
    'SELECT b.*, ub.achieved_at
     FROM badges b
     LEFT JOIN user_badges ub
       ON ub.badge_id = b.id AND ub.user_id = ?
     ORDER BY b.id'
);
$badgeStatement->execute([$_SESSION['user_id']]);
$badges = $badgeStatement->fetchAll();
$unlockedBadgeCount = count(array_filter($badges, static function ($badge) {
    return $badge['achieved_at'] !== null;
}));

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
<section class="profile-hero">
    <span class="profile-avatar-large" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['nama'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
    <div>
        <p class="eyebrow">Profil atlet</p>
        <h1><?= e($user['nama']) ?></h1>
        <p>Bergabung sejak <?= e(date('Y', strtotime($user['created_at']))) ?></p>
    </div>
</section>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>
<?php if ($success): ?>
    <div class="flash flash-success"><?= e($success) ?></div>
<?php endif; ?>

<section class="profile-overview" aria-label="Statistik aktivitas">
    <article class="stat-card">
        <span class="profile-stat-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M3 17 9 11l4 4 8-9"/><path d="M15 6h6v6"/></svg>
        </span>
        <div>
            <span class="stat-label">Total jarak</span>
            <strong class="stat-number"><?= number_format((float) $activitySummary['total_jarak'], 1) ?><small> km</small></strong>
        </div>
    </article>
    <article class="stat-card">
        <span class="profile-stat-icon green" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </span>
        <div>
            <span class="stat-label">Total waktu</span>
            <strong class="stat-number"><?= number_format((float) $activitySummary['total_durasi'] / 60, 1) ?><small> jam</small></strong>
        </div>
    </article>
    <article class="stat-card">
        <span class="profile-stat-icon blue" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 19V9m8 10V4m8 15v-7"/><path d="M2 21h20"/></svg>
        </span>
        <div>
            <span class="stat-label">Aktivitas</span>
            <strong class="stat-number"><?= (int) $activitySummary['total_aktivitas'] ?></strong>
        </div>
    </article>
</section>

<section class="profile-streak">
    <span class="streak-mascot <?= $displayStreak >= 7 ? 'celebrating' : ($displayStreak > 0 ? 'active' : 'resting') ?>" aria-hidden="true">
        <svg viewBox="0 0 64 64">
            <path class="flame-body" d="M34 4c3 13-1 17-8 22C18 31 15 38 16 46c1 9 8 15 17 15s17-7 17-18c0-10-6-19-16-29 1 9-2 12-6 15 1-9-2-17-7-25 2 13-8 17-9 29 6-5 12-9 15-15 4-6 7-10 7-14Z"/>
            <path class="flame-core" d="M34 34c1 5-1 7-4 9-3 2-4 5-3 8 1 4 4 6 8 6s7-3 7-7c0-5-3-9-8-14Z"/>
            <circle class="flame-eye" cx="30" cy="44" r="1.5"/>
            <circle class="flame-eye" cx="38" cy="44" r="1.5"/>
            <path class="flame-smile" d="M31 49c2 2 4 2 6 0"/>
        </svg>
    </span>
    <div>
        <span class="stat-label">Run streak</span>
        <strong><?= $displayStreak ?> hari <span>· Terpanjang <?= (int) $user['longest_streak'] ?> hari</span></strong>
        <p><?= $displayStreak > 0 ? 'Keren, pertahankan ritmemu hari ini.' : 'Streak kembali ke 0. Mulai lagi hari ini.' ?></p>
    </div>
</section>

<section class="profile-badges-section">
    <div class="profile-badges-heading">
        <h2 class="section-title">Badge</h2>
        <p><?= $unlockedBadgeCount ?> dari <?= count($badges) ?> terbuka</p>
    </div>
    <div class="badge-grid profile-badge-grid">
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
        <?php if (!$badges): ?>
            <div class="panel-card empty-state">Badge akan tampil setelah kamu mulai beraktivitas.</div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
