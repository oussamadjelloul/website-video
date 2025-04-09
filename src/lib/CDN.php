<?php
// Firebase JWT library is required for this class
// Install using: composer require firebase/php-jwt

// Explicitly import the Firebase JWT classes
// use Firebase\JWT\JWT;
// use Firebase\JWT\Key;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\JWSVerifier;

class CDN
{
    private $configured = false;
    private $provider;
    private $apiKey;
    private $region;
    private $bucket;
    private $signKeys = [];
    private $currentKeyId = '0';

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
            $this->signKeys['0'] = $_ENV['CDN_SIGN_KEY0'];
        }
        if (isset($_ENV['CDN_SIGN_KEY1'])) {
            $this->signKeys['1'] = $_ENV['CDN_SIGN_KEY1'];
        }
        if (isset($_ENV['CDN_SIGN_KEY2'])) {
            $this->signKeys['2'] = $_ENV['CDN_SIGN_KEY2'];
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
        // Use specified key ID or default to current key
        $useKeyId = $keyId ?? $this->currentKeyId;

        // Create a structured JWK (JSON Web Key)
        // Create a structured JWK (JSON Web Key)
        $jwk = new JWK([
            'kty' => 'oct',
            'k' => $this->base64UrlEncode($this->signKeys[$useKeyId]), // Modified this line
            'alg' => 'HS256',
            'kid' => $useKeyId
        ]);

        // Prepare claims
        $claims = [
            'iss' => 'origin-sign.infra.cerist.test',
            'exp' => time() + $expirationTime,
            'nbf' => time(),
            'iat' => time(),
            'sub' => $urlPath,
            'cdnistt' => 1,
            'cdniets' => 3600
        ];

        // Add custom claims
        $claims = array_merge($claims, $customClaims);

        // Create token
        $algorithmManager = new \Jose\Component\Core\AlgorithmManager([
            new HS256()
        ]);
        $jwsBuilder = new JWSBuilder($algorithmManager);
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($claims))
            ->addSignature($jwk, ['alg' => 'HS256', 'kid' => $useKeyId, 'type' => 'JWT'])
            ->build();

        $serializer = new CompactSerializer();
        $token = $serializer->serialize($jws);

        // Build URL
        $urlPath = ltrim($urlPath, '/');
        $separator = (strpos($urlPath, '?') !== false) ? '&' : '?';
        $timestamp = time();
        $signedUrl = '/' . $urlPath . $separator . 't=' . $timestamp . '&URISigningPackage=' . $token;

        return $signedUrl;
    }

    /**
     * Helper method to Base64Url encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Verifies a signed URL token
     * 
     * @param string $token The JWT token to verify
     * @return array|false Returns the decoded token payload if valid, false otherwise
     */
    public function verifySignedUrlToken($token)
    {
        try {
            $serializer = new CompactSerializer();
            $jws = $serializer->unserialize($token);

            // Extract header
            $header = $jws->getSignature(0)->getProtectedHeader();

            if (!isset($header['kid']) || !isset($this->signKeys[$header['kid']])) {
                return false;
            }

            // Create JWK
            $jwk = new JWK([
                'kty' => 'oct',
                'k' => base64_encode($this->signKeys[$header['kid']]),
                'alg' => 'HS256',
                'kid' => $header['kid']
            ]);

            // Verify signature
            $algorithmManager = new \Jose\Component\Core\AlgorithmManager([new HS256()]);
            $jwsVerifier = new JWSVerifier($algorithmManager);
            $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);

            if (!$isVerified) {
                return false;
            }

            // Decode payload
            $payload = json_decode($jws->getPayload(), true);

            // Validate expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }

            return $payload;
        } catch (Exception $e) {
            error_log('Token verification failed: ' . $e->getMessage());
            return false;
        }
    }
}
