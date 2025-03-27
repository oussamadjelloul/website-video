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
                <?php 
                // Priority of URL sources: 
                // 1. Secure signed URL (if available)
                // 2. CDN URL (if available)
                // 3. Regular video URL
                $videoSrc = isset($video['secure_video_url']) ? $video['secure_video_url'] : 
                           (isset($video['cdn_url']) && $video['cdn_url'] ? $video['cdn_url'] : $video['video_url']);
                
                // Same for thumbnails
                $posterSrc = isset($video['secure_thumbnail_url']) ? $video['secure_thumbnail_url'] : 
                            (isset($video['thumbnail_url']) ? $video['thumbnail_url'] : '');
                ?>
                <video id="videoPlayer" controls poster="<?= htmlspecialchars($posterSrc) ?>">
                    <source src="<?= htmlspecialchars($videoSrc) ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
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

            <?php if (isset($video['secure_video_url']) || (isset($video['cdn_url']) && $video['cdn_url'])): ?>
                <div class="alert alert-info">
                    <h5 class="alert-heading">CDN Information</h5>
                    <p>This video is being served from a CDN with secure signed URLs.</p>
                    <hr>
                    <div class="mb-0">
                        <strong>Local URL:</strong> <code><?= htmlspecialchars($video['video_url']) ?></code><br>
                        <?php if (isset($video['cdn_url']) && $video['cdn_url']): ?>
                            <strong>CDN URL:</strong> <code><?= htmlspecialchars($video['cdn_url']) ?></code><br>
                        <?php endif; ?>
                        <?php if (isset($video['secure_video_url'])): ?>
                            <strong>Secure URL:</strong> <code><?= htmlspecialchars(substr($video['secure_video_url'], 0, 60)) ?>...</code>
                            <p class="mt-2 mb-0 text-muted small">Note: Secure URLs expire after 2 hours for videos and 24 hours for thumbnails.</p>
                        <?php endif; ?>
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

<script>
// Add a listener to reload the video when the secure URL expires
document.getElementById('videoPlayer').addEventListener('error', function(e) {
    console.log('Video playback error detected, possibly due to expired token');
    
    // Only reload if we're using a secure URL (which contains a token)
    const videoSrc = this.querySelector('source').src;
    if (videoSrc.includes('token=')) {
        console.log('Reloading page to refresh token');
        
        // Remember the current playback position
        const currentTime = this.currentTime;
        
        // Store the position in sessionStorage
        sessionStorage.setItem('videoPlaybackPosition', currentTime);
        
        // Reload the page to get a fresh token
        window.location.reload();
    }
});

// Restore playback position if available
document.addEventListener('DOMContentLoaded', function() {
    const player = document.getElementById('videoPlayer');
    const savedPosition = sessionStorage.getItem('videoPlaybackPosition');
    
    if (savedPosition !== null) {
        // Set the playback position
        player.currentTime = parseFloat(savedPosition);
        
        // Clear the stored position
        sessionStorage.removeItem('videoPlaybackPosition');
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>