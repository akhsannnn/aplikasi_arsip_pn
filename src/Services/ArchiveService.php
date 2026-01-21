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
        
        // QUERY AKTIVITAS (DENGAN LOKASI)
        $sql = "
            (SELECT 
                'upload' as type,
                f.filename as name, 
                f.uploaded_at as time, 
                u.full_name, 
                u.role,
                f.filepath as location  -- Ambil Filepath untuk tahu lokasinya
            FROM files f 
            LEFT JOIN users u ON f.user_id = u.id 
            WHERE f.deleted_at IS NULL)
            
            UNION ALL
            
            (SELECT 
                'folder' as type,
                fo.name as name, 
                fo.created_at as time, 
                u.full_name, 
                u.role,
                fo.year as location     -- Ambil Tahun untuk folder
            FROM folders fo 
            LEFT JOIN users u ON fo.created_by = u.id 
            WHERE fo.deleted_at IS NULL)
            
            ORDER BY time DESC 
            LIMIT 10
        ";
        
        $recent = [];
        $q = $this->db->query($sql);
        if($q) while($r = $q->fetch_assoc()) { 
            // Bersihkan Lokasi
            if($r['type'] === 'upload') {
                // Dari 'uploads/2026/namafile.pdf' ambil '2026'
                $parts = explode('/', str_replace('\\', '/', $r['location']));
                $r['location_clean'] = isset($parts[1]) && is_numeric($parts[1]) ? "Arsip " . $parts[1] : "Arsip";
            } else {
                $r['location_clean'] = "Arsip " . $r['location'];
            }
            $recent[] = $r; 
        }

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
    // ...

    // --- GET CONTENT (PERBAIKAN FOLDER ANAK) ---
 public function getContent($year, $folderId) {
        // 1. NORMALISASI ID FOLDER
        // Pastikan kita tahu ini lagi di ROOT atau SUBFOLDER
        // Jika folderId itu 'null', string kosong, atau false, maka dia adalah ROOT
        $isRoot = (!$folderId || $folderId === 'null' || $folderId === '');

        $folders = [];
        $files = [];

        // ==========================================
        // LOGIKA PENGAMBILAN FOLDER
        // ==========================================
        if ($isRoot) {
            // KASUS ROOT: Ambil folder yang tidak punya induk (parent_id IS NULL) DAN Tahunnya sesuai
            $sqlFolder = "SELECT fo.*, u.full_name as creator 
                          FROM folders fo 
                          LEFT JOIN users u ON fo.created_by = u.id 
                          WHERE fo.parent_id IS NULL 
                          AND fo.year = '$year' 
                          AND fo.deleted_at IS NULL 
                          ORDER BY fo.name ASC";
        } else {
            // KASUS SUBFOLDER: Ambil folder anak dari folderId ini
            // (Tidak perlu cek tahun, karena anak pasti ikut tahun induknya)
            $sqlFolder = "SELECT fo.*, u.full_name as creator 
                          FROM folders fo 
                          LEFT JOIN users u ON fo.created_by = u.id 
                          WHERE fo.parent_id = '$folderId' 
                          AND fo.deleted_at IS NULL 
                          ORDER BY fo.name ASC";
        }

        // Eksekusi Query Folder
        $qF = $this->db->query($sqlFolder);
        if($qF) while($r = $qF->fetch_assoc()) $folders[] = $r;


        // ==========================================
        // LOGIKA PENGAMBILAN FILE (PERBAIKAN UTAMA)
        // ==========================================
        if ($isRoot) {
            // KASUS ROOT: 
            // 1. File harus TIDAK PUNYA folder (folder_id IS NULL)
            // 2. File harus mengandung unsur tahun ini di path-nya (LIKE %2026%)
            //    Kita pakai LIKE yang longgar agar kena baik di Windows (\) maupun Linux (/)
            
            $sqlFile = "SELECT f.*, u.full_name as uploader 
                        FROM files f 
                        LEFT JOIN users u ON f.user_id = u.id 
                        WHERE f.folder_id IS NULL 
                        AND f.filepath LIKE '%$year%' 
                        AND f.deleted_at IS NULL 
                        ORDER BY f.id DESC";
        } else {
            // KASUS SUBFOLDER:
            // 1. Cukup ambil file yang folder_id-nya SAMA dengan folder yang dibuka.
            // 2. JANGAN filter path/tahun lagi! (Ini penyebab bug sebelumnya).
            //    Jika file sudah masuk ke folder ID 5, tampilkan saja, tidak peduli path-nya.
            
            $sqlFile = "SELECT f.*, u.full_name as uploader 
                        FROM files f 
                        LEFT JOIN users u ON f.user_id = u.id 
                        WHERE f.folder_id = '$folderId' 
                        AND f.deleted_at IS NULL 
                        ORDER BY f.id DESC";
        }

        // Eksekusi Query File
        $qFi = $this->db->query($sqlFile);
        if($qFi) while($r = $qFi->fetch_assoc()) {
            $ext = strtolower(pathinfo($r['filename'], PATHINFO_EXTENSION));
            $r['is_previewable'] = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']);
            
            // Helper untuk UI: Coba tebak tahun dari path
            $parts = explode('/', str_replace('\\', '/', $r['filepath'])); // Normalisasi slash
            $r['year'] = isset($parts[1]) && is_numeric($parts[1]) ? $parts[1] : $year;
            
            $files[] = $r;
        }

        // 3. Breadcrumbs (Tetap Sama)
        $breadcrumbs = [];
        if(!$isRoot) {
            $curr = $folderId;
            while($curr) {
                // Ambil info folder saat ini
                $d = $this->db->query("SELECT id, name, parent_id FROM folders WHERE id='$curr'")->fetch_assoc();
                if($d) { 
                    array_unshift($breadcrumbs, $d); 
                    $curr = $d['parent_id']; 
                } else { 
                    $curr = null; 
                }
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
        // 1. Pastikan folder tujuan ada
        $targetDir = __DIR__ . "/../../public/uploads/$year/";
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                throw new Exception("Gagal membuat folder: $targetDir (Cek Permission Docker)");
            }
        }

        $fileName = $this->db->real_escape_string($file['name']);
        // Bersihkan nama file agar aman
        $uniqueName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", $fileName);
        $targetFile = $targetDir . $uniqueName;
        $dbPath = "uploads/$year/$uniqueName"; 

        // 2. Coba Pindahkan File
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $fid = ($folderId && $folderId != 'null') ? "'$folderId'" : "NULL";
            $uid = $userId ? "'$userId'" : "NULL";
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // 3. Masukkan ke Database (TAMBAHKAN 'uploaded_at')
            // Kita tambahkan uploaded_at = NOW() untuk memastikan data waktu terekam
            $sql = "INSERT INTO files (folder_id, filename, filepath, filetype, user_id, uploaded_at) 
                    VALUES ($fid, '$fileName', '$dbPath', '$ext', $uid, NOW())";
            
            if ($this->db->query($sql)) {
                return true;
            } else {
                // Jika Gagal SQL: Hapus file fisik agar tidak jadi sampah & Tampilkan Error
                unlink($targetFile);
                throw new Exception("Database Error: " . $this->db->error);
            }
        } else {
            throw new Exception("Gagal memindahkan file ke folder uploads. Cek izin folder.");
        }
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

  public function search($keyword) {
        $key = $this->db->real_escape_string($keyword);
        $folders = [];
        $files = [];
        
        // 1. CARI FOLDER (Tambahkan JOIN users)
        $sqlF = "SELECT fo.*, u.full_name as creator 
                 FROM folders fo
                 LEFT JOIN users u ON fo.created_by = u.id
                 WHERE (fo.name LIKE '%$key%' OR fo.description LIKE '%$key%' OR fo.year LIKE '%$key%') 
                 AND fo.deleted_at IS NULL 
                 ORDER BY fo.year DESC, fo.name ASC LIMIT 20";
        
        $qF = $this->db->query($sqlF);
        if($qF) while($r = $qF->fetch_assoc()) $folders[] = $r;

        // 2. CARI FILE (Tambahkan JOIN users & Logika Tahun)
        $sqlFi = "SELECT f.*, u.full_name as uploader 
                  FROM files f 
                  LEFT JOIN users u ON f.user_id = u.id 
                  WHERE f.filename LIKE '%$key%' AND f.deleted_at IS NULL 
                  ORDER BY f.id DESC LIMIT 50";
                  
        $qFi = $this->db->query($sqlFi);
        if($qFi) while($r = $qFi->fetch_assoc()) {
            $ext = strtolower(pathinfo($r['filename'], PATHINFO_EXTENSION));
            $r['is_previewable'] = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']);
            
            // Ekstrak tahun dari path
            $parts = explode('/', $r['filepath']);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $r['year'] = $parts[1];
            } else {
                $r['year'] = '-';
            }
            $files[] = $r;
        }

        return ['folders' => $folders, 'files' => $files];
    }

    // --- SEARCH TRASH (BARU) ---
    public function searchTrash($keyword) {
        $key = $this->db->real_escape_string($keyword);
        $folders = []; $files = [];

        // Cari Folder Terhapus
        $qF = $this->db->query("SELECT f.*, u.username as deleter 
                                FROM folders f 
                                LEFT JOIN users u ON f.deleted_by = u.id 
                                WHERE f.deleted_at IS NOT NULL 
                                AND (f.name LIKE '%$key%' OR f.description LIKE '%$key%') 
                                ORDER BY f.deleted_at DESC LIMIT 20");
        if($qF) while($r=$qF->fetch_assoc()) $folders[] = $r;

        // Cari File Terhapus
        $qFi = $this->db->query("SELECT f.*, u.username as deleter 
                                 FROM files f 
                                 LEFT JOIN users u ON f.deleted_by = u.id 
                                 WHERE f.deleted_at IS NOT NULL 
                                 AND f.filename LIKE '%$key%' 
                                 ORDER BY f.deleted_at DESC LIMIT 50");
        if($qFi) while($r=$qFi->fetch_assoc()) $files[] = $r;

        return ['folders' => $folders, 'files' => $files];
    }
} // <--- Pastikan ini kurung penutup Class
?>