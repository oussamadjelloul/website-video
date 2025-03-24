<?php
class Database
{
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    private $conn;
    private $stmt;
    private static $instance = null;

    public function __construct()
    {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;

        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }

    // Singleton pattern
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get raw PDO connection
    public function getConnection()
    {
        return $this->conn;
    }

    // Prepare statement
    public function query($sql)
    {
        $this->stmt = $this->conn->prepare($sql);
        return $this;
    }

    // Bind values
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    // Execute prepared statement
    public function execute()
    {
        return $this->stmt->execute();
    }

    // Get result set as array of objects
    public function fetchAll()
    {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    // Get single record
    public function fetch()
    {
        $this->execute();
        return $this->stmt->fetch();
    }

    // Get row count
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    // Get last inserted ID
    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    // Transaction methods
    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollBack()
    {
        return $this->conn->rollBack();
    }
}
