<?php
include 'db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ===== ACTION: TAMPILKAN FORM PILIH TEMPLATE =====
if ($action == 'select') {
    $targetYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
    
    // Cek apakah sudah ada folder di tahun ini
    $check = mysqli_query($conn, "SELECT id FROM folders WHERE year = '$targetYear' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Tahun $targetYear sudah memiliki data. Silakan hapus terlebih dahulu.'); window.location='index.php';</script>";
        exit;
    }
    
    // Ambil semua template
    $templates = mysqli_query($conn, "SELECT * FROM templates ORDER BY is_default DESC, name ASC");
    $template_list = [];
    while ($t = mysqli_fetch_assoc($templates)) {
        $template_list[] = $t;
    }
    
    // Tampilkan form HTML untuk pilih template
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pilih Template</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="bg-gray-100">
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Buat Tahun <?php echo $targetYear; ?></h2>
                <p class="text-gray-500 mb-6">Pilih template struktur folder untuk tahun ini:</p>
                
                <form id="formSelectTemplate">
                    <input type="hidden" name="year" value="<?php echo $targetYear; ?>">
                    
                    <div class="space-y-3 mb-6">
                        <?php foreach ($template_list as $template): ?>
                        <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-green-500 hover:bg-green-50 transition" onclick="this.querySelector('input').checked = true">
                            <input type="radio" name="template_id" value="<?php echo $template['id']; ?>" class="mr-3" <?php echo $template['is_default'] ? 'checked' : ''; ?>>
                            <div class="flex-1">
                                <div class="font-bold text-gray-800"><?php echo $template['name']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo $template['description']; ?></div>
                            </div>
                            <?php if ($template['is_default']): ?>
                                <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded">DEFAULT</span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" onclick="window.location='index.php'" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                            Batal
                        </button>
                        <button type="button" onclick="submitGenerateFromTemplate()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-bold">
                            <i class="fa-solid fa-check mr-2"></i> Buat
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <a href="template_manager.php" class="text-blue-600 hover:underline text-sm">
                        <i class="fa-solid fa-gear mr-2"></i> Kelola Template
                    </a>
                </div>
            </div>
        </div>
        
        <script>
        function submitGenerateFromTemplate() {
            const templateId = document.querySelector('input[name="template_id"]:checked')?.value;
            const year = document.querySelector('input[name="year"]').value;
            
            if (!templateId) {
                alert('Pilih template terlebih dahulu');
                return;
            }
            
            window.location = `generate.php?action=apply&template_id=${templateId}&year=${year}`;
        }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ===== ACTION: APPLY TEMPLATE (Buat folder berdasarkan template) =====
if ($action == 'apply') {
    $templateId = isset($_POST['template_id']) ? $_POST['template_id'] : $_GET['template_id'];
    $targetYear = isset($_POST['year']) ? $_POST['year'] : $_GET['year'];
    
    if (!$templateId || !$targetYear) {
        echo "<script>alert('Data tidak valid'); window.location='index.php';</script>";
        exit;
    }
    
    // Fungsi rekursif untuk copy template folder
    function applyTemplateRecursively($conn, $templateId, $parentTemplateId, $parentFolderId, $targetYear) {
        $query = "SELECT * FROM template_folders WHERE template_id = '$templateId'";
        
        if ($parentTemplateId === NULL) {
            $query .= " AND parent_id IS NULL";
        } else {
            $query .= " AND parent_id = '$parentTemplateId'";
        }
        
        $query .= " ORDER BY order_index ASC";
        $result = mysqli_query($conn, $query);
        
        while ($folder = mysqli_fetch_assoc($result)) {
            $name = mysqli_real_escape_string($conn, $folder['name']);
            $desc = mysqli_real_escape_string($conn, $folder['description']);
            
            $pid_val = $parentFolderId ? "'$parentFolderId'" : "NULL";
            $insert = "INSERT INTO folders (name, year, parent_id, description) VALUES ('$name', '$targetYear', $pid_val, '$desc')";
            
            if (mysqli_query($conn, $insert)) {
                $newFolderId = mysqli_insert_id($conn);
                // Rekursi untuk subfolder
                applyTemplateRecursively($conn, $templateId, $folder['id'], $newFolderId, $targetYear);
            }
        }
    }
    
    // Jalankan proses
    applyTemplateRecursively($conn, $templateId, NULL, NULL, $targetYear);
    
    echo "<script>alert('Sukses membuat struktur tahun $targetYear dari template!'); window.location='index.php';</script>";
    exit;
}

// ===== ACTION: DEFAULT - Jalankan generate dari tahun lalu (backward compatible) =====
$currentYear = date('Y');
$lastYear = $currentYear - 1;

// Cek apakah sudah ada folder di tahun ini
$check = mysqli_query($conn, "SELECT * FROM folders WHERE year = '$currentYear'");
if (mysqli_num_rows($check) > 0) {
    echo "<script>alert('Folder tahun $currentYear sudah ada.'); window.location='index.php';</script>";
    exit;
}

// Fungsi Rekursif untuk copy folder dan sub-foldernya (dari tahun lalu)
function copyFolders($conn, $parent_id_old, $parent_id_new, $sourceYear, $targetYear) {
    $query = "SELECT * FROM folders WHERE year = '$sourceYear'";
    if ($parent_id_old === NULL) {
        $query .= " AND parent_id IS NULL";
    } else {
        $query .= " AND parent_id = '$parent_id_old'";
    }
    
    $result = mysqli_query($conn, $query);

    while ($folder = mysqli_fetch_assoc($result)) {
        $name = mysqli_real_escape_string($conn, $folder['name']);
        $desc = mysqli_real_escape_string($conn, $folder['description']);
        
        $pid_val = $parent_id_new ? "'$parent_id_new'" : "NULL";
        $insert = "INSERT INTO folders (name, year, parent_id, description) VALUES ('$name', '$targetYear', $pid_val, '$desc')";
        
        if (mysqli_query($conn, $insert)) {
            $new_id = mysqli_insert_id($conn);
            copyFolders($conn, $folder['id'], $new_id, $sourceYear, $targetYear);
        }
    }
}

// Jalankan dari tahun lalu
copyFolders($conn, NULL, NULL, $lastYear, $currentYear);

echo "<script>alert('Sukses menyalin struktur folder dari tahun $lastYear!'); window.location='index.php';</script>";
?>