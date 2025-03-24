<?php
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $auth;
    private $userModel;

    public function __construct()
    {
        $this->auth = Auth::getInstance();
        $this->userModel = new User();
    }

    // Display register form
    public function showRegisterForm()
    {
        // Include register view
        require_once __DIR__ . '/../views/auth/register.php';
    }

    // Process registration
    public function register()
    {
        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/register');
            exit;
        }

        // Get form data
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);

        // Basic validation
        $errors = [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        // If validation errors, return to form
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'email' => $email,
                'name' => $name
            ];
            header('Location: /auth/register');
            exit;
        }

        // Register the user
        $result = $this->auth->register($email, $password, $name);

        if ($result['success']) {
            // Send verification email (simplified for demo)
            $verificationCode = md5(uniqid(rand(), true));

            // Store verification code in session for demo
            // In production, store in database with user ID
            $_SESSION['verification_codes'][$email] = $verificationCode;

            // Redirect to login with success message
            $_SESSION['success_message'] = 'Registration successful! Please check your email for verification.';
            header('Location: /auth/login');
            exit;
        } else {
            $_SESSION['errors'] = [$result['message']];
            $_SESSION['form_data'] = [
                'email' => $email,
                'name' => $name
            ];
            header('Location: /auth/register');
            exit;
        }
    }

    // Display login form
    public function showLoginForm()
    {
        // Include login view
        require_once __DIR__ . '/../views/auth/login.php';
    }

    // Process login
    public function login()
    {
        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/login');
            exit;
        }

        // Get form data
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        // Basic validation
        $errors = [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        // If validation errors, return to form
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'email' => $email
            ];
            header('Location: /auth/login');
            exit;
        }

        // Login the user
        $result = $this->auth->login($email, $password);

        if ($result['success']) {
            // Redirect to dashboard
            $_SESSION['success_message'] = 'Login successful!';
            header('Location: /');
            exit;
        } else {
            $_SESSION['errors'] = [$result['message']];
            $_SESSION['form_data'] = [
                'email' => $email
            ];
            header('Location: /auth/login');
            exit;
        }
    }

    // Process logout
    public function logout()
    {
        $this->auth->logout();
        $_SESSION['success_message'] = 'You have been logged out';
        header('Location: /auth/login');
        exit;
    }

    // Display password reset request form
    public function showPasswordResetRequestForm()
    {
        require_once __DIR__ . '/../views/auth/reset-password-request.php';
    }

    // Process password reset request
    public function processPasswordResetRequest()
    {
        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/reset-password-request');
            exit;
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errors'] = ['Please enter a valid email address'];
            header('Location: /auth/reset-password-request');
            exit;
        }

        // Check if user exists
        $user = $this->userModel->findByEmail($email);

        if ($user) {
            // Generate reset token
            $resetToken = md5(uniqid(rand(), true));

            // Store reset token in session for demo
            // In production, store in database with user ID and expiration
            $_SESSION['reset_tokens'][$email] = [
                'token' => $resetToken,
                'expires' => time() + 3600 // 1 hour
            ];

            // Send reset email (simplified for demo)
            $_SESSION['success_message'] = 'Password reset instructions have been sent to your email';
        } else {
            // Don't reveal if email exists or not for security
            $_SESSION['success_message'] = 'If your email exists in our system, you will receive reset instructions';
        }

        header('Location: /auth/login');
        exit;
    }

    // Display password reset form
    public function showPasswordResetForm()
    {
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';

        // Validate token (simplified for demo)
        $isValidToken = false;

        if (
            isset($_SESSION['reset_tokens'][$email]) &&
            $_SESSION['reset_tokens'][$email]['token'] === $token &&
            $_SESSION['reset_tokens'][$email]['expires'] > time()
        ) {
            $isValidToken = true;
        }

        if (!$isValidToken) {
            $_SESSION['errors'] = ['Invalid or expired password reset token'];
            header('Location: /auth/login');
            exit;
        }

        require_once __DIR__ . '/../views/auth/reset-password.php';
    }

    // Process password reset
    public function processPasswordReset()
    {
        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/login');
            exit;
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate token (simplified for demo)
        $isValidToken = false;

        if (
            isset($_SESSION['reset_tokens'][$email]) &&
            $_SESSION['reset_tokens'][$email]['token'] === $token &&
            $_SESSION['reset_tokens'][$email]['expires'] > time()
        ) {
            $isValidToken = true;
        }

        if (!$isValidToken) {
            $_SESSION['errors'] = ['Invalid or expired password reset token'];
            header('Location: /auth/login');
            exit;
        }

        // Validate password
        $errors = [];

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /auth/reset-password?token=' . $token . '&email=' . $email);
            exit;
        }

        // Update password
        $user = $this->userModel->findByEmail($email);

        if ($user && $this->userModel->updatePassword($user['id'], $password)) {
            // Remove used token
            unset($_SESSION['reset_tokens'][$email]);

            $_SESSION['success_message'] = 'Your password has been reset successfully';
            header('Location: /auth/login');
        } else {
            $_SESSION['errors'] = ['Failed to reset password'];
            header('Location: /auth/reset-password?token=' . $token . '&email=' . $email);
        }

        exit;
    }
}
