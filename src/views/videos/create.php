<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/videos">Videos</a></li>
            <li class="breadcrumb-item active" aria-current="page">Upload Video</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            <h1 class="card-title h3 mb-0">Upload New Video</h1>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>

            <form action="/videos/create" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required
                        value="<?= htmlspecialchars($_SESSION['form_data']['title'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($_SESSION['form_data']['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="video" class="form-label">Video File <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="video" name="video" required accept="video/mp4,video/webm">
                    <div class="form-text">
                        Max file size: <?= MAX_FILE_SIZE / 1024 / 1024 ?>MB. Supported formats: MP4, WebM.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="thumbnail" class="form-label">Custom Thumbnail (optional)</label>
                    <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/gif">
                    <div class="form-text">
                        If not provided, a thumbnail will be generated from the video. Max file size: <?= MAX_FILE_SIZE / 1024 / 1024 ?>MB.
                    </div>
                </div>

                <div class="mb-3" style="display: none;">
                    <div class="form-check hidden">
                        <input class="form-check-input" type="checkbox" id="useCdn" name="use_cdn" checked>
                        <label class="form-check-label" for="useCdn">
                            Use CDN for video delivery (if available)
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="/videos" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Upload Video</button>
                </div>
            </form>

            <?php if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
        </div>
    </div>
</div>

<script>
    // Preview selected video and automatically generate thumbnail
    document.getElementById('video').addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.onloadedmetadata = function() {
                // You could display video duration here if needed
                URL.revokeObjectURL(video.src);
            }
            video.src = URL.createObjectURL(file);
        }
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>