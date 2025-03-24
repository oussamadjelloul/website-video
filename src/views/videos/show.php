<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/videos">Videos</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($video['title']) ?></li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-body p-0">
            <!-- Video Player -->
            <div class="ratio ratio-16x9">
                <?php if ($video['cdn_url']): ?>
                    <video id="videoPlayer" controls poster="<?= htmlspecialchars($video['thumbnail_url'] ?? '') ?>">
                        <source src="<?= htmlspecialchars($video['cdn_url']) ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <video id="videoPlayer" controls poster="<?= htmlspecialchars($video['thumbnail_url'] ?? '') ?>">
                        <source src="<?= htmlspecialchars($video['video_url']) ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <h1 class="mb-3"><?= htmlspecialchars($video['title']) ?></h1>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="text-muted">
                        Uploaded by <?= htmlspecialchars($video['author_name']) ?> on
                        <?= date('M j, Y', strtotime($video['created_at'])) ?>
                    </span>
                    <br>
                    <span class="text-muted">
                        Duration: <?= Video::formatDuration($video['duration']) ?>
                    </span>
                </div>

                <?php if (isset($_SESSION['user_id']) && $video['user_id'] === $_SESSION['user_id']): ?>
                    <div>
                        <a href="/videos/edit/<?= $video['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="/videos/delete" method="POST" class="d-inline"
                            onsubmit="return confirm('Are you sure you want to delete this video?');">
                            <input type="hidden" name="id" value="<?= $video['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <h5>Description</h5>
            <div class="card mb-4">
                <div class="card-body bg-light">
                    <?php if (!empty($video['description'])): ?>
                        <p class="card-text"><?= nl2br(htmlspecialchars($video['description'])) ?></p>
                    <?php else: ?>
                        <p class="card-text text-muted">No description available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($video['cdn_url']) && $video['cdn_url']): ?>
                <div class="alert alert-info">
                    <h5 class="alert-heading">CDN Information</h5>
                    <p>This video is being served from a CDN.</p>
                    <hr>
                    <div class="mb-0">
                        <strong>Local URL:</strong> <code><?= htmlspecialchars($video['video_url']) ?></code><br>
                        <strong>CDN URL:</strong> <code><?= htmlspecialchars($video['cdn_url']) ?></code>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Related Videos</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php
                    // This would typically come from the controller with a query for related videos
                    // For now, we'll just show a placeholder
                    ?>
                    <li class="list-group-item text-muted">Related videos will appear here.</li>
                </ul>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="mb-4">
        <a href="/videos" class="btn btn-secondary">&larr; Back to Videos</a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>