<?php
require 'app/config.php';
require 'app/auth.php';

// Pastikan hanya member yang akses (Opsional, admin juga boleh lihat punya sendiri)
// if ($_SESSION['role'] !== 'member') { ... }

$user_id = $_SESSION['user_id'];
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');

// 1. Ambil Semua Event di Bulan & Tahun Terpilih
$stmt = $pdo->prepare("SELECT * FROM events WHERE MONTH(start_time) = ? AND YEAR(start_time) = ? ORDER BY start_time DESC");
$stmt->execute([$month, $year]);
$events = $stmt->fetchAll();

// 2. Ambil Absensi Saya di Bulan Tersebut
$stmtAbsen = $pdo->prepare("
    SELECT * FROM attendances 
    WHERE user_id = ? 
    AND MONTH(created_at) = ? 
    AND YEAR(created_at) = ?
");
$stmtAbsen->execute([$user_id, $month, $year]);
$myAttendance = $stmtAbsen->fetchAll();

// Mapping agar mudah dicocokkan (Event ID -> Data Absen)
$logAbsen = [];
foreach($myAttendance as $row) {
    // Kita simpan berdasarkan event_id dan type (in/out)
    $logAbsen[$row['event_id']][$row['type']] = $row;
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h5 class="mb-0">Riwayat Absensi Saya</h5>
        
        <form method="GET" class="d-flex gap-2 mt-2 mt-md-0">
            <select name="month" class="form-select form-select-sm">
                <?php for($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($m==$month)?'selected':'' ?>>
                        <?= date('F', mktime(0,0,0,$m,1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-select form-select-sm">
                <?php for($y=2024; $y<=date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= ($y==$year)?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        </form>
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Kegiatan</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($events) > 0): ?>
                    <?php foreach($events as $ev): 
                        // Cek Data Absen Masuk & Pulang
                        $dataMasuk  = isset($logAbsen[$ev['id']]['in']) ? $logAbsen[$ev['id']]['in'] : null;
                        $dataPulang = isset($logAbsen[$ev['id']]['out']) ? $logAbsen[$ev['id']]['out'] : null;
                        
                        // Logika Status
                        $statusBadge = '';
                        if ($dataMasuk) {
                            $statusBadge = '<span class="badge bg-success">Hadir</span>';
                        } else {
                            // Jika event sudah lewat tapi tidak ada data absen -> Alpha
                            if (date('Y-m-d H:i:s') > $ev['end_time']) {
                                $statusBadge = '<span class="badge bg-danger">Alpha (Tidak Hadir)</span>';
                            } else {
                                $statusBadge = '<span class="badge bg-secondary">Belum Mulai</span>';
                            }
                        }
                    ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($ev['start_time'])) ?></td>
                        <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                        
                        <td>
                            <?php if($dataMasuk): ?>
                                <?= date('H:i', strtotime($dataMasuk['created_at'])) ?>
                                <br><small class="text-muted"><?= number_format($dataMasuk['distance_m'],0) ?>m</small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if($dataPulang): ?>
                                <?= date('H:i', strtotime($dataPulang['created_at'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td><?= $statusBadge ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Tidak ada kegiatan bulan ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>