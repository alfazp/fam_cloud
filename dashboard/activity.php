<?php
require_once __DIR__ . '/../auth/auth.php';

$page_title = 'Activity';
$current_menu = 'activity';
$userId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'member';
$logs = [];
$connection = connect();

if ($userRole === 'admin') {
    $sql = 'SELECT activity_logs.activity, activity_logs.ip_address, activity_logs.created_at, users.full_name, users.username
            FROM activity_logs
            INNER JOIN users ON users.id = activity_logs.user_id
            ORDER BY activity_logs.created_at DESC
            LIMIT 100';
    $statement = mysqli_prepare($connection, $sql);
} else {
    $sql = 'SELECT activity_logs.activity, activity_logs.ip_address, activity_logs.created_at, users.full_name, users.username
            FROM activity_logs
            INNER JOIN users ON users.id = activity_logs.user_id
            WHERE activity_logs.user_id = ?
            ORDER BY activity_logs.created_at DESC
            LIMIT 100';
    $statement = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($statement, 'i', $userId);
}

mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);

while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
}

mysqli_stmt_close($statement);
mysqli_close($connection);

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<main id="content" class="bg-light">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid p-0">
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">Activity</h1>
            <p class="text-muted mb-0">
                <?php echo $userRole === 'admin' ? 'Seluruh aktivitas user Family Cloud.' : 'Riwayat aktivitas akun Anda.'; ?>
            </p>
        </div>

        <div class="card dashboard-card border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Activity</th>
                            <th>IP Address</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)) : ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada activity log.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($log['full_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['username'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($log['activity'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
