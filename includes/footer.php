<?php
?>
</main>
<footer class="site-footer">
    <p>PaceFire &copy; <?= date('Y') ?></p>
</footer>
<?php if (is_logged_in()): ?>
<nav class="bottom-nav" aria-label="Navigasi cepat">
    <?php if ($_SESSION['role'] === 'user'): ?>
        <a href="/pacefire/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/><path d="M9 20v-6h6v6"/></svg>
            <span>Today</span>
        </a>
        <a href="/pacefire/activity_create.php" class="<?= basename($_SERVER['PHP_SELF']) === 'activity_create.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
            <span>Log</span>
        </a>
        <a href="/pacefire/routes.php" class="<?= basename($_SERVER['PHP_SELF']) === 'routes.php' || basename($_SERVER['PHP_SELF']) === 'route_detail.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 20c2-5 4-3 6-8s4-5 8-8"/><circle cx="5" cy="20" r="2"/><circle cx="19" cy="4" r="2"/></svg>
            <span>Routes</span>
        </a>
        <a href="/pacefire/challenges.php" class="<?= basename($_SERVER['PHP_SELF']) === 'challenges.php' || basename($_SERVER['PHP_SELF']) === 'challenge_detail.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4v1a4 4 0 0 0 4 4M17 6h3v1a4 4 0 0 1-4 4"/></svg>
            <span>Challenges</span>
        </a>
        <a class="profile-nav <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>" href="/pacefire/profile.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 4-7 8-7s7 2 8 7"/></svg>
            <span>Profile</span>
        </a>
    <?php else: ?>
        <a href="/pacefire/admin/index.php"><span>Today</span></a>
        <a href="/pacefire/admin/routes.php"><span>Routes</span></a>
        <a href="/pacefire/admin/activities.php"><span>Log</span></a>
        <a href="/pacefire/admin/challenges.php"><span>Challenges</span></a>
        <a href="/pacefire/logout.php"><span>Logout</span></a>
    <?php endif; ?>
</nav>
<?php endif; ?>
<script src="/pacefire/assets/js/main.js"></script>
</body>
</html>
