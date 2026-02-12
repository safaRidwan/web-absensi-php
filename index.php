<?php
require 'app/config.php';
require 'app/auth.php';

// Proteksi Admin
if (!in_array($_SESSION['role'], ['admin', 'pengurus'])) {
    die("Akses ditolak.");
}

// --- 1. LOGIC STATISTIK ---
$totalMembers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();
$ongoingEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE is_active=1 AND NOW() BETWEEN start_time AND end_time")->fetchColumn();
$finishedEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE NOW() > end_time")->fetchColumn();
$todayAbsence = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM attendances WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// --- 2. LOGIC ABSENSI PADA KEGIATAN TERAKHIR ---
$lastEvent = $pdo->query("SELECT id, title FROM events ORDER BY id DESC LIMIT 1")->fetch();
$lastEventId = $lastEvent ? $lastEvent['id'] : 0;
$lastEventTitle = $lastEvent ? $lastEvent['title'] : "Tidak ada kegiatan";

$dashboardLog = [];
if ($lastEventId > 0) {
    $sqlLog = "SELECT a.*, u.name FROM attendances a JOIN users u ON a.user_id = u.id WHERE a.event_id = ? ORDER BY a.created_at DESC";
    $stmtLog = $pdo->prepare($sqlLog);
    $stmtLog->execute([$lastEventId]);
    foreach ($stmtLog->fetchAll() as $row) {
        $key = $row['user_id'];
        if (!isset($dashboardLog[$key])) {
            $dashboardLog[$key] = ['name' => $row['name'], 'in_time' => '-', 'out_time' => '-'];
        }
        if ($row['type'] == 'in') $dashboardLog[$key]['in_time'] = date('H:i', strtotime($row['created_at']));
        elseif ($row['type'] == 'out') $dashboardLog[$key]['out_time'] = date('H:i', strtotime($row['created_at']));
    }
}

include 'includes/header.php';
?>

<style>
    /* Optimasi Global Mobile */
    body { background-color: #f5f5f9; }
    .card { border-radius: 12px; border: none; box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12); }
    
    /* Statistik Card */
    .stat-value { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

    /* Table Mobile Fixes */
    .table > :not(caption) > * > * { padding: 0.75rem 0.5rem; }
    .sticky-col { 
        position: sticky; 
        left: 0; 
        background-color: #fff !important; 
        z-index: 5; 
        box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1);
    }
    
    .text-truncate-mobile {
        max-width: 120px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
        vertical-align: middle;
    }

    @media (max-width: 576px) {
        .container-xxl { padding-left: 10px; padding-right: 10px; }
        .stat-value { font-size: 1.1rem; }
        .btn-sm-mobile { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .badge { font-size: 0.7rem; padding: 0.4em 0.6em; }
    }
</style>

<div class="container-xxl">
    <div class="row g-2 mb-4">
        <div class="col-3">
            <div class="card h-100 bg-white">
                <div class="card-body p-2 text-center">
                    <div class="stat-label text-muted mb-1">User</div>
                    <div class="stat-value"><?= $totalMembers ?></div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card h-100 bg-label-success">
                <div class="card-body p-2 text-center">
                    <div class="stat-label text-success mb-1">Aktif</div>
                    <div class="stat-value text-success"><?= $ongoingEvents ?></div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card h-100 bg-white">
                <div class="card-body p-2 text-center">
                    <div class="stat-label text-muted mb-1">Done</div>
                    <div class="stat-value"><?= $finishedEvents ?></div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card h-100 bg-label-primary">
                <div class="card-body p-2 text-center">
                    <div class="stat-label text-primary mb-1">Today</div>
                    <div class="stat-value text-primary"><?= $todayAbsence ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 overflow-hidden">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3 px-3">
            <h6 class="mb-0 fw-bold"><i class="bx bx-list-ul me-1"></i> Daftar Kegiatan</h6>
            <a href="event_create.php" class="btn btn-primary btn-sm rounded-pill"><i class="bx bx-plus"></i></a>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="sticky-col" style="width: 50%;">Nama</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $events = $pdo->query("SELECT * FROM events ORDER BY id DESC LIMIT 5")->fetchAll();
                    foreach($events as $ev): 
                        $now = date('Y-m-d H:i:s');
                    ?>
                    <tr>
                        <td class="sticky-col">
                            <span class="fw-bold text-dark text-truncate-mobile"><?= htmlspecialchars($ev['title']) ?></span>
                            <div class="text-muted" style="font-size: 0.7rem;"><?= date('d M, H:i', strtotime($ev['start_time'])) ?></div>
                        </td>
                        <td class="text-center">
                            <?php 
                                if($ev['is_active'] == 0) echo '<span class="badge bg-label-secondary">Off</span>';
                                elseif($now >= $ev['start_time'] && $now <= $ev['end_time']) echo '<span class="badge bg-label-success">Aktif</span>';
                                elseif($now < $ev['start_time']) echo '<span class="badge bg-label-warning">Nanti</span>';
                                else echo '<span class="badge bg-label-dark">Done</span>';
                            ?>
                        </td>
                        <td class="text-center">
                            <a href="event_edit.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-icon btn-label-warning rounded-circle">
                                <i class="bx bx-edit-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-3">
            <h6 class="mb-0 fw-bold text-truncate"><i class="bx bx-user-check me-1"></i> Log: <?= htmlspecialchars($lastEventTitle) ?></h6>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th class="sticky-col text-start">Anggota</th>
                        <th>In</th>
                        <th>Out</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dashboardLog as $log): ?>
                    <tr class="text-center">
                        <td class="sticky-col text-start fw-semibold">
                            <span class="text-truncate-mobile small"><?= htmlspecialchars($log['name']) ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $log['in_time'] != '-' ? 'bg-label-primary' : 'bg-transparent text-light' ?> border-0">
                                <?= $log['in_time'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $log['out_time'] != '-' ? 'bg-label-warning' : 'bg-transparent text-light' ?> border-0">
                                <?= $log['out_time'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($dashboardLog)): ?>
                        <tr><td colspan="3" class="text-center py-4 text-muted small">Tidak ada aktivitas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>