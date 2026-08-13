<?php
/**
 * Konfigurasi koneksi database Family Cloud.
 */

function connect()
{
    $host = 'sql113.infinityfree.com';
    $username = 'if0_42598599';
    $password = 'Tasik682009';
    $database = 'if0_42598599_family_cloud';
    $port = 3306;

    $connection = mysqli_connect($host, $username, $password, $database, $port);

    if (!$connection) {
        die('Koneksi database gagal: ' . mysqli_connect_error());
    }

    if (!mysqli_set_charset($connection, 'utf8mb4')) {
        die('Gagal mengatur charset database: ' . mysqli_error($connection));
    }

    return $connection;
}

