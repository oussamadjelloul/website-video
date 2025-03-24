<?php
$pageTitle = 'Videos';

// Start output buffering
ob_start();
?>

<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Videos</h1>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/videos/create" class="btn btn-primary">Upload New Video</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['errors'])): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php unset($_SESSION['errors']); ?>
        </div>
    <?php endif; ?>

    <!-- Search and filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="/videos" method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="search" placeholder="Search videos..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="user_id" value="<?= $_SESSION['user_id'] ?>"
                                <?= (isset($_GET['user_id']) && $_GET['user_id'] === $_SESSION['user_id']) ? 'checked' : '' ?>>
                            <label class="form-check-label">
                                My videos only
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($videos)): ?>
        <div class="alert alert-info">No videos found.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($videos as $video): ?>
                <div class="col">
                    <div class="card h-100">
                        <!-- Video Thumbnail -->
                        <a href="/videos/view/<?= $video['id'] ?>">
                            <?php if ($video['thumbnail_url']): ?>
                                <img src="<?= htmlspecialchars($video['thumbnail_url']) ?>"
                                    class="card-img-top" alt="<?= htmlspecialchars($video['title']) ?>"
                                    style="height: 180px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-dark d-flex align-items-center justify-content-center"
                                    style="height: 180px;">
                                    <i class="bi bi-film" style="font-size: 3rem; color: white;"></i>
                                </div>
                            <?php endif; ?>
                        </a>

                        <!-- Video Info -->
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="/videos/view/<?= $video['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($video['title']) ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted">
                                <small>
                                    By <?= htmlspecialchars($video['author_name']) ?> |
                                    <?= Video::formatDuration($video['duration']) ?> |
                                    <?= date('M j, Y', strtotime($video['created_at'])) ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <?= nl2br(htmlspecialchars(substr($video['description'] ?? '', 0, 100))) ?>
                                <?= (strlen($video['description'] ?? '') > 100) ? '...' : '' ?>
                            </p>
                        </div>

                        <!-- Video Actions -->
                        <?php if (isset($_SESSION['user_id']) && $video['user_id'] === $_SESSION['user_id']): ?>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <a href="/videos/edit/<?= $video['id'] ?>"
                                        class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="/videos/delete" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this video?');">
                                        <input type="hidden" name="id" value="<?= $video['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : '' ?><?= isset($_GET['user_id']) ? '&user_id=' . htmlspecialchars($_GET['user_id']) : '' ?>">
                            Previous
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : '' ?><?= isset($_GET['user_id']) ? '&user_id=' . htmlspecialchars($_GET['user_id']) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : '' ?><?= isset($_GET['user_id']) ? '&user_id=' . htmlspecialchars($_GET['user_id']) : '' ?>">
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>