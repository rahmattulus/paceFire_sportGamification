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
$weekDistance = array_sum(array_column($chart, 'distance'));

$todayActivitiesStatement = $pdo->prepare(
    'SELECT a.jenis_olahraga, a.jarak_km, a.durasi_menit, a.created_at, r.nama_jalur
     FROM activities a
     LEFT JOIN routes r ON r.id = a.route_id
     WHERE a.user_id = ? AND a.tanggal = ?
     ORDER BY a.created_at DESC
     LIMIT 5'
);
$todayActivitiesStatement->execute([$_SESSION['user_id'], date('Y-m-d')]);
$todayActivities = $todayActivitiesStatement->fetchAll();

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
    <p class="eyebrow">Hari ini</p>
    <h1>Halo, <?= e($user['nama']) ?></h1>
    <p>Siap bergerak sedikit lebih baik dari kemarin?</p>
</section>

<section class="dashboard-stats" aria-label="Ringkasan progres">
    <article class="stat-card dashboard-stat">
        <span class="stat-icon points-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7L21 9.6l-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/></svg>
        </span>
        <div><span class="stat-label">Total poin</span>
        <strong class="stat-number"><?= number_format((int) $user['total_poin']) ?></strong></div>
    </article>
    <article class="stat-card dashboard-stat streak-card">
        <span class="stat-icon streak-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 22c4.4 0 7-3 7-7 0-3.1-1.6-5.3-4.2-8.5-.2 2.3-1 3.3-2.1 4.2C12.8 7.3 11 4.6 8.5 2 8.8 6.8 5 9.2 5 15c0 4 2.7 7 7 7Z"/><path d="M12 19c1.7 0 2.8-1.2 2.8-2.8 0-1.1-.6-2-1.7-3.2-.2 1-.7 1.4-1.2 1.8-.2-1-.8-1.8-1.7-2.8-.1 2.1-1.1 3.1-1.1 4.4A2.7 2.7 0 0 0 12 19Z"/></svg>
        </span>
        <div><span class="stat-label">Streak</span>
        <strong class="stat-number"><?= $streakState ?><small> hari</small></strong></div>
    </article>
    <article class="stat-card dashboard-stat">
        <span class="stat-icon distance-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 17 10 11l4 4 7-8"/><path d="M15 7h6v6"/></svg>
        </span>
        <div><span class="stat-label">Jarak minggu ini</span>
        <strong class="stat-number"><?= number_format($weekDistance, 1) ?><small> km</small></strong></div>
    </article>
</section>

<section class="dashboard-content">
    <div class="dashboard-main">
        <article class="panel-card chart-card">
            <div class="chart-heading">
                <div>
                    <span class="stat-label">7 hari terakhir</span>
                    <h2>Progres mingguan</h2>
                </div>
                <a class="text-link" href="/pacefire/activity_history.php">Riwayat aktivitas</a>
            </div>
            <canvas id="dashboard-chart" height="280" aria-label="Grafik aktivitas tujuh hari terakhir"></canvas>
            <div id="chart-tooltip" class="chart-tooltip" hidden></div>
            <script id="dashboard-chart-data" type="application/json"><?= json_encode($chart, JSON_UNESCAPED_UNICODE) ?></script>
        </article>
        <article class="panel-card today-panel">
            <div class="panel-heading-row">
                <div><span class="stat-label">Catatan harian</span><h2>Aktivitas hari ini</h2></div>
                <a class="text-link" href="/pacefire/activity_history.php">Lihat semua</a>
            </div>
            <?php if (!$todayActivities): ?>
                <div class="today-empty">
                    <span class="empty-state-icon" aria-hidden="true">↗</span>
                    <p>Belum ada aktivitas hari ini. Mulai dengan langkah kecil.</p>
                    <a class="btn btn-volt" href="/pacefire/activity_create.php">Catat aktivitas</a>
                </div>
            <?php else: ?>
                <ol class="today-timeline">
                    <?php foreach ($todayActivities as $activity): ?>
                        <?php $isRun = $activity['jenis_olahraga'] === 'lari'; ?>
                        <li>
                            <span class="timeline-dot <?= $isRun ? 'run' : 'bike' ?>" aria-hidden="true"></span>
                            <span class="timeline-copy">
                                <strong><?= $isRun ? 'Lari' : 'Sepeda' ?><?= $activity['nama_jalur'] ? ' · ' . e($activity['nama_jalur']) : '' ?></strong>
                                <small><?= number_format((float) $activity['jarak_km'], 2) ?> km · <?= number_format((float) $activity['durasi_menit'], 0) ?> menit</small>
                            </span>
                            <time><?= e(date('H:i', strtotime($activity['created_at']))) ?></time>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </article>
    </div>
    <aside class="dashboard-aside">
        <article class="panel-card challenge-summary">
            <div class="panel-heading-row"><h2>Challenge aktif</h2><a class="text-link" href="/pacefire/challenges.php">Jelajahi</a></div>
            <?php if (!$activeChallenges): ?>
                <p class="empty-state">Belum ada challenge yang diikuti.</p>
                <a class="btn btn-secondary" href="/pacefire/challenges.php">Cari challenge</a>
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
            <div class="panel-heading-row"><h2>Badge terbaru</h2><a class="text-link" href="/pacefire/profile.php">Profil</a></div>
            <?php if (!$recentBadges): ?>
                <p class="empty-state">Badge pertamamu menunggu untuk dibuka.</p>
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
        <article class="panel-card leaderboard-summary">
            <span class="stat-label">Peringkat komunitas</span>
            <strong>#<?= (int) $rank ?></strong>
            <a class="text-link" href="/pacefire/leaderboard.php">Lihat leaderboard</a>
        </article>
    </aside>
</section>
<script src="/pacefire/assets/js/dashboard_chart.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
