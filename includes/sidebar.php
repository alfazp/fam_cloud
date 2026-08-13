<?php
/**
 * includes/sidebar.php
 * 
 * Sidebar navigasi utama menggunakan Bootstrap 5.
 * Mendukung status item aktif berdasarkan variabel $current_menu.
 */

if (!isset($current_menu)) {
    $current_menu = '';
}

$userRole = $_SESSION['role'] ?? 'member';
?>
<nav id="sidebar" class="bg-dark border-end border-secondary">
    <div class="sidebar-header text-center py-4 bg-black bg-opacity-25">
        <h4 class="m-0 text-white fw-bold">
            <i class="bi bi-cloud-arrow-up-fill text-primary"></i> Family Cloud
        </h4>
        <small class="text-muted text-uppercase tracking-wider">Drive Keluarga</small>
    </div>

    <ul class="list-unstyled components flex-grow-1 m-0">
        <li class="<?php echo $current_menu === 'dashboard' ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>dashboard/index.php">
                <i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard
            </a>
        </li>

        <li class="<?php echo $current_menu === 'files' ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>dashboard/files.php">
                <i class="bi bi-folder2-open me-2 text-warning"></i> My Files
            </a>
        </li>

        <li class="<?php echo $current_menu === 'shared' ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>dashboard/shared.php">
                <i class="bi bi-people me-2 text-info"></i> Shared With Me
            </a>
        </li>

        <li class="<?php echo $current_menu === 'upload' ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>dashboard/upload.php">
                <i class="bi bi-cloud-arrow-up me-2 text-success"></i> Upload
            </a>
        </li>

        <li class="<?php echo $current_menu === 'activity' ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>dashboard/activity.php">
                <i class="bi bi-clock-history me-2 text-light"></i> Activity
            </a>
        </li>

        <li class="<?php echo $current_menu === 'settings' ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>dashboard/settings.php">
                <i class="bi bi-gear me-2 text-secondary"></i> Settings
            </a>
        </li>

        <?php if ($userRole === 'admin') : ?>
            <li class="<?php echo $current_menu === 'users' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/users.php">
                    <i class="bi bi-person-gear me-2 text-danger"></i> User Management
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="p-3 bg-black bg-opacity-10 border-top border-secondary text-white-50" style="font-size: 0.85rem;">
        <div class="d-flex justify-content-between mb-1">
            <span>Penyimpanan</span>
            <span class="fw-bold text-white">Family Cloud</span>
        </div>
        <small>Drive keluarga pribadi</small>
    </div>
</nav>
