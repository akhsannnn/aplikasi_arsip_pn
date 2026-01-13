<?php
class AuthService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($username, $password) {
        $u = $this->db->real_escape_string($username);
        
        // Cari user berdasarkan username
        $query = $this->db->query("SELECT * FROM users WHERE username = '$u'");
        $user = $query->fetch_assoc();

        // Verifikasi password (Hash)
        if ($user && password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }
}