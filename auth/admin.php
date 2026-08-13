<?php
require_once __DIR__ . '/auth.php';

// Hanya role admin yang boleh masuk halaman admin.
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit;
}
