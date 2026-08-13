<?php
require_once __DIR__ . '/../auth/admin.php';

$page_title = 'User Management';
$current_menu = 'users';
$connection = connect();
$alert = $_SESSION['user_management_alert'] ?? null;
unset($_SESSION['user_management_alert']);

function redirect_users(): void
{
    header('Location: ' . BASE_URL . 'admin/users.php');
    exit;
}

function set_user_alert(string $type, string $message): void
{
    $_SESSION['user_management_alert'] = [
        'type' => $type,
        'message' => $message,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'member';

        if ($fullName === '' || $username === '' || $password === '' || !in_array($role, ['admin', 'member'], true)) {
            set_user_alert('danger', 'Data user baru belum lengkap.');
            redirect_users();
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = 'INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)';
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'ssss', $fullName, $username, $passwordHash, $role);

        if (mysqli_stmt_execute($statement)) {
            set_user_alert('success', 'User berhasil ditambahkan.');
        } else {
            set_user_alert('danger', 'Gagal menambahkan user: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($statement);
        redirect_users();
    }

    if ($action === 'update') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'member';

        if ($userId <= 0 || $fullName === '' || $username === '' || !in_array($role, ['admin', 'member'], true)) {
            set_user_alert('danger', 'Data edit user belum lengkap.');
            redirect_users();
        }

        $sql = 'UPDATE users SET full_name = ?, username = ?, role = ? WHERE id = ?';
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'sssi', $fullName, $username, $role, $userId);

        if (mysqli_stmt_execute($statement)) {
            set_user_alert('success', 'User berhasil diperbarui.');
        } else {
            set_user_alert('danger', 'Gagal memperbarui user: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($statement);
        redirect_users();
    }

    if ($action === 'delete') {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0 || $userId === (int) $_SESSION['user_id']) {
            set_user_alert('danger', 'User tidak dapat dihapus.');
            redirect_users();
        }

        $sql = 'DELETE FROM users WHERE id = ?';
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'i', $userId);

        if (mysqli_stmt_execute($statement)) {
            set_user_alert('success', 'User berhasil dihapus.');
        } else {
            set_user_alert('danger', 'Gagal menghapus user: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($statement);
        redirect_users();
    }

    if ($action === 'reset_password') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($userId <= 0 || $password === '') {
            set_user_alert('danger', 'Password baru wajib diisi.');
            redirect_users();
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = 'UPDATE users SET password = ? WHERE id = ?';
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'si', $passwordHash, $userId);

        if (mysqli_stmt_execute($statement)) {
            set_user_alert('success', 'Password user berhasil direset.');
        } else {
            set_user_alert('danger', 'Gagal reset password: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($statement);
        redirect_users();
    }
}

$users = [];
$sql = 'SELECT id, full_name, username, role, created_at FROM users ORDER BY created_at DESC';
$statement = mysqli_prepare($connection, $sql);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);

while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

mysqli_stmt_close($statement);
mysqli_close($connection);

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<main id="content" class="bg-light">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid p-0">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">User Management</h1>
                <p class="text-muted mb-0">Kelola akun admin dan member Family Cloud.</p>
            </div>
            <button class="btn btn-primary align-self-start align-self-md-center" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus me-1"></i> Tambah User
            </button>
        </div>

        <?php if ($alert) : ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card dashboard-card border-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Dibuat</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)) : ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada user.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($users as $user) : ?>
                            <?php
                            $userId = (int) $user['id'];
                            $isCurrentUser = $userId === (int) $_SESSION['user_id'];
                            ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge text-bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>"><?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $userId; ?>">Edit</button>
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal<?php echo $userId; ?>">Reset Password</button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $userId; ?>" <?php echo $isCurrentUser ? 'disabled' : ''; ?>>Delete</button>

                                    <div class="modal fade text-start" id="editUserModal<?php echo $userId; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form class="modal-content" method="post">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                                <div class="modal-header">
                                                    <h2 class="modal-title h5">Edit User</h2>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="editFullName<?php echo $userId; ?>">Nama Lengkap</label>
                                                        <input class="form-control" id="editFullName<?php echo $userId; ?>" name="full_name" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="editUsername<?php echo $userId; ?>">Username</label>
                                                        <input class="form-control" id="editUsername<?php echo $userId; ?>" name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    </div>
                                                    <div>
                                                        <label class="form-label" for="editRole<?php echo $userId; ?>">Role</label>
                                                        <select class="form-select" id="editRole<?php echo $userId; ?>" name="role" required>
                                                            <option value="member" <?php echo $user['role'] === 'member' ? 'selected' : ''; ?>>Member</option>
                                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="modal fade text-start" id="resetPasswordModal<?php echo $userId; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form class="modal-content" method="post">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                                <div class="modal-header">
                                                    <h2 class="modal-title h5">Reset Password</h2>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-muted">Masukkan password baru untuk <?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>.</p>
                                                    <label class="form-label" for="resetPassword<?php echo $userId; ?>">Password Baru</label>
                                                    <input type="password" class="form-control" id="resetPassword<?php echo $userId; ?>" name="password" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-warning">Reset Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="modal fade text-start" id="deleteUserModal<?php echo $userId; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form class="modal-content" method="post">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                                <div class="modal-header">
                                                    <h2 class="modal-title h5">Delete User</h2>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="mb-0">Hapus user <strong><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>?</p>
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
        </div>
    </div>
</main>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h2 class="modal-title h5">Tambah User</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="createFullName">Nama Lengkap</label>
                    <input class="form-control" id="createFullName" name="full_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="createUsername">Username</label>
                    <input class="form-control" id="createUsername" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="createPassword">Password</label>
                    <input type="password" class="form-control" id="createPassword" name="password" required>
                </div>
                <div>
                    <label class="form-label" for="createRole">Role</label>
                    <select class="form-select" id="createRole" name="role" required>
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Tambah User</button>
            </div>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
