<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h1>My Profile</h1>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Profile Information</h5>
                    <div class="text-center mb-4">
                        <img src="https://via.placeholder.com/150" class="rounded-circle mb-3" alt="Profile avatar" width="150" height="150">
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Name:</strong></span>
                            <span><?= htmlspecialchars($user['name'] ?? 'Not provided') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Email:</strong></span>
                            <span><?= htmlspecialchars($user['email']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Member Since:</strong></span>
                            <span><?= date('M j, Y', strtotime($user['created_at'])) ?></span>
                        </li>
                    </ul>
                    <div class="mt-3">
                        <a href="/profile/edit" class="btn btn-primary w-100 mb-2">Edit Profile</a>
                        <a href="/profile/change-password" class="btn btn-outline-secondary w-100">Change Password</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Recent Posts</h5>
                    <a href="/posts?user_id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <p>Your most recent posts will appear here.</p>
                    <a href="/posts/create" class="btn btn-primary">Create New Post</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Recent Videos</h5>
                    <a href="/videos?user_id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <p>Your most recent videos will appear here.</p>
                    <a href="/videos/create" class="btn btn-primary">Upload New Video</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>