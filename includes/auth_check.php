<?php

require_once __DIR__ . '/functions.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        redirect('/pacefire/login.php');
    }
}

function require_user(): void
{
    require_login();
    if ($_SESSION['role'] !== 'user') {
        redirect('/pacefire/admin/index.php');
    }
}

function require_admin(): void
{
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        redirect('/pacefire/dashboard.php');
    }
}
