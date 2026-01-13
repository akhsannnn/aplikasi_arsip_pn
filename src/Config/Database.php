<?php
class Database {
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            // Mengambil settingan dari Environment Variable (Docker)
            // Jika tidak ada, gunakan default (Localhost/XAMPP)
            $host = getenv('DB_HOST') ?: 'db';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASSWORD') ?: 'root'; 
            $name = getenv('DB_NAME') ?: 'db_arsip';

            $this->conn = new mysqli($host, $user, $pass, $name);
            
            // Cek koneksi
            if ($this->conn->connect_error) {
                // Fallback untuk local XAMPP jika Docker env tidak terbaca
                $this->conn = new mysqli('localhost', 'root', '', 'db_arsip');
                if ($this->conn->connect_error) {
                    throw new Exception("Connection Error: " . $this->conn->connect_error);
                }
            }
        } catch (Exception $e) {
            // Return JSON error agar frontend tidak hang
            die(json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]));
        }
        return $this->conn;
    }
}