<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_user();

$statement = $pdo->prepare(
    'SELECT nama, total_poin, current_streak, longest_streak, last_active_date
     FROM users WHERE id = ?'
);
$statement->execute([$_SESSION['user_id']]);
$user = $statement->fetch();

if ($user['last_active_date'] !== null) {
    $streakState = $user['last_active_date'] >= date('Y-m-d', strtotime('-1 day'))
        ? (int) $user['current_streak']
        : 0;
} else {
    $streakState = 0;
}

$rankStatement = $pdo->prepare(
    'SELECT COUNT(*) + 1 AS peringkat
     FROM users
     WHERE role = \'user\' AND total_poin > ?'
);
$rankStatement->execute([$user['total_poin']]);
$rank = $rankStatement->fetch()['peringkat'];

$pageTitle = 'Dashboard - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Dashboard Atlet</p>
    <h1>Halo, <?= e($user['nama']) ?></h1>
</section>

<section class="bento-grid">
    <article class="stat-card">
        <span class="stat-label">Total Poin</span>
        <strong class="stat-number"><?= number_format((int) $user['total_poin']) ?></strong>
    </article>
    <article class="stat-card streak-card">
        <span class="stat-label">Daily Streak</span>
        <strong class="stat-number"><?= $streakState ?> Hari</strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Peringkat Global</span>
        <strong class="stat-number">#<?= (int) $rank ?></strong>
    </article>
    <article class="panel-card">
        <h2>Mulai Aktivitas</h2>
        <p>Catat lari atau bersepeda untuk mengumpulkan poin dan membuka badge.</p>
        <a class="btn btn-volt" href="/pacefire/activity_create.php">Catat Aktivitas</a>
        <a class="btn btn-secondary" href="/pacefire/routes.php">Jelajahi Jalur</a>
    </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
