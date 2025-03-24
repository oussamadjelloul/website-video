<?php
$pageTitle = 'Edit Profile';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="form-container">
                <h2 class="mb-4">Edit Profile</h2>

                <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>

                <form method="POST" action="/profile/update" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="<?= isset($_SESSION['form_data']['name']) ? htmlspecialchars($_SESSION['form_data']['name']) : htmlspecialchars($user['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?= isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : htmlspecialchars($user['email'] ?? '') ?>"
                            required>
                        <div class="invalid-feedback">Please enter a valid email address</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/profile" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
                <?php unset($_SESSION['form_data']); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>