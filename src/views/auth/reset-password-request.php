<?php
$pageTitle = 'Reset Password';

// Start output buffering
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Reset Your Password</h4>
            </div>
            <div class="card-body">
                <p class="mb-3">Enter your email address and we'll send you a link to reset your password.</p>

                <form action="/auth/reset-password-request" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
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