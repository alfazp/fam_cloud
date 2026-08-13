<?php
/**
 * includes/navbar.php
 * 
 * Top Navigation Bar menggunakan Bootstrap 5.
 * Dilengkapi dengan bar pencarian berkas dan informasi profil pengguna.
 */

$displayName = $_SESSION['full_name'] ?? 'Family Cloud';
$displayRole = $_SESSION['role'] ?? 'member';
$profilePicture = $_SESSION['profile_picture'] ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-light navbar-custom py-2 px-3 mb-4 rounded-3 border">
    <div class="container-fluid p-0">
        <!-- Tombol Toggle Sidebar untuk tampilan mobile -->
        <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary btn-sm me-3" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>

        <!-- Form Pencarian Berkas -->
        <form class="d-none d-sm-flex flex-grow-1 max-width-search me-4" style="max-width: 400px;">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted" id="search-addon">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control bg-light border-start-0" placeholder="Cari folder atau file..." aria-label="Search" aria-describedby="search-addon">
            </div>
        </form>

        <!-- Dropdown Profil & Notifikasi -->
        <div class="d-flex align-items-center ms-auto">
            <!-- Notifikasi -->
            <div class="dropdown me-3">
                <a class="text-secondary position-relative fs-5 text-decoration-none" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">Notifikasi Baru</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="notificationDropdown" style="width: 280px;">
                    <li class="px-3 py-2 border-bottom"><h6 class="m-0 fw-bold">Notifikasi Terkini</h6></li>
                    <li><a class="dropdown-item py-2" href="#"><small class="d-block text-muted">Baru saja</small><i class="bi bi-file-earmark-arrow-up text-primary me-1"></i> "foto_keluarga.jpg" berhasil diupload</a></li>
                    <li><a class="dropdown-item py-2" href="#"><small class="d-block text-muted">2 jam yang lalu</small><i class="bi bi-share text-success me-1"></i> Ayah membagikan folder "Liburan"</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center text-primary" href="#"><small>Lihat semua aktivitas</small></a></li>
                </ul>
            </div>

            <!-- Profil Dropdown -->
            <div class="dropdown">
                <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" href="#" role="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php if (!empty($profilePicture)) : ?>
                        <img src="<?php echo BASE_URL . htmlspecialchars($profilePicture, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto Profil" class="avatar-image me-2">
                    <?php else : ?>
                        <span class="avatar-circle me-2"><?php echo strtoupper(substr($displayName, 0, 1)); ?></span>
                    <?php endif; ?>
                    <div class="d-none d-md-block text-start">
                        <div class="fw-semibold lh-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></div>
                        <small class="text-muted text-capitalize" style="font-size: 0.75rem;"><?php echo htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenuDropdown">
                    <li class="px-3 py-2 border-bottom d-md-none">
                        <div class="fw-semibold"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></div>
                        <small class="text-muted text-capitalize"><?php echo htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8'); ?></small>
                    </li>
                    <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>dashboard/settings.php"><i class="bi bi-person me-2"></i> Profil Saya</a></li>
                    <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>dashboard/settings.php#storage"><i class="bi bi-hdd me-2"></i> Detail Penyimpanan</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>auth/logout.php?csrf_token=<?php echo urlencode(generate_csrf_token()); ?>"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
