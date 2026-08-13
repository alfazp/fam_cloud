<?php
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

function upload_response(bool $success, string $message): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
    ]);
    exit;
}

function generate_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    upload_response(false, 'Metode request tidak valid.');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    upload_response(false, 'File wajib dipilih dan berhasil dikirim.');
}

$file = $_FILES['file'];
$maxSize = 100 * 1024 * 1024;

if ($file['size'] > $maxSize) {
    upload_response(false, 'Ukuran file maksimal 100MB.');
}

$originalName = basename($file['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExtensions = [
    'jpg', 'jpeg', 'png', 'gif',
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'csv',
    'zip', 'rar', '7z',
    'mp3', 'wav', 'mp4', 'mov', 'mkv'
];
$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'text/csv',
    'application/zip',
    'application/x-zip-compressed',
    'application/vnd.rar',
    'application/x-rar-compressed',
    'application/x-7z-compressed',
    'audio/mpeg',
    'audio/wav',
    'audio/x-wav',
    'video/mp4',
    'video/quicktime',
    'video/x-matroska',
    'application/octet-stream',
];

if (!in_array($extension, $allowedExtensions, true)) {
    upload_response(false, 'Extension file tidak diizinkan.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimeTypes, true)) {
    upload_response(false, 'MIME Type file tidak valid.');
}

$userId = (int) $_SESSION['user_id'];
$folderId = null;
$postedFolderId = trim($_POST['folder_id'] ?? '');
$connection = connect();

if ($postedFolderId !== '') {
    $candidateFolderId = (int) $postedFolderId;
    $folderSql = 'SELECT id FROM folders WHERE id = ? AND user_id = ? LIMIT 1';
    $folderStatement = mysqli_prepare($connection, $folderSql);
    mysqli_stmt_bind_param($folderStatement, 'ii', $candidateFolderId, $userId);
    mysqli_stmt_execute($folderStatement);
    $folderResult = mysqli_stmt_get_result($folderStatement);
    $folder = mysqli_fetch_assoc($folderResult);
    mysqli_stmt_close($folderStatement);

    if (!$folder) {
        mysqli_close($connection);
        upload_response(false, 'Folder tidak valid.');
    }

    $folderId = $candidateFolderId;
}

$uploadDirectory = __DIR__ . '/../uploads/' . $userId . '/';

if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
    mysqli_close($connection);
    upload_response(false, 'Folder upload gagal dibuat.');
}

$storageName = generate_uuid() . '.' . $extension;
$destination = $uploadDirectory . $storageName;
$fileSize = (int) $file['size'];

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    mysqli_close($connection);
    upload_response(false, 'File gagal disimpan.');
}

$sql = 'INSERT INTO files (user_id, folder_id, original_name, storage_name, file_extension, mime_type, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';
$statement = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($statement, 'iissssi', $userId, $folderId, $originalName, $storageName, $extension, $mimeType, $fileSize);

if (!mysqli_stmt_execute($statement)) {
    $databaseError = mysqli_error($connection);
    unlink($destination);
    mysqli_stmt_close($statement);
    mysqli_close($connection);
    upload_response(false, 'Gagal menyimpan data file: ' . $databaseError);
}

mysqli_stmt_close($statement);
log_activity($connection, $userId, 'Upload file: ' . $originalName);
mysqli_close($connection);

upload_response(true, 'File berhasil diupload.');
