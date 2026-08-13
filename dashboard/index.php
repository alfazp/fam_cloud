<?php
require_once __DIR__ . '/../auth/auth.php';

$page_title = 'Dashboard';
$current_menu = 'dashboard';
$userId = (int) $_SESSION['user_id'];
$totalFiles = 0;
$storageUsed = 0;
$recentUploads = [];

$connection = connect();

$summarySql = 'SELECT COUNT(id) AS total_files, COALESCE(SUM(file_size), 0) AS storage_used FROM files WHERE user_id = ? AND is_deleted = 0';
$summaryStatement = mysqli_prepare($connection, $summarySql);
mysqli_stmt_bind_param($summaryStatement, 'i', $userId);
mysqli_stmt_execute($summaryStatement);
$summaryResult = mysqli_stmt_get_result($summaryStatement);
$summary = mysqli_fetch_assoc($summaryResult);
mysqli_stmt_close($summaryStatement);

if ($summary) {
    $totalFiles = (int) $summary['total_files'];
    $storageUsed = (int) $summary['storage_used'];
}

$recentSql = 'SELECT original_name, file_extension, file_size, uploaded_at FROM files WHERE user_id = ? AND is_deleted = 0 ORDER BY uploaded_at DESC LIMIT 5';
$recentStatement = mysqli_prepare($connection, $recentSql);
mysqli_stmt_bind_param($recentStatement, 'i', $userId);
mysqli_stmt_execute($recentStatement);
$recentResult = mysqli_stmt_get_result($recentStatement);

while ($row = mysqli_fetch_assoc($recentResult)) {
    $recentUploads[] = $row;
}

mysqli_stmt_close($recentStatement);
mysqli_close($connection);

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

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<main id="content" class="bg-light">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid p-0">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Dashboard</h1>
                <p class="text-muted mb-0">Ringkasan file keluarga Anda.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>dashboard/upload.php" class="btn btn-primary align-self-start align-self-md-center">
                <i class="bi bi-cloud-arrow-up me-1"></i> Upload
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card dashboard-card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Total File</p>
                                <h2 class="fw-bold mb-0"><?php echo number_format($totalFiles); ?></h2>
                            </div>
                            <div class="dashboard-icon bg-primary-subtle text-primary">
                                <i class="bi bi-files"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card dashboard-card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Storage Digunakan</p>
                                <h2 class="fw-bold mb-0"><?php echo format_file_size($storageUsed); ?></h2>
                            </div>
                            <div class="dashboard-icon bg-success-subtle text-success">
                                <i class="bi bi-hdd"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card dashboard-card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Recent Upload</p>
                                <h2 class="fw-bold mb-0"><?php echo count($recentUploads); ?></h2>
                            </div>
                            <div class="dashboard-icon bg-info-subtle text-info">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card dashboard-card border-0">
            <div class="card-header bg-white border-0 py-3">
                <h2 class="h5 fw-bold mb-0">Recent Upload</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nama File</th>
                            <th>Tipe</th>
                            <th>Ukuran</th>
                            <th>Tanggal Upload</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentUploads)) : ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada upload.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($recentUploads as $file) : ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-uppercase"><?php echo htmlspecialchars($file['file_extension'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo format_file_size((int) $file['file_size']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($file['uploaded_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
