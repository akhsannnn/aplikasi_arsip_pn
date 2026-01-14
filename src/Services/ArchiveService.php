<?php
class ArchiveService {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // --- DASHBOARD ---
    public function getDashboardStats() {
        $f = $this->db->query("SELECT COUNT(*) as c FROM files WHERE deleted_at IS NULL")->fetch_assoc()['c'];
        $d = $this->db->query("SELECT COUNT(*) as c FROM folders WHERE deleted_at IS NULL")->fetch_assoc()['c'];
        
        $recent = [];
        $sql = "SELECT f.filename, f.uploaded_at, u.username, u.full_name, u.role 
                FROM files f 
                LEFT JOIN users u ON f.user_id = u.id 
                WHERE f.deleted_at IS NULL 
                ORDER BY f.uploaded_at DESC LIMIT 10"; 
        
        $q = $this->db->query($sql);
        if($q) while($r = $q->fetch_assoc()) { $recent[] = $r; }

        return ['total_files' => $f, 'total_folders' => $d, 'recent' => $recent];
    }

    // --- SIDEBAR DATA ---
    public function getSidebarYears() {
        $years = [];
        $q = $this->db->query("SELECT DISTINCT year FROM folders WHERE deleted_at IS NULL ORDER BY year DESC");
        if($q) while($r = $q->fetch_assoc()) $years[] = $r['year'];
        return $years;
    }

    // --- INTI PERBAIKAN: GET CONTENT ---
    public function getContent($year, $folderId) {
        // 1. Perbaiki Logika NULL vs "null"
        // JavaScript sering mengirim string 'null', kita harus ubah jadi SQL IS NULL
        if ($folderId && $folderId !== 'null' && $folderId !== '') {
             $pidQ = "parent_id = '$folderId'";
             $fidQ = "folder_id = '$folderId'";
        } else {
             $pidQ = "parent_id IS NULL";
             $fidQ = "folder_id IS NULL";
        }

        // 2. Ambil Folder
        $folders = [];
        // Folder difilter berdasarkan Tahun
        $q = $this->db->query("SELECT * FROM folders WHERE year='$year' AND $pidQ AND deleted_at IS NULL ORDER BY name ASC");
        if($q) while($r = $q->fetch_assoc()) $folders[] = $r;

        // 3. Ambil File
        // CATATAN: Kita HAPUS filter path tahun (LIKE 'uploads/$year')
        // Supaya file yang tersimpan di path '2026' tetap muncul di '2027' jika folder_id-nya benar.
        $files = [];
        $sqlFile = "SELECT f.*, u.full_name as uploader 
                    FROM files f 
                    LEFT JOIN users u ON f.user_id = u.id 
                    WHERE $fidQ AND f.deleted_at IS NULL 
                    ORDER BY f.id DESC";
                    
        $q = $this->db->query($sqlFile);
        if($q) while($r = $q->fetch_assoc()) {
            $ext = strtolower(pathinfo($r['filename'], PATHINFO_EXTENSION));
            $r['is_previewable'] = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']);
            $files[] = $r;
        }

        // 4. Breadcrumbs
        $breadcrumbs = [];
        if($folderId && $folderId !== 'null') {
            $curr = $folderId;
            while($curr) {
                $d = $this->db->query("SELECT id, name, parent_id FROM folders WHERE id='$curr'")->fetch_assoc();
                if($d) { array_unshift($breadcrumbs, $d); $curr = $d['parent_id']; } else { $curr = null; }
            }
        }
        
        // Kita kembalikan years juga untuk kompatibilitas app.js lama
        return ['folders' => $folders, 'files' => $files, 'breadcrumbs' => $breadcrumbs, 'years' => $this->getSidebarYears()];
    }

    // --- CREATE & UPLOAD ---
    public function createFolder($name, $desc, $year, $parentId) {
        $n = $this->db->real_escape_string($name);
        $d = $this->db->real_escape_string($desc);
        $pid = ($parentId && $parentId != 'null') ? "'$parentId'" : "NULL";
        $uid = $_SESSION['user_id'] ?? "NULL";
        return $this->db->query("INSERT INTO folders (name, description, year, parent_id, created_by) VALUES ('$n', '$d', '$year', $pid, $uid)");
    }

    public function uploadFile($file, $year, $folderId, $userId) {
        // Simpan ke public/uploads agar bisa diakses
        $targetDir = __DIR__ . "/../../public/uploads/$year/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = $this->db->real_escape_string($file['name']);
        $uniqueName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", $fileName);
        $targetFile = $targetDir . $uniqueName;
        
        // Simpan path relatif untuk DB
        $dbPath = "uploads/$year/$uniqueName"; 

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $fid = ($folderId && $folderId != 'null') ? "'$folderId'" : "NULL";
            $uid = $userId ? "'$userId'" : "NULL";
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            return $this->db->query("INSERT INTO files (folder_id, filename, filepath, filetype, user_id) VALUES ($fid, '$fileName', '$dbPath', '$ext', $uid)");
        }
        return false;
    }

    // --- DELETE & RESTORE ---
    public function deleteItem($type, $id, $userId) {
        $table = ($type == 'folder') ? 'folders' : 'files';
        $now = date('Y-m-d H:i:s');
        $uid = $userId ? "'$userId'" : "NULL";
        return $this->db->query("UPDATE $table SET deleted_at = '$now', deleted_by = $uid WHERE id = '$id'");
    }

    public function deleteYear($year, $userId) {
        $now = date('Y-m-d H:i:s');
        $uid = $userId ? "'$userId'" : "NULL";
        return $this->db->query("UPDATE folders SET deleted_at = '$now', deleted_by = $uid WHERE year = '$year'");
    }

    public function getTrash() {
        $folders = []; $files = [];
        $qF = $this->db->query("SELECT f.*, u.username as deleter FROM folders f LEFT JOIN users u ON f.deleted_by = u.id WHERE f.deleted_at IS NOT NULL ORDER BY f.deleted_at DESC");
        if($qF) while($r=$qF->fetch_assoc()) $folders[] = $r;
        $qFi = $this->db->query("SELECT f.*, u.username as deleter FROM files f LEFT JOIN users u ON f.deleted_by = u.id WHERE f.deleted_at IS NOT NULL ORDER BY f.deleted_at DESC");
        if($qFi) while($r=$qFi->fetch_assoc()) $files[] = $r;
        return ['folders' => $folders, 'files' => $files];
    }

    public function restoreItem($type, $id) {
        $table = ($type == 'folder') ? 'folders' : 'files';
        return $this->db->query("UPDATE $table SET deleted_at = NULL, deleted_by = NULL WHERE id = '$id'");
    }
}
?>