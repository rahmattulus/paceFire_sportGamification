<?php
require_once __DIR__ . '/auth_check.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$flash = get_flash();
$isAuthenticated = is_logged_in();
$isAdminArea = $isAuthenticated && $_SESSION['role'] === 'admin';
$displayName = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Atlet';
$activeNavigation = static function (string $href, array $aliases = []) use ($currentPage): bool {
    return $currentPage === basename($href) || in_array($currentPage, $aliases, true);
};

$userNavigation = [
    [
        'label' => 'Dashboard',
        'href' => '/pacefire/dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>',
    ],
    [
        'label' => 'Jalur',
        'href' => '/pacefire/routes.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20c2-5 4-3 6-8s4-5 8-8"/><circle cx="5" cy="20" r="2"/><circle cx="19" cy="4" r="2"/></svg>',
        'aliases' => ['route_detail.php'],
    ],
    [
        'label' => 'Challenge',
        'href' => '/pacefire/challenges.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4v1a4 4 0 0 0 4 4M17 6h3v1a4 4 0 0 1-4 4"/></svg>',
        'aliases' => ['challenge_detail.php', 'challenge_create.php'],
    ],
    [
        'label' => 'Peringkat',
        'href' => '/pacefire/leaderboard.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V9m8 10V4m8 15v-7"/><path d="M2 21h20"/></svg>',
    ],
    [
        'label' => 'Profil',
        'href' => '/pacefire/profile.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 4-7 8-7s7 2 8 7"/></svg>',
    ],
];

$adminNavigation = [
    [
        'label' => 'Dashboard',
        'href' => '/pacefire/admin/index.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>',
    ],
    [
        'label' => 'Kelola jalur',
        'href' => '/pacefire/admin/routes.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20c2-5 4-3 6-8s4-5 8-8"/><circle cx="5" cy="20" r="2"/><circle cx="19" cy="4" r="2"/></svg>',
    ],
    [
        'label' => 'Kelola challenge',
        'href' => '/pacefire/admin/challenges.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4v1a4 4 0 0 0 4 4M17 6h3v1a4 4 0 0 1-4 4"/></svg>',
    ],
    [
        'label' => 'Tinjau aktivitas',
        'href' => '/pacefire/admin/activities.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h10"/><circle cx="19" cy="19" r="3"/></svg>',
    ],
];
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
<body class="<?= !empty($authPage) ? 'auth-body' : '' ?><?= $isAdminArea ? ' admin-body' : '' ?>">
<?php if ($isAuthenticated): ?>
<div class="app-layout<?= $isAdminArea ? ' app-layout-admin' : '' ?>">
    <aside class="app-sidebar" aria-label="Navigasi utama">
        <a class="brand" href="<?= $isAdminArea ? '/pacefire/admin/index.php' : '/pacefire/dashboard.php' ?>" aria-label="PaceFire">
            <span class="brand-symbol">P</span>
            <span class="brand-wordmark">Pace<span>Fire</span></span>
        </a>
        <p class="sidebar-caption"><?= $isAdminArea ? 'Ruang admin' : 'Ruang gerakmu' ?></p>
        <?php if (!$isAdminArea): ?>
            <a class="sidebar-log-action" href="/pacefire/activity_create.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
                <span>Catat aktivitas</span>
            </a>
        <?php endif; ?>
        <nav class="sidebar-nav">
            <?php foreach ($isAdminArea ? $adminNavigation : $userNavigation as $item): ?>
                <?php
                $aliases = $item['aliases'] ?? [];
                $isActive = $activeNavigation($item['href'], $aliases);
                ?>
                <a
                    class="<?= $isActive ? 'active' : '' ?>"
                    href="<?= e($item['href']) ?>"
                    aria-current="<?= $isActive ? 'page' : 'false' ?>"
                    title="<?= e($item['label']) ?>"
                >
                    <?= $item['icon'] ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (!$isAdminArea): ?>
                <a
                    class="<?= $currentPage === 'activity_history.php' ? 'active' : '' ?>"
                    href="/pacefire/activity_history.php"
                    aria-current="<?= $currentPage === 'activity_history.php' ? 'page' : 'false' ?>"
                    title="Riwayat aktivitas"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5m4-1v5l3 2"/></svg>
                    <span>Riwayat aktivitas</span>
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-account">
            <a class="sidebar-user" href="<?= $isAdminArea ? '/pacefire/admin/index.php' : '/pacefire/profile.php' ?>" aria-label="<?= e($displayName) ?>, profil">
                <span class="sidebar-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                <span class="sidebar-user-copy">
                    <strong><?= e($displayName) ?></strong>
                    <small><?= $isAdminArea ? 'Administrator' : 'Atlet PaceFire' ?></small>
                </span>
            </a>
            <a class="sidebar-logout" href="/pacefire/logout.php" aria-label="Keluar">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3"/><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg>
                <span>Keluar</span>
            </a>
        </div>
    </aside>
    <div class="app-main">
        <header class="mobile-header">
            <a class="brand" href="<?= $isAdminArea ? '/pacefire/admin/index.php' : '/pacefire/dashboard.php' ?>" aria-label="PaceFire">
                <span class="brand-symbol">P</span>
                <span class="brand-wordmark">Pace<span>Fire</span></span>
            </a>
            <?php if (!$isAdminArea): ?>
                <div class="mobile-header-actions">
                    <a class="mobile-logout" href="/pacefire/logout.php">Keluar</a>
                    <a class="mobile-log-action" href="/pacefire/activity_create.php" aria-label="Catat aktivitas">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
                    </a>
                </div>
            <?php endif; ?>
        </header>
<?php else: ?>
<header class="topbar public-header">
    <a class="brand" href="/pacefire/index.php" aria-label="PaceFire beranda">
        <span class="brand-symbol">P</span>
        <span class="brand-wordmark">Pace<span>Fire</span></span>
    </a>
    <nav class="main-nav" aria-label="Navigasi utama">
        <a href="/pacefire/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Beranda</a>
        <a href="/pacefire/login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">Masuk</a>
        <a class="btn btn-small" href="/pacefire/register.php">Daftar</a>
    </nav>
</header>
<?php endif; ?>
<main class="site-shell">
<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>
