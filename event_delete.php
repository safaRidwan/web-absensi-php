<?php
require 'app/config.php';
require 'app/auth.php';

if ($_SESSION['role'] !== 'admin' || !isset($_GET['id'])) {
    die("Akses ditolak.");
}

$id = $_GET['id'];

// Hapus data absensi terkait terlebih dahulu (Constraint Foreign Key)
$pdo->prepare("DELETE FROM attendances WHERE event_id = ?")->execute([$id]);

// Baru hapus eventnya
$pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);

header("Location: index.php?msg=deleted");
exit;
?>