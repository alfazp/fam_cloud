<?php
require_once __DIR__ . '/../auth/auth.php';

$page_title = 'Settings';
$current_menu = 'settings';
$userId = (int) $_SESSION['user_id'];
$connection = connect();
$alert = $_SESSION['settings_alert'] ?? null;
unset($_SESSION['settings_alert']);

function set_settings_alert(string $type, string $message): void
{
    $_SESSION['settings_alert'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function redirect_settings(): void
{
    header('Location: ' . BASE_URL . 'dashboard/settings.php');
    exit;
}

function settings_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function get_settings_user(mysqli $connection, int $userId): ?array
{
    $sql = 'SELECT id, full_name, username, password, role, profile_picture FROM users WHERE id = ? LIMIT 1';
    $statement = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($statement, 'i', $userId);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    return $user ?: null;
}

function settings_file_size(int $bytes): string
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user = get_settings_user($connection, $userId);

    if (!$user) {
        set_settings_alert('danger', 'User tidak ditemukan.');
        redirect_settings();
    }

    if ($action === 'profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if ($fullName === '' || $username === '') {
            set_settings_alert('danger', 'Nama dan username wajib diisi.');
            redirect_settings();
        }

        $checkSql = 'SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1';
        $checkStatement = mysqli_prepare($connection, $checkSql);
        mysqli_stmt_bind_param($checkStatement, 'si', $username, $userId);
        mysqli_stmt_execute($checkStatement);
        $checkResult = mysqli_stmt_get_result($checkStatement);
        $existingUser = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStatement);

        if ($existingUser) {
            set_settings_alert('danger', 'Username sudah digunakan.');
            redirect_settings();
        }

        $sql = 'UPDATE users SET full_name = ?, username = ? WHERE id = ?';
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'ssi', $fullName, $username, $userId);

        if (mysqli_stmt_execute($statement)) {
            $_SESSION['full_name'] = $fullName;
            $_SESSION['username'] = $username;
            log_activity($connection, $userId, 'Update settings: profil');
            set_settings_alert('success', 'Profil berhasil diperbarui.');
        } else {
            set_settings_alert('danger', 'Gagal memperbarui profil: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($statement);
        redirect_settings();
    }

    if ($action === 'password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            set_settings_alert('danger', 'Semua field password wajib diisi.');
            redirect_settings();
        }

        if (!password_verify($oldPassword, $user['password'])) {
            set_settings_alert('danger', 'Password lama tidak sesuai.');
            redirect_settings();
        }

        if ($newPassword !== $confirmPassword) {
            set_settings_alert('danger', 'Konfirmasi password baru tidak sesuai.');
            redirect_settings();
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = 'UPDATE users SET password = ? WHERE id = ?';
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'si', $passwordHash, $userId);

        if (mysqli_stmt_execute($statement)) {
            log_activity($connection, $userId, 'Update settings: password');
            set_settings_alert('success', 'Password berhasil diperbarui.');
        } else {
            set_settings_alert('danger', 'Gagal memperbarui password: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($statement);
        redirect_settings();
    }

    if ($action === 'photo') {
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            set_settings_alert('danger', 'Foto profil wajib dipilih.');
            redirect_settings();
        }

        $file = $_FILES['profile_picture'];
        $maxSize = 2 * 1024 * 1024;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if ($file['size'] > $maxSize) {
            set_settings_alert('danger', 'Ukuran foto maksimal 2MB.');
            redirect_settings();
        }

        if (!in_array($extension, $allowedExtensions, true)) {
            set_settings_alert('danger', 'Extension foto tidak valid.');
            redirect_settings();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            set_settings_alert('danger', 'MIME Type foto tidak valid.');
            redirect_settings();
        }

        $uploadDirectory = __DIR__ . '/../uploads/' . $userId . '/profile/';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
            set_settings_alert('danger', 'Folder foto profil gagal dibuat.');
            redirect_settings();
        }

        $fileName = settings_uuid() . '.' . $extension;
        $relativePath = 'uploads/' . $userId . '/profile/' . $fileName;
        $destination = $uploadDirectory . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            set_settings_alert('danger', 'Foto profil gagal disimpan.');
            redirect_settings();
        }

        $sql = 'UPDATE users SET profile_picture = ? WHERE id = ?';
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'si', $relativePath, $userId);

        if (mysqli_stmt_execute($statement)) {
            if (!empty($user['profile_picture'])) {
                $oldPath = __DIR__ . '/../' . $user['profile_picture'];
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

            $_SESSION['profile_picture'] = $relativePath;
            log_activity($connection, $userId, 'Update settings: foto profil');
            set_settings_alert('success', 'Foto profil berhasil diperbarui.');
        } else {
            unlink($destination);
            set_settings_alert('danger', 'Gagal memperbarui foto profil: ' . mysqli_error($connection));
        }

        mysqli_stmt_close($statement);
        redirect_settings();
    }
}

$currentUser = get_settings_user($connection, $userId);
$storage = [
    'total_files' => 0,
    'storage_used' => 0,
];

$storageSql = 'SELECT COUNT(id) AS total_files, COALESCE(SUM(file_size), 0) AS storage_used FROM files WHERE user_id = ? AND is_deleted = 0';
$storageStatement = mysqli_prepare($connection, $storageSql);
mysqli_stmt_bind_param($storageStatement, 'i', $userId);
mysqli_stmt_execute($storageStatement);
$storageResult = mysqli_stmt_get_result($storageStatement);
$storageRow = mysqli_fetch_assoc($storageResult);
mysqli_stmt_close($storageStatement);

if ($storageRow) {
    $storage['total_files'] = (int) $storageRow['total_files'];
    $storage['storage_used'] = (int) $storageRow['storage_used'];
}

mysqli_close($connection);

if (!$currentUser) {
    header('Location: ' . BASE_URL . 'auth/logout.php');
    exit;
}

$profilePicture = $currentUser['profile_picture'] ?? '';

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<main id="content" class="bg-light">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid p-0">
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">Settings</h1>
            <p class="text-muted mb-0">Kelola profil dan keamanan akun Anda.</p>
        </div>

        <?php if ($alert) : ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card dashboard-card border-0">
                    <div class="card-body text-center p-4">
                        <?php if (!empty($profilePicture)) : ?>
                            <img src="<?php echo BASE_URL . htmlspecialchars($profilePicture, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto Profil" class="profile-photo mb-3">
                        <?php else : ?>
                            <div class="profile-photo profile-photo-placeholder mx-auto mb-3">
                                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <h2 class="h5 fw-bold mb-1"><?php echo htmlspecialchars($currentUser['full_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="text-muted mb-0">@<?php echo htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card dashboard-card border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h2 class="h5 fw-bold mb-0">Profil</h2>
                    </div>
                    <div class="card-body">
                        <form class="needs-validation" method="post" novalidate>
                            <input type="hidden" name="action" value="profile">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="full_name" class="form-label">Nama</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <div class="invalid-feedback">Nama wajib diisi.</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <div class="invalid-feedback">Username wajib diisi.</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Simpan Profil</button>
                        </form>
                    </div>
                </div>

                <div class="card dashboard-card border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h2 class="h5 fw-bold mb-0">Password</h2>
                    </div>
                    <div class="card-body">
                        <form class="needs-validation" method="post" novalidate>
                            <input type="hidden" name="action" value="password">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="old_password" class="form-label">Password Lama</label>
                                    <input type="password" class="form-control" id="old_password" name="old_password" required>
                                    <div class="invalid-feedback">Password lama wajib diisi.</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                    <div class="invalid-feedback">Password baru minimal 6 karakter.</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                    <div class="invalid-feedback">Konfirmasi password wajib diisi.</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Ubah Password</button>
                        </form>
                    </div>
                </div>

                <div class="card dashboard-card border-0 mb-4" id="storage">
                    <div class="card-header bg-white border-0 py-3">
                        <h2 class="h5 fw-bold mb-0">Detail Penyimpanan</h2>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="text-muted mb-1">Total File</div>
                                    <div class="h4 fw-bold mb-0"><?php echo number_format($storage['total_files']); ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="text-muted mb-1">Storage Digunakan</div>
                                    <div class="h4 fw-bold mb-0"><?php echo settings_file_size($storage['storage_used']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card dashboard-card border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h2 class="h5 fw-bold mb-0">Foto Profil</h2>
                    </div>
                    <div class="card-body">
                        <form class="needs-validation" method="post" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="action" value="photo">
                            <label for="profile_picture" class="form-label">Upload Foto</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png,.gif" required>
                            <div class="form-text">Format: JPG, PNG, GIF. Maksimal 2MB.</div>
                            <div class="invalid-feedback">Foto profil wajib dipilih.</div>
                            <button type="submit" class="btn btn-primary mt-3">Upload Foto</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
