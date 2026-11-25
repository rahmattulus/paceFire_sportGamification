<?php
require_once __DIR__ . '/auth_check.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$flash = get_flash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'PaceFire') ?></title>
    <link rel="stylesheet" href="/pacefire/assets/css/style.css">
    <?php if (!empty($authPage)): ?>
        <link rel="stylesheet" href="/pacefire/assets/css/auth.css">
    <?php endif; ?>
</head>
<body>
<header class="topbar">
    <a class="brand" href="/pacefire/index.php" aria-label="PaceFire beranda">
        <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
            <circle cx="24" cy="24" r="21" fill="#FFD25C"/>
            <path d="M17 33c-5-5-5-12 0-17 5-5 12-5 17 0-7 1-11 5-12 10-1 4 0 8 3 11-4 0-7-1-8-4Z" fill="#FF6B35"/>
            <path d="M27 15c-2 5-2 10 2 14 3 3 7 4 10 2-2-6-6-12-12-16Z" fill="#F4877E" opacity=".88"/>
            <circle cx="18" cy="18" r="3" fill="#FFF7E7"/>
        </svg>
        <span class="brand-wordmark">Pace<span>Fire</span></span>
    </a>
    <nav class="main-nav" aria-label="Navigasi utama">
        <?php if (is_logged_in()): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="/pacefire/admin/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Admin</a>
                <a href="/pacefire/admin/routes.php">Jalur</a>
                <a href="/pacefire/admin/activities.php">Aktivitas</a>
                <a href="/pacefire/admin/challenges.php">Challenge</a>
            <?php else: ?>
                <a href="/pacefire/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="/pacefire/activity_create.php">Catat</a>
                <a href="/pacefire/activity_history.php">Riwayat</a>
                <a href="/pacefire/routes.php">Jalur</a>
                <a href="/pacefire/challenges.php">Challenge</a>
                <a href="/pacefire/leaderboard.php">Leaderboard</a>
                <a href="/pacefire/profile.php">Profil</a>
            <?php endif; ?>
            <a class="nav-logout" href="/pacefire/logout.php">Keluar</a>
        <?php else: ?>
            <a href="/pacefire/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Beranda</a>
            <a href="/pacefire/login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">Masuk</a>
            <a class="btn btn-volt btn-small" href="/pacefire/register.php">Daftar</a>
        <?php endif; ?>
    </nav>
</header>
<main class="site-shell">
<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>
