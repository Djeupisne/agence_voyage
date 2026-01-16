<?php
class Database {
    private $conn;
    private $isClosed = false;

    public function __construct() {
        $this->conn = new mysqli('localhost', 'root', '', 'agence_voyage');
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function query($sql, $params = [], $types = '') {
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        return $stmt;
    }

    public function get_error() {
        return $this->conn->error;
    }

    public function get_insert_id() {
        return $this->conn->insert_id;
    }

    public function close() {
        if (!$this->isClosed && $this->conn) {
            $this->conn->close();
            $this->isClosed = true;
        }
    }

    public function __destruct() {
        $this->close();
    }
}
?>