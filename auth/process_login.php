<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

require_csrf_token($_POST['csrf_token'] ?? null);

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    header('Location: login.php');
    exit;
}

$connection = connect();
$sql = 'SELECT id, full_name, username, password, role, profile_picture FROM users WHERE username = ? LIMIT 1';
$statement = mysqli_prepare($connection, $sql);

if (!$statement) {
    die('Query login gagal disiapkan: ' . mysqli_error($connection));
}

mysqli_stmt_bind_param($statement, 's', $username);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($statement);

if (!$user || !password_verify($password, $user['password'])) {
    mysqli_close($connection);
    $_SESSION['login_error'] = 'Username atau password salah.';
    header('Location: login.php');
    exit;
}

log_activity($connection, (int) $user['id'], 'Login');
mysqli_close($connection);

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['profile_picture'] = $user['profile_picture'];

$redirectPath = $user['role'] === 'admin' ? '../dashboard/admin.php' : '../dashboard/index.php';
header('Location: ' . $redirectPath);
exit;
