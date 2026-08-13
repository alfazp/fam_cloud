<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$redirectPath = $_SESSION['role'] === 'admin' ? 'dashboard/admin.php' : 'dashboard/index.php';
header('Location: ' . BASE_URL . $redirectPath);
exit;
