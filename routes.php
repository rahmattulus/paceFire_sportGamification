<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_user();

$jenis = (string) ($_GET['jenis'] ?? '');
if (!in_array($jenis, ['', 'lari', 'sepeda'], true)) {
    $jenis = '';
}
$search = trim((string) ($_GET['q'] ?? ''));
$conditions = ['is_active = 1'];
$routeParams = [];

if ($jenis !== '') {
    $conditions[] = 'jenis_olahraga = ?';
    $routeParams[] = $jenis;
}
if ($search !== '') {
    $conditions[] = '(nama_jalur LIKE ? OR lokasi LIKE ?)';
    $routeParams[] = '%' . $search . '%';
    $routeParams[] = '%' . $search . '%';
}

$routeStatement = $pdo->prepare(
    'SELECT id, nama_jalur, jenis_olahraga, jarak_km, lokasi, tingkat_kesulitan
     FROM routes
     WHERE ' . implode(' AND ', $conditions) . '
     ORDER BY tingkat_kesulitan, nama_jalur'
);
$routeStatement->execute($routeParams);
$routes = $routeStatement->fetchAll();

$pageTitle = 'Jalur Olahraga - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Eksplorasi Rute</p>
    <div class="page-heading-row">
        <div>
            <h1>Pilih jalur</h1>
            <p>Temukan rute yang pas dengan ritme dan targetmu.</p>
        </div>
    </div>
</section>

<form method="get" class="filter-bar route-filters" role="search">
    <label for="route-search">Cari jalur
        <input id="route-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Nama jalur atau lokasi">
    </label>
    <label for="route-type">Jenis olahraga
        <select id="route-type" name="jenis">
            <option value="">Semua jenis</option>
            <option value="lari" <?= $jenis === 'lari' ? 'selected' : '' ?>>Lari</option>
            <option value="sepeda" <?= $jenis === 'sepeda' ? 'selected' : '' ?>>Sepeda</option>
        </select>
    </label>
    <button class="btn btn-volt" type="submit">Cari jalur</button>
</form>

<nav class="route-categories" aria-label="Filter jenis jalur">
    <a class="<?= $jenis === 'lari' ? 'active' : '' ?>" href="?jenis=lari">
        <span class="route-category-icon run" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="15" cy="5" r="2"/><path d="m12 8 3 2 3 1M9 10l3-2-2 5-4 3m6-3 3 3 2 4m-9-4-3 4"/></svg>
        </span>
        <span><strong>Jalur lari</strong><small>Temukan langkah berikutnya</small></span>
    </a>
    <a class="<?= $jenis === 'sepeda' ? 'active' : '' ?>" href="?jenis=sepeda">
        <span class="route-category-icon bike" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="6" cy="17" r="4"/><circle cx="18" cy="17" r="4"/><path d="m6 17 4-8h4l4 8M9 13h7m-6-4H8"/></svg>
        </span>
        <span><strong>Jalur sepeda</strong><small>Jelajahi lebih jauh</small></span>
    </a>
</nav>

<section class="route-grid">
    <?php foreach ($routes as $route): ?>
        <?php $routeCategory = $route['jenis_olahraga'] === 'lari' ? 'run' : 'bike'; ?>
        <article class="panel-card route-card route-card-<?= $routeCategory ?>">
            <p class="eyebrow"><?= e(ucfirst($route['jenis_olahraga'])) ?> · <?= e(ucfirst($route['tingkat_kesulitan'])) ?></p>
            <h2><?= e($route['nama_jalur']) ?></h2>
            <strong class="stat-number"><?= number_format((float) $route['jarak_km'], 2) ?><small> km</small></strong>
            <?php if ($route['lokasi']): ?>
                <p><?= e($route['lokasi']) ?></p>
            <?php endif; ?>
            <a class="btn btn-secondary" href="/pacefire/route_detail.php?id=<?= (int) $route['id'] ?>">Lihat jalur</a>
        </article>
    <?php endforeach; ?>
    <?php if (!$routes): ?>
        <div class="panel-card empty-state route-empty-state">
            <span class="empty-state-icon" aria-hidden="true">⌖</span>
            <strong>Jalur belum ditemukan</strong>
            <span>Coba ubah kata pencarian atau pilih jenis olahraga lain.</span>
            <a class="btn btn-secondary" href="/pacefire/routes.php">Lihat semua jalur</a>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
