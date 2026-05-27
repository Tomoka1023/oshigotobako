<?php
require_once __DIR__ . '/../app/config.php';
require_once BASE_PATH . '/app/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('/dashboard.php'));
    exit;
}

header('Location: ' . url('/login.php'));
exit;