<?php
require 'app/config.php';
require 'app/auth.php';
require 'app/functions.php';

$user_id = $_SESSION['user_id'];
$msg_profile = "";

// --- LOGIC 1: UPDATE PROFIL ---
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $pass = $_POST['password'];

    if (!empty($pass)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, password_hash = MD5(?) WHERE id = ?");
        $stmt->execute([$name, $pass, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $user_id]);
    }
    
    $_SESSION['name'] = $name;
    $msg_profile = "<div class='alert alert-success p-2 small auto-dismiss'>Profil berhasil disimpan!</div>";
}

// --- LOGIC 2: AMBIL DATA ANGGOTA ---
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$me = $stmtUser->fetch();

// --- LOGIC 3: AMBIL EVENT AKTIF & CEK WAKTU ---
$stmt = $pdo->query("SELECT * FROM events WHERE is_active = 1 ORDER BY start_time DESC LIMIT 1");
$event = $stmt->fetch();

$statusAbsensi = 'belum'; 
$eventExpired = false;

if ($event) {
    $now = date('Y-m-d H:i:s');
    
    // 1. Cek dulu status absensinya di database
    $stmtIn = $pdo->prepare("SELECT id FROM attendances WHERE user_id = ? AND event_id = ? AND type = 'in'");
    $stmtIn->execute([$user_id, $event['id']]);
    $dataIn = $stmtIn->fetch();

    $stmtOut = $pdo->prepare("SELECT id FROM attendances WHERE user_id = ? AND event_id = ? AND type = 'out'");
    $stmtOut->execute([$user_id, $event['id']]);
    $dataOut = $stmtOut->fetch();

    // 2. Tentukan status berdasarkan record yang ada
    if (!$dataIn) {
        $statusAbsensi = 'siap_masuk';
    } elseif ($dataIn && !$dataOut) {
        $statusAbsensi = 'siap_pulang';
    } else {
        $statusAbsensi = 'selesai';
    }

    // 3. Cek apakah waktu sudah expired (Hanya jika status BELUM 'selesai')
    if ($statusAbsensi !== 'selesai' && $now > $event['end_time']) {
        $eventExpired = true;
    }
}

// --- LOGIC 4: AMBIL RIWAYAT ---
$stmtHistory = $pdo->prepare("
    SELECT a.*, e.title, e.start_time 
    FROM attendances a 
    JOIN events e ON a.event_id = e.id 
    WHERE a.user_id = ? 
    ORDER BY e.start_time DESC, a.created_at ASC
");
$stmtHistory->execute([$user_id]);
$rawHistory = $stmtHistory->fetchAll();

$groupedHistory = [];
foreach ($rawHistory as $row) {
    $evtId = $row['event_id'];
    if (!isset($groupedHistory[$evtId])) {
        $groupedHistory[$evtId] = ['title' => $row['title'], 'date' => $row['start_time'], 'in' => '-', 'out' => '-'];
    }
    if ($row['type'] == 'in') $groupedHistory[$evtId]['in'] = date('H:i', strtotime($row['created_at']));
    else $groupedHistory[$evtId]['out'] = date('H:i', strtotime($row['created_at']));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Anggota Kartar - Mobile</title>
    <link rel="stylesheet" href="assets/vendor/css/core.css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />
    <link rel="icon" type="image/x-icon" href="assets/img/logo_kartar2.png" />
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Public Sans', sans-serif; padding-bottom: 20px; }
        
        /* Tab Navigation - Mobile Style */
        .nav-pills { 
            background: #ffffff; 
            border: 1px solid #e7e7ff;
            padding: 5px;
            border-radius: 15px !important;
        }
        .nav-pills .nav-link { 
            padding: 10px 5px; 
            font-size: 0.75rem; 
            font-weight: 600;
            color: #a1acb8;
            border-radius: 12px !important;
        }
        .nav-pills .nav-link i { display: block; font-size: 1.5rem; margin-bottom: 3px; }
        .nav-pills .nav-link.active { 
            background-color: #696cff !important; 
            box-shadow: 0 2px 10px rgba(105, 108, 255, 0.4); 
            color: #fff !important;
        }

        /* Camera UI */
        #camera-container { 
            width: 100%; 
            max-width: 400px;
            margin: 0 auto;
            background: #000; 
            border-radius: 20px; 
            overflow: hidden; 
            display: none; 
            position: relative;
            aspect-ratio: 3/4;
        }
        #video-preview { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        
        .btn-capture { 
            position: absolute; 
            bottom: 20px; 
            left: 50%; 
            transform: translateX(-50%); 
            width: 70px;
            height: 70px;
            border: 5px solid rgba(255,255,255,0.5); 
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 20;
        }
        .btn-capture i { color: #696cff; font-size: 2rem; }

        #img-result { 
            width: 100%; 
            max-width: 300px;
            border-radius: 15px; 
            display: none; 
            margin: 15px auto; 
            border: 4px solid #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Cards & Buttons */
        .card { border-radius: 20px; border: none; overflow: hidden; }
        .btn-lg { border-radius: 15px; padding: 15px; font-weight: bold; font-size: 1rem; }
        .badge { padding: 6px 12px; border-radius: 8px; }
        
        .event-header {
            background: linear-gradient(135deg, #696cff 0%, #3f42af 100%);
            color: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container-xxl container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div class="d-flex align-items-center">
            <img src="assets/img/logo_kartar2.png" alt="Logo" class="me-3" style="width: 50px; height: auto;">
            
            <div>
                <span class="text-muted d-block small">Holaa...</span>
                <h5 class="fw-bold m-0"><?= htmlspecialchars($me['name']) ?></h5>
            </div>
        </div>

        <a href="logout.php" class="btn btn-icon btn-danger rounded-circle shadow-sm">
            <i class="bx bx-power-off"></i>
        </a>
    </div>

    <ul class="nav nav-pills nav-fill mb-4 shadow-sm" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#navs-absen">
                <i class="bx bx-scan"></i>Absen
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-history">
                <i class="bx bx-time-five"></i>Riwayat
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-profile">
                <i class="bx bx-user-circle"></i>Profil
            </button>
        </li>
    </ul>

    <div class="tab-content p-0 bg-transparent shadow-none">
        
        <div class="tab-pane fade show active" id="navs-absen" role="tabpanel">
            <?php if (!$event): ?>
                <div class="card text-center p-5 shadow-sm">
                    <div class="mb-3"><i class="bx bx-info-circle text-muted" style="font-size: 3rem;"></i></div>
                    <h6 class="text-muted">Tidak ada kegiatan aktif saat ini.</h6>
                </div>
            <?php elseif ($eventExpired): ?>
                <div class="card bg-label-danger text-center p-5 shadow-sm">
                    <h5 class="text-danger fw-bold">Waktu Absensi Berakhir</h5>
                    <p class="mb-0 small">Kegiatan ini sudah melewati batas waktu.</p>
                </div>
            <?php elseif ($statusAbsensi == 'selesai'): ?>
                <div class="card bg-label-success text-center p-5 shadow-sm">
                    <div class="mb-3"><i class="bx bxs-check-circle text-success" style="font-size: 4rem;"></i></div>
                    <h5 class="text-success fw-bold">Absensi Selesai!</h5>
                    <p class="text-muted mb-0">Terima kasih telah berpartisipasi.</p>
                </div>
            <?php else: ?>
                
                <div class="event-header shadow">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-lg"><span class="avatar-initial rounded-circle bg-white text-primary shadow"><i class="bx bx-calendar-event"></i></span></div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <h5 class="mb-0 fw-bold text-white text-truncate"><?= htmlspecialchars($event['title']) ?></h5>
                            <small class="opacity-75"><?= date('H:i', strtotime($event['start_time'])) ?> - <?= date('H:i', strtotime($event['end_time'])) ?> WIB</small>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body p-4">
                        <form action="process_attendance.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <input type="hidden" id="lat" name="latitude">
                            <input type="hidden" id="lon" name="longitude">
                            <input type="hidden" name="type" value="<?= ($statusAbsensi == 'siap_masuk') ? 'in' : 'out' ?>">
                            <input type="file" name="selfie" id="real-file-input" style="display: none;">

                            <div class="text-center mb-4">
                                <div id="camera-container">
                                    <video id="video-preview" autoplay playsinline></video>
                                    <button type="button" class="btn-capture" onclick="takePicture()">
                                        <i class="bx bx-camera"></i>
                                    </button>
                                </div>
                                <canvas id="canvas-result" style="display:none;"></canvas>
                                <img id="img-result">
                                
                                <button type="button" id="btnStartCamera" class="btn btn-outline-primary btn-md w-100 rounded-pill mt-2" onclick="startCamera()">
                                    <i class='bx bx-camera me-1'></i> <?= ($statusAbsensi == 'siap_masuk') ? 'Ambil Selfie Masuk' : 'Ambil Selfie Pulang' ?>
                                </button>
                            </div>

                            <div class="bg-light rounded-3 p-3 mb-4 text-center border">
                                <div id="status-lokasi" class="small fw-bold text-warning mb-2">
                                    <span class='spinner-border spinner-border-sm'></span> Menghubungkan GPS...
                                </div>
                                <button type="button" id="btnRefresh" class="btn btn-white btn-sm border shadow-sm rounded-pill px-3" onclick="getLocation()">
                                    <i class="bx bx-refresh"></i> Refresh Lokasi
                                </button>
                            </div>

                            <button type="submit" id="btnSubmit" class="btn w-100 btn-lg shadow-primary <?= ($statusAbsensi == 'siap_masuk') ? 'btn-primary' : 'btn-warning' ?>" disabled>
                                <i class="bx bx-check-double me-1"></i>
                                <?= ($statusAbsensi == 'siap_masuk') ? 'KIRIM ABSEN MASUK' : 'KIRIM ABSEN PULANG' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="navs-history" role="tabpanel">
            <div class="card shadow-sm p-2">
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Kegiatan</th><th class="text-center">IN</th><th class="text-center">OUT</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($groupedHistory as $h): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 140px;"><?= htmlspecialchars($h['title']) ?></div>
                                    <small class="text-muted"><?= date('d M Y', strtotime($h['date'])) ?></small>
                                </td>
                                <td class="text-center"><span class="badge <?= $h['in'] != '-' ? 'bg-label-primary' : 'bg-label-secondary' ?>"><?= $h['in'] ?></span></td>
                                <td class="text-center"><span class="badge <?= $h['out'] != '-' ? 'bg-label-warning' : 'bg-label-secondary' ?>"><?= $h['out'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($groupedHistory)): ?>
                                <tr><td colspan="3" class="text-center p-4 text-muted small">Belum ada riwayat absensi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="navs-profile" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 text-center">
                    <div class="avatar avatar-xl mx-auto mb-3">
                        <span class="avatar-initial rounded-circle bg-label-primary" style="font-size: 2.5rem;"><i class="bx bx-user"></i></span>
                    </div>
                    <h5 class="fw-bold mb-4">Edit Profil</h5>
                    <?= $msg_profile ?>
                    <form method="POST" class="text-start">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Nama Lengkap</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-user"></i></span>
                                <input type="text" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($me['name']) ?>" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-uppercase">Ganti Password</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                                <input type="password" name="password" class="form-control form-control-lg" placeholder="Isi hanya jika ingin ganti">
                            </div>
                            <div class="form-text small">Kosongkan jika tidak ingin mengubah password.</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script src="assets/vendor/libs/jquery/jquery.js"></script>
<script src="assets/vendor/js/bootstrap.js"></script>

<script>
    const btnSubmit = document.getElementById('btnSubmit');
    const statusLoc = document.getElementById('status-lokasi');
    const btnRefresh = document.getElementById('btnRefresh');
    let hasLocation = false;
    let hasPhoto = false;

    function validateForm() {
        if(hasLocation && hasPhoto) {
            btnSubmit.disabled = false;
        } else {
            btnSubmit.disabled = true;
        }
    }

    // LOKASI LOGIC
    function getLocation() {
        btnRefresh.disabled = true;
        statusLoc.innerHTML = "<span class='spinner-border spinner-border-sm'></span> Menghubungkan GPS...";
        statusLoc.className = "small fw-bold text-warning mb-2";

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.getElementById('lat').value = pos.coords.latitude;
                    document.getElementById('lon').value = pos.coords.longitude;
                    statusLoc.innerHTML = "<i class='bx bxs-check-circle me-1'></i> Lokasi Terdeteksi!";
                    statusLoc.className = "small fw-bold text-success mb-2";
                    hasLocation = true;
                    btnRefresh.disabled = false;
                    validateForm();
                }, 
                (err) => {
                    statusLoc.innerHTML = "<i class='bx bxs-error-circle me-1'></i> Gagal Mendapatkan Lokasi";
                    statusLoc.className = "small fw-bold text-danger mb-2";
                    btnRefresh.disabled = false;
                    hasLocation = false;
                    validateForm();
                    alert("Pastikan GPS Laptop/HP sudah aktif dan izin lokasi diberikan!");
                }, 
                {enableHighAccuracy: true, timeout: 10000}
            );
        }
    }
    window.onload = getLocation;

    // KAMERA LOGIC
    let videoStream = null;
    const video = document.getElementById('video-preview');
    const canvas = document.getElementById('canvas-result');
    const imgResult = document.getElementById('img-result');
    const container = document.getElementById('camera-container');
    const realInput = document.getElementById('real-file-input');
    const btnStartCamera = document.getElementById('btnStartCamera');

    async function startCamera() {
        btnStartCamera.style.display = 'none';
        container.style.display = 'block';
        imgResult.style.display = 'none';
        
        try {
            videoStream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: "user", width: {ideal: 720} } 
            });
            video.srcObject = videoStream;
        } catch (err) { 
            alert("Gagal mengakses kamera. Pastikan izin diberikan!"); 
            btnStartCamera.style.display = 'block';
        }
    }

    function takePicture() {
        canvas.width = video.videoWidth; 
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        
        // Efek mirror untuk selfie
        ctx.translate(canvas.width, 0); 
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0);
        
        // Stop kamera
        if(videoStream) videoStream.getTracks().forEach(t => t.stop());
        
        container.style.display = 'none';
        imgResult.src = canvas.toDataURL('image/jpeg', 0.8);
        imgResult.style.display = 'block';
        btnStartCamera.innerText = "Foto Ulang";
        btnStartCamera.style.display = 'block';

        canvas.toBlob(blob => {
            const file = new File([blob], "selfie.jpg", { type: "image/jpeg" });
            const dt = new DataTransfer(); 
            dt.items.add(file);
            realInput.files = dt.files;
            hasPhoto = true; 
            validateForm();
        }, 'image/jpeg', 0.8);
    }
</script>
</body>
</html>