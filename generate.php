<?php
include 'db.php';

$currentYear = date('Y');
$lastYear = $currentYear - 1;

// Cek apakah sudah ada folder di tahun ini
$check = mysqli_query($conn, "SELECT * FROM folders WHERE year = '$currentYear'");
if (mysqli_num_rows($check) > 0) {
    echo "<script>alert('Folder tahun $currentYear sudah ada.'); window.location='index.php';</script>";
    exit;
}

// Fungsi Rekursif untuk copy folder dan sub-foldernya
function copyFolders($conn, $parent_id_old, $parent_id_new, $sourceYear, $targetYear) {
    // Cari folder di tahun lalu yang parent_id nya sesuai
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
        
        // Buat folder baru di tahun ini
        $pid_val = $parent_id_new ? "'$parent_id_new'" : "NULL";
        $insert = "INSERT INTO folders (name, year, parent_id, description) VALUES ('$name', '$targetYear', $pid_val, '$desc')";
        
        if (mysqli_query($conn, $insert)) {
            $new_id = mysqli_insert_id($conn);
            // Panggil fungsi ini lagi untuk anak-anak folder ini (Rekursif)
            copyFolders($conn, $folder['id'], $new_id, $sourceYear, $targetYear);
        }
    }
}

// Jalankan fungsi mulai dari Root (NULL)
copyFolders($conn, NULL, NULL, $lastYear, $currentYear);

echo "<script>alert('Sukses menyalin seluruh struktur folder!'); window.location='index.php';</script>";
?>