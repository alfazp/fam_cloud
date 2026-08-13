<?php
require_once __DIR__ . '/../auth/auth.php';

$page_title = 'Upload';
$current_menu = 'upload';
$userId = (int) $_SESSION['user_id'];
$folders = [];
$connection = connect();

$sql = 'SELECT id, folder_name FROM folders WHERE user_id = ? ORDER BY folder_name ASC';
$statement = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($statement, 'i', $userId);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);

while ($row = mysqli_fetch_assoc($result)) {
    $folders[] = $row;
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
                <h1 class="h3 fw-bold mb-1">Upload File</h1>
                <p class="text-muted mb-0">Upload file keluarga hingga 100MB.</p>
            </div>
        </div>

        <div id="uploadAlert" class="alert d-none" role="alert"></div>

        <div class="card dashboard-card border-0">
            <div class="card-body p-4">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="file" class="form-label">File</label>
                        <input type="file" class="form-control" id="file" name="file" required>
                        <div class="form-text">Extension yang diizinkan: jpg, png, gif, pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv, zip, rar, 7z, mp3, wav, mp4, mov, mkv.</div>
                    </div>

                    <div class="mb-4">
                        <label for="folder_id" class="form-label">Folder</label>
                        <select class="form-select" id="folder_id" name="folder_id">
                            <option value="">Tanpa folder</option>
                            <?php foreach ($folders as $folder) : ?>
                                <option value="<?php echo (int) $folder['id']; ?>"><?php echo htmlspecialchars($folder['folder_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="progress mb-3 d-none" id="uploadProgressWrapper" style="height: 22px;">
                        <div id="uploadProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('uploadForm');
    const alertBox = document.getElementById('uploadAlert');
    const progressWrapper = document.getElementById('uploadProgressWrapper');
    const progressBar = document.getElementById('uploadProgress');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const request = new XMLHttpRequest();
        const formData = new FormData(form);

        alertBox.className = 'alert d-none';
        alertBox.textContent = '';
        progressWrapper.classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', '0');
        progressBar.textContent = '0%';

        request.upload.addEventListener('progress', function (event) {
            if (!event.lengthComputable) {
                return;
            }

            const percent = Math.round((event.loaded / event.total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', String(percent));
            progressBar.textContent = percent + '%';
        });

        request.addEventListener('load', function () {
            let response = { success: false, message: 'Upload gagal diproses.' };

            try {
                response = JSON.parse(request.responseText);
            } catch (error) {
                response.message = 'Response upload tidak valid.';
            }

            alertBox.className = 'alert alert-' + (response.success ? 'success' : 'danger');
            alertBox.textContent = response.message;

            if (response.success) {
                form.reset();
            }
        });

        request.addEventListener('error', function () {
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = 'Upload gagal. Silakan coba lagi.';
        });

        request.open('POST', '<?php echo BASE_URL; ?>dashboard/process_upload.php');
        request.send(formData);
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
