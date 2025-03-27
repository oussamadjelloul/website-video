<?php
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../lib/CDN.php';

class PostController
{
    private $auth;
    private $postModel;
    private $cdn;

    public function __construct()
    {
        $this->auth = Auth::getInstance();
        $this->postModel = new Post();
        $this->cdn = new CDN();
    }

    // Display all posts
    public function index()
    {
        // Get query parameters for filtering and pagination
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $userId = filter_input(INPUT_GET, 'user_id', FILTER_UNSAFE_RAW);

        // Get posts with pagination
        $posts = $this->postModel->getAll($limit, $offset, $userId);
        $totalPosts = $this->postModel->getCount($userId);
        $totalPages = ceil($totalPosts / $limit);

        // Create a MediaController instance to generate secure URLs
        $mediaController = new MediaController();

        // Generate signed URLs for each post's image
        foreach ($posts as &$post) {
            if (!empty($post['image_url'])) {
                $imagePathParts = explode('/', ltrim($post['image_url'], '/'));
                if (count($imagePathParts) >= 3 && $imagePathParts[0] === 'uploads') {
                    $folder = $imagePathParts[1];
                    $filename = end($imagePathParts);

                    // Generate the secure URL - expires in 20 seconds
                    $post['secure_image_url'] = $mediaController->getSecureUrl($folder, $filename, 20);
                }
            }
        }

        // Pass data to view
        $data = [
            'posts' => $posts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'userId' => $userId
        ];

        require_once __DIR__ . '/../views/posts/index.php';
    }

    // Display single post
    public function show($id)
    {
        $post = $this->postModel->getById($id);

        if (!$post) {
            $_SESSION['errors'] = ['Post not found'];
            header('Location: /posts');
            exit;
        }

        // Create a MediaController instance to generate secure URLs
        $mediaController = new MediaController();

        // Generate a signed URL for the image with a 24-hour expiration
        if (!empty($post['image_url'])) {
            $imagePathParts = explode('/', ltrim($post['image_url'], '/'));
            if (count($imagePathParts) >= 3 && $imagePathParts[0] === 'uploads') {
                $folder = $imagePathParts[1];
                $filename = end($imagePathParts);

                // Add custom claims based on user role/permissions
                $customClaims = [];

                // Add user information if logged in
                if ($this->auth->check()) {
                    $user = $this->auth->user();
                    $customClaims['userId'] = $user['id'];
                }

                // Generate the secure URL - expires in 24 hours (86400 seconds)
                $post['secure_image_url'] = $mediaController->getSecureUrl($folder, $filename, 86400, $customClaims);
            }
        }

        require_once __DIR__ . '/../views/posts/show.php';
    }

    // Display post creation form
    public function create()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to create a post'];
            header('Location: /auth/login');
            exit;
        }

        require_once __DIR__ . '/../views/posts/create.php';
    }

    // Process post creation
    public function store()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to create a post'];
            header('Location: /auth/login');
            exit;
        }

        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /posts/create');
            exit;
        }

        // Get form data
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $content = $_POST['content'] ?? '';
        $userId = $this->auth->user()['id'];

        // Validate form data
        $errors = [];

        if (empty($title)) {
            $errors[] = 'Title is required';
        }

        if (empty($content)) {
            $errors[] = 'Content is required';
        }

        // Handle image upload if present
        $imageUrl = null;
        $cdnUrl = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];

            // Validate file type
            $fileType = $file['type'];
            if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
                $errors[] = 'Invalid image type. Allowed types: ' . implode(', ', ALLOWED_IMAGE_TYPES);
            }

            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'File size exceeds the maximum allowed size of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            }

            if (empty($errors)) {
                // Generate unique filename
                $fileName = uniqid('post_') . '_' . $file['name'];
                $uploadPath = UPLOAD_DIR . 'images/' . $fileName;

                // Create directory if it doesn't exist
                if (!is_dir(dirname($uploadPath))) {
                    mkdir(dirname($uploadPath), 0755, true);
                }

                // Move file to uploads directory
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $imageUrl = '/uploads/images/' . $fileName;

                    // // Upload to CDN (if configured)
                    // $cdnResult = $this->cdn->upload($uploadPath);
                    // if ($cdnResult['success']) {
                    //     $cdnUrl = $cdnResult['url'];
                    // }
                } else {
                    $errors[] = 'Failed to upload image';
                }
            }
        }

        // If validation errors, return to form
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'title' => $title,
                'content' => $content
            ];
            header('Location: /posts/create');
            exit;
        }

        // Create the post
        $postData = [
            'title' => $title,
            'content' => $content,
            'user_id' => $userId,
            'image_url' => $imageUrl,
            'cdn_url' => $cdnUrl
        ];

        $result = $this->postModel->create($postData);

        if ($result) {
            $_SESSION['success_message'] = 'Post created successfully';
            header('Location: /posts');
        } else {
            $_SESSION['errors'] = ['Failed to create post'];
            $_SESSION['form_data'] = [
                'title' => $title,
                'content' => $content
            ];
            header('Location: /posts/create');
        }

        exit;
    }

    // Display post edit form
    public function edit($id)
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to edit a post'];
            header('Location: /auth/login');
            exit;
        }

        $post = $this->postModel->getById($id);

        if (!$post) {
            $_SESSION['errors'] = ['Post not found'];
            header('Location: /posts');
            exit;
        }

        // Check if user owns the post
        if ($post['user_id'] !== $this->auth->user()['id']) {
            $_SESSION['errors'] = ['You do not have permission to edit this post'];
            header('Location: /posts');
            exit;
        }

        require_once __DIR__ . '/../views/posts/edit.php';
    }

    // Process post update
    public function update($id)
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to update a post'];
            header('Location: /auth/login');
            exit;
        }

        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /posts/edit?id=' . $id);
            exit;
        }

        $post = $this->postModel->getById($id);

        if (!$post) {
            $_SESSION['errors'] = ['Post not found'];
            header('Location: /posts');
            exit;
        }

        // Check if user owns the post
        if ($post['user_id'] !== $this->auth->user()['id']) {
            $_SESSION['errors'] = ['You do not have permission to update this post'];
            header('Location: /posts');
            exit;
        }

        // Get form data
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $content = $_POST['content'] ?? '';

        // Validate form data
        $errors = [];

        if (empty($title)) {
            $errors[] = 'Title is required';
        }

        if (empty($content)) {
            $errors[] = 'Content is required';
        }

        // Handle image upload if present
        $imageUrl = $post['image_url'];
        $cdnUrl = $post['cdn_url'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];

            // Validate file type
            $fileType = $file['type'];
            if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
                $errors[] = 'Invalid image type. Allowed types: ' . implode(', ', ALLOWED_IMAGE_TYPES);
            }

            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'File size exceeds the maximum allowed size of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            }

            if (empty($errors)) {
                // Generate unique filename
                $fileName = uniqid('post_') . '_' . $file['name'];
                $uploadPath = UPLOAD_DIR . 'images/' . $fileName;

                // Create directory if it doesn't exist
                if (!is_dir(dirname($uploadPath))) {
                    mkdir(dirname($uploadPath), 0755, true);
                }

                // Move file to uploads directory
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Delete old image if exists
                    if ($imageUrl && file_exists(UPLOAD_DIR . str_replace('/uploads/', '', $imageUrl))) {
                        unlink(UPLOAD_DIR . str_replace('/uploads/', '', $imageUrl));
                    }

                    $imageUrl = '/uploads/images/' . $fileName;

                    // // Upload to CDN (if configured)
                    // $cdnResult = $this->cdn->upload($uploadPath);
                    // if ($cdnResult['success']) {
                    //     $cdnUrl = $cdnResult['url'];
                    // }
                } else {
                    $errors[] = 'Failed to upload image';
                }
            }
        }

        // If validation errors, return to form
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'title' => $title,
                'content' => $content
            ];
            header('Location: /posts/edit?id=' . $id);
            exit;
        }

        // Update the post
        $postData = [
            'title' => $title,
            'content' => $content,
            'image_url' => $imageUrl,
            'cdn_url' => $cdnUrl
        ];

        $result = $this->postModel->update($id, $postData);

        if ($result) {
            $_SESSION['success_message'] = 'Post updated successfully';
            header('Location: /posts/view?id=' . $id);
        } else {
            $_SESSION['errors'] = ['Failed to update post'];
            $_SESSION['form_data'] = [
                'title' => $title,
                'content' => $content
            ];
            header('Location: /posts/edit?id=' . $id);
        }

        exit;
    }

    // Process post deletion
    public function delete($id)
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to delete a post'];
            header('Location: /auth/login');
            exit;
        }

        $post = $this->postModel->getById($id);

        if (!$post) {
            $_SESSION['errors'] = ['Post not found'];
            header('Location: /posts');
            exit;
        }

        // Check if user owns the post
        if ($post['user_id'] !== $this->auth->user()['id']) {
            $_SESSION['errors'] = ['You do not have permission to delete this post'];
            header('Location: /posts');
            exit;
        }

        // Delete the post
        $result = $this->postModel->delete($id);

        // Delete associated image if exists
        if ($result && $post['image_url'] && file_exists(UPLOAD_DIR . str_replace('/uploads/', '', $post['image_url']))) {
            unlink(UPLOAD_DIR . str_replace('/uploads/', '', $post['image_url']));
        }

        if ($result) {
            $_SESSION['success_message'] = 'Post deleted successfully';
        } else {
            $_SESSION['errors'] = ['Failed to delete post'];
        }

        header('Location: /posts');
        exit;
    }
}
