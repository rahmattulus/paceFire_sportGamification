<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_user();

$routes = $pdo->query(
    'SELECT id, nama_jalur, jenis_olahraga, jarak_km, lokasi, tingkat_kesulitan
     FROM routes
     WHERE is_active = 1
     ORDER BY tingkat_kesulitan, nama_jalur'
)->fetchAll();

$pageTitle = 'Jalur Olahraga - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Eksplorasi Rute</p>
    <h1>Pilih Jalur</h1>
</section>

<section class="route-grid">
    <?php foreach ($routes as $route): ?>
        <article class="panel-card route-card">
            <p class="eyebrow"><?= e(ucfirst($route['jenis_olahraga'])) ?> - <?= e(ucfirst($route['tingkat_kesulitan'])) ?></p>
            <h2><?= e($route['nama_jalur']) ?></h2>
            <strong class="stat-number"><?= number_format((float) $route['jarak_km'], 2) ?><small> km</small></strong>
            <?php if ($route['lokasi']): ?>
                <p><?= e($route['lokasi']) ?></p>
            <?php endif; ?>
            <a class="btn btn-secondary" href="/pacefire/route_detail.php?id=<?= (int) $route['id'] ?>">Lihat Detail</a>
        </article>
    <?php endforeach; ?>
    <?php if (!$routes): ?>
        <div class="panel-card empty-state">Belum ada jalur aktif.</div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
