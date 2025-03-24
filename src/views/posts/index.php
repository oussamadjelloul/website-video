<?php
$pageTitle = 'Posts';

// Start output buffering
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Posts</h1>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/posts/create" class="btn btn-primary">New Post</a>
    <?php endif; ?>
</div>

<?php if (empty($posts)): ?>
    <div class="alert alert-info">
        No posts found. <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/posts/create">Create your first post!</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <?php if ($post['image_url'] || $post['cdn_url']): ?>
                        <img src="<?= htmlspecialchars($post['image_url']) ?>"
                            class="card-img-top" alt="<?= htmlspecialchars($post['title']) ?>"
                            style="height: 200px; object-fit: cover;">
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text text-muted">
                            By <?= htmlspecialchars($post['author_name']) ?> |
                            <?= date('M j, Y', strtotime($post['created_at'])) ?>
                        </p>
                        <p class="card-text">
                            <?= htmlspecialchars(substr(strip_tags($post['content']), 0, 100)) ?>...
                        </p>
                        <a href="/posts/view/<?= $post['id'] ?>" class="btn btn-primary">Read More</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $userId ? '&user_id=' . $userId : '' ?>">
                            Previous
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Previous</span>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $userId ? '&user_id=' . $userId : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $userId ? '&user_id=' . $userId : '' ?>">
                            Next
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Next</span>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php
// Get the content from the output buffer
$content = ob_get_clean();

// Include the main layout template
require_once __DIR__ . '/../layouts/main.php';
?>