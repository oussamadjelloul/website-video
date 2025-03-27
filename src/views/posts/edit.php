<?php
$pageTitle = 'Edit Post';

// Start output buffering
ob_start();
?>

<div class="mb-4">
    <a href="/posts/view?id=<?= $post['id'] ?>" class="btn btn-outline-primary">&larr; Back to Post</a>
</div>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">Edit Post</h4>
    </div>
    <div class="card-body">
        <form action="/posts/edit" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $post['id'] ?>">

            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title"
                    value="<?= htmlspecialchars($_SESSION['form_data']['title'] ?? $post['title']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($_SESSION['form_data']['content'] ?? $post['content']) ?></textarea>
            </div>

            <?php if ($post['image_url'] || $post['cdn_url']): ?>
                <div class="mb-3">
                    <label class="form-label">Current Image</label>
                    <div>
                        <img src="<?= htmlspecialchars($post['secure_image_url'] ?? $post['cdn_url'] ?? $post['image_url']) ?>"
                            class="img-thumbnail mb-2" style="max-height: 200px;" alt="Current image">
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="image" class="form-label">New Image (Optional)</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <div class="form-text">Max file size: <?= MAX_FILE_SIZE / 1024 / 1024 ?>MB. Allowed formats: JPG, PNG, GIF</div>
            </div>

            <div class="d-grid d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Update Post</button>
            </div>
        </form>
    </div>
</div>

<?php
// Get the content from the output buffer
$content = ob_get_clean();

// Extra scripts
$extraScripts = <<<HTML
<script>
    // Basic form validation
    document.querySelector('form').addEventListener('submit', function(event) {
        const title = document.getElementById('title').value.trim();
        const content = document.getElementById('content').value.trim();
        
        if (!title || !content) {
            event.preventDefault();
            alert('Please fill in all required fields');
        }
    });
</script>
HTML;

// Include the main layout template
require_once __DIR__ . '/../layouts/main.php';
?>