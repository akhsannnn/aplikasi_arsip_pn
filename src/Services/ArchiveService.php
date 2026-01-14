<?php
class ArchiveService {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // --- DASHBOARD ---
    // ... (kode konstruktor tetap sama) ...

    public function getDashboardStats() {
        // 1. Hitung Total (Pastikan query tidak error)
        $q1 = $this->db->query("SELECT COUNT(*) as c FROM files WHERE deleted_at IS NULL");
        $f = $q1 ? $q1->fetch_assoc()['c'] : 0;

        $q2 = $this->db->query("SELECT COUNT(*) as c FROM folders WHERE deleted_at IS NULL");
        $d = $q2 ? $q2->fetch_assoc()['c'] : 0;
        
        // 2. Query Gabungan (UNION) untuk Aktivitas Terakhir
        // Menggabungkan kejadian Upload File dan Create Folder
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

    // ... (Sisa fungsi getContent, createFolder, dll TETAP SAMA seperti sebelumnya) ...

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

    // ... fungsi deleteItem, deleteYear, getTrash, restoreItem yang lama TETAP ADA ...

    // --- FUNGSI BARU: HAPUS PERMANEN SATU ITEM ---
    public function deletePermanent($type, $id) {
        if ($type == 'file') {
            // 1. Ambil path file dulu sebelum hapus DB
            $q = $this->db->query("SELECT filepath FROM files WHERE id = '$id'");
            $file = $q->fetch_assoc();
            
            // 2. Hapus File Fisik
            if ($file) {
                // Path relatif dari public/api.php ke public/uploads
                $physicalPath = __DIR__ . "/../../public/" . $file['filepath'];
                if (file_exists($physicalPath)) {
                    unlink($physicalPath);
                }
            }
            // 3. Hapus data di DB
            return $this->db->query("DELETE FROM files WHERE id = '$id'");
        
        } else {
            // Jika Folder, hapus folder di DB (File di dalamnya akan ikut terhapus karena ON DELETE CASCADE)
            // TAPI: Kita harus hapus file fisiknya dulu secara manual
            
            // Cari semua file di dalam folder ini (termasuk subfolder logic sederhana)
            // Untuk kesederhanaan, kita asumsikan user sudah mengosongkan folder atau kita hapus paksa
            // Query untuk mendapatkan semua file yang ada di dalam folder ini
            $q = $this->db->query("SELECT filepath FROM files WHERE folder_id = '$id'");
            while($f = $q->fetch_assoc()) {
                $p = __DIR__ . "/../../public/" . $f['filepath'];
                if(file_exists($p)) unlink($p);
            }
            
            return $this->db->query("DELETE FROM folders WHERE id = '$id'");
        }
    }

    // --- FUNGSI BARU: KOSONGKAN SAMPAH ---
    public function emptyTrash() {
        // 1. Ambil semua file yang ada di sampah
        $q = $this->db->query("SELECT filepath FROM files WHERE deleted_at IS NOT NULL");
        $count = 0;
        
        // 2. Hapus fisik semua file tsb
        while ($file = $q->fetch_assoc()) {
            $path = __DIR__ . "/../../public/" . $file['filepath'];
            if (file_exists($path)) {
                unlink($path);
            }
            $count++;
        }

        // 3. Bersihkan Database (Hard Delete)
        $delFiles = $this->db->query("DELETE FROM files WHERE deleted_at IS NOT NULL");
        $delFolders = $this->db->query("DELETE FROM folders WHERE deleted_at IS NOT NULL");

        return $count;
    }
}


?>