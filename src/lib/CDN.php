<?php
// Firebase JWT library is required for this class
// Install using: composer require firebase/php-jwt

// Explicitly import the Firebase JWT classes
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CDN
{
    private $configured = false;
    private $provider;
    private $apiKey;
    private $region;
    private $bucket;
    private $signKeys = [];
    private $currentKeyId = 'key0';

    public function __construct()
    {
        // Check if CDN credentials are configured
        // if (isset($_ENV['CDN_PROVIDER']) && isset($_ENV['CDN_API_KEY'])) {
            $this->configured = true;
            $this->provider = $_ENV['CDN_PROVIDER'];
            $this->apiKey = $_ENV['CDN_API_KEY'];
            $this->region = $_ENV['CDN_REGION'] ?? '';
            $this->bucket = $_ENV['CDN_BUCKET'] ?? '';
            
            // Load signing keys for URL signing if configured
            if (isset($_ENV['CDN_SIGN_KEY0'])) {
                $this->signKeys['key0'] = $_ENV['CDN_SIGN_KEY0'];
            }
            if (isset($_ENV['CDN_SIGN_KEY1'])) {
                $this->signKeys['key1'] = $_ENV['CDN_SIGN_KEY1'];
            }
            if (isset($_ENV['CDN_SIGN_KEY2'])) {
                $this->signKeys['key2'] = $_ENV['CDN_SIGN_KEY2'];
            }
            
            // Set current key ID if specified
            if (isset($_ENV['CDN_CURRENT_KEY_ID'])) {
                $this->currentKeyId = $_ENV['CDN_CURRENT_KEY_ID'];
            }
        // }
    }

    /**
     * Upload a file to the CDN
     * 
     * @param string $filePath The local file path to upload
     * @return array Result with success flag and URL if successful
     */
    public function upload($filePath)
    {
        // If CDN is not configured, return early
        if (!$this->configured) {
            return [
                'success' => false,
                'message' => 'CDN not configured',
                'url' => null
            ];
        }

        // For the demo, we'll just simulate the CDN upload
        // In a real application, you would implement the actual CDN provider's API here

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'File not found',
                'url' => null
            ];
        }

        // Simulate a CDN URL
        $fileName = basename($filePath);
        $cdnUrl = "https://cdn-example.com/" . $this->bucket . "/" . $fileName;

        // Log the "upload" for debugging
        error_log("Simulated CDN upload: {$filePath} -> {$cdnUrl}");

        return [
            'success' => true,
            'message' => 'File uploaded to CDN successfully',
            'url' => $cdnUrl
        ];
    }

    /**
     * Delete a file from the CDN
     * 
     * @param string $cdnUrl The CDN URL of the file to delete
     * @return array Result with success flag
     */
    public function delete($cdnUrl)
    {
        // If CDN is not configured, return early
        if (!$this->configured) {
            return [
                'success' => false,
                'message' => 'CDN not configured'
            ];
        }

        // For the demo, we'll just simulate the CDN deletion
        // In a real application, you would implement the actual CDN provider's API here

        error_log("Simulated CDN deletion: {$cdnUrl}");

        return [
            'success' => true,
            'message' => 'File deleted from CDN successfully'
        ];
    }

    /**
     * Check if CDN integration is configured
     * 
     * @return bool True if configured, false otherwise
     */
    public function isConfigured()
    {
        return $this->configured;
    }

    /**
     * Get the CDN URL for a local file path
     * 
     * @param string $localPath The local path
     * @return string|null The CDN URL or null if not configured
     */
    public function getUrl($localPath)
    {
        if (!$this->configured) {
            return null;
        }

        $fileName = basename($localPath);
        return "https://cdn-example.com/" . $this->bucket . "/" . $fileName;
    }
    
    /**
     * Generate a signed URL for Apache Traffic Control CDN
     * 
     * @param string $urlPath The URL path to sign (without domain)
     * @param int $expirationTime Expiration time in seconds from now
     * @param array $customClaims Optional custom claims to include in the JWT
     * @param string $keyId Optional key ID to use for signing (defaults to current key)
     * @return string|null The signed URL or null if signing is not configured
     * @throws Exception If the signing key is not available
     */
    public function generateSignedUrl($urlPath, $expirationTime, $customClaims = [], $keyId = null)
    {
        error_log("Generating signed URL for path: $urlPath with expiration: $expirationTime seconds");
        // If no keys are configured, return null
        if (empty($this->signKeys)) {
            return null;
        }
        
        // Use specified key ID or default to current key
        $useKeyId = $keyId ?? $this->currentKeyId;
        
        // Check if the key exists
        if (!isset($this->signKeys[$useKeyId])) {
            throw new Exception("Signing key '$useKeyId' not found");
        }
        
        // Get the signing key
        $signingKey = $this->signKeys[$useKeyId];
        
        // Calculate the expiration timestamp
        $expirationTimestamp = time() + $expirationTime;
        
        // Prepare standard claims
        $claims = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'cdn.example.com',  // Issuer
            'exp' => $expirationTimestamp,                       // Expiration time
            'nbf' => time(),                                     // Not before time
            'iat' => time(),                                     // Issued at time
            'sub' => $urlPath,                                   // Subject (the URL path)
            'kid' => $useKeyId                                   // Key ID used for verification
        ];
        
        // Add custom claims
        $claims = array_merge($claims, $customClaims);
        
        // Generate the JWT token
        try {
            $token = JWT::encode($claims, $signingKey, 'HS256', null, ['kid' => $useKeyId]);
            
            // Build the complete URL
            // $baseUrl = isset($_ENV['CDN_BASE_URL']) ? $_ENV['CDN_BASE_URL'] : 'https://cdn.example.com';
            $urlPath = ltrim($urlPath, '/'); // Remove leading slash if present
            
            // Add the JWT token as a query parameter
            $separator = (strpos($urlPath, '?') !== false) ? '&' : '?';
            $signedUrl = '/' . $urlPath . $separator . 'token=' . $token;
            
            return $signedUrl;
        } catch (Exception $e) {
            error_log('Error generating signed URL: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifies a signed URL token
     * 
     * @param string $token The JWT token to verify
     * @return array|false Returns the decoded token payload if valid, false otherwise
     */
    public function verifySignedUrlToken($token)
    {
        if (empty($this->signKeys)) {
            return false;
        }
        
        try {
            // Extract the key ID from the token header
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return false;
            }
            
            $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0])), true);
            
            if (!isset($header['kid']) || !isset($this->signKeys[$header['kid']])) {
                return false;
            }
            
            $keyId = $header['kid'];
            $key = $this->signKeys[$keyId];
            
            // Decode and verify the token
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
            
            // Validate the expiration time
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return false; // Token has expired
            }
            
            // Convert to array
            return (array) $decoded;
        } catch (Exception $e) {
            error_log('Token verification failed: ' . $e->getMessage());
            return false;
        }
    }
}
