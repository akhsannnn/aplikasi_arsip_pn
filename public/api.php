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
            Response::json(true, 'Session Valid', ['user' => $_SESSION['username'], 'role' => $_SESSION['role']]);
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
            if(!isset($_FILES['file'])) Response::json(false, 'File tidak ditemukan');
            // FIX: Kirim user_id
            $userId = $_SESSION['user_id'] ?? null;
            $res = $archive->uploadFile($_FILES['file'], $year, $_POST['folder_id'], $userId);
            Response::json($res, $res ? 'Upload Berhasil' : 'Gagal Upload');
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

        default:
            Response::json(false, "Action '$action' not found");
    }

} catch (Exception $e) {
    Response::json(false, $e->getMessage());
}
?>