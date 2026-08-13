<?php
/**
 * includes/header.php
 * 
 * Template Header HTML global.
 * Memuat file meta, Bootstrap 5 CSS, Bootstrap Icons, dan stylesheet kustom.
 */

// Memastikan file config dimuat jika belum dimuat secara manual di halaman utama
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5.3.x CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons (CDN) untuk mendukung visualisasi file/folder -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    
    <!-- Wrapper utama untuk Sidebar dan Konten Utama -->
    <div id="wrapper">
