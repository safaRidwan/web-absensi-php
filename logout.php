<?php
// 1. Mulai session (wajib ada untuk mengakses session yang ingin dihapus)
session_start();

// 2. Kosongkan semua variabel session ($_SESSION['user_id'], dll)
$_SESSION = [];

// 3. Hapus cookie session dari browser (Penting untuk keamanan)
// Ini memastikan ID session lama tidak bisa digunakan lagi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan session fisik di server
session_destroy();

// 5. Redirect kembali ke halaman login dengan pesan sukses
header("Location: login.php?msg=logout");
exit;
?>