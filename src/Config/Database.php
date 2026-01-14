<?php
class Database {
    private $host = "db";
    private $db_name = "db_arsip";
    private $username = "root";
    private $password = "root";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // UBAH KE MYSQLI (Agar support fetch_assoc di Service)
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            if ($this->conn->connect_error) {
                throw new Exception("Koneksi gagal: " . $this->conn->connect_error);
            }
        } catch(Exception $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>