<?php
/**
 * MediaController
 * 
 * Handles serving media files from the uploads directory
 * with appropriate cache headers
 */
class MediaController {
    
    // Base directory for all uploads
    private $uploadsBaseDir;
    
    // Cache durations in seconds
    private $cacheTimes = [
        'images' => 604800,     // 1 week
        'thumbnails' => 1209600, // 2 weeks
        'videos' => 31536000,   // 1 year
    ];
    
    // Content type mappings
    private $contentTypes = [
        // Images
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        
        // Videos
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
        
        // Audio
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        
        // Documents
        'pdf' => 'application/pdf',
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set base directory for uploads
        $this->uploadsBaseDir = __DIR__ . '/../../public/uploads';
    }
    
    /**
     * Serve a file from the uploads directory
     * 
     * @param string $folder The folder within uploads (images, videos, thumbnails)
     * @param string $filename The filename to serve
     */
    public function serve($folder, $filename) {
        $filename = urldecode($filename);
        error_log("MediaController->serve called: folder=$folder, file=$filename");
        // Validate folder - only allow specific folders
        if (!in_array($folder, ['images', 'thumbnails', 'videos'])) {
            $this->send404('Invalid folder');
            return;
        }
        
        // Validate filename to prevent directory traversal
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            $this->send404('Invalid filename');
            return;
        }
        
        // Build full path to file
        $filePath = $this->uploadsBaseDir . '/' . $folder . '/' . $filename;
        
        // Check if file exists
        if (!file_exists($filePath)) {
            $this->send404('File not found');
            return;
        }
        
        // Get file extension and check if it's supported
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!isset($this->contentTypes[$extension])) {
            $this->send404('Unsupported file type');
            return;
        }
        
        // Set content type
        header('Content-Type: ' . $this->contentTypes[$extension]);
        
        // Set cache duration based on folder type
        $cacheTime = $this->cacheTimes[$folder] ?? 3600; // Default 1 hour
        
        // Set cache headers
        header('Cache-Control: public, max-age=' . $cacheTime . ', s-maxage=' . ($cacheTime * 2));
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
        
        // Set ETag for cache validation
        $etag = md5(filemtime($filePath) . filesize($filePath));
        header('ETag: "' . $etag . '"');
        
        // Check if client has a valid cached version
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
            trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }
        
        // Set content length
        header('Content-Length: ' . filesize($filePath));
        
        // For videos, support range requests (for seeking)
        if (strpos($this->contentTypes[$extension], 'video/') === 0) {
            $this->handleRangeRequest($filePath);
        } else {
            // Output file for other types
            readfile($filePath);
        }
        
        exit;
    }
    
    /**
     * Handle HTTP Range requests for video seeking
     * 
     * @param string $filePath Path to the video file
     */
    private function handleRangeRequest($filePath) {
        $fileSize = filesize($filePath);
        $offset = 0;
        $length = $fileSize;
        
        // Process range header if present
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches)) {
                $offset = intval($matches[1]);
                $length = isset($matches[2]) ? (intval($matches[2]) - $offset + 1) : ($fileSize - $offset);
                
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes ' . $offset . '-' . ($offset + $length - 1) . '/' . $fileSize);
                header('Content-Length: ' . $length);
            }
        }
        
        // Output the file with requested offset and length
        $handle = fopen($filePath, 'rb');
        fseek($handle, $offset);
        
        $buffer = 1024 * 8; // 8KB buffer
        $totalRead = 0;
        
        while (!feof($handle) && $totalRead < $length) {
            $toRead = min($buffer, $length - $totalRead);
            echo fread($handle, $toRead);
            $totalRead += $toRead;
            flush();
        }
        
        fclose($handle);
    }
    
    /**
     * Send a 404 response
     * 
     * @param string $message Error message
     */
    private function send404($message = 'File not found') {
        header('HTTP/1.0 404 Not Found');
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - File Not Found</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                h1 { font-size: 36px; margin-bottom: 10px; }
                p { font-size: 18px; color: #555; }
            </style>
        </head>
        <body>
            <h1>404</h1>
            <p>' . htmlspecialchars($message) . '</p>
            <p><a href="/">Back to Home</a></p>
        </body>
        </html>';
        exit;
    }
    
    /**
     * Stream a video file (alternative method for streaming)
     * Could be used for more advanced video streaming needs
     * 
     * @param string $folder The folder (videos)
     * @param string $filename The video filename
     */
    public function streamVideo($folder, $filename) {
        // Only allow videos folder
        if ($folder !== 'videos') {
            $this->send404('Invalid folder');
            return;
        }
        
        // Rest of validation same as serve()
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            $this->send404('Invalid filename');
            return;
        }
        
        $filePath = $this->uploadsBaseDir . '/' . $folder . '/' . $filename;
        
        if (!file_exists($filePath)) {
            $this->send404('Video not found');
            return;
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!isset($this->contentTypes[$extension]) || 
            strpos($this->contentTypes[$extension], 'video/') !== 0) {
            $this->send404('Invalid video format');
            return;
        }
        
        // Set video content type
        header('Content-Type: ' . $this->contentTypes[$extension]);
        
        // Set cache headers - long cache for videos
        $cacheTime = $this->cacheTimes['videos'];
        header('Cache-Control: public, max-age=' . $cacheTime . ', s-maxage=' . ($cacheTime * 2));
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
        
        // Handle range requests for video seeking
        $this->handleRangeRequest($filePath);
        exit;
    }
}