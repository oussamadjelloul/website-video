<?php
require_once __DIR__ . '/../lib/Database.php';

class Video
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Create a new video
    public function create($data)
    {
        // Generate UUID for video
        $id = $this->generateUUID();

        $this->db->query("INSERT INTO videos (id, title, description, video_url, cdn_url, thumbnail_url, duration, user_id) 
                          VALUES (:id, :title, :description, :video_url, :cdn_url, :thumbnail_url, :duration, :user_id)");

        $this->db->bind(':id', $id);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description'] ?? '');
        $this->db->bind(':video_url', $data['video_url']);
        $this->db->bind(':cdn_url', $data['cdn_url'] ?? null);
        $this->db->bind(':thumbnail_url', $data['thumbnail_url'] ?? null);
        $this->db->bind(':duration', $data['duration'] ?? 0);
        $this->db->bind(':user_id', $data['user_id']);

        if ($this->db->execute()) {
            return $id;
        } else {
            return false;
        }
    }

    // Get all videos with pagination and optional filtering
    public function getAll($limit = 10, $offset = 0, $userId = null)
    {
        $sql = "SELECT v.*, u.name as author_name 
                FROM videos v
                JOIN users u ON v.user_id = u.id ";

        if ($userId) {
            $sql .= "WHERE v.user_id = :user_id ";
        }

        $sql .= "ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);

        if ($userId) {
            $this->db->bind(':user_id', $userId);
        }

        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->fetchAll();
    }

    // Get total video count (for pagination)
    public function getCount($userId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM videos";

        if ($userId) {
            $sql .= " WHERE user_id = :user_id";
            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
        } else {
            $this->db->query($sql);
        }

        $result = $this->db->fetch();
        return $result['count'];
    }

    // Get a single video by ID
    public function getById($id)
    {
        $this->db->query("SELECT v.*, u.name as author_name 
                          FROM videos v
                          JOIN users u ON v.user_id = u.id
                          WHERE v.id = :id");

        $this->db->bind(':id', $id);

        return $this->db->fetch();
    }

    // Update video details
    public function update($id, $data)
    {
        $setClause = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'description', 'thumbnail_url', 'cdn_url'])) {
                $setClause[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($setClause)) {
            return false;
        }

        $sql = "UPDATE videos SET " . implode(', ', $setClause) . " WHERE id = :id";

        $this->db->query($sql);

        foreach ($params as $param => $value) {
            $this->db->bind($param, $value);
        }

        return $this->db->execute();
    }

    // Delete a video
    public function delete($id)
    {
        $this->db->query("DELETE FROM videos WHERE id = :id");
        $this->db->bind(':id', $id);

        return $this->db->execute();
    }

    // Get videos by user ID
    public function getByUserId($userId, $limit = 10, $offset = 0)
    {
        $this->db->query("SELECT v.*, u.name as author_name 
                          FROM videos v
                          JOIN users u ON v.user_id = u.id
                          WHERE v.user_id = :user_id
                          ORDER BY v.created_at DESC
                          LIMIT :limit OFFSET :offset");

        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->fetchAll();
    }

    // Search videos
    public function search($keyword, $limit = 10, $offset = 0)
    {
        $this->db->query("SELECT v.*, u.name as author_name 
                          FROM videos v
                          JOIN users u ON v.user_id = u.id
                          WHERE v.title LIKE :keyword OR v.description LIKE :keyword
                          ORDER BY v.created_at DESC
                          LIMIT :limit OFFSET :offset");

        $this->db->bind(':keyword', '%' . $keyword . '%');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->fetchAll();
    }

    // Get recently uploaded videos
    public function getRecent($limit = 5)
    {
        $this->db->query("SELECT v.*, u.name as author_name 
                          FROM videos v
                          JOIN users u ON v.user_id = u.id
                          ORDER BY v.created_at DESC
                          LIMIT :limit");

        $this->db->bind(':limit', $limit, PDO::PARAM_INT);

        return $this->db->fetchAll();
    }

    // Get popular videos (placeholder - in a real app would be based on views/likes)
    public function getPopular($limit = 5)
    {
        // This is just a placeholder - in a real app, you might sort by views or likes
        $this->db->query("SELECT v.*, u.name as author_name 
                          FROM videos v
                          JOIN users u ON v.user_id = u.id
                          ORDER BY RAND()
                          LIMIT :limit");

        $this->db->bind(':limit', $limit, PDO::PARAM_INT);

        return $this->db->fetchAll();
    }

    // Format duration from seconds to MM:SS format
    public static function formatDuration($seconds)
    {
        if (!$seconds) {
            return '00:00';
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
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
