<?php
$pageTitle = htmlspecialchars($post['title']);

// Start output buffering
ob_start();
?>

<div class="mb-4">
    <a href="/posts" class="btn btn-outline-primary">&larr; Back to Posts</a>
</div>

<article class="blog-post">
    <header class="mb-4">
        <h1 class="mb-1"><?= htmlspecialchars($post['title']) ?></h1>
        <div class="text-muted mb-3">
            By <?= htmlspecialchars($post['author_name']) ?> |
            <?= date('F j, Y', strtotime($post['created_at'])) ?>
            <?php if ($post['updated_at'] != $post['created_at']): ?>
                (Updated: <?= date('F j, Y', strtotime($post['updated_at'])) ?>)
            <?php endif; ?>
        </div>
    </header>

    <?php if ($post['image_url'] || $post['cdn_url']): ?>
        <div class="mb-4 text-center">
            <img src="<?= htmlspecialchars($post['secure_image_url'] ?? $post['cdn_url'] ?? $post['image_url']) ?>"
                class="img-fluid rounded" style="max-height: 400px;"
                alt="<?= htmlspecialchars($post['title']) ?>">
        </div>
    <?php endif; ?>

    <div class="blog-content mb-5">
        <?= nl2br(htmlspecialchars($post['content'])) ?>
    </div>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $post['user_id']): ?>
        <div class="mt-4">
            <a href="/posts/edit/<?= $post['id'] ?>" class="btn btn-primary me-2">Edit Post</a>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deletePostModal">
                Delete Post
            </button>
        </div>

        <!-- Delete Post Modal -->
        <div class="modal fade" id="deletePostModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this post? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form action="/posts/delete" method="post">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</article>

<?php
// Get the content from the output buffer
$content = ob_get_clean();

// Include the main layout template
require_once __DIR__ . '/../layouts/main.php';
?>