<?php
class CDN
{
    private $configured = false;
    private $provider;
    private $apiKey;
    private $region;
    private $bucket;

    public function __construct()
    {
        // Check if CDN credentials are configured
        if (isset($_ENV['CDN_PROVIDER']) && isset($_ENV['CDN_API_KEY'])) {
            $this->configured = true;
            $this->provider = $_ENV['CDN_PROVIDER'];
            $this->apiKey = $_ENV['CDN_API_KEY'];
            $this->region = $_ENV['CDN_REGION'] ?? '';
            $this->bucket = $_ENV['CDN_BUCKET'] ?? '';
        }
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
}
