<?php
// 1. Generate CSRF Token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 2. Rumus Haversine (Hitung Jarak dalam Meter)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Meter
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

// 3. Validasi & Upload Foto
function uploadPhoto($file) {
    $targetDir = __DIR__ . "/../uploads/";
    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png'];
    
    // Cek ekstensi
    if (!in_array($fileType, $allowedTypes)) {
        return ['status' => false, 'msg' => 'Hanya file JPG/PNG yang diperbolehkan.'];
    }
    
    // Cek ukuran (Max 5MB)
    if ($file["size"] > 5000000) {
        return ['status' => false, 'msg' => 'Ukuran file terlalu besar (Max 5MB).'];
    }

    // Generate nama unik
    $fileName = uniqid() . '_' . time() . '.' . $fileType;
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['status' => true, 'path' => $fileName, 'full_path' => $targetFile];
    }
    
    return ['status' => false, 'msg' => 'Gagal mengupload file.'];
}
?>