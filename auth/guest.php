<?php
require_once __DIR__ . '/../config/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Halaman guest seperti login tidak perlu dibuka lagi setelah user berhasil login.
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $redirectPath = $_SESSION['role'] === 'admin' ? '../dashboard/admin.php' : '../dashboard/index.php';
    header('Location: ' . $redirectPath);
    exit;
}
