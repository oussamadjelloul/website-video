<?php
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/Video.php';
require_once __DIR__ . '/../lib/CDN.php';

class VideoController
{
    private $auth;
    private $videoModel;
    private $cdn;

    public function __construct()
    {
        $this->auth = Auth::getInstance();
        $this->videoModel = new Video();
        $this->cdn = new CDN();
    }

    // Display all videos
    public function index()
    {
        // Get query parameters for filtering and pagination
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $userId = filter_input(INPUT_GET, 'user_id', FILTER_UNSAFE_RAW);

        // Get videos with pagination
        $videos = $this->videoModel->getAll($limit, $offset, $userId);
        $totalVideos = $this->videoModel->getCount($userId);
        $totalPages = ceil($totalVideos / $limit);

        // Pass data to view
        $data = [
            'videos' => $videos,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'userId' => $userId
        ];

        require_once __DIR__ . '/../views/videos/index.php';
    }

    // Display single video
    public function show($id)
    {
        $video = $this->videoModel->getById($id);

        if (!$video) {
            $_SESSION['errors'] = ['Video not found'];
            header('Location: /videos');
            exit;
        }

        require_once __DIR__ . '/../views/videos/show.php';
    }

    // Display video upload form
    public function create()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to upload a video'];
            header('Location: /auth/login');
            exit;
        }

        require_once __DIR__ . '/../views/videos/create.php';
    }

    // Process video upload
    public function store()
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to upload a video'];
            header('Location: /auth/login');
            exit;
        }

        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /videos/create');
            exit;
        }


        // Get form data
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
        $userId = $this->auth->user()['id'];


        // Validate form data
        $errors = [];

        if (empty($title)) {
            $errors[] = 'Title is required';
        }
        if (empty($description)) {
            $errors[] = 'description is required';
        }

        // Check if video file is uploaded
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Video file is required';
        } else {
            $file = $_FILES['video'];

            // Validate file type
            $fileType = $file['type'];
            if (!in_array($fileType, ALLOWED_VIDEO_TYPES)) {
                $errors[] = 'Invalid video format. Allowed formats: ' . implode(', ', ALLOWED_VIDEO_TYPES);
            }

            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'File size exceeds the maximum allowed size of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            }
        }


        // Check if thumbnail file is uploaded
        $thumbnail = $_FILES['thumbnail'] ?? null;
        if (!$thumbnail) {
            $errors[] = 'Thumbnail file is required';
        }

        if ($thumbnail) {
            // Validate file type
            $fileType = $thumbnail['type'];
            if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
                $errors[] = 'Invalid thumbnail format. Allowed formats: ' . implode(', ', ALLOWED_IMAGE_TYPES);
            }

            // Validate file size
            if ($thumbnail['size'] > MAX_FILE_SIZE) {
                $errors[] = 'File size exceeds the maximum allowed size of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            }
        }
        // If validation errors, return to form
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'title' => $title,
                'description' => $description
            ];
            header('Location: /videos/create');
            exit;
        }

        // Process video upload
        $file = $_FILES['video'];
        $fileName = uniqid('video_') . '_' . pathinfo($file['name'], PATHINFO_FILENAME) . '.mp4';
        $uploadPath = UPLOAD_DIR . 'videos/' . $fileName;

        // Create directory if it doesn't exist
        if (!is_dir(dirname($uploadPath))) {
            mkdir(dirname($uploadPath), 0755, true);
        }

        // Move file to uploads directory
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $_SESSION['errors'] = ['Failed to upload video file'];
            $_SESSION['form_data'] = [
                'title' => $title,
                'description' => $description
            ];
            header('Location: /videos/create');
            exit;
        }

        $videoUrl = '/uploads/videos/' . $fileName;
        $cdnUrl = null;
        $thumbnailUrl = null;
        $duration = 0;
        // Generate video thumbnail
        $thumbnailFileName = uniqid('thumb_') . '.jpg';
        $thumbnailPath = UPLOAD_DIR . 'thumbnails/' . $thumbnailFileName;

        // Create thumbnails directory if it doesn't exist
        if (!is_dir(dirname($thumbnailPath))) {
            mkdir(dirname($thumbnailPath), 0755, true);
        }

        // Move image thumbnail to the uploads directory
        if (!move_uploaded_file($thumbnail['tmp_name'], $thumbnailPath)) {
            $_SESSION['errors'] = ['Failed to upload thumbnail file'];
            $_SESSION['form_data'] = [
                'title' => $title,
                'description' => $description
            ];
            header('Location: /videos/create');
            exit;
        }

        // Set the thumbnail URL for database storage
        $thumbnailUrl = '/uploads/thumbnails/' . $thumbnailFileName;


        // // Upload to CDN if configured
        // $cdnResult = $this->cdn->upload($uploadPath);
        // if ($cdnResult['success']) {
        //     $cdnUrl = $cdnResult['url'];
        // }

        // Store video in database
        $videoData = [
            'title' => $title,
            'description' => $description,
            'video_url' => $videoUrl,
            'cdn_url' => $cdnUrl,
            'thumbnail_url' => $thumbnailUrl,
            'duration' => $duration,
            'user_id' => $userId
        ];

        $result = $this->videoModel->create($videoData);

        if ($result) {
            $_SESSION['success_message'] = 'Video uploaded successfully';
            header('Location: /videos');
        } else {
            $_SESSION['errors'] = ['Failed to save video information'];
            $_SESSION['form_data'] = [
                'title' => $title,
                'description' => $description
            ];
            header('Location: /videos/create');
        }

        exit;
    }

    // Display video edit form
    public function edit($id)
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to edit a video'];
            header('Location: /auth/login');
            exit;
        }

        $video = $this->videoModel->getById($id);

        if (!$video) {
            $_SESSION['errors'] = ['Video not found'];
            header('Location: /videos');
            exit;
        }

        // Check if user owns the video
        if ($video['user_id'] !== $this->auth->user()['id']) {
            $_SESSION['errors'] = ['You do not have permission to edit this video'];
            header('Location: /videos');
            exit;
        }

        require_once __DIR__ . '/../views/videos/edit';
    }

    // Process video update
    public function update($id)
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to update a video'];
            header('Location: /auth/login');
            exit;
        }

        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /videos/edit?id=' . $id);
            exit;
        }

        $video = $this->videoModel->getById($id);

        if (!$video) {
            $_SESSION['errors'] = ['Video not found'];
            header('Location: /videos');
            exit;
        }

        // Check if user owns the video
        if ($video['user_id'] !== $this->auth->user()['id']) {
            $_SESSION['errors'] = ['You do not have permission to update this video'];
            header('Location: /videos');
            exit;
        }

        // Get form data
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);

        // Validate form data
        $errors = [];

        if (empty($title)) {
            $errors[] = 'Title is required';
        }

        // Handle new thumbnail upload if present
        $thumbnailUrl = $video['thumbnail_url'];

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['thumbnail'];

            // Validate file type
            $fileType = $file['type'];
            if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
                $errors[] = 'Invalid thumbnail format. Allowed formats: ' . implode(', ', ALLOWED_IMAGE_TYPES);
            }

            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'File size exceeds the maximum allowed size of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            }

            if (empty($errors)) {
                // Generate unique filename
                $fileName = uniqid('thumb_') . '_' . $file['name'];
                $uploadPath = UPLOAD_DIR . 'thumbnails/' . $fileName;

                // Create directory if it doesn't exist
                if (!is_dir(dirname($uploadPath))) {
                    mkdir(dirname($uploadPath), 0755, true);
                }

                // Move file to uploads directory
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Delete old thumbnail if exists
                    if ($thumbnailUrl && file_exists(UPLOAD_DIR . str_replace('/uploads/', '', $thumbnailUrl))) {
                        unlink(UPLOAD_DIR . str_replace('/uploads/', '', $thumbnailUrl));
                    }

                    $thumbnailUrl = '/uploads/thumbnails/' . $fileName;
                } else {
                    $errors[] = 'Failed to upload thumbnail';
                }
            }
        }

        // If validation errors, return to form
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'title' => $title,
                'description' => $description
            ];
            header('Location: /videos/edit?id=' . $id);
            exit;
        }

        // Update the video
        $videoData = [
            'title' => $title,
            'description' => $description,
            'thumbnail_url' => $thumbnailUrl
        ];

        $result = $this->videoModel->update($id, $videoData);

        if ($result) {
            $_SESSION['success_message'] = 'Video updated successfully';
            header('Location: /videos/view?id=' . $id);
        } else {
            $_SESSION['errors'] = ['Failed to update video'];
            $_SESSION['form_data'] = [
                'title' => $title,
                'description' => $description
            ];
            header('Location: /videos/edit?id=' . $id);
        }

        exit;
    }

    // Process video deletion
    public function delete($id)
    {
        // Check if user is logged in
        if (!$this->auth->check()) {
            $_SESSION['errors'] = ['You must be logged in to delete a video'];
            header('Location: /auth/login');
            exit;
        }

        $video = $this->videoModel->getById($id);

        if (!$video) {
            $_SESSION['errors'] = ['Video not found'];
            header('Location: /videos');
            exit;
        }

        // Check if user owns the video
        if ($video['user_id'] !== $this->auth->user()['id']) {
            $_SESSION['errors'] = ['You do not have permission to delete this video'];
            header('Location: /videos');
            exit;
        }

        // Delete the video
        $result = $this->videoModel->delete($id);

        // Delete associated video file if exists
        if ($result && $video['video_url'] && file_exists(UPLOAD_DIR . str_replace('/uploads/', '', $video['video_url']))) {
            unlink(UPLOAD_DIR . str_replace('/uploads/', '', $video['video_url']));
        }

        // Delete associated thumbnail if exists
        if ($result && $video['thumbnail_url'] && file_exists(UPLOAD_DIR . str_replace('/uploads/', '', $video['thumbnail_url']))) {
            unlink(UPLOAD_DIR . str_replace('/uploads/', '', $video['thumbnail_url']));
        }

        if ($result) {
            $_SESSION['success_message'] = 'Video deleted successfully';
        } else {
            $_SESSION['errors'] = ['Failed to delete video'];
        }

        header('Location: /videos');
        exit;
    }
}
