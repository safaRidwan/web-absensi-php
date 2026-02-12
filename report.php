<?php
require 'app/config.php';
require 'app/auth.php';

// Proteksi: Hanya Admin & Pengurus yang boleh akses
if (!in_array($_SESSION['role'], ['admin', 'pengurus'])) {
    die("Akses ditolak");
}

// 1. Tangkap Input Filter
$day   = isset($_GET['day']) ? $_GET['day'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');

// 2. Query Event
$sqlEvent = "SELECT * FROM events WHERE MONTH(start_time) = ? AND YEAR(start_time) = ?";
$paramsEvent = [$month, $year];
if (!empty($day)) {
    $sqlEvent .= " AND DAY(start_time) = ?";
    $paramsEvent[] = $day;
}
$sqlEvent .= " ORDER BY start_time ASC";
$stmt = $pdo->prepare($sqlEvent);
$stmt->execute($paramsEvent);
$events = $stmt->fetchAll();

// 3. Ambil Semua Member
$members = $pdo->query("SELECT * FROM users WHERE role = 'member' ORDER BY name ASC")->fetchAll();

// 4. Ambil Data Absensi
$sqlAbsen = "
    SELECT a.user_id, a.event_id, a.type, a.created_at, a.latitude, a.longitude, a.selfie_path 
    FROM attendances a 
    JOIN events e ON a.event_id = e.id 
    WHERE MONTH(e.start_time) = ? AND YEAR(e.start_time) = ?
";
$paramsAbsen = [$month, $year];
if (!empty($day)) {
    $sqlAbsen .= " AND DAY(e.start_time) = ?";
    $paramsAbsen[] = $day;
}
$stmtAbsen = $pdo->prepare($sqlAbsen);
$stmtAbsen->execute($paramsAbsen);
$rawAbsensi = $stmtAbsen->fetchAll();

$attendanceMap = [];
foreach ($rawAbsensi as $row) {
    $attendanceMap[$row['user_id']][$row['event_id']][$row['type']] = [
        'time' => date('H:i', strtotime($row['created_at'])),
        'latitude' => $row['latitude'],
        'longitude' => $row['longitude'],
        'selfie_path' => $row['selfie_path']
    ];
}

include 'includes/header.php';
?>

<style>
    /* Card & General Softness */
    .card { border-radius: 15px !important; border: none; overflow: hidden; }
    .form-select-sm, .btn-sm { border-radius: 8px !important; }
    .report-filter { border-radius: 12px !important; border: 1px solid #eef0f2; }

    /* Table Styling */
    .table thead th { background-color: #f8f9fa; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.5px; vertical-align: middle; }
    .sticky-col { position: sticky; left: 0; background-color: #ffffff !important; z-index: 2; border-right: 2px solid #f1f1f1 !important; box-shadow: 2px 0 5px rgba(0,0,0,0.02); }
    .user-name-cell { min-width: 130px; white-space: normal; line-height: 1.3; }
    
    /* Attendance Mini Card */
    .absen-box { padding: 4px; border-radius: 6px; transition: all 0.2s; cursor: pointer; border: 1px solid transparent; }
    .absen-box:hover { background-color: #f0f1ff; border-color: #d1d5ff; }
    .event-title-wrap { display: block; font-size: 0.75rem; font-weight: 800; white-space: normal; line-height: 1.2; min-width: 100px; color: #333; }

    /* Modal Styling */
    .modal-content { border-radius: 20px !important; border: none; }
    .btn-maps { border-radius: 10px !important; font-weight: 600; }

    @media (max-width: 767.98px) {
        .report-filter { flex-direction: column; }
        .report-filter .col-auto { width: 100%; }
        .user-name-cell { min-width: 100px !important; font-size: 0.75rem !important; }
    }
</style>

<div class="card shadow-sm mb-4">
    <div class="card-header p-3 border-0">
        <h5 class="mb-3 fw-bold text-primary"><i class="bx bx-check-double me-1"></i> Verifikasi Absensi</h5>
        
        <form method="GET" class="row g-2 align-items-center bg-light p-3 report-filter mx-0 shadow-sm">
            <div class="col-auto">
                <select name="day" class="form-select form-select-sm border-0 shadow-sm">
                    <option value="">-- Tgl --</option>
                    <?php for($d=1; $d<=31; $d++): ?>
                        <option value="<?= $d ?>" <?= ($day == $d) ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="month" class="form-select form-select-sm border-0 shadow-sm">
                    <?php 
                    $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    foreach($months as $mNum => $mName): ?>
                        <option value="<?= $mNum ?>" <?= ($mNum == $month) ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="year" class="form-select form-select-sm border-0 shadow-sm">
                    <?php for($y=2024; $y<=date('Y')+1; $y++): ?>
                        <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto ms-md-auto">
                <button type="submit" class="btn btn-sm btn-primary w-100 px-4 fw-bold shadow-sm rounded-pill">
                    <i class="bx bx-search-alt"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>

    <div class="table-responsive p-0">
        <table class="table table-hover table-sm text-center align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th rowspan="2" class="sticky-col user-name-cell">Nama Anggota</th>
                    <?php foreach($events as $ev): ?>
                        <th class="p-2 border-bottom">
                            <span class="event-title-wrap text-primary"><?= htmlspecialchars($ev['title']) ?></span>
                            <span class="badge bg-label-secondary rounded-pill mt-1" style="font-size: 0.6rem;">
                                <?= date('d M', strtotime($ev['start_time'])) ?>
                            </span>
                        </th>
                    <?php endforeach; ?>
                    <th rowspan="2" class="bg-label-success text-success fw-bold px-3">Hadir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($members as $member): 
                    $totalH = 0;
                ?>
                <tr>
                    <td class="text-start fw-bold sticky-col user-name-cell bg-white small">
                        <?= htmlspecialchars($member['name']) ?>
                    </td>
                    <?php foreach($events as $ev): ?>
                        <td class="p-1">
                            <?php 
                                $dataUser = isset($attendanceMap[$member['id']][$ev['id']]) ? $attendanceMap[$member['id']][$ev['id']] : null;
                                
                                if (isset($dataUser['in'])): 
                                    $totalH++;
                                    $infoIn = $dataUser['in'];
                                    $infoOut = isset($dataUser['out']) ? $dataUser['out'] : null;
                                    $nameJS  = addslashes($member['name']);
                            ?>
                                <div class="absen-box" onclick="showDetail('<?= $nameJS ?>', '<?= $infoIn['time'] ?>', '<?= !empty($infoIn['selfie_path']) ? $infoIn['selfie_path'] : 'no-selfie.jpg' ?>', '<?= $infoIn['latitude'] ?>', '<?= $infoIn['longitude'] ?>', '<?= $infoOut ? $infoOut['time'] : '--:--' ?>', '<?= $infoOut ? $infoOut['selfie_path'] : '' ?>', '<?= $infoOut ? $infoOut['latitude'] : '' ?>', '<?= $infoOut ? $infoOut['longitude'] : '' ?>')">
                                    <div class="text-success fw-bold" style="font-size: 0.7rem;">
                                        <i class="bx bx-log-in-circle"></i> <?= $infoIn['time'] ?>
                                    </div>
                                    <div class="<?= $infoOut ? 'text-primary' : 'text-muted' ?>" style="font-size: 0.7rem;">
                                        <i class="bx bx-log-out-circle"></i> <?= $infoOut ? $infoOut['time'] : '--:--' ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-light">-</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="fw-bold text-dark"><?= $totalH ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-primary">Bukti Kehadiran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-center fw-bold mb-3" id="detUser"></p>
                
                <div class="row g-2">
                    <div class="col-6">
                        <div class="bg-light p-2 rounded-3 text-center border">
                            <small class="fw-bold text-success d-block mb-1">ABSEN MASUK</small>
                            <img id="imgIn" src="" class="img-fluid rounded shadow-sm mb-2" style="height: 120px; width: 100%; object-fit: cover;">
                            <div class="small fw-bold mb-2" id="timeIn"></div>
                            <a id="mapIn" href="" target="_blank" class="btn btn-xxs btn-outline-success w-100 rounded-pill" style="font-size: 0.65rem;">
                                <i class="bx bx-map"></i> Lokasi
                            </a>
                        </div>
                    </div>
                    <div class="col-6">
                        <div id="boxOut" class="bg-light p-2 rounded-3 text-center border">
                            <small class="fw-bold text-primary d-block mb-1">ABSEN PULANG</small>
                            <img id="imgOut" src="" class="img-fluid rounded shadow-sm mb-2" style="height: 120px; width: 100%; object-fit: cover;">
                            <div class="small fw-bold mb-2" id="timeOut"></div>
                            <a id="mapOut" href="" target="_blank" class="btn btn-xxs btn-outline-primary w-100 rounded-pill" style="font-size: 0.65rem;">
                                <i class="bx bx-map"></i> Lokasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetail(name, tIn, imgIn, latIn, lonIn, tOut, imgOut, latOut, lonOut) {
    document.getElementById('detUser').innerText = name;
    
    // Set In
    document.getElementById('timeIn').innerText = tIn;
    document.getElementById('imgIn').src = "uploads/" + imgIn;
    document.getElementById('mapIn').href = "https://www.google.com/maps?q=" + latIn + "," + lonIn;

    // Set Out
    if(tOut !== '--:--') {
        document.getElementById('boxOut').style.opacity = "1";
        document.getElementById('timeOut').innerText = tOut;
        document.getElementById('imgOut').src = "uploads/" + (imgOut ? imgOut : 'no-selfie.jpg');
        document.getElementById('mapOut').href = "https://www.google.com/maps?q=" + latOut + "," + lonOut;
        document.getElementById('mapOut').style.display = "block";
    } else {
        document.getElementById('boxOut').style.opacity = "0.4";
        document.getElementById('timeOut').innerText = "Belum Absen";
        document.getElementById('imgOut').src = "assets/img/no-selfie.jpg";
        document.getElementById('mapOut').style.display = "none";
    }
    
    new bootstrap.Modal(document.getElementById('modalDetail')).show();
}
</script>

<?php include 'includes/footer.php'; ?>