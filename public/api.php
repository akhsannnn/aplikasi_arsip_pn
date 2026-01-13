<?php
session_start();
// Error Handling: Matikan output error ke browser agar JSON tetap bersih
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

// Load Dependencies (Autoload Manual)
require_once '../src/Config/Database.php';
require_once '../src/Helpers/Response.php';
require_once '../src/Services/AuthService.php';
require_once '../src/Services/ArchiveService.php';
require_once '../src/Services/TemplateService.php';

// Init Services
$database = new Database();
$db = $database->getConnection();

$auth = new AuthService($db);
$archive = new ArchiveService($db);
$template = new TemplateService($db);

$action = $_GET['action'] ?? '';
$year = $_GET['year'] ?? date('Y');

try {
    // --- PUBLIC ROUTES (No Login Required) ---
    if ($action === 'login') {
        if ($auth->login($_POST['username'], $_POST['password'])) {
            Response::json(true, 'Login Berhasil');
        } else {
            Response::json(false, 'Username atau Password Salah');
        }
    }

    // --- PROTECTED ROUTES (Login Required) ---
    if (!isset($_SESSION['user_id'])) {
        Response::json(false, 'Unauthorized');
    }

    switch ($action) {
        // Auth
        case 'check_session':
            Response::json(true, 'Session Valid', ['user' => $_SESSION['username']]);
            break;
        case 'logout':
            session_destroy();
            Response::json(true, 'Logout Berhasil');
            break;

        // Dashboard
        case 'dashboard':
            Response::json(true, 'Stats Loaded', $archive->getDashboardStats());
            break;

        // Archive Content
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
            $res = $archive->uploadFile($_FILES['file'], $year, $_POST['folder_id']);
            Response::json($res, $res ? 'Upload Berhasil' : 'Gagal Upload');
            break;
        case 'delete_item':
            $res = $archive->deleteItem($_POST['type'], $_POST['id']);
            Response::json($res, 'Item dihapus');
            break;

        // Trash
        case 'get_trash':
            Response::json(true, 'Trash Loaded', $archive->getTrash());
            break;
        case 'restore_item':
            $res = $archive->restoreItem($_POST['type'], $_POST['id']);
            Response::json($res, 'Item dipulihkan');
            break;

        // Template Manager
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
            $res = $template->applyTemplate($_POST['template_id'], $_POST['target_year']);
            Response::json($res['success'], $res['message']);
            break;

        default:
            Response::json(false, "Action '$action' not found");
    }

} catch (Exception $e) {
    Response::json(false, $e->getMessage());
}