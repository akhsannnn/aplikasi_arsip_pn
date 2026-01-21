<?php
class UserService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUsers() {
        $data = [];
        // Jangan kirim password hash ke frontend demi keamanan
        $q = $this->db->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY role ASC, full_name ASC");
        if($q) while($r = $q->fetch_assoc()) $data[] = $r;
        return $data;
    }

    public function createUser($username, $password, $fullName, $role) {
        // Cek username kembar
        $u = $this->db->real_escape_string($username);
        $cek = $this->db->query("SELECT id FROM users WHERE username = '$u'");
        if($cek && $cek->num_rows > 0) return ['success'=>false, 'message'=>'Username sudah dipakai!'];

        // Hash Password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $fn = $this->db->real_escape_string($fullName);
        $r = $this->db->real_escape_string($role);

        $sql = "INSERT INTO users (username, password, full_name, role) VALUES ('$u', '$hash', '$fn', '$r')";
        if($this->db->query($sql)) return ['success'=>true, 'message'=>'User berhasil dibuat.'];
        return ['success'=>false, 'message'=>'Gagal membuat user.'];
    }

    public function updateUser($id, $username, $password, $fullName, $role) {
        $id = (int)$id;
        $u = $this->db->real_escape_string($username);
        $fn = $this->db->real_escape_string($fullName);
        $r = $this->db->real_escape_string($role);

        // Cek username lain (kecuali diri sendiri)
        $cek = $this->db->query("SELECT id FROM users WHERE username = '$u' AND id != $id");
        if($cek && $cek->num_rows > 0) return ['success'=>false, 'message'=>'Username sudah dipakai orang lain!'];

        // Logika Update Password (Jika kosong, jangan diubah)
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username='$u', password='$hash', full_name='$fn', role='$r' WHERE id=$id";
        } else {
            $sql = "UPDATE users SET username='$u', full_name='$fn', role='$r' WHERE id=$id";
        }

        if($this->db->query($sql)) return ['success'=>true, 'message'=>'User berhasil diupdate.'];
        return ['success'=>false, 'message'=>'Gagal update user.'];
    }

    public function deleteUser($id) {
        $id = (int)$id;
        // Mencegah Admin menghapus dirinya sendiri
        if($id == $_SESSION['user_id']) return ['success'=>false, 'message'=>'Anda tidak bisa menghapus akun sendiri!'];
        
        if($this->db->query("DELETE FROM users WHERE id=$id")) return ['success'=>true, 'message'=>'User dihapus.'];
        return ['success'=>false, 'message'=>'Gagal menghapus user.'];
    }
}
?>