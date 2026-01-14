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
            // PERBAIKAN: Gunakan MySQLi (bukan PDO) agar kompatibel dengan ArchiveService
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            // Cek jika ada error koneksi
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