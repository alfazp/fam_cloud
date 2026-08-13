<?php
/**
 * config/config.php
 * 
 * Konfigurasi global aplikasi.
 * File ini bertindak sebagai bootstrapper utama, menginisialisasi konstanta global,
 * path, dynamic base URL, serta memuat file database dan session helper secara otomatis.
 */

// Nama Aplikasi
define('APP_NAME', 'Family Cloud');

// Path Fisik Folder Root Server
define('BASE_PATH', dirname(__DIR__) . '/');

// Menghitung Base URL secara dinamis agar link absolut selalu tepat (di XAMPP maupun Production)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = preg_replace('/[^A-Za-z0-9\.\-:]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');

// Deteksi base URL untuk localhost subfolder dan hosting root.
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if ($host === 'cloudalfa.ct.ws' || str_ends_with($host, '.ct.ws')) {
    define('BASE_URL', $protocol . '://' . $host . '/');
} elseif (strpos($scriptName, '/family_drive/') !== false) {
    define('BASE_URL', $protocol . '://' . $host . '/family_drive/');
} else {
    define('BASE_URL', $protocol . '://' . $host . '/');
}

// Muat koneksi database & session helper secara otomatis
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'includes/session.php';
require_once BASE_PATH . 'includes/activity.php';
?>
