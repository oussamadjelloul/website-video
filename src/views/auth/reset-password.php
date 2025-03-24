<?php
$pageTitle = 'Set New Password';

// Start output buffering
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Set New Password</h4>
            </div>
            <div class="card-body">
                <form action="/auth/reset-password" method="post">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                            required minlength="8">
                        <div class="form-text">Password must be at least 8 characters long</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            required minlength="8">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Set New Password</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <a href="/auth/login">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php
// Get the content from the output buffer
$content = ob_get_clean();

// Include the main layout template
require_once __DIR__ . '/../layouts/main.php';
?>