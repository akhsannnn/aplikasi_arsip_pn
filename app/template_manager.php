<?php
include 'db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$template_id = isset($_GET['id']) ? $_GET['id'] : '';

// ===== ACTION: CREATE TEMPLATE =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($name)) {
        $error = "Nama template tidak boleh kosong";
    } else {
        // Jika ada yang di-set sebagai default, unset yang lain
        if ($is_default) {
            mysqli_query($conn, "UPDATE templates SET is_default = 0");
        }
        
        $query = "INSERT INTO templates (name, description, is_default) VALUES ('$name', '$description', '$is_default')";
        if (mysqli_query($conn, $query)) {
            $new_template_id = mysqli_insert_id($conn);
            header("Location: template_manager.php?id=$new_template_id&success=Template berhasil dibuat");
            exit;
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// ===== ACTION: UPDATE TEMPLATE =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = $_POST['template_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if ($is_default) {
        mysqli_query($conn, "UPDATE templates SET is_default = 0");
    }
    
    $query = "UPDATE templates SET name = '$name', description = '$description', is_default = '$is_default' WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        header("Location: template_manager.php?id=$id&success=Template berhasil diupdate");
        exit;
    }
}

// ===== ACTION: DELETE TEMPLATE =====
if ($action == 'delete' && $template_id) {
    $query = "DELETE FROM templates WHERE id = '$template_id'";
    if (mysqli_query($conn, $query)) {
        header("Location: template_manager.php?success=Template berhasil dihapus");
        exit;
    }
}

// ===== ACTION: ADD FOLDER KE TEMPLATE =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_folder') {
    $template_id = $_POST['template_id'];
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;
    $name = mysqli_real_escape_string($conn, $_POST['folder_name']);
    $description = mysqli_real_escape_string($conn, $_POST['folder_desc']);
    $order_index = !empty($_POST['order_index']) ? $_POST['order_index'] : 0;
    
    $parent_val = $parent_id ? "'$parent_id'" : "NULL";
    $query = "INSERT INTO template_folders (template_id, parent_id, name, description, order_index) 
              VALUES ('$template_id', $parent_val, '$name', '$description', '$order_index')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: template_manager.php?id=$template_id&success=Folder berhasil ditambahkan");
        exit;
    }
}

// ===== ACTION: DELETE FOLDER DARI TEMPLATE =====
if ($action == 'delete_folder' && isset($_GET['folder_id'])) {
    $folder_id = $_GET['folder_id'];
    $query = "SELECT template_id FROM template_folders WHERE id = '$folder_id'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $tid = $row['template_id'];
    
    // Hapus folder dan subfolder-nya
    mysqli_query($conn, "DELETE FROM template_folders WHERE id = '$folder_id'");
    
    header("Location: template_manager.php?id=$tid&success=Folder berhasil dihapus");
    exit;
}

// Ambil template yang sedang diedit
$current_template = null;
$template_folders = [];
if ($template_id) {
    $query = "SELECT * FROM templates WHERE id = '$template_id'";
    $result = mysqli_query($conn, $query);
    $current_template = mysqli_fetch_assoc($result);
    
    // Ambil semua folder di template ini
    $query = "SELECT * FROM template_folders WHERE template_id = '$template_id' ORDER BY parent_id, order_index";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $template_folders[] = $row;
    }
}

// Ambil semua template
$all_templates = [];
$query = "SELECT * FROM templates ORDER BY is_default DESC, name ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $all_templates[] = $row;
}
function buildFolderOptions($folders, $parent_id = null, $level = 0) {
    foreach ($folders as $f) {
        if ($f['parent_id'] == $parent_id) {
            echo "<option value='{$f['id']}'>";
            echo str_repeat('— ', $level) . $f['name'];
            echo "</option>";
            buildFolderOptions($folders, $f['id'], $level + 1);
        }
    }
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Manager - Sistem Arsip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; }
        .court-green { color: #1b5e20; }
        .bg-court-green { background-color: #1b5e20; }
        .court-gold { color: #c6a700; }
        .bg-court-gold { background-color: #c6a700; }
        .tree-item { margin-left: 1.5rem; }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-court-green text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-scale-balanced text-court-gold text-2xl"></i>
                <h1 class="text-lg font-bold text-court-gold">TEMPLATE MANAGER</h1>
            </div>
            <a href="index.php" class="text-white hover:text-court-gold transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fa-solid fa-check-circle mr-2"></i> <?php echo $_GET['success']; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- SIDEBAR: Daftar Template -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h3 class="text-lg font-bold text-court-green mb-4">
                        <i class="fa-solid fa-list mr-2"></i> Daftar Template
                    </h3>
                    
                    <div class="space-y-2 mb-4">
                        <?php foreach ($all_templates as $t): ?>
                        <a href="template_manager.php?id=<?php echo $t['id']; ?>" 
                           class="block p-3 rounded border-l-4 transition <?php echo ($template_id == $t['id']) ? 'bg-court-green text-white border-l-court-gold' : 'bg-gray-50 border-l-gray-300 hover:bg-gray-100'; ?>">
                            <div class="font-bold text-sm"><?php echo $t['name']; ?></div>
                            <?php if ($t['is_default']): ?>
                                <span class="text-[10px] inline-block mt-1 px-2 py-0.5 bg-yellow-300 rounded">DEFAULT</span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <button onclick="showCreateTemplate()" class="w-full bg-court-green text-white py-2 rounded hover:bg-opacity-90 text-sm font-bold">
                        <i class="fa-solid fa-plus mr-2"></i> Template Baru
                    </button>
                </div>
            </div>

            <!-- MAIN: Detail Template -->
            <div class="md:col-span-2">
                <?php if ($current_template): ?>
                
                <!-- Edit Template Info -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-bold text-court-green mb-4">Edit Template</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="template_id" value="<?php echo $current_template['id']; ?>">
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Nama Template</label>
                            <input type="text" name="name" value="<?php echo $current_template['name']; ?>" required 
                                   class="w-full border rounded p-2 focus:border-court-green outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi</label>
                            <textarea name="description" class="w-full border rounded p-2 focus:border-court-green outline-none"><?php echo $current_template['description']; ?></textarea>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="is_default" id="is_default" <?php echo $current_template['is_default'] ? 'checked' : ''; ?> class="mr-2">
                            <label for="is_default" class="text-sm font-medium text-gray-700">Set sebagai template default</label>
                        </div>
                        
                        <div class="flex gap-3">
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 font-bold">
                                <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                            </button>
                            <?php if (!$current_template['is_default']): ?>
                            <button type="button" onclick="confirmDelete(<?php echo $current_template['id']; ?>)" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 font-bold">
                                <i class="fa-solid fa-trash mr-2"></i> Hapus Template
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Struktur Folder -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-court-green mb-4">Struktur Folder</h3>
                    
                    <?php if (empty($template_folders)): ?>
                    <p class="text-gray-500 text-sm mb-4">Belum ada folder dalam template ini.</p>
                    <?php else: ?>
                    <div class="bg-gray-50 p-4 rounded mb-4 max-h-96 overflow-y-auto">
                        <?php
                        function renderFolderTree($folders, $parent_id = null, $level = 0) {
                            foreach ($folders as $folder) {
                                if ($folder['parent_id'] == $parent_id) {
                                    $margin = ($level * 1.5) . 'rem';
                                    echo "<div style='margin-left: {$margin}' class='py-2 border-b border-gray-200'>";
                                    echo "<div class='flex items-center justify-between'>";
                                    echo "<div>";
                                    echo "<div class='font-bold text-gray-800'>" . $folder['name'] . "</div>";
                                    if ($folder['description']) {
                                        echo "<div class='text-xs text-gray-500'>" . $folder['description'] . "</div>";
                                    }
                                    echo "</div>";
                                    echo "<a href='template_manager.php?action=delete_folder&folder_id={$folder['id']}&id={$_GET['id']}' class='text-red-500 hover:text-red-700 text-sm' onclick=\"return confirm('Hapus folder ini?')\">";
                                    echo "<i class='fa-solid fa-trash'></i>";
                                    echo "</a>";
                                    echo "</div>";
                                    echo "</div>";
                                    renderFolderTree($folders, $folder['id'], $level + 1);
                                }
                            }
                        }
                        renderFolderTree($template_folders);
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Form Tambah Folder -->
                    <form method="POST" class="mt-6 p-4 bg-blue-50 rounded border border-blue-200">
                        <h4 class="font-bold text-blue-900 mb-3">Tambah Folder</h4>
                        <input type="hidden" name="action" value="add_folder">
                        <input type="hidden" name="template_id" value="<?php echo $current_template['id']; ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Folder</label>
                                <input type="text" name="folder_name" required class="w-full border rounded p-2 text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Parent Folder (Opsional)</label>
                                <select name="parent_id" class="w-full border rounded p-2 text-sm">
                                    <option value="">-- Root (Tidak ada parent) --</option>
                                    <?php buildFolderOptions($template_folders); ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Urutan</label>
                                <input type="number" name="order_index" value="0" class="w-full border rounded p-2 text-sm">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Deskripsi</label>
                            <textarea name="folder_desc" class="w-full border rounded p-2 text-sm"></textarea>
                        </div>
                        
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-bold">
                            <i class="fa-solid fa-plus mr-2"></i> Tambah
                        </button>
                    </form>
                </div>

                <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i class="fa-solid fa-folder-open text-6xl text-gray-300 mb-4 block"></i>
                    <p class="text-gray-500 mb-4">Pilih template dari daftar atau buat yang baru</p>
                    <button onclick="showCreateTemplate()" class="bg-court-green text-white px-6 py-2 rounded hover:bg-opacity-90 font-bold">
                        <i class="fa-solid fa-plus mr-2"></i> Template Baru
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal: Create Template -->
    <div id="modalCreateTemplate" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg w-full max-w-md p-6 shadow-xl">
            <h3 class="font-bold text-court-green mb-4 text-lg">Template Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-3">
                    <label class="text-sm font-bold block text-gray-700 mb-1">Nama Template *</label>
                    <input type="text" name="name" required class="w-full border rounded p-2 focus:border-court-green outline-none">
                </div>
                
                <div class="mb-3">
                    <label class="text-sm font-bold block text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" class="w-full border rounded p-2 focus:border-court-green outline-none"></textarea>
                </div>
                
                <div class="mb-4 flex items-center">
                    <input type="checkbox" name="is_default" id="def" class="mr-2">
                    <label for="def" class="text-sm text-gray-700">Set sebagai default</label>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeCreateTemplate()" class="flex-1 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 font-bold">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-court-green text-white rounded hover:bg-opacity-90 font-bold">Buat</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showCreateTemplate() {
        document.getElementById('modalCreateTemplate').classList.remove('hidden');
    }
    function closeCreateTemplate() {
        document.getElementById('modalCreateTemplate').classList.add('hidden');
    }
    function confirmDelete(id) {
        if (confirm('Hapus template ini? Aksi ini tidak bisa dibatalkan.')) {
            window.location = `template_manager.php?action=delete&id=${id}`;
        }
    }
    </script>
</body>
</html>
