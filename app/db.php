<?php
// Ambil dari environment variables Docker atau fallback ke localhost
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'db_arsip';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    // Return JSON error jika koneksi gagal agar frontend tidak hang
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Koneksi Database Gagal: ' . mysqli_connect_error()]);
    exit;
}
?>