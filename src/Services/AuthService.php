<?php
class AuthService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($username, $password) {
        $u = $this->db->real_escape_string($username);
        
        // 1. Ambil data user
        $query = $this->db->query("SELECT * FROM users WHERE username = '$u'");
        $user = $query->fetch_assoc();

        if ($user) {
            // A. CEK PASSWORD LAMA (PLAIN TEXT)
            // Jika password di database belum di-hash (tidak diawali $2y$)
            // DAN passwordnya cocok secara text biasa...
            if (strpos($user['password'], '$2y$') !== 0 && $password == $user['password']) {
                
                // FITUR SECURITY: AUTO-MIGRATE
                // Hash passwordnya sekarang agar aman untuk login berikutnya
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $uid = $user['id'];
                $this->db->query("UPDATE users SET password = '$newHash' WHERE id = $uid");
                
                // Lanjut login
                $this->setSession($user);
                return true;
            }
            
            // B. CEK PASSWORD BARU (HASH)
            // Gunakan password_verify untuk keamanan standar
            if (password_verify($password, $user['password'])) {
                $this->setSession($user);
                return true;
            }
        }
        
        return false;
    }

    private function setSession($user) {
        // Regenerate ID untuk mencegah Session Fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['last_activity'] = time(); // Reset timer timeout
    }
}
?>