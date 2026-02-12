<?php
require 'app/config.php';
require 'app/auth.php';
require 'app/functions.php';

// Cek Admin
if (!in_array($_SESSION['role'], ['admin', 'pengurus'])) {
    die("Akses ditolak.");
}

// Proses Simpan Data Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Token Error");
    }

    $title = $_POST['title'];
    $start = str_replace('T', ' ', $_POST['start_time']);
    $end   = str_replace('T', ' ', $_POST['end_time']);
    $lat   = $_POST['lat'];
    $lon   = $_POST['lon'];
    $tol   = $_POST['tolerance'];
    $active = isset($_POST['is_active']) ? 1 : 0; 

    $sql = "INSERT INTO events (title, start_time, end_time, lat, lon, tolerance_meters, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if($stmt->execute([$title, $start, $end, $lat, $lon, $tol, $active])) {
        header("Location: index.php?msg=created");
        exit;
    } else {
        $error = "Gagal membuat kegiatan baru.";
    }
}

include 'includes/header.php';
?>

<style>
    /* Global Softness */
    .card { border-radius: 18px !important; border: none; }
    .btn { border-radius: 12px !important; }
    .rounded-pill-custom { border-radius: 50px !important; }
    
    /* Input Group - Menyatukan Icon dan Input */
    .input-group-merge { 
        background-color: #f9f9f9; 
        border: 1px solid #d9dee3; 
        border-radius: 12px !important; 
        transition: all 0.2s;
    }
    .input-group-merge:focus-within {
        border-color: #696cff;
        box-shadow: 0 0 0 0.15rem rgba(105, 108, 255, 0.1);
        background-color: #fff;
    }
    .input-group-merge .input-group-text {
        background-color: transparent !important;
        border: none !important;
        padding-right: 5px;
    }
    .input-group-merge .form-control {
        background-color: transparent !important;
        border: none !important;
        padding-left: 5px;
    }
    .input-group-merge .form-control:focus {
        box-shadow: none !important;
    }

    /* Form Styles */
    .form-control-soft { border-radius: 12px !important; background-color: #f9f9f9; }
    .bg-light-lokasi { background-color: #f1f3f5; border-radius: 15px; }
    .form-check-input { width: 2.4em; height: 1.2em; cursor: pointer; }
    
    @media (max-width: 576px) {
        .card-body { padding: 1.5rem 1rem !important; }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center p-3 border-0">
                <h5 class="mb-0 fw-bold text-primary"><i class="bx bx-calendar-plus me-1"></i> Buat Kegiatan</h5>

            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger auto-dismiss rounded-3 p-2 small"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted ms-1">NAMA KEGIATAN</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text text-primary"><i class="bx bx-calendar-event"></i></span>
                            <input type="text" class="form-control" name="title" placeholder="Contoh: Kerja Bakti Rutin" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted ms-1">WAKTU MULAI</label>
                            <input type="datetime-local" class="form-control form-control-soft border" name="start_time" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted ms-1">WAKTU SELESAI</label>
                            <input type="datetime-local" class="form-control form-control-soft border" name="end_time" required>
                        </div>
                    </div>

                    <div class="bg-light-lokasi p-3 border mb-3 mx-0 shadow-sm text-center">
                        <div class="row mb-2 text-start">
                            <div class="col-6">
                                <label class="form-label fw-bold small text-muted">LATITUDE</label>
                                <input type="text" class="form-control bg-white border-0 shadow-sm rounded-3 py-1 text-center" id="lat" name="lat" required readonly placeholder="0.000">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-muted">LONGITUDE</label>
                                <input type="text" class="form-control bg-white border-0 shadow-sm rounded-3 py-1 text-center" id="lon" name="lon" required readonly placeholder="0.000">
                            </div>
                        </div>
                        <button type="button" id="btnRefresh" class="btn btn-info btn-sm px-4 rounded-pill-custom shadow-sm" onclick="getLocation()">
                            <i class='bx bx-refresh'></i> Refresh Lokasi
                        </button>
                        <div id="status-lokasi" class="form-text mt-2 small">GPS Standby</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted ms-1">RADIUS TOLERANSI (METER)</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text text-primary"><i class="bx bx-radar"></i></span>
                            <input type="number" class="form-control" name="tolerance" value="50" required>
                        </div>
                        <div class="form-text small text-muted ms-1">Minimal 50 meter disarankan.</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch d-flex align-items-center bg-light p-2 rounded-3 ps-5 border">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                            <label class="form-check-label small fw-bold ms-2" for="isActive">Aktifkan kegiatan sekarang</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow rounded-pill-custom fw-bold py-2" id="btnSubmit" disabled>
                        <i class="bx bx-save me-1"></i> SIMPAN KEGIATAN
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function getLocation() {
        const statusLabel = document.getElementById('status-lokasi');
        const btnSubmit = document.getElementById('btnSubmit');
        const btnRefresh = document.getElementById('btnRefresh');
        const latInput = document.getElementById('lat');
        const lonInput = document.getElementById('lon');

        btnRefresh.disabled = true;
        btnRefresh.innerHTML = "<span class='spinner-border spinner-border-sm'></span>";
        statusLabel.className = "form-text text-warning mt-2 small";

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    latInput.value = position.coords.latitude;
                    lonInput.value = position.coords.longitude;
                    statusLabel.innerHTML = "<i class='bx bx-check-circle'></i> Lokasi Terkunci!";
                    statusLabel.className = "form-text text-success mt-2 fw-bold small";
                    btnRefresh.disabled = false;
                    btnRefresh.innerHTML = "<i class='bx bx-refresh'></i> Refresh Lokasi";
                    btnSubmit.disabled = false;
                },
                (error) => {
                    statusLabel.innerHTML = "Gagal. Aktifkan GPS!";
                    statusLabel.className = "form-text text-danger mt-2 small";
                    btnRefresh.disabled = false;
                    btnRefresh.innerHTML = "Ulangi";
                },
                { enableHighAccuracy: true }
            );
        }
    }
    window.onload = getLocation;
</script>