<?php
/**
 * includes/session.php
 * 
 * Helper untuk manajemen session yang aman dan terstruktur.
 * Menyediakan fungsi-fungsi pembantu untuk mengelola session, pesan kilat (flash messages),
 * serta proteksi session dasar.
 */

// Konfigurasi session dengan parameter keamanan tambahan jika belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan cookie session agar lebih aman
    ini_set('session.cookie_httponly', 1); // Mencegah akses cookie lewat JavaScript (XSS Protection)
    ini_set('session.use_only_cookies', 1); // Memaksa session hanya menggunakan cookie (mencegah PHPSESSID di URL)
    ini_set('session.cookie_samesite', 'Strict');
    
    // Jika menggunakan HTTPS, aktifkan secure cookie
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    if ($isSecure) {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

/**
 * Menghasilkan CSRF token untuk keamanan form submission
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF token dari form
 */
function verify_csrf_token(?string $token): bool {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Menghentikan request jika CSRF token tidak valid.
 */
function require_csrf_token(?string $token): void {
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        die('CSRF token tidak valid.');
    }
}

/**
 * Mengisi data ke session
 */
function set_session(string $key, $value): void {
    $_SESSION[$key] = $value;
}

/**
 * Mengambil data dari session
 */
function get_session(string $key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

/**
 * Menghapus data spesifik dari session
 */
function remove_session(string $key): void {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

/**
 * Membuat Flash Message (Pesan yang langsung dihapus setelah dibaca sekali)
 * Sangat berguna untuk alert sukses/gagal form submission.
 */
function set_flash_message(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

/**
 * Membaca & Menghapus Flash Message
 */
function get_flash_message(string $type): ?string {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]); // Hapus agar tidak muncul di load berikutnya
        return $message;
    }
    return null;
}

/**
 * Mengecek apakah flash message tipe tertentu ada
 */
function has_flash_message(string $type): bool {
    return isset($_SESSION['flash'][$type]);
}

/**
 * Menghancurkan session (Logout)
 */
function destroy_user_session(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
?>
