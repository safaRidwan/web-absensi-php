<?php
// Mendapatkan nama file yang sedang dibuka
$page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? ''; 

?>

<style>
    /* Custom style untuk efek "Empuk" */
    #layout-menu {
        border-right: none !important;
        box-shadow: 0 0 15px rgba(0,0,0,0.05); 
    }
    .menu-item {
        margin: 2px 15px; 
    }
    .menu-link {
        border-radius: 12px !important; 
        transition: all 0.3s ease-in-out !important;
        padding-top: 10px !important;
        padding-bottom: 10px !important;
    }
    .menu-item.active > .menu-link {
        box-shadow: 0 4px 12px rgba(105, 108, 255, 0.2); 
    }
    .menu-header {
        margin-top: 1.5rem !important;
        padding-left: 25px !important;
    }
    .menu-header-text {
        font-weight: 700 !important;
        letter-spacing: 1px;
        opacity: 0.6;
    }
    .menu-item:hover .menu-icon {
        transform: scale(1.1);
        transition: 0.2s;
    }
</style>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    
    <div class="app-brand demo" style="height: auto; padding-top: 25px; padding-bottom: 20px; padding-left: 20px;">
        <a href="<?= ($role === 'admin') ? 'index.php' : 'member.php' ?>" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="assets/img/logo_kartar2.png" alt="Logo" style="width: 42px; height: auto; border-radius: 8px;">
            </span>
            
            <div class="d-flex flex-column ms-3">
                <span class="app-brand-text demo menu-text fw-bolder" style="text-transform: uppercase; font-size: 1.1rem; line-height: 1.1;">
                    KARTAR
                </span>
                <span class="app-brand-text demo menu-text fw-bold text-secondary" style="text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.8px; margin-top: 2px;">
                    Manunggal Putra
                </span>
            </div>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-2">

        <?php if ($role === 'admin' || $role === 'pengurus'): ?>
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Administrator</span>
            </li>

            <li class="menu-item <?= ($page == 'index.php') ? 'active' : '' ?>">
                <a href="index.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Dashboard</div>
                </a>
            </li>

            <li class="menu-item <?= ($page == 'member.php') ? 'active' : '' ?>">
                <a href="member.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-scan"></i>
                    <div>Absensi</div>
                </a>
            </li>

            <li class="menu-item <?= ($page == 'members.php') ? 'active' : '' ?>">
                <a href="members.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-group"></i>
                    <div>Data Anggota</div>
                </a>
            </li>

            <li class="menu-item <?= ($page == 'event_create.php') ? 'active' : '' ?>">
                <a href="event_create.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar-plus"></i>
                    <div>Buat Kegiatan</div>
                </a>
            </li>

            <li class="menu-item <?= ($page == 'report.php') ? 'active' : '' ?>">
                <a href="report.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-file"></i>
                    <div>Laporan Bulanan</div>
                </a>
            </li>
        <?php endif; ?>


        <?php if ($role === 'member'): ?>
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Menu Anggota</span>
            </li>

            <li class="menu-item <?= ($page == 'member.php') ? 'active' : '' ?>">
                <a href="member.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-scan"></i>
                    <div>Absensi (Scan)</div>
                </a>
            </li>

            <li class="menu-item <?= ($page == 'my_report.php') ? 'active' : '' ?>">
                <a href="my_report.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-history"></i>
                    <div>Riwayat Saya</div>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Aksi</span>
        </li>
        
        <li class="menu-item">
            <a href="logout.php" class="menu-link text-danger" style="background: rgba(255, 62, 29, 0.05); margin-top: 10px;">
                <i class="menu-icon tf-icons bx bx-power-off"></i>
                <div>Logout</div>
            </a>
        </li>

    </ul>
</aside>