<?php
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/User.php';

class ProfileController
{
    private $auth;
    private $userModel;

    public function __construct()
    {
        $this->auth = Auth::getInstance();
        $this->userModel = new User();
    }

    // Display user profile
    public function show()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to view your profile'];
            header('Location: /auth/login');
            exit;
        }

        // Get user data
        $user = $this->auth->user();
        
        // Generate secure URL for profile image if it exists
        if (!empty($user['profile_image'])) {
            $mediaController = new MediaController();
            $imagePathParts = explode('/', ltrim($user['profile_image'], '/'));
            if (count($imagePathParts) >= 3 && $imagePathParts[0] === 'uploads') {
                $folder = $imagePathParts[1];
                $filename = end($imagePathParts);
                
                // Generate the secure URL - expires in 24 hours (86400 seconds)
                $user['secure_profile_image'] = $mediaController->getSecureUrl($folder, $filename, 86400);
            }
        }

        // Get user's posts and videos (if needed)
        // $posts = (new Post())->getByUserId($user['id'], 5);
        // $videos = (new Video())->getByUserId($user['id'], 5);

        // Pass to view
        require_once __DIR__ . '/../views/profile/index.php';
    }

    // Display edit profile form
    public function edit()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to edit your profile'];
            header('Location: /auth/login');
            exit;
        }

        // Get user data
        $user = $this->auth->user();

        // Pass to view
        require_once __DIR__ . '/../views/profile/edit.php';
    }

    // Update user profile
    public function update()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to update your profile'];
            header('Location: /auth/login');
            exit;
        }

        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile/edit');
            exit;
        }

        // Get form data
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $currentPassword = $_POST['current_password'] ?? '';

        // Get current user data
        $user = $this->auth->user();

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errors'] = ['Please enter a valid email address'];
            $_SESSION['form_data'] = ['name' => $name];
            header('Location: /profile/edit');
            exit;
        }

        // Check if email changed and is already in use
        if ($email !== $user['email']) {
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser) {
                $_SESSION['errors'] = ['Email address is already in use'];
                $_SESSION['form_data'] = ['name' => $name];
                header('Location: /profile/edit');
                exit;
            }
        }

        // Update user profile
        $updateData = [
            'name' => $name,
            'email' => $email
        ];

        $result = $this->userModel->update($user['id'], $updateData);

        if ($result) {
            // Update session data
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;

            $_SESSION['success_message'] = 'Profile updated successfully';
            header('Location: /profile');
        } else {
            $_SESSION['errors'] = ['Failed to update profile'];
            $_SESSION['form_data'] = ['name' => $name];
            header('Location: /profile/edit');
        }
        exit;
    }

    // Display change password form
    public function showChangePasswordForm()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to change your password'];
            header('Location: /auth/login');
            exit;
        }

        // Pass to view
        require_once __DIR__ . '/../views/profile/change-password.php';
    }

    // Process password change
    public function changePassword()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to change your password'];
            header('Location: /auth/login');
            exit;
        }

        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile/change-password');
            exit;
        }

        // Get form data
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Get current user
        $user = $this->auth->user();

        // Validate passwords
        $errors = [];

        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }

        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        }

        // If there are errors, redirect back to form
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /profile/change-password');
            exit;
        }

        // Verify current password
        $db = Database::getInstance();
        $db->query("SELECT password FROM users WHERE id = :id");
        $db->bind(':id', $user['id']);
        $userData = $db->fetch();

        if (!$userData || !password_verify($currentPassword, $userData['password'])) {
            $_SESSION['errors'] = ['Current password is incorrect'];
            header('Location: /profile/change-password');
            exit;
        }

        // Update the password
        $result = $this->userModel->updatePassword($user['id'], $newPassword);

        if ($result) {
            $_SESSION['success_message'] = 'Password changed successfully';
            header('Location: /profile');
        } else {
            $_SESSION['errors'] = ['Failed to update password'];
            header('Location: /profile/change-password');
        }
        exit;
    }
}
