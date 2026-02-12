<?php
require 'app/config.php';
require 'app/auth.php';
require 'app/functions.php';

// Cek Admin
if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// 1. Ambil Data Event Lama
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) die("Event tidak ditemukan.");

// 2. Proses Update Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Token Error");
    }

    $title = $_POST['title'];
    // Ubah format HTML datetime-local (T) ke format MySQL (spasi)
    $start = str_replace('T', ' ', $_POST['start_time']);
    $end   = str_replace('T', ' ', $_POST['end_time']);
    $lat   = $_POST['lat'];
    $lon   = $_POST['lon'];
    $tol   = $_POST['tolerance'];
    
    // Checkbox is_active (jika dicentang kirim 'on', jika tidak kirim null)
    $active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "UPDATE events SET title=?, start_time=?, end_time=?, lat=?, lon=?, tolerance_meters=?, is_active=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    
    if($stmt->execute([$title, $start, $end, $lat, $lon, $tol, $active, $id])) {
        // Redirect kembali ke index
        header("Location: index.php?msg=updated");
        exit;
    } else {
        $error = "Gagal mengupdate event.";
    }
}

// Helper: Ubah format MySQL (2025-11-26 10:00:00) ke format HTML input (2025-11-26T10:00)
$startVal = date('Y-m-d\TH:i', strtotime($event['start_time']));
$endVal   = date('Y-m-d\TH:i', strtotime($event['end_time']));

include 'includes/header.php';
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Edit Kegiatan</h5>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>
    <div class="card-body">
        <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="mb-3">
                <label class="form-label">Nama Kegiatan</label>
                <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($event['title']) ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="datetime-local" class="form-control" name="start_time" value="<?= $startVal ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="datetime-local" class="form-control" name="end_time" value="<?= $endVal ?>" required>
                </div>
            </div>

            <hr class="my-3">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" class="form-control" name="lat" value="<?= $event['lat'] ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" class="form-control" name="lon" value="<?= $event['lon'] ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Radius Toleransi (Meter)</label>
                <input type="number" class="form-control" name="tolerance" value="<?= $event['tolerance_meters'] ?>" required>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= $event['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Status Aktif (Tampilkan di Absen)</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            
            <a href="event_delete.php?id=<?= $id ?>" class="btn btn-outline-danger float-end" onclick="return confirm('Yakin hapus kegiatan ini? Semua data absen terkait akan hilang!')">
                <i class='bx bx-trash'></i> Hapus Kegiatan
            </a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>