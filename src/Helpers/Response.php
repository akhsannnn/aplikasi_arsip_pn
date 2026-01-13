<?php
class Response {
    public static function json($success, $message, $data = null) {
        // Membersihkan output buffer (menghapus spasi/error PHP yang tidak diinginkan)
        ob_clean(); 
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit; // Menghentikan eksekusi script setelah kirim JSON
    }
}