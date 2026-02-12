<?php
require 'app/config.php';
require 'app/auth.php';
require 'app/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak");
}

$user_id  = $_SESSION['user_id'];
$event_id = $_POST['event_id'];
$type     = $_POST['type']; 

// Perbaikan 1: Ambil koordinat dengan aman
$lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
$lon = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

if ($lat === null || $lon === null) {
    die("Gagal: Koordinat lokasi tidak terdeteksi. Silakan aktifkan GPS dan refresh halaman.");
}

// 1. Validasi File Foto
if (!isset($_FILES['selfie']) || $_FILES['selfie']['error'] !== UPLOAD_ERR_OK) {
    die("Foto selfie wajib diunggah.");
}

// 2. Persiapan Folder Upload
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 3. Penamaan File Foto
$fileExtension = pathinfo($_FILES['selfie']['name'], PATHINFO_EXTENSION);
$fileName = 'selfie_' . $user_id . '_' . time() . '.' . $fileExtension;
$uploadPath = $uploadDir . $fileName;

if (move_uploaded_file($_FILES['selfie']['tmp_name'], $uploadPath)) {
    try {
        // Perbaikan 2: Simpan $fileName saja ke database agar tidak double path di view
        $sql = "INSERT INTO attendances (user_id, event_id, type, latitude, longitude, selfie_path, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'valid', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $event_id, $type, $lat, $lon, $fileName]);

        header("Location: member.php?status=success");
        exit();
    } catch (PDOException $e) {
        // Jika DB gagal, hapus foto yang sudah terlanjur diupload agar tidak nyampah
        if (file_exists($uploadPath)) unlink($uploadPath);
        die("Gagal menyimpan ke database: " . $e->getMessage());
    }
} else {
    die("Gagal mengunggah foto ke server. Cek izin folder uploads.");
}