<?php
require_once __DIR__ . '/../auth/auth.php';

$page_title = 'Shared With Me';
$current_menu = 'shared';
$userId = (int) $_SESSION['user_id'];
$sharedFiles = [];
$connection = connect();

$sql = 'SELECT files.id, files.user_id AS owner_id, files.original_name, files.file_extension, files.mime_type, files.file_size, file_shares.shared_at, users.full_name AS owner_name
        FROM file_shares
        INNER JOIN files ON files.id = file_shares.file_id
        INNER JOIN users ON users.id = file_shares.owner_id
        WHERE file_shares.shared_to = ? AND files.is_deleted = 0
        ORDER BY file_shares.shared_at DESC';
$statement = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($statement, 'i', $userId);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);

while ($row = mysqli_fetch_assoc($result)) {
    $sharedFiles[] = $row;
}

mysqli_stmt_close($statement);
mysqli_close($connection);

function shared_file_size(int $bytes): string
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

function shared_file_icon(string $extension): string
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

    return 'bi-file-earmark text-muted';
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<main id="content" class="bg-light">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid p-0">
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">Shared With Me</h1>
            <p class="text-muted mb-0">File yang dibagikan oleh anggota keluarga kepada Anda.</p>
        </div>

        <div class="card dashboard-card border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">File</th>
                            <th>Owner</th>
                            <th>Extension</th>
                            <th>Ukuran</th>
                            <th>Dibagikan</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sharedFiles)) : ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada file yang dibagikan.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($sharedFiles as $file) : ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi <?php echo shared_file_icon($file['file_extension'] ?? ''); ?> fs-4"></i>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($file['owner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-uppercase"><?php echo htmlspecialchars($file['file_extension'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo shared_file_size((int) $file['file_size']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($file['shared_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <a class="btn btn-sm btn-outline-success" href="<?php echo BASE_URL; ?>dashboard/download.php?id=<?php echo (int) $file['id']; ?>">Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
