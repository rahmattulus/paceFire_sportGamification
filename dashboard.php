<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_user();
require_once __DIR__ . '/includes/gamification.php';

sync_expired_challenges($pdo);

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

$chart = [];
for ($offset = 6; $offset >= 0; $offset--) {
    $date = date('Y-m-d', strtotime("-{$offset} days"));
    $activityStatement = $pdo->prepare(
        'SELECT COALESCE(SUM(jarak_km), 0) AS jarak,
                COALESCE(SUM(poin_total), 0) AS poin
         FROM activities
         WHERE user_id = ? AND tanggal = ?'
    );
    $activityStatement->execute([$_SESSION['user_id'], $date]);
    $activityData = $activityStatement->fetch();
    $chart[] = [
        'date' => $date,
        'label' => date('D', strtotime($date)),
        'distance' => (float) $activityData['jarak'],
        'points' => (int) $activityData['poin'],
    ];
}

$challengeStatement = $pdo->prepare(
    'SELECT c.id, c.judul, c.tipe_target, c.nilai_target
     FROM challenges c
     JOIN challenge_participants cp ON cp.challenge_id = c.id
     WHERE cp.user_id = ? AND c.status = \'aktif\'
     ORDER BY c.tanggal_selesai ASC
     LIMIT 3'
);
$challengeStatement->execute([$_SESSION['user_id']]);
$activeChallenges = $challengeStatement->fetchAll();
foreach ($activeChallenges as &$activeChallenge) {
    $activeChallenge['score'] = challenge_participant_score(
        $pdo,
        (int) $activeChallenge['id'],
        $_SESSION['user_id']
    );
}
unset($activeChallenge);

$badgeStatement = $pdo->prepare(
    'SELECT b.nama_badge, b.deskripsi, b.bonus_poin, ub.achieved_at
     FROM user_badges ub
     JOIN badges b ON b.id = ub.badge_id
     WHERE ub.user_id = ?
     ORDER BY ub.achieved_at DESC
     LIMIT 3'
);
$badgeStatement->execute([$_SESSION['user_id']]);
$recentBadges = $badgeStatement->fetchAll();

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
    <article class="panel-card chart-card">
        <div class="chart-heading">
            <div>
                <span class="stat-label">Aktivitas 7 Hari</span>
                <h2>Progres Mingguan</h2>
            </div>
        </div>
        <canvas id="dashboard-chart" height="280" aria-label="Grafik aktivitas tujuh hari terakhir"></canvas>
        <div id="chart-tooltip" class="chart-tooltip" hidden></div>
        <script id="dashboard-chart-data" type="application/json"><?= json_encode($chart, JSON_UNESCAPED_UNICODE) ?></script>
    </article>
    <article class="panel-card challenge-summary">
        <h2>Challenge Aktif</h2>
        <?php if (!$activeChallenges): ?>
            <p class="empty-state">Belum ada challenge yang diikuti.</p>
        <?php else: ?>
            <?php foreach ($activeChallenges as $activeChallenge): ?>
                <?php
                $progress = $activeChallenge['nilai_target'] > 0
                    ? min(100, round(($activeChallenge['score'] / (float) $activeChallenge['nilai_target']) * 100))
                    : 0;
                ?>
                <a class="challenge-summary-item" href="/pacefire/challenge_detail.php?id=<?= (int) $activeChallenge['id'] ?>">
                    <strong><?= e($activeChallenge['judul']) ?></strong>
                    <div class="progress-track"><span style="width: <?= $progress ?>%"></span></div>
                    <small><?= number_format($activeChallenge['score'], 2) ?> / <?= number_format((float) $activeChallenge['nilai_target'], 2) ?></small>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </article>
    <article class="panel-card recent-badges">
        <h2>Badge Terbaru</h2>
        <?php if (!$recentBadges): ?>
            <p class="empty-state">Belum ada badge terbuka.</p>
        <?php else: ?>
            <div class="badge-mini-grid">
                <?php foreach ($recentBadges as $badge): ?>
                    <div class="badge-mini unlocked">
                        <span><?= e(mb_strtoupper(mb_substr($badge['nama_badge'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                        <small><?= e($badge['nama_badge']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <article class="panel-card">
        <h2>Mulai Aktivitas</h2>
        <p>Catat lari atau bersepeda untuk mengumpulkan poin dan membuka badge.</p>
        <a class="btn btn-volt" href="/pacefire/activity_create.php">Catat Aktivitas</a>
        <a class="btn btn-secondary" href="/pacefire/routes.php">Jelajahi Jalur</a>
        <a class="btn btn-secondary" href="/pacefire/challenges.php">Lihat Challenge</a>
    </article>
</section>
<script src="/pacefire/assets/js/dashboard_chart.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
