<?php
class Auth
{
    private static $instance = null;

    // Singleton pattern
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }

    // Register a new user
    public function register($email, $password, $name = '')
    {
        $db = Database::getInstance();

        // Check if user already exists
        $db->query("SELECT * FROM users WHERE email = :email");
        $db->bind(':email', $email);
        $user = $db->fetch();

        if ($user) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Generate UUID
        $uuid = $this->generateUUID();

        // Insert user
        $db->query("INSERT INTO users (id, email, password, name) VALUES (:id, :email, :password, :name)");
        $db->bind(':id', $uuid);
        $db->bind(':email', $email);
        $db->bind(':password', $hashedPassword);
        $db->bind(':name', $name);

        if ($db->execute()) {
            return ['success' => true, 'user_id' => $uuid];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    // Login a user
    public function login($email, $password)
    {
        $db = Database::getInstance();

        // Find user by email
        $db->query("SELECT * FROM users WHERE email = :email");
        $db->bind(':email', $email);
        $user = $db->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];

        return ['success' => true, 'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ]];
    }

    // Logout
    public function logout()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        session_destroy();
        return ['success' => true];
    }

    // Check if user is logged in
    public function check()
    {
        return isset($_SESSION['user_id']);
    }

    // Get current user
    public function user()
    {
        if (!$this->check()) {
            return null;
        }

        $db = Database::getInstance();
        $db->query("SELECT id, email, name, created_at, updated_at FROM users WHERE id = :id");
        $db->bind(':id', $_SESSION['user_id']);
        return $db->fetch();
    }

    // Generate UUID
    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
