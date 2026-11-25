<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_user();

$routeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$routeId) {
    set_flash('error', 'Jalur tidak ditemukan.');
    redirect('/pacefire/routes.php');
}

$routeStatement = $pdo->prepare(
    'SELECT * FROM routes WHERE id = ? AND is_active = 1'
);
$routeStatement->execute([$routeId]);
$route = $routeStatement->fetch();
if (!$route) {
    set_flash('error', 'Jalur tidak ditemukan atau sudah dinonaktifkan.');
    redirect('/pacefire/routes.php');
}

$pbStatement = $pdo->prepare(
    'SELECT MIN(durasi_menit) AS waktu_terbaik
     FROM activities
     WHERE route_id = ? AND user_id = ?'
);
$pbStatement->execute([$routeId, $_SESSION['user_id']]);
$pb = $pbStatement->fetch()['waktu_terbaik'];

$leaderStatement = $pdo->prepare(
    'SELECT u.id, u.nama, MIN(a.durasi_menit) AS waktu_terbaik
     FROM activities a
     JOIN users u ON u.id = a.user_id
     WHERE a.route_id = ?
     GROUP BY u.id, u.nama
     ORDER BY waktu_terbaik ASC
     LIMIT 10'
);
$leaderStatement->execute([$routeId]);
$leaders = $leaderStatement->fetchAll();

$pageTitle = e($route['nama_jalur']) . ' - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow"><?= e(ucfirst($route['jenis_olahraga'])) ?> - <?= e(ucfirst($route['tingkat_kesulitan'])) ?></p>
    <h1><?= e($route['nama_jalur']) ?></h1>
</section>

<section class="route-detail-grid">
    <article class="panel-card route-visual">
        <div class="route-schematic" aria-label="Ilustrasi rute">
            <svg viewBox="0 0 600 260" role="img">
                <path d="M40 220 C120 40, 220 220, 300 120 S480 40, 560 180" fill="none" stroke="#FF6B35" stroke-width="10" stroke-linecap="round"/>
                <circle cx="40" cy="220" r="12" fill="#7E9F4B"/>
                <circle cx="560" cy="180" r="12" fill="#F4877E"/>
            </svg>
        </div>
        <h2>Leaderboard Tercepat</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Peringkat</th><th>Atlet</th><th>Waktu Terbaik</th></tr></thead>
                <tbody>
                <?php foreach ($leaders as $index => $leader): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= e($leader['nama']) ?></td>
                        <td><?= number_format((float) $leader['waktu_terbaik'], 2) ?> menit</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$leaders): ?>
                    <tr><td colspan="3" class="empty-state">Belum ada waktu tercatat.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <aside class="panel-card route-action">
        <span class="stat-label">Jarak Resmi</span>
        <strong class="stat-number"><?= number_format((float) $route['jarak_km'], 2) ?> km</strong>
        <?php if ($route['lokasi']): ?><p><strong>Lokasi:</strong> <?= e($route['lokasi']) ?></p><?php endif; ?>
        <?php if ($route['deskripsi']): ?><p><?= e($route['deskripsi']) ?></p><?php endif; ?>
        <p><strong>Personal Best:</strong>
            <?= $pb !== null ? number_format((float) $pb, 2) . ' menit' : 'Belum ada' ?>
        </p>
        <a class="btn btn-volt" href="/pacefire/activity_create.php?route_id=<?= (int) $route['id'] ?>">Catat Aktivitas di Jalur Ini</a>
    </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
