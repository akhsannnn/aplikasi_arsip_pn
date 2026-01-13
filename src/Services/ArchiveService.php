<?php
class ArchiveService {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // --- 1. DASHBOARD STATS & HISTORY (UPDATE) ---
    public function getDashboardStats() {
        // Hitung total
        $f = $this->db->query("SELECT COUNT(*) as c FROM files WHERE deleted_at IS NULL")->fetch_assoc()['c'];
        $d = $this->db->query("SELECT COUNT(*) as c FROM folders WHERE deleted_at IS NULL")->fetch_assoc()['c'];
        
        // Ambil History Upload Lengkap dengan Nama User
        // Menggabungkan tabel files dengan users
        $recent = [];
        $sql = "SELECT f.filename, f.uploaded_at, u.username, u.full_name, u.role 
                FROM files f 
                LEFT JOIN users u ON f.user_id = u.id 
                WHERE f.deleted_at IS NULL 
                ORDER BY f.uploaded_at DESC LIMIT 10"; // Tampilkan 10 aktivitas terakhir
        
        $q = $this->db->query($sql);
        while($r = $q->fetch_assoc()) {
            // Format waktu agar lebih enak dibaca (opsional, bisa diurus di JS)
            $recent[] = $r;
        }

        return ['total_files' => $f, 'total_folders' => $d, 'recent' => $recent];
    }

    // --- 2. UPLOAD FILE DENGAN USER ID (UPDATE) ---
    public function uploadFile($file, $year, $folderId, $userId) {
        $targetDir = __DIR__ . "/../../uploads/$year/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = $this->db->real_escape_string($file['name']);
        $uniqueName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", $fileName);
        $targetFile = $targetDir . $uniqueName;
        $dbPath = "../uploads/$year/$uniqueName"; 

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $fid = ($folderId && $folderId != 'null') ? "'$folderId'" : "NULL";
            $uid = $userId ? "'$userId'" : "NULL"; // Simpan ID User
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            return $this->db->query("INSERT INTO files (folder_id, filename, filepath, filetype, user_id) VALUES ($fid, '$fileName', '$dbPath', '$ext', $uid)");
        }
        return false;
    }

    // --- 3. AMBIL TAHUN UNTUK SIDEBAR (BARU) ---
    public function getSidebarYears() {
        $years = [];
        // Ambil tahun yang unik dari tabel folders
        $q = $this->db->query("SELECT DISTINCT year FROM folders WHERE deleted_at IS NULL ORDER BY year DESC");
        while($r = $q->fetch_assoc()) $years[] = $r['year'];
        return $years;
    }

    // ... (FUNGSI LAINNYA: getContent, createFolder, deleteItem, getTrash, restoreItem TETAP SAMA) ...
    // Pastikan fungsi-fungsi lama tetap ada di bawah sini
    
    public function getContent($year, $folderId) {
        // Query Folder
        $pidQ = $folderId ? "parent_id = '$folderId'" : "parent_id IS NULL";
        $folders = [];
        $q = $this->db->query("SELECT * FROM folders WHERE year='$year' AND $pidQ AND deleted_at IS NULL ORDER BY name ASC");
        while($r = $q->fetch_assoc()) $folders[] = $r;

        // Query File
        $fidQ = $folderId ? "folder_id = '$folderId'" : "folder_id IS NULL";
        $files = [];
        $q = $this->db->query("SELECT * FROM files WHERE $fidQ AND deleted_at IS NULL ORDER BY id DESC");
        while($r = $q->fetch_assoc()) {
            $ext = strtolower(pathinfo($r['filename'], PATHINFO_EXTENSION));
            $r['is_previewable'] = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']);
            $files[] = $r;
        }

        // Breadcrumbs
        $breadcrumbs = [];
        if($folderId) {
            $curr = $folderId;
            while($curr) {
                $d = $this->db->query("SELECT id, name, parent_id FROM folders WHERE id='$curr'")->fetch_assoc();
                if($d) { array_unshift($breadcrumbs, $d); $curr = $d['parent_id']; } else { $curr = null; }
            }
        }
        
        // Return tanpa years (karena years sudah dihandle getSidebarYears)
        return ['folders' => $folders, 'files' => $files, 'breadcrumbs' => $breadcrumbs];
    }

    public function createFolder($name, $desc, $year, $parentId) {
        $n = $this->db->real_escape_string($name);
        $d = $this->db->real_escape_string($desc);
        $pid = ($parentId && $parentId != 'null') ? "'$parentId'" : "NULL";
        return $this->db->query("INSERT INTO folders (name, description, year, parent_id) VALUES ('$n', '$d', '$year', $pid)");
    }
    
    public function deleteItem($type, $id) {
        $table = ($type == 'folder') ? 'folders' : 'files';
        $now = date('Y-m-d H:i:s');
        return $this->db->query("UPDATE $table SET deleted_at = '$now' WHERE id = '$id'");
    }

    public function getTrash() {
        $folders = []; $files = [];
        $q = $this->db->query("SELECT * FROM folders WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        while($r=$q->fetch_assoc()) $folders[] = $r;
        $q = $this->db->query("SELECT * FROM files WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        while($r=$q->fetch_assoc()) $files[] = $r;
        return ['folders' => $folders, 'files' => $files];
    }

    public function restoreItem($type, $id) {
        $table = ($type == 'folder') ? 'folders' : 'files';
        return $this->db->query("UPDATE $table SET deleted_at = NULL WHERE id = '$id'");
    }
}
?>