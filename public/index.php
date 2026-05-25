<?php
require_once __DIR__ . '/../app/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

header('Location: ' . BASE_URL . '/login.php');
exit;