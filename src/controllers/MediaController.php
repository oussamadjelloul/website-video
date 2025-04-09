<?php
require_once __DIR__ . '/../lib/CDN.php';

/**
 * MediaController
 *
 * Handles serving media files from the uploads directory
 * with appropriate cache headers
 */
class MediaController
{

    // Base directory for all uploads
    private $uploadsBaseDir;
    private $cdn;

    // Cache durations in seconds
    private $cacheTimes = [
        'images' => 20,     // Set to 20 seconds for testing
        'thumbnails' => 20, // Set to 20 seconds for testing
        'videos' => 20,     // Set to 20 seconds for testing
    ];

    // Cache durations for token-protected content
    private $tokenCacheTimes = [
        'images' => 0,          // No caching for token-protected images
        'thumbnails' => 0,      // No caching for token-protected thumbnails
        'videos' => 20,         // 20 seconds for token-protected videos
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
    public function __construct()
    {
        // Set base directory for uploads
        $this->uploadsBaseDir = __DIR__ . '/../../public/uploads';
        $this->cdn = new CDN();
    }

    /**
     * Serve a file from the uploads directory
     *
     * @param string $folder The folder within uploads (images, videos, thumbnails)
     * @param string $filename The filename to serve
     * @param string $token JWT token for validation (optional)
     */
    public function serve($folder, $filename)
    {
        $token = isset($_GET['URISigningPackage']) ? $_GET['URISigningPackage'] : null;
        $t = isset($_GET['t']) ? $_GET['t'] : null;
        $filename = urldecode($filename);
        $filePath = $this->uploadsBaseDir . '/' . $folder . '/' . $filename;
        error_log("MediaController->serve called: folder=$folder, file=$filename");
        error_log("MediaController path debug: __DIR__ = " . __DIR__);
        error_log("MediaController path debug: uploadsBaseDir = " . $this->uploadsBaseDir);
        error_log("MediaController path debug: full file path = " . $filePath);
        error_log("MediaController path debug: file exists check = " . (file_exists($filePath) ? 'true' : 'false'));
        error_log("generateSignedUrl->serve called: folder=$folder, file=$filename");
        // Validate folder - only allow specific folders
        error_log(">>>>>>>>>>>>>>>>>>>>>>>>> 88888888888888 >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
        error_log(">>>>>>>>>>>>>>>>>>>>>> token:$token");
        if (!in_array($folder, ['images', 'thumbnails', 'videos'])) {
            $this->send404('Invalid folder');
            return;
        }

        // Validate filename to prevent directory traversal
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            $this->send404('Invalid filename');
            error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 404 ");
            return;
        }

        // Build full path to file
        $filePath = $this->uploadsBaseDir . '/' . $folder . '/' . $filename;

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->send404('File not found');
            error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 404 ");
            return;
        }

        // Verify token if provided
        if ($token) {
            error_log("Verifying token: $token");
            $tokenData = $this->cdn->verifySignedUrlToken($token);

            if (!$tokenData) {
                header('HTTP/1.1 403 Forbidden');
                echo "Invalid or expired token";
                error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 404 : $tokenData ");
                exit;
            }

            // Check if token is for the correct resource
            $expectedPath = "/uploads/{$folder}/{$filename}?t={$t}";
            if (isset($tokenData['sub']) && $tokenData['sub'] !== $expectedPath) {
                header('HTTP/1.1 403 Forbidden');
                error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 404 incorrect resource ");
                echo "Token not valid for this resource";
                exit;
            }

            // Check if token has expired
            //if (isset($tokenData['exp']) && $tokenData['exp'] < time()) {
            //    error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 404 time expired ");
            //  header('HTTP/1.1 403 Forbidden');
            //    echo "Token has expired";
            //    exit;
            // }
            $currentTime = date('Y-m-d H:i:s T'); // Formatted date with timezone
            error_log("Current time: >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>" . $currentTime);
            // Correct token expiration check
            if (isset($tokenData['exp']) && time() > $tokenData['exp']) {
                header('HTTP/1.1 403 Forbidden');
                error_log("Token expired: current time " . time() . " > token expiration " . $tokenData['exp']);
                echo "Token has expired";
                exit;
            }
        } else {
            error_log(" >>>>>>>>>>>>>>>>>>>>>>>>>>  Token not provided, not serving file directly.");
            $this->send404('Unsupported file type');
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

        // Set different cache headers based on whether token is provided
        if ($token) {
            // For token-protected content, use no caching or very short cache time
            $cacheTime = $this->tokenCacheTimes[$folder] ?? 0;

            // Always set no-cache headers for token-protected content
            //header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0');
            //header('Pragma: no-cache');
            //header('Expires: 0');

            // Add a Vary header to ensure proxy servers don't serve cached content across users
            //header('Vary: Authorization, Cookie');

            // Add cache-busting headers
            //header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
            //header('ETag: "' . md5(time()) . '"');
        } else {
            // For public content, use normal cache times
            $cacheTime = $this->cacheTimes[$folder] ?? 20; // Default 20 seconds

            // Set cache headers
            header('Cache-Control: public, max-age=' . $cacheTime . ', s-maxage=' . ($cacheTime * 2));
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
        }

        // Check if client has a valid cached version
        if (
            !$token && isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
            trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === md5(time())
        ) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }

        // Set content length
        header('Content-Length: ' . filesize($filePath));
        error_log("$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ get images ");
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
     * Generate a secure CDN URL for the given media
     *
     * @param string $folder The folder (images, videos, thumbnails)
     * @param string $filename The filename
     * @param int $expirationTime Time in seconds until the URL expires (default 20 seconds)
     * @param array $customClaims Optional additional claims to include in the token
     * @return string|null The signed URL or direct URL if CDN signing not available
     */
    public function getSecureUrl($folder, $filename, $expirationTime = 20, $customClaims = [])
    {
        // Validate the folder
        if (!in_array($folder, ['images', 'thumbnails', 'videos'])) {
            return null;
        }

        // Validate filename to prevent directory traversal
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            return null;
        }

        // Build the URL path
        $urlPath = "/uploads/{$folder}/{$filename}";

        // If CDN is configured with signing keys, generate a signed URL
        if ($this->cdn->isConfigured()) {
            try {
                // Add specific claims based on content type
                $specificClaims = [];

                // For videos, we might want to restrict specific IP ranges or add other security measures
                if ($folder === 'videos') {
                    $specificClaims['allowedIpRange'] = $_SERVER['REMOTE_ADDR'] ?? '*';

                    // For premium content, we could add user-specific claims
                    if (isset($_SESSION['user_id'])) {
                        $specificClaims['uid'] = $_SESSION['user_id'];
                    }
                }

                // Merge custom and specific claims
                $allClaims = array_merge($customClaims, $specificClaims);

                // Add cache-busting query parameter to prevent browser caching for token-protected content
                // This ensures the browser makes a new request when the token expires
                // TODO wec can test without cacheBuster
                // $cacheBuster = '?t=' . time();

                // Generate the signed URL with Apache Traffic Control CDN compatibility
                $signedUrl = $this->cdn->generateSignedUrl($urlPath, $expirationTime, $allClaims);

                // Add additional cache-busting parameters
                $separator = (strpos($signedUrl, '?') !== false) ? '&' : '?';
                return $signedUrl . $separator . '_=' . time();
            } catch (Exception $e) {
                error_log("Error generating secure URL: " . $e->getMessage());
                // Fall back to direct URL if signing fails
            }
        }

        // Fall back to direct URL if CDN signing is not available
        $baseUrl = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] : 'https://example.com';
        return $baseUrl . $urlPath;
    }

    /**
     * Stream a protected video file with token verification
     *
     * @param string $folder The folder (videos)
     * @param string $filename The video filename
     * @param string $token JWT token for validation
     */
    public function streamSecureVideo($folder, $filename, $token = null)
    {
        // Only allow videos folder
        if ($folder !== 'videos') {
            $this->send404('Invalid folder');
            return;
        }

        // Validate filename
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            $this->send404('Invalid filename');
            return;
        }

        $filePath = $this->uploadsBaseDir . '/' . $folder . '/' . $filename;

        if (!file_exists($filePath)) {
            $this->send404('Video not found');
            return;
        }

        // Verify token if provided
        if ($token) {
            $tokenData = $this->cdn->verifySignedUrlToken($token);

            if (!$tokenData) {
                header('HTTP/1.1 403 Forbidden');
                echo "Invalid or expired token";
                exit;
            }

            // Check if token is for the correct resource
            $expectedPath = "/uploads/{$folder}/{$filename}";
            if ($tokenData['sub'] !== $expectedPath) {
                header('HTTP/1.1 403 Forbidden');
                echo "Token not valid for this resource";
                exit;
            }

            // Check if token has expired
            if (isset($tokenData['exp']) && $tokenData['exp'] < time()) {
                header('HTTP/1.1 403 Forbidden');
                echo "Token has expired";
                exit;
            }

            // Additional validation checks could be performed here,
            // such as checking IP restrictions, user ID, etc.
        }

        // Proceed with streaming as usual
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (
            !isset($this->contentTypes[$extension]) ||
            strpos($this->contentTypes[$extension], 'video/') !== 0
        ) {
            $this->send404('Invalid video format');
            return;
        }

        // Set video content type
        header('Content-Type: ' . $this->contentTypes[$extension]);

        // For protected content, use shorter cache times
        $cacheTime = $token ? $this->tokenCacheTimes['videos'] : $this->cacheTimes['videos'];

        if ($token) {
            // For token-protected videos, use shorter cache time but allow some caching for better streaming
            header('Cache-Control: private, max-age=' . $cacheTime);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
            header('Vary: Authorization, Cookie');
        } else {
            // For public videos, use normal long cache times
            header('Cache-Control: public, max-age=' . $cacheTime . ', s-maxage=' . ($cacheTime * 2));
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
        }

        // Handle range requests for video seeking
        $this->handleRangeRequest($filePath);
        exit;
    }

    /**
     * Handle HTTP Range requests for video seeking
     *
     * @param string $filePath Path to the video file
     */
    private function handleRangeRequest($filePath)
    {
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
    private function send404($message = 'File not found')
    {
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
    public function streamVideo($folder, $filename)
    {
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
        if (
            !isset($this->contentTypes[$extension]) ||
            strpos($this->contentTypes[$extension], 'video/') !== 0
        ) {
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
