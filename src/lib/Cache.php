<?php

namespace Oussama\App\lib;

/**
 * Cache - HTTP Caching utility class
 * 
 * This class provides methods to set appropriate HTTP caching headers
 * for different types of content to work efficiently with reverse proxies.
 */
class Cache {
    /**
     * Default cache control settings
     */
    const NO_CACHE = 'no-store, no-cache, must-revalidate, max-age=0';
    const PUBLIC_CACHE = 'public';
    const PRIVATE_CACHE = 'private';

    /**
     * Set cache headers for static assets (CSS, JS, images)
     * 
     * @param int $maxAge Cache lifetime in seconds
     * @param bool $immutable Whether the resource is immutable (will not change)
     */
    public static function setStaticAssetHeaders(int $maxAge = 604800, bool $immutable = true): void {
        // 604800 = 1 week in seconds
        $cacheControl = self::PUBLIC_CACHE . ", max-age={$maxAge}";
        
        if ($immutable) {
            $cacheControl .= ", immutable";
        }
        
        // Set ETag based on the last modified time of the file (if available)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $requestUri;
        
        if (file_exists($filePath)) {
            $etagValue = '"' . md5_file($filePath) . '"';
            header("ETag: {$etagValue}");
            
            // Check if the client sent If-None-Match header
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etagValue) {
                http_response_code(304); // Not Modified
                exit;
            }
            
            // Set Last-Modified header based on file's modification time
            $lastModified = gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT';
            header("Last-Modified: {$lastModified}");
            
            // Check if the client sent If-Modified-Since header
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= filemtime($filePath)) {
                http_response_code(304); // Not Modified
                exit;
            }
        }
        
        header("Cache-Control: {$cacheControl}");
        header("Pragma: cache"); // Overrides older HTTP/1.0 proxies
    }

    /**
     * Set cache headers for dynamic content (HTML pages, API responses)
     * 
     * @param int $maxAge Cache lifetime in seconds (default: 60 seconds)
     * @param bool $public Whether the cache is public or private
     * @param bool $mustRevalidate Whether clients must revalidate resources
     */
    public static function setDynamicContentHeaders(int $maxAge = 60, bool $public = false, bool $mustRevalidate = true): void {
        $cacheControl = $public ? self::PUBLIC_CACHE : self::PRIVATE_CACHE;
        $cacheControl .= ", max-age={$maxAge}";
        
        if ($mustRevalidate) {
            $cacheControl .= ", must-revalidate";
        }
        
        header("Cache-Control: {$cacheControl}");
        
        // Set Vary header to tell proxies which headers affect caching
        header("Vary: Accept-Encoding, Cookie");
    }

    /**
     * Set cache headers for user-specific content (private data)
     */
    public static function setPrivateContentHeaders(): void {
        header("Cache-Control: private, no-cache, max-age=0, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    /**
     * Set cache headers for media content (videos, large files)
     * 
     * @param int $maxAge Cache lifetime in seconds
     */
    public static function setMediaHeaders(int $maxAge = 2592000): void {
        // 2592000 = 30 days in seconds
        header("Cache-Control: public, max-age={$maxAge}");
        
        // Generate and set ETag for the resource
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $requestUri;
        
        if (file_exists($filePath)) {
            // For large files, use file size and mtime instead of md5_file for performance
            $fileSize = filesize($filePath);
            $modTime = filemtime($filePath);
            $etagValue = '"' . md5($fileSize . $modTime) . '"';
            header("ETag: {$etagValue}");
            
            // Support for range requests (for video streaming)
            header("Accept-Ranges: bytes");
        }
    }

    /**
     * Disable caching (for sensitive operations like login, payment, etc.)
     */
    public static function disableCache(): void {
        header("Cache-Control: " . self::NO_CACHE);
        header("Pragma: no-cache");
        header("Expires: 0");
    }
    
    /**
     * Set headers for versioned assets (includes a version in URL)
     * These can be cached for very long periods or "forever"
     * 
     * @param int $maxAge Cache lifetime in seconds (default 1 year)
     */
    public static function setVersionedAssetHeaders(int $maxAge = 31536000): void {
        // 31536000 = 1 year in seconds
        header("Cache-Control: public, max-age={$maxAge}, immutable");
    }
}