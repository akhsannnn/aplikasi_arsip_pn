<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

require_once '../src/Config/Database.php';
require_once '../src/Helpers/Response.php';
require_once '../src/Services/AuthService.php';
require_once '../src/Services/ArchiveService.php';
require_once '../src/Services/TemplateService.php';

$database = new Database();
$db = $database->getConnection();

$auth = new AuthService($db);
$archive = new ArchiveService($db);
$template = new TemplateService($db);

$action = $_GET['action'] ?? '';
$year = $_GET['year'] ?? date('Y');

try {
    // --- PUBLIC ---
    if ($action === 'login') {
        if ($auth->login($_POST['username'], $_POST['password'])) {
            Response::json(true, 'Login Berhasil');
        } else {
            Response::json(false, 'Username atau Password Salah');
        }
    }

    // --- PROTECTED ---
    if (!isset($_SESSION['user_id'])) {
        Response::json(false, 'Unauthorized');
    }

    // --- FILTER ROLE ADMIN ---
    $adminActions = [
        'dashboard',
        'delete_year', 'delete_item', 'get_trash', 'restore_item', 
        'create_template', 'add_template_item', 'delete_template', 'delete_template_item', 'apply_template'
    ];
    if (in_array($action, $adminActions)) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            Response::json(false, 'AKSES DITOLAK: Fitur ini hanya untuk Admin.');
        }
    }

    switch ($action) {
       case 'check_session':
            Response::json(true, 'Session Valid', [
                'user' => $_SESSION['username'],
                'role' => $_SESSION['role'] // <--- Pastikan baris ini ada
            ]);
            break;
        case 'logout':
            session_destroy();
            Response::json(true, 'Logout Berhasil');
            break;

        case 'dashboard':
            Response::json(true, 'Stats Loaded', $archive->getDashboardStats());
            break;
        case 'get_sidebar':
            Response::json(true, 'Sidebar Loaded', $archive->getSidebarYears());
            break;

        case 'get_content':
            $fid = $_GET['folder_id'] ?? null;
            $data = $archive->getContent($year, $fid);
            Response::json(true, 'Data Loaded', $data);
            break;
        case 'create_folder':
            $res = $archive->createFolder($_POST['name'], $_POST['desc'], $year, $_POST['parent_id']);
            Response::json($res, $res ? 'Folder Dibuat' : 'Gagal Membuat Folder');
            break;
        case 'upload_file':
            // Cek apakah ada file yang dikirim
            if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
                Response::json(false, 'Tidak ada file yang dipilih.');
            }

            $userId = $_SESSION['user_id'] ?? null;
            $uploadedCount = 0;
            $failedCount = 0;
            
            // Ambil array files dari form
            $files = $_FILES['files'];
            $totalFiles = count($files['name']);

            // LOOPING UNTUK MEMPROSES SETIAP FILE
            for ($i = 0; $i < $totalFiles; $i++) {
                // Kita harus menyusun ulang array agar sesuai format yang diterima ArchiveService::uploadFile
                // ArchiveService mengharapkan array tunggal: ['name'=>..., 'tmp_name'=>..., etc]
                
                $singleFile = [
                    'name'      => $files['name'][$i],
                    'type'      => $files['type'][$i],
                    'tmp_name'  => $files['tmp_name'][$i],
                    'error'     => $files['error'][$i],
                    'size'      => $files['size'][$i]
                ];

                // Skip jika ada error pada file spesifik ini
                if ($singleFile['error'] !== UPLOAD_ERR_OK) {
                    $failedCount++;
                    continue;
                }

                // Panggil Service untuk upload 1 file ini
                if ($archive->uploadFile($singleFile, $year, $_POST['folder_id'], $userId)) {
                    $uploadedCount++;
                } else {
                    $failedCount++;
                }
            }

            if ($uploadedCount > 0) {
                $msg = "$uploadedCount file berhasil diupload.";
                if ($failedCount > 0) $msg .= " ($failedCount gagal)";
                Response::json(true, $msg);
            } else {
                Response::json(false, "Gagal mengupload file. Silakan coba lagi.");
            }
            break;
        
        case 'delete_item':
            // FIX: Kirim user_id
            $res = $archive->deleteItem($_POST['type'], $_POST['id'], $_SESSION['user_id']);
            Response::json($res, 'Item dihapus');
            break;
        
        case 'delete_year':
            // FIX: Kirim user_id
            $res = $archive->deleteYear($_POST['year'], $_SESSION['user_id']);
            Response::json($res, 'Arsip Tahun ' . $_POST['year'] . ' dihapus.');
            break;

        case 'get_trash':
            Response::json(true, 'Trash Loaded', $archive->getTrash());
            break;
        case 'restore_item':
            $res = $archive->restoreItem($_POST['type'], $_POST['id']);
            Response::json($res, 'Item dipulihkan');
            break;

        case 'get_templates':
            Response::json(true, 'Loaded', $template->getTemplates());
            break;
        case 'get_template_items':
            $tid = $_GET['template_id'];
            Response::json(true, 'Loaded', $template->getTemplateItems($tid));
            break;
        case 'create_template':
            $id = $template->createTemplate($_POST['name'], $_POST['description']);
            Response::json(true, 'Template Dibuat', ['id' => $id]);
            break;
        case 'add_template_item':
            $res = $template->addItem($_POST['template_id'], $_POST['name'], $_POST['parent_id']);
            Response::json($res, 'Folder Template Ditambahkan');
            break;
        case 'delete_template':
            $template->deleteTemplate($_POST['id']);
            Response::json(true, 'Template Dihapus');
            break;
        case 'delete_template_item':
            $template->deleteItem($_POST['id']);
            Response::json(true, 'Folder Template Dihapus');
            break;
        case 'apply_template':
            // FIX: Kirim user_id
            $res = $template->applyTemplate($_POST['template_id'], $_POST['target_year'], $_SESSION['user_id']);
            Response::json($res['success'], $res['message']);
            break;
        // --- TAMBAHAN UNTUK DELETE PERMANEN ---
        case 'delete_permanent':
            if ($_SESSION['role'] !== 'admin') Response::json(false, 'Hanya Admin!');
            
            $res = $archive->deletePermanent($_POST['type'], $_POST['id']);
            Response::json($res, $res ? 'Item dihapus permanen' : 'Gagal menghapus');
            break;

        case 'empty_trash':
            if ($_SESSION['role'] !== 'admin') Response::json(false, 'Hanya Admin!');
            
            $count = $archive->emptyTrash();
            Response::json(true, "Sampah dikosongkan. $count file fisik dihapus.");
            break;

        default:
            Response::json(false, "Action '$action' not found");
    }

} catch (Exception $e) {
    Response::json(false, $e->getMessage());
}
?>