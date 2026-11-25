<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$leaders = $pdo->query(
    'SELECT nama, total_poin, current_streak
     FROM users
     WHERE role = \'user\'
     ORDER BY total_poin DESC, nama ASC
     LIMIT 5'
)->fetchAll();

$pageTitle = 'PaceFire - Lari dan Bersepeda';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-panel">
    <div>
        <p class="eyebrow">Gerak. Catat. Menang.</p>
        <h1>Jadikan Setiap Kilometer Bernilai</h1>
        <p>Berkonsistensi, kumpulkan poin, buka badge, dan tantang komunitas PaceFire.</p>
        <?php if (!is_logged_in()): ?>
            <a class="btn btn-volt" href="/pacefire/register.php">Mulai Sekarang</a>
        <?php else: ?>
            <a class="btn btn-volt" href="/pacefire/dashboard.php">Buka Dashboard</a>
        <?php endif; ?>
    </div>
</section>

<section>
    <h2 class="section-title">Top 5 Leaderboard</h2>
    <div class="leaderboard-card">
        <?php if (!$leaders): ?>
            <p class="empty-state">Belum ada pengguna.</p>
        <?php else: ?>
            <?php foreach ($leaders as $index => $leader): ?>
                <div class="leaderboard-row">
                    <strong class="rank"><?= $index + 1 ?></strong>
                    <span><?= e($leader['nama']) ?></span>
                    <span class="points-badge"><?= number_format((int) $leader['total_poin']) ?> poin</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
