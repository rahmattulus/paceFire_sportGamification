<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_admin();

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$todayActivities = (int) $pdo->query("SELECT COUNT(*) FROM activities WHERE tanggal = CURDATE()")->fetchColumn();
$activeChallenges = (int) $pdo->query("SELECT COUNT(*) FROM challenges WHERE status = 'aktif'")->fetchColumn();

$pageTitle = 'Dashboard Admin - PaceFire';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Administrasi</p>
    <h1>Ringkasan Sistem</h1>
</section>
<section class="bento-grid bento-grid-three">
    <article class="stat-card">
        <span class="stat-label">Total Pengguna</span>
        <strong class="stat-number"><?= $totalUsers ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Aktivitas Hari Ini</span>
        <strong class="stat-number"><?= $todayActivities ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Challenge Aktif</span>
        <strong class="stat-number"><?= $activeChallenges ?></strong>
    </article>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
