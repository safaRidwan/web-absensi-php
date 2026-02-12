<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database Dinamis (Mengambil dari Environment Variables Railway)
$host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db   = getenv('MYSQLDATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: 'qnrZnungDLceUkeghLWDtyQxDbqNsGdD';
$port = getenv('MYSQLPORT') ?: '3306';

try {
    // Menambahkan variabel $port ke dalam DSN
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Base URL Otomatis
// Jika di hosting, akan mengambil domain railway. Jika di lokal, kembali ke localhost.
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$current_domain = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . "://" . $current_domain . "/");

?>




