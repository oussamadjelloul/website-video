<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/videos">Videos</a></li>
            <li class="breadcrumb-item"><a href="/videos/view/<?= $video['id'] ?>"><?= htmlspecialchars($video['title']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            <h1 class="card-title h3 mb-0">Edit Video</h1>
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

            <form action="/videos/edit/<?= $video['id'] ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $video['id'] ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required
                        value="<?= htmlspecialchars($_SESSION['form_data']['title'] ?? $video['title']) ?>">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($_SESSION['form_data']['description'] ?? $video['description'] ?? '') ?></textarea>
                </div>

                <?php if ($video['thumbnail_url']): ?>
                    <div class="mb-3">
                        <label class="form-label">Current Thumbnail</label>
                        <div>
                            <img src="<?= htmlspecialchars($video['cdn_url'] ? $video['thumbnail_url'] : $video['thumbnail_url']) ?>"
                                alt="Current thumbnail" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="thumbnail" class="form-label">New Thumbnail (optional)</label>
                    <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/gif">
                    <div class="form-text">
                        Leave empty to keep the current thumbnail. Max file size: <?= MAX_FILE_SIZE / 1024 / 1024 ?>MB.
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="useCdn" name="use_cdn"
                            <?= $video['cdn_url'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="useCdn">
                            Use CDN for video delivery (if available)
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <h5>Video Information</h5>
                    <ul class="list-group">
                        <li class="list-group-item">Duration: <?= Video::formatDuration($video['duration']) ?></li>
                        <li class="list-group-item">Uploaded on: <?= date('F j, Y g:i A', strtotime($video['created_at'])) ?></li>
                        <?php if ($video['cdn_url']): ?>
                            <li class="list-group-item">CDN URL: <code><?= htmlspecialchars($video['cdn_url']) ?></code></li>
                        <?php else: ?>
                            <li class="list-group-item">Local URL: <code><?= htmlspecialchars($video['video_url']) ?></code></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="/videos/view/<?= $video['id'] ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Video</button>
                </div>
            </form>

            <?php if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>