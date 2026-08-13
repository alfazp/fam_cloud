<?php
require_once __DIR__ . '/../auth/auth.php';

$fileId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if ($fileId <= 0) {
    header('Location: ' . BASE_URL . 'dashboard/files.php');
    exit;
}

$connection = connect();
$sql = 'SELECT files.user_id AS owner_id, files.original_name, files.storage_name, files.mime_type, files.file_size
        FROM files
        LEFT JOIN file_shares ON file_shares.file_id = files.id AND file_shares.shared_to = ?
        WHERE files.id = ? AND files.is_deleted = 0 AND (files.user_id = ? OR file_shares.shared_to = ?)
        LIMIT 1';
$statement = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($statement, 'iiii', $userId, $fileId, $userId, $userId);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);
$file = mysqli_fetch_assoc($result);
mysqli_stmt_close($statement);

if (!$file) {
    mysqli_close($connection);
    header('Location: ' . BASE_URL . 'dashboard/files.php');
    exit;
}

$path = __DIR__ . '/../uploads/' . (int) $file['owner_id'] . '/' . $file['storage_name'];

if (!is_file($path)) {
    mysqli_close($connection);
    header('Location: ' . BASE_URL . 'dashboard/files.php');
    exit;
}

log_activity($connection, $userId, 'Download file: ' . $file['original_name']);
mysqli_close($connection);

header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . $file['file_size']);
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
