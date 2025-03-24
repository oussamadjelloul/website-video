<?php
$pageTitle = 'Login';

// Start output buffering
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <form action="/auth/login" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <div class="mb-2">
                    <a href="/auth/reset-password-request">Forgot your password?</a>
                </div>
                <div>
                    Don't have an account? <a href="/auth/register">Register</a>
                </div>
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