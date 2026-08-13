<?php
require_once __DIR__ . '/../auth/auth.php';

$page_title = 'My Files';
$current_menu = 'files';
$userId = (int) $_SESSION['user_id'];
$connection = connect();
$alert = $_SESSION['files_alert'] ?? null;
unset($_SESSION['files_alert']);

function set_files_alert(string $type, string $message): void
{
    $_SESSION['files_alert'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function redirect_files(): void
{
    header('Location: ' . BASE_URL . 'dashboard/files.php');
    exit;
}

function format_file_size(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }

    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' B';
}

function file_icon_class(string $extension): string
{
    $extension = strtolower($extension);

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
        return 'bi-file-earmark-image text-primary';
    }

    if ($extension === 'pdf') {
        return 'bi-file-earmark-pdf text-danger';
    }

    if (in_array($extension, ['doc', 'docx'], true)) {
        return 'bi-file-earmark-word text-info';
    }

    if (in_array($extension, ['xls', 'xlsx'], true)) {
        return 'bi-file-earmark-spreadsheet text-success';
    }

    if (in_array($extension, ['ppt', 'pptx'], true)) {
        return 'bi-file-earmark-slides text-warning';
    }

    if ($extension === 'zip') {
        return 'bi-file-earmark-zip text-secondary';
    }

    return 'bi-file-earmark text-muted';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $fileId = (int) ($_POST['file_id'] ?? 0);

    if ($action === 'rename') {
        $newName = trim($_POST['original_name'] ?? '');

        if ($fileId <= 0 || $newName === '') {
            set_files_alert('danger', 'Nama file baru wajib diisi.');
            redirect_files();
        }

        $oldNameSql = 'SELECT original_name FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0 LIMIT 1';
        $oldNameStatement = mysqli_prepare($connection, $oldNameSql);
        mysqli_stmt_bind_param($oldNameStatement, 'ii', $fileId, $userId);
        mysqli_stmt_execute($oldNameStatement);
        $oldNameResult = mysqli_stmt_get_result($oldNameStatement);
        $oldFile = mysqli_fetch_assoc($oldNameResult);
        mysqli_stmt_close($oldNameStatement);

        if (!$oldFile) {
            set_files_alert('danger', 'File tidak ditemukan.');
            redirect_files();
        }

        $sql = 'UPDATE files SET original_name = ? WHERE id = ? AND user_id = ? AND is_deleted = 0';
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'sii', $newName, $fileId, $userId);

        if (mysqli_stmt_execute($statement)) {
            log_activity($connection, $userId, 'Rename file: ' . $oldFile['original_name'] . ' menjadi ' . $newName);
            set_files_alert('success', 'File berhasil di-rename.');
        } else {
            set_files_alert('danger', 'Gagal rename file: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($statement);
        redirect_files();
    }

    if ($action === 'delete') {
        if ($fileId <= 0) {
            set_files_alert('danger', 'File tidak valid.');
            redirect_files();
        }

        $selectSql = 'SELECT original_name, storage_name FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0 LIMIT 1';
        $selectStatement = mysqli_prepare($connection, $selectSql);
        mysqli_stmt_bind_param($selectStatement, 'ii', $fileId, $userId);
        mysqli_stmt_execute($selectStatement);
        $selectResult = mysqli_stmt_get_result($selectStatement);
        $file = mysqli_fetch_assoc($selectResult);
        mysqli_stmt_close($selectStatement);

        if (!$file) {
            set_files_alert('danger', 'File tidak ditemukan.');
            redirect_files();
        }

        $deleteSql = 'UPDATE files SET is_deleted = 1 WHERE id = ? AND user_id = ?';
        $deleteStatement = mysqli_prepare($connection, $deleteSql);
        mysqli_stmt_bind_param($deleteStatement, 'ii', $fileId, $userId);

        if (mysqli_stmt_execute($deleteStatement)) {
            $path = __DIR__ . '/../uploads/' . $userId . '/' . $file['storage_name'];
            if (is_file($path)) {
                unlink($path);
            }
            log_activity($connection, $userId, 'Delete file: ' . $file['original_name']);
            set_files_alert('success', 'File berhasil dihapus.');
        } else {
            set_files_alert('danger', 'Gagal menghapus file: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($deleteStatement);
        redirect_files();
    }

    if ($action === 'share') {
        $sharedTo = (int) ($_POST['shared_to'] ?? 0);

        if ($fileId <= 0 || $sharedTo <= 0 || $sharedTo === $userId) {
            set_files_alert('danger', 'User tujuan share tidak valid.');
            redirect_files();
        }

        $fileSql = 'SELECT id, original_name FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0 LIMIT 1';
        $fileStatement = mysqli_prepare($connection, $fileSql);
        mysqli_stmt_bind_param($fileStatement, 'ii', $fileId, $userId);
        mysqli_stmt_execute($fileStatement);
        $fileResult = mysqli_stmt_get_result($fileStatement);
        $ownedFile = mysqli_fetch_assoc($fileResult);
        mysqli_stmt_close($fileStatement);

        if (!$ownedFile) {
            set_files_alert('danger', 'File tidak ditemukan atau bukan milik Anda.');
            redirect_files();
        }

        $userSql = 'SELECT id, full_name FROM users WHERE id = ? LIMIT 1';
        $userStatement = mysqli_prepare($connection, $userSql);
        mysqli_stmt_bind_param($userStatement, 'i', $sharedTo);
        mysqli_stmt_execute($userStatement);
        $userResult = mysqli_stmt_get_result($userStatement);
        $targetUser = mysqli_fetch_assoc($userResult);
        mysqli_stmt_close($userStatement);

        if (!$targetUser) {
            set_files_alert('danger', 'User tujuan tidak ditemukan.');
            redirect_files();
        }

        $checkSql = 'SELECT id FROM file_shares WHERE file_id = ? AND owner_id = ? AND shared_to = ? LIMIT 1';
        $checkStatement = mysqli_prepare($connection, $checkSql);
        mysqli_stmt_bind_param($checkStatement, 'iii', $fileId, $userId, $sharedTo);
        mysqli_stmt_execute($checkStatement);
        $checkResult = mysqli_stmt_get_result($checkStatement);
        $existingShare = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStatement);

        if ($existingShare) {
            set_files_alert('info', 'File sudah pernah dibagikan ke user tersebut.');
            redirect_files();
        }

        $shareSql = 'INSERT INTO file_shares (file_id, owner_id, shared_to, can_edit) VALUES (?, ?, ?, 0)';
        $shareStatement = mysqli_prepare($connection, $shareSql);
        mysqli_stmt_bind_param($shareStatement, 'iii', $fileId, $userId, $sharedTo);

        if (mysqli_stmt_execute($shareStatement)) {
            log_activity($connection, $userId, 'Share file: ' . $ownedFile['original_name'] . ' ke ' . $targetUser['full_name']);
            set_files_alert('success', 'File berhasil dibagikan.');
        } else {
            set_files_alert('danger', 'Gagal membagikan file: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($shareStatement);
        redirect_files();
    }
}

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'uploaded_at';
$direction = strtolower($_GET['direction'] ?? 'desc');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$allowedSorts = [
    'name' => 'original_name',
    'size' => 'file_size',
    'uploaded_at' => 'uploaded_at',
    'extension' => 'file_extension',
];

$sortColumn = $allowedSorts[$sort] ?? 'uploaded_at';
$direction = $direction === 'asc' ? 'asc' : 'desc';
$searchLike = '%' . $search . '%';

$countSql = 'SELECT COUNT(id) AS total FROM files WHERE user_id = ? AND is_deleted = 0';
if ($search !== '') {
    $countSql .= ' AND original_name LIKE ?';
}

$countStatement = mysqli_prepare($connection, $countSql);
if ($search !== '') {
    mysqli_stmt_bind_param($countStatement, 'is', $userId, $searchLike);
} else {
    mysqli_stmt_bind_param($countStatement, 'i', $userId);
}
mysqli_stmt_execute($countStatement);
$countResult = mysqli_stmt_get_result($countStatement);
$totalRows = (int) (mysqli_fetch_assoc($countResult)['total'] ?? 0);
mysqli_stmt_close($countStatement);

$totalPages = max(1, (int) ceil($totalRows / $perPage));

$filesSql = "SELECT id, original_name, storage_name, file_extension, mime_type, file_size, uploaded_at FROM files WHERE user_id = ? AND is_deleted = 0";
if ($search !== '') {
    $filesSql .= ' AND original_name LIKE ?';
}
$filesSql .= " ORDER BY {$sortColumn} {$direction} LIMIT ? OFFSET ?";

$filesStatement = mysqli_prepare($connection, $filesSql);
if ($search !== '') {
    mysqli_stmt_bind_param($filesStatement, 'isii', $userId, $searchLike, $perPage, $offset);
} else {
    mysqli_stmt_bind_param($filesStatement, 'iii', $userId, $perPage, $offset);
}
mysqli_stmt_execute($filesStatement);
$filesResult = mysqli_stmt_get_result($filesStatement);
$files = [];

while ($row = mysqli_fetch_assoc($filesResult)) {
    $files[] = $row;
}

mysqli_stmt_close($filesStatement);

$shareUsers = [];
$usersSql = 'SELECT id, full_name, username FROM users WHERE id != ? ORDER BY full_name ASC';
$usersStatement = mysqli_prepare($connection, $usersSql);
mysqli_stmt_bind_param($usersStatement, 'i', $userId);
mysqli_stmt_execute($usersStatement);
$usersResult = mysqli_stmt_get_result($usersStatement);

while ($row = mysqli_fetch_assoc($usersResult)) {
    $shareUsers[] = $row;
}

mysqli_stmt_close($usersStatement);
mysqli_close($connection);

$toggleDirection = $direction === 'asc' ? 'desc' : 'asc';

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<main id="content" class="bg-light">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid p-0">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">My Files</h1>
                <p class="text-muted mb-0">File pribadi milik akun Anda.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>dashboard/upload.php" class="btn btn-primary align-self-start align-self-md-center">
                <i class="bi bi-cloud-arrow-up me-1"></i> Upload
            </a>
        </div>

        <?php if ($alert) : ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card dashboard-card border-0">
            <div class="card-body border-bottom">
                <form class="row g-2 align-items-center" method="get">
                    <div class="col-12 col-md">
                        <input type="search" class="form-control" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search file...">
                    </div>
                    <div class="col-6 col-md-3">
                        <select class="form-select" name="sort">
                            <option value="uploaded_at" <?php echo $sort === 'uploaded_at' ? 'selected' : ''; ?>>Tanggal Upload</option>
                            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Nama</option>
                            <option value="size" <?php echo $sort === 'size' ? 'selected' : ''; ?>>Ukuran</option>
                            <option value="extension" <?php echo $sort === 'extension' ? 'selected' : ''; ?>>Extension</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select class="form-select" name="direction">
                            <option value="desc" <?php echo $direction === 'desc' ? 'selected' : ''; ?>>Desc</option>
                            <option value="asc" <?php echo $direction === 'asc' ? 'selected' : ''; ?>>Asc</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button class="btn btn-outline-primary w-100" type="submit">Terapkan</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">File</th>
                            <th>Extension</th>
                            <th>MIME Type</th>
                            <th>Ukuran</th>
                            <th>Upload Date</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($files)) : ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">File tidak ditemukan.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($files as $file) : ?>
                            <?php $fileId = (int) $file['id']; ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi <?php echo file_icon_class($file['file_extension'] ?? ''); ?> fs-4"></i>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td class="text-uppercase"><?php echo htmlspecialchars($file['file_extension'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($file['mime_type'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo format_file_size((int) $file['file_size']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($file['uploaded_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#renameModal<?php echo $fileId; ?>">Rename</button>
                                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#shareModal<?php echo $fileId; ?>">Share</button>
                                    <a class="btn btn-sm btn-outline-success" href="<?php echo BASE_URL; ?>dashboard/download.php?id=<?php echo $fileId; ?>">Download</a>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $fileId; ?>">Delete</button>

                                    <div class="modal fade text-start" id="renameModal<?php echo $fileId; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form class="modal-content" method="post">
                                                <input type="hidden" name="action" value="rename">
                                                <input type="hidden" name="file_id" value="<?php echo $fileId; ?>">
                                                <div class="modal-header">
                                                    <h2 class="modal-title h5">Rename File</h2>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <label class="form-label" for="rename<?php echo $fileId; ?>">Nama File</label>
                                                    <input class="form-control" id="rename<?php echo $fileId; ?>" name="original_name" value="<?php echo htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="modal fade text-start" id="shareModal<?php echo $fileId; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form class="modal-content" method="post">
                                                <input type="hidden" name="action" value="share">
                                                <input type="hidden" name="file_id" value="<?php echo $fileId; ?>">
                                                <div class="modal-header">
                                                    <h2 class="modal-title h5">Share File</h2>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-muted">Pilih user tujuan untuk file <strong><?php echo htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
                                                    <label class="form-label" for="shareTo<?php echo $fileId; ?>">User Tujuan</label>
                                                    <select class="form-select" id="shareTo<?php echo $fileId; ?>" name="shared_to" required>
                                                        <option value="">Pilih user</option>
                                                        <?php foreach ($shareUsers as $shareUser) : ?>
                                                            <option value="<?php echo (int) $shareUser['id']; ?>">
                                                                <?php echo htmlspecialchars($shareUser['full_name'] . ' (' . $shareUser['username'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-info text-white">Share</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="modal fade text-start" id="deleteModal<?php echo $fileId; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form class="modal-content" method="post">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="file_id" value="<?php echo $fileId; ?>">
                                                <div class="modal-header">
                                                    <h2 class="modal-title h5">Delete File</h2>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="mb-0">Hapus file <strong><?php echo htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8'); ?></strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white border-0">
                <nav aria-label="Pagination file">
                    <ul class="pagination justify-content-end mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo BASE_URL; ?>dashboard/files.php?search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>&direction=<?php echo urlencode($direction); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
