    <?php
    // 1. KONFIGURASI ERROR HANDLING (Sangat Penting)
    // Matikan display error agar teks error PHP tidak merusak format JSON
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);

    // 2. SETTING RESOURCE LIMIT (Override php.ini)
    ini_set('memory_limit', '2048M');
    ini_set('post_max_size', '2048M');
    ini_set('upload_max_filesize', '2048M');
    set_time_limit(36000); // 10 Jam

    // 3. START BUFFERING
    // Menangkap semua output yang tidak sengaja keluar sebelum header JSON
    ob_start();

    include 'db.php';

    // Cek Koneksi DB sekali lagi (Safety)
    if (!$conn) {
        sendJson(false, 'Koneksi Database Terputus: ' . mysqli_connect_error());
    }

    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $year   = isset($_GET['year']) ? $_GET['year'] : date('Y');

    // --- HELPER FUNCTION UNTUK KIRIM JSON AMAN ---
    function sendJson($success, $message, $data = null) {
        // Bersihkan buffer (buang text warning/notice sampah)
        ob_clean(); 
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success, 
            'message' => $message, 
            'data' => $data
        ]);
        exit;
    }

    // --- FUNGSI HAPUS FOLDER FISIK (Rekursif Kuat) ---
    function deleteDir($dirPath) {
        if (!is_dir($dirPath)) return;
        $files = array_diff(scandir($dirPath), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dirPath/$file";
            (is_dir($path)) ? deleteDir($path) : @unlink($path); // @ untuk suppress error jika file terkunci
        }
        @rmdir($dirPath);
    }

    // --- LOGIC UTAMA ---

    try {

        // 1. GET CONTENT
        if ($action == 'get_content') {
            $folder_id = (isset($_GET['folder_id']) && $_GET['folder_id'] != 'null') ? $_GET['folder_id'] : null;
            
            // Breadcrumbs
            $breadcrumbs = [];
            if($folder_id) {
                $curr = $folder_id;
                while($curr) {
                    $q = mysqli_query($conn, "SELECT id, name, parent_id FROM folders WHERE id='$curr'");
                    $d = mysqli_fetch_assoc($q);
                    if($d) { array_unshift($breadcrumbs, $d); $curr = $d['parent_id']; } 
                    else { $curr = null; }
                }
            }

            // Fetch Data
            $pid_query = $folder_id ? "parent_id = '$folder_id'" : "parent_id IS NULL";
            $folders = [];
            $q_f = mysqli_query($conn, "SELECT * FROM folders WHERE year = '$year' AND $pid_query ORDER BY name ASC");
            while($r = mysqli_fetch_assoc($q_f)) $folders[] = $r;

            $files = [];
            $fid_query = $folder_id ? "folder_id = '$folder_id'" : "folder_id IS NULL";
            $q_fi = mysqli_query($conn, "SELECT * FROM files WHERE $fid_query ORDER BY id DESC");
            while($r = mysqli_fetch_assoc($q_fi)) {
                $ext = strtolower(pathinfo($r['filename'], PATHINFO_EXTENSION));
                $r['is_previewable'] = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'mp4', 'avi', 'mov', 'mkv', 'webm']);
                $files[] = $r;
            }

            // Years
            $years = [];
            $q_y = mysqli_query($conn, "SELECT DISTINCT year FROM folders ORDER BY year DESC");
            while($r = mysqli_fetch_assoc($q_y)) $years[] = $r['year'];

            sendJson(true, 'Data fetched', ['breadcrumbs' => $breadcrumbs, 'folders' => $folders, 'files' => $files, 'years' => $years]);
        }

        // 2. GENERATE CUSTOM (Generate Template)
        if ($action == 'generate_custom') {
            $source = $_POST['source_year'];
            $target = $_POST['target_year'];

            if(empty($target)) sendJson(false, "Tahun target tidak boleh kosong.");

            $check = mysqli_query($conn, "SELECT id FROM folders WHERE year = '$target' LIMIT 1");
            if(mysqli_num_rows($check) > 0) sendJson(false, "Tahun $target sudah ada datanya. Harap hapus dulu tahun $target sebelum generate ulang.");

            // Fungsi Copy
            function copyRecursively($conn, $pid_old, $pid_new, $s_year, $t_year) {
                $sql = "SELECT * FROM folders WHERE year = '$s_year' " . (($pid_old === NULL) ? "AND parent_id IS NULL" : "AND parent_id = '$pid_old'");
                $res = mysqli_query($conn, $sql);
                
                while ($f = mysqli_fetch_assoc($res)) {
                    $name = mysqli_real_escape_string($conn, $f['name']);
                    $desc = mysqli_real_escape_string($conn, $f['description']);
                    $pid_val = $pid_new ? "'$pid_new'" : "NULL";
                    
                    mysqli_query($conn, "INSERT INTO folders (name, year, parent_id, description) VALUES ('$name', '$t_year', $pid_val, '$desc')");
                    $new_id = mysqli_insert_id($conn);
                    
                    // Panggil diri sendiri untuk sub-folder
                    copyRecursively($conn, $f['id'], $new_id, $s_year, $t_year);
                }
            }

            copyRecursively($conn, NULL, NULL, $source, $target);
            sendJson(true, "Berhasil menyalin struktur dari $source ke $target");
        }

        // 3. DELETE YEAR (FIX GHOST DATA)
        if ($action == 'delete_year') {
            $del_year = $_POST['year'];
            
            if(!$del_year) sendJson(false, "Tahun tidak valid.");

            // Hapus Fisik
            deleteDir("uploads/" . $del_year); 
            
            // Hapus Database (Cari semua folder ID dulu untuk hapus file)
            // Kita tidak mengandalkan CASCADE delete database untuk keamanan script ini
            $q_ids = mysqli_query($conn, "SELECT id FROM folders WHERE year = '$del_year'");
            $ids = []; 
            while($r = mysqli_fetch_assoc($q_ids)) $ids[] = $r['id'];
            
            if(!empty($ids)) {
                $ids_str = implode(',', $ids);
                mysqli_query($conn, "DELETE FROM files WHERE folder_id IN ($ids_str)");
            }
            
            // Hapus Folders
            $res = mysqli_query($conn, "DELETE FROM folders WHERE year = '$del_year'");
            
            if($res) sendJson(true, "Tahun $del_year berhasil dihapus total.");
            else sendJson(false, "Gagal menghapus database: " . mysqli_error($conn));
        }

        // 4. CRUD LAINNYA
        if ($action == 'upload_file') {
            $folder_id = ($_POST['folder_id'] !== 'null') ? $_POST['folder_id'] : "NULL";
            $target_dir = "uploads/" . $year . "/";
            
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            if(!isset($_FILES['file'])) sendJson(false, "Tidak ada file yang dikirim (Cek post_max_size)");
            
            $file_name = $_FILES['file']['name'];
            $tmp_name  = $_FILES['file']['tmp_name'];
            
            if(!$tmp_name) sendJson(false, "File Gagal diupload ke Temp (File terlalu besar?)");

            $final_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name);
            $target_file = $target_dir . $final_name;

            if (move_uploaded_file($tmp_name, $target_file)) {
                $fid_val = ($folder_id === "NULL") ? "NULL" : "'$folder_id'";
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                mysqli_query($conn, "INSERT INTO files (folder_id, filename, filepath, filetype) VALUES ($fid_val, '$file_name', '$target_file', '$ext')");
                sendJson(true, 'Upload berhasil');
            } else {
                sendJson(false, 'Gagal memindahkan file ke folder uploads (Cek Permission Folder).');
            }
        }

        if ($action == 'create_folder') {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $desc = mysqli_real_escape_string($conn, $_POST['desc']);
            $pid  = ($_POST['parent_id'] !== 'null' && $_POST['parent_id'] !== '') ? "'".$_POST['parent_id']."'" : "NULL";
            
            $q = mysqli_query($conn, "INSERT INTO folders (name, year, parent_id, description) VALUES ('$name', '$year', $pid, '$desc')");
            if($q) sendJson(true, 'Folder dibuat');
            else sendJson(false, 'Gagal DB: '.mysqli_error($conn));
        }

        if ($action == 'rename_folder') {
            $id   = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['desc'] ?? '');

            if (!$id || $name === '') {
                sendJson(false, 'ID folder atau nama tidak valid');
            }

            $name = mysqli_real_escape_string($conn, $name);
            $desc = mysqli_real_escape_string($conn, $desc);

            $q = mysqli_query(
                $conn,
                "UPDATE folders SET name = '$name', description = '$desc' WHERE id = '$id'"
            );

            if ($q) {
                sendJson(true, 'Folder berhasil diubah');
            } else {
                sendJson(false, 'DB Error: ' . mysqli_error($conn));
            }
        }
        
        // DELETE FOLDER (Rekursif + Files)
        if ($action == 'delete_folder') {
            $folder_id = $_POST['id'] ?? null;

            if (!$folder_id) {
                sendJson(false, 'ID folder tidak valid');
            }

            // 1. Cari semua subfolder secara rekursif
            $all_ids = [];
            function getAllFolderIds($conn, $parent_id, &$ids) {
                $q = mysqli_query($conn, "SELECT id FROM folders WHERE parent_id = '$parent_id'");
                while ($r = mysqli_fetch_assoc($q)) {
                    $ids[] = $r['id'];
                    getAllFolderIds($conn, $r['id'], $ids);
                }
            }
            $all_ids[] = $folder_id;
            getAllFolderIds($conn, $folder_id, $all_ids);

            // 2. Hapus files dari folder + subfolder
            $ids_str = implode(',', $all_ids);
            $q_files = mysqli_query($conn, "SELECT filepath FROM files WHERE folder_id IN ($ids_str)");
            while ($f = mysqli_fetch_assoc($q_files)) {
                @unlink($f['filepath']); // Hapus file fisik
            }
            mysqli_query($conn, "DELETE FROM files WHERE folder_id IN ($ids_str)");

            // 3. Hapus semua folder
            $q_del = mysqli_query($conn, "DELETE FROM folders WHERE id IN ($ids_str)");

            if ($q_del) {
                sendJson(true, 'Folder dan isinya berhasil dihapus');
            } else {
                sendJson(false, 'DB Error: ' . mysqli_error($conn));
            }
        }

        // DELETE FILE
        if ($action == 'delete_file') {
            $file_id = $_POST['id'] ?? null;

            if (!$file_id) {
                sendJson(false, 'ID file tidak valid');
            }

            // 1. Ambil filepath
            $q = mysqli_query($conn, "SELECT filepath FROM files WHERE id = '$file_id'");
            $f = mysqli_fetch_assoc($q);

            if (!$f) {
                sendJson(false, 'File tidak ditemukan');
            }

            // 2. Hapus file fisik
            @unlink($f['filepath']);

            // 3. Hapus dari database
            $q_del = mysqli_query($conn, "DELETE FROM files WHERE id = '$file_id'");

            if ($q_del) {
                sendJson(true, 'File berhasil dihapus');
            } else {
                sendJson(false, 'DB Error: ' . mysqli_error($conn));
            }
        }

        // Default response jika action tidak ketemu
        if($action == '') sendJson(false, "Action tidak ditemukan");

    } catch (Exception $e) {
        sendJson(false, "System Error: " . $e->getMessage());
    }

?>