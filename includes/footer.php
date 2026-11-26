<?php
?>
</main>
<footer class="site-footer">
    <p>PaceFire &copy; <?= date('Y') ?> <span>Bergerak dengan ritmemu.</span></p>
</footer>
<?php if (is_logged_in()): ?>
</div>
<?php
$mobileNavigation = $_SESSION['role'] === 'admin'
    ? [
        ['label' => 'Dashboard', 'href' => '/pacefire/admin/index.php', 'icon' => $adminNavigation[0]['icon']],
        ['label' => 'Jalur', 'href' => '/pacefire/admin/routes.php', 'icon' => $adminNavigation[1]['icon']],
        ['label' => 'Aktivitas', 'href' => '/pacefire/admin/activities.php', 'icon' => $adminNavigation[3]['icon']],
        ['label' => 'Challenge', 'href' => '/pacefire/admin/challenges.php', 'icon' => $adminNavigation[2]['icon']],
        ['label' => 'Keluar', 'href' => '/pacefire/logout.php', 'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3"/><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg>'],
    ]
    : [
        ['label' => 'Hari ini', 'href' => '/pacefire/dashboard.php', 'icon' => $userNavigation[0]['icon']],
        ['label' => 'Jalur', 'href' => '/pacefire/routes.php', 'icon' => $userNavigation[1]['icon'], 'aliases' => ['route_detail.php']],
        ['label' => 'Catat', 'href' => '/pacefire/activity_create.php', 'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>'],
        ['label' => 'Challenge', 'href' => '/pacefire/challenges.php', 'icon' => $userNavigation[2]['icon'], 'aliases' => ['challenge_detail.php', 'challenge_create.php']],
        ['label' => 'Profil', 'href' => '/pacefire/profile.php', 'icon' => $userNavigation[4]['icon']],
    ];
?>
<nav class="bottom-nav<?= $_SESSION['role'] === 'admin' ? ' bottom-nav-admin' : '' ?>" aria-label="Navigasi cepat">
    <?php foreach ($mobileNavigation as $index => $item): ?>
        <?php
        $isActive = $activeNavigation($item['href'], $item['aliases'] ?? []);
        $isPrimary = $_SESSION['role'] === 'user' && $item['label'] === 'Catat';
        ?>
        <a
            class="<?= $isActive ? 'active' : '' ?><?= $isPrimary ? 'bottom-nav-primary' : '' ?>"
            href="<?= e($item['href']) ?>"
            aria-current="<?= $isActive ? 'page' : 'false' ?>"
            title="<?= e($item['label']) ?>"
        >
            <?= $item['icon'] ?>
            <span><?= e($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
<?php if (is_logged_in()): ?>
</div>
<?php endif; ?>
<script src="/pacefire/assets/js/main.js"></script>
</body>
</html>
