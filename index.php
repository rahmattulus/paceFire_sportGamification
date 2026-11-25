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
        <p class="eyebrow">Gerak yang terasa ringan</p>
        <h1>Mulai hari dengan langkahmu</h1>
        <p>Catat lari atau gowes, jaga ritme, dan rayakan setiap pencapaian dengan PaceFire.</p>
        <?php if (!is_logged_in()): ?>
            <a class="btn btn-volt" href="/pacefire/register.php">Mulai Sekarang</a>
        <?php else: ?>
            <a class="btn btn-volt" href="/pacefire/dashboard.php">Buka Dashboard</a>
        <?php endif; ?>
    </div>
</section>

<section class="feature-grid" aria-label="Fitur PaceFire">
    <article class="panel-card">
        <p class="eyebrow">Catat</p>
        <h2>Aktivitas Nyata</h2>
        <p>Catat lari dan bersepeda dengan validasi jarak serta pace yang wajar.</p>
    </article>
    <article class="panel-card">
        <p class="eyebrow">Konsisten</p>
        <h2>Api Streak</h2>
        <p>Bergerak setiap hari untuk menjaga streak dan membuka bonus tambahan.</p>
    </article>
    <article class="panel-card">
        <p class="eyebrow">Bersaing</p>
        <h2>Challenge & Badge</h2>
        <p>Ikuti challenge komunitas dan kumpulkan lencana pencapaian seumur hidup.</p>
    </article>
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
