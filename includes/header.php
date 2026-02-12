<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Sistem Absensi</title>
    
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="icon" type="image/x-icon" href="assets/img/logo_kartar2.png" />

    <style>
        /* Modern Glassmorphism Navbar */
        .navbar-detached {
            box-shadow: 0 8px 25px rgba(105, 108, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            border-radius: 15px !important;
            background-color: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            margin-top: 15px !important;
        }

        /* Avatar Styling */
        .avatar-online img {
            border: 2px solid #696cff !important; /* Biru modern */
            padding: 2px;
            background: #fff;
            transition: all 0.3s ease-in-out;
        }

        .avatar-online:hover img {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.3);
        }

        /* Status Badges */
        .status-badge-admin {
            background: linear-gradient(45deg, #696cff, #8592ff);
            color: white !important;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .status-badge-user {
            background: linear-gradient(45deg, #71dd37, #93f94a);
            color: white !important;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        /* Responsive Fixes */
        @media (max-width: 767.98px) {
            .navbar-detached {
                margin: 0.5rem 0.8rem !important;
                padding: 0.4rem 0.6rem !important;
            }
            .user-info-text { display: none; }
        }

        .text-dark-custom { color: #32475c !important; font-weight: 700; }
        .text-role-custom { color: #a1acb8 !important; font-weight: 500; }
    </style>

    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/js/config.js"></script>
</head>

<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="layout-page">
            <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center" id="layout-navbar">
                
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                        <i class="bx bx-menu bx-sm text-secondary"></i>
                    </a>
                </div>

                <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                    <div class="navbar-nav align-items-center">
                        <div class="nav-item d-flex align-items-center">
                            <?php if (in_array($_SESSION['role'], ['admin', 'pengurus'])): ?>
                                <span class="d-flex align-items-center">Holaa... <?= htmlspecialchars($_SESSION['name']) ?>!</span>
                                
                            <?php else: ?>
                                <span class="status-badge-user shadow-sm">
                                    <i class="bx bxs-user-circle me-1"></i> PENGURUS
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <ul class="navbar-nav flex-row align-items-center ms-auto">
                        <li class="nav-item me-3 d-none d-md-block text-end user-info-text">
                            <span class="text-dark-custom d-block lh-1 mb-1" style="font-size: 0.9rem;">
                                <?= htmlspecialchars($_SESSION['name']) ?>
                            </span>
                            <small class="text-role-custom text-uppercase" style="font-size: 0.7rem;">
                                <?= $_SESSION['role'] ?>
                            </small>
                        </li>

                        <li class="nav-item navbar-dropdown dropdown-user dropdown">
                            <a class="nav-link p-0" href="profile.php">
                                <div class="avatar avatar-online">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=696cff&color=fff&bold=true&format=svg" alt="User" class="rounded-circle" />
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y px-3 px-sm-4">