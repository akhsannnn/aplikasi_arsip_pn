<?php
class ArchiveService {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // --- DASHBOARD (Dengan UNION Query untuk Aktivitas Gabungan) ---
    public function getDashboardStats() {
        $q1 = $this->db->query("SELECT COUNT(*) as c FROM files WHERE deleted_at IS NULL");
        $f = $q1 ? $q1->fetch_assoc()['c'] : 0;

        $q2 = $this->db->query("SELECT COUNT(*) as c FROM folders WHERE deleted_at IS NULL");
        $d = $q2 ? $q2->fetch_assoc()['c'] : 0;
        
        // Query Gabungan (Upload & Create Folder)
        $sql = "
            (SELECT 
                'upload' as type,
                f.filename as name, 
                f.uploaded_at as time, 
                u.full_name, 
                u.role 
            FROM files f 
            LEFT JOIN users u ON f.user_id = u.id 
            WHERE f.deleted_at IS NULL)
            
            UNION ALL
            
            (SELECT 
                'folder' as type,
                fo.name as name, 
                fo.created_at as time, 
                u.full_name, 
                u.role 
            FROM folders fo 
            LEFT JOIN users u ON fo.created_by = u.id 
            WHERE fo.deleted_at IS NULL)
            
            ORDER BY time DESC 
            LIMIT 10
        ";
        
        $recent = [];
        $q = $this->db->query($sql);
        if($q) while($r = $q->fetch_assoc()) { $recent[] = $r; }

        return ['total_files' => $f, 'total_folders' => $d, 'recent' => $recent];
    }

    // --- SIDEBAR ---
    public function getSidebarYears() {
        $years = [];
        $q = $this->db->query("SELECT DISTINCT year FROM folders WHERE deleted_at IS NULL ORDER BY year DESC");
        if($q) while($r = $q->fetch_assoc()) $years[] = $r['year'];
        return $years;
    }

    // --- GET CONTENT ---
    public function getContent($year, $folderId) {
        // Logic Strict NULL
        if ($folderId && $folderId !== 'null' && $folderId !== '') {
             $pidQ = "parent_id = '$folderId'";
             $fidQ = "folder_id = '$folderId'";
        } else {
             $pidQ = "parent_id IS NULL";
             $fidQ = "folder_id IS NULL";
        }

        // Folders
        $folders = [];
        $q = $this->db->query("SELECT * FROM folders WHERE year='$year' AND $pidQ AND deleted_at IS NULL ORDER BY name ASC");
        if($q) while($r = $q->fetch_assoc()) $folders[] = $r;

        // Files
        $yearFilter = "AND filepath LIKE '%uploads/$year/%'";
        $files = [];
        $q = $this->db->query("SELECT * FROM files WHERE $fidQ $yearFilter AND deleted_at IS NULL ORDER BY id DESC");
        if($q) while($r = $q->fetch_assoc()) {
            $ext = strtolower(pathinfo($r['filename'], PATHINFO_EXTENSION));
            $r['is_previewable'] = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']);
            $files[] = $r;
        }

        // Breadcrumbs
        $breadcrumbs = [];
        if($folderId && $folderId !== 'null') {
            $curr = $folderId;
            while($curr) {
                $d = $this->db->query("SELECT id, name, parent_id FROM folders WHERE id='$curr'")->fetch_assoc();
                if($d) { array_unshift($breadcrumbs, $d); $curr = $d['parent_id']; } else { $curr = null; }
            }
        }
        
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
        $targetDir = __DIR__ . "/../../public/uploads/$year/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = $this->db->real_escape_string($file['name']);
        $uniqueName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", $fileName);
        $targetFile = $targetDir . $uniqueName;
        $dbPath = "uploads/$year/$uniqueName"; 

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $fid = ($folderId && $folderId != 'null') ? "'$folderId'" : "NULL";
            $uid = $userId ? "'$userId'" : "NULL";
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            return $this->db->query("INSERT INTO files (folder_id, filename, filepath, filetype, user_id) VALUES ($fid, '$fileName', '$dbPath', '$ext', $uid)");
        }
        return false;
    }

    // --- DELETE (SOFT DELETE) ---
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

    // --- TRASH & RESTORE ---
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

    // --- FUNGSI BARU YANG SEBELUMNYA HILANG ---
    
    // 1. HAPUS PERMANEN (HARD DELETE + UNLINK)
    public function deletePermanent($type, $id) {
        if ($type == 'file') {
            // Ambil path file
            $q = $this->db->query("SELECT filepath FROM files WHERE id = '$id'");
            $file = $q->fetch_assoc();
            
            // Hapus Fisik
            if ($file) {
                $physicalPath = __DIR__ . "/../../public/" . $file['filepath'];
                if (file_exists($physicalPath)) {
                    unlink($physicalPath);
                }
            }
            // Hapus DB
            return $this->db->query("DELETE FROM files WHERE id = '$id'");
        } else {
            // Hapus file fisik di dalam folder ini (agar tidak jadi sampah)
            $q = $this->db->query("SELECT filepath FROM files WHERE folder_id = '$id'");
            while($f = $q->fetch_assoc()) {
                $p = __DIR__ . "/../../public/" . $f['filepath'];
                if(file_exists($p)) unlink($p);
            }
            return $this->db->query("DELETE FROM folders WHERE id = '$id'");
        }
    }

    // 2. KOSONGKAN SAMPAH
    public function emptyTrash() {
        // Ambil semua file sampah
        $q = $this->db->query("SELECT filepath FROM files WHERE deleted_at IS NOT NULL");
        $count = 0;
        
        while ($file = $q->fetch_assoc()) {
            $path = __DIR__ . "/../../public/" . $file['filepath'];
            if (file_exists($path)) {
                unlink($path);
            }
            $count++;
        }

        // Hapus Data DB
        $this->db->query("DELETE FROM files WHERE deleted_at IS NOT NULL");
        $this->db->query("DELETE FROM folders WHERE deleted_at IS NOT NULL");

        return $count;
    }
}
?>