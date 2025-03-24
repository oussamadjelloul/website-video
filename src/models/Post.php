<?php
require_once __DIR__ . '/../lib/Database.php';

class Post
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Create a new post
    public function create($data)
    {
        // Generate UUID for post
        $id = $this->generateUUID();

        $this->db->query("INSERT INTO posts (id, title, content, image_url, cdn_url, user_id) 
                          VALUES (:id, :title, :content, :image_url, :cdn_url, :user_id)");

        $this->db->bind(':id', $id);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':content', $data['content']);
        $this->db->bind(':image_url', $data['image_url']);
        $this->db->bind(':cdn_url', $data['cdn_url']);
        $this->db->bind(':user_id', $data['user_id']);

        if ($this->db->execute()) {
            return $id;
        } else {
            return false;
        }
    }

    // Get all posts with pagination and optional filtering
    public function getAll($limit = 10, $offset = 0, $userId = null)
    {
        $sql = "SELECT p.*, u.name as author_name 
                FROM posts p
                JOIN users u ON p.user_id = u.id ";

        if ($userId) {
            $sql .= "WHERE p.user_id = :user_id ";
        }

        $sql .= "ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);

        if ($userId) {
            $this->db->bind(':user_id', $userId);
        }

        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->fetchAll();
    }

    // Get total post count (for pagination)
    public function getCount($userId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM posts";

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

    // Get a single post by ID
    public function getById($id)
    {
        $this->db->query("SELECT p.*, u.name as author_name 
                          FROM posts p
                          JOIN users u ON p.user_id = u.id
                          WHERE p.id = :id");

        $this->db->bind(':id', $id);

        return $this->db->fetch();
    }

    // Update a post
    public function update($id, $data)
    {
        $setClause = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'content', 'image_url', 'cdn_url'])) {
                $setClause[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($setClause)) {
            return false;
        }

        $sql = "UPDATE posts SET " . implode(', ', $setClause) . " WHERE id = :id";

        $this->db->query($sql);

        foreach ($params as $param => $value) {
            $this->db->bind($param, $value);
        }

        return $this->db->execute();
    }

    // Delete a post
    public function delete($id)
    {
        $this->db->query("DELETE FROM posts WHERE id = :id");
        $this->db->bind(':id', $id);

        return $this->db->execute();
    }

    // Get posts by user ID
    public function getByUserId($userId, $limit = 10, $offset = 0)
    {
        $this->db->query("SELECT p.*, u.name as author_name 
                          FROM posts p
                          JOIN users u ON p.user_id = u.id
                          WHERE p.user_id = :user_id
                          ORDER BY p.created_at DESC
                          LIMIT :limit OFFSET :offset");

        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->fetchAll();
    }

    // Search posts
    public function search($keyword, $limit = 10, $offset = 0)
    {
        $this->db->query("SELECT p.*, u.name as author_name 
                          FROM posts p
                          JOIN users u ON p.user_id = u.id
                          WHERE p.title LIKE :keyword OR p.content LIKE :keyword
                          ORDER BY p.created_at DESC
                          LIMIT :limit OFFSET :offset");

        $this->db->bind(':keyword', '%' . $keyword . '%');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->fetchAll();
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
