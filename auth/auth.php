<?php
require_once __DIR__ . '/../config/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Halaman yang memakai middleware ini hanya bisa dibuka oleh user yang sudah login.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
