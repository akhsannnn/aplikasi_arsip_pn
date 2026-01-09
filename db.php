<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_arsip";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    // Return JSON error jika koneksi gagal agar frontend tidak hang
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Koneksi Database Gagal: ' . mysqli_connect_error()]);
    exit;
}
?>