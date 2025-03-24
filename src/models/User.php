<?php
class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Find user by ID
    public function findById($id)
    {
        $this->db->query("SELECT id, email, name, created_at, updated_at FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    // Find user by email
    public function findByEmail($email)
    {
        $this->db->query("SELECT id, email, name, created_at, updated_at FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->fetch();
    }

    // Update user
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'created_at' && $key !== 'updated_at') {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->query($sql);

        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        return $this->db->execute();
    }

    // Update password
    public function updatePassword($id, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
