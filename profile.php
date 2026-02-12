<?php
require 'app/config.php';
require 'app/auth.php';

// Proses Update Profil
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $password = $_POST['new_password'];
    $id = $_SESSION['user_id'];

    if (!empty($password)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, password_hash = MD5(?) WHERE id = ?");
        $exec = $stmt->execute([$name, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $exec = $stmt->execute([$name, $id]);
    }

    if ($exec) {
        $_SESSION['name'] = $name; 
        $msg = "<div class='alert alert-success rounded-3 border-0 shadow-sm d-flex align-items-center mb-3' role='alert'><i class='bx bx-check-circle me-2'></i> Profil berhasil diperbarui!</div>";
    } else {
        $msg = "<div class='alert alert-danger rounded-3 border-0 shadow-sm d-flex align-items-center mb-3' role='alert'><i class='bx bx-error-circle me-2'></i> Gagal update profil.</div>";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

include 'includes/header.php';
?>

<style>
    /* Global Softness */
    .card { border-radius: 20px !important; border: none; overflow: hidden; }
    .rounded-pill-custom { border-radius: 50px !important; }
    
    /* Avatar Style */
    .avatar-wrapper {
        padding: 5px;
        background: linear-gradient(45deg, #696cff, #e7e7ff);
        border-radius: 50%;
        display: inline-block;
    }

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
    }
    .input-group-merge .form-control {
        background-color: transparent !important;
        border: none !important;
    }
    .input-group-merge .form-control:focus {
        box-shadow: none !important;
    }

    .divider-text { background-color: #fff !important; padding: 0 15px; }

    /* CSS Khusus Mobile */
    @media (max-width: 767.98px) {
        .profile-header-text { font-size: 1.1rem !important; text-align: center; margin-bottom: 1rem !important; }
        .avatar-img { height: 90px !important; width: 90px !important; }
        .order-mobile-1 { order: 1; } 
        .order-mobile-2 { order: 2; }
    }
</style>

<div class="container-fluid px-2">
    <h4 class="fw-bold py-2 mb-3 profile-header-text">
        <span class="text-muted fw-light">Akun /</span> Pengaturan Profil
    </h4>

    <div class="row">
        <div class="col-md-5 col-lg-4 order-mobile-1 mb-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body pt-5">
                    <div class="avatar-wrapper mb-3 shadow">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=696cff&color=fff&size=128&bold=true" 
                             alt="user-avatar" 
                             class="rounded-circle avatar-img bg-white" 
                             height="100" 
                             width="100"
                             style="border: 3px solid #fff;">
                    </div>
                    
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($user['name']) ?></h5>
                    <p class="text-muted small mb-3">@<?= htmlspecialchars($user['username']) ?></p>
                    <span class="badge bg-label-primary rounded-pill px-3 mb-4"><?= strtoupper($user['role']) ?></span>
                    
                    <div class="py-3 px-3 bg-light rounded-4 d-flex justify-content-center align-items-center text-muted small">
                        <i class='bx bx-calendar-check me-2 text-primary fs-5'></i>
                        <span>Bergabung: <span class="fw-bold text-dark"><?= date('d M Y', strtotime($user['created_at'])) ?></span></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-7 col-lg-8 order-mobile-2">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold"><i class='bx bx-edit-alt me-2 text-primary'></i>Edit Informasi Profil</h5>
                </div>
                <div class="card-body p-4">
                    <?= $msg ?>
                    <form method="POST">
                        <div class="row">
                            <div class="mb-3 col-12">
                                <label class="form-label fw-bold small text-muted ms-1">USERNAME</label>
                                <div class="input-group input-group-merge" style="background-color: #eee;">
                                    <span class="input-group-text"><i class="bx bx-user text-secondary"></i></span>
                                    <input type="text" class="form-control text-secondary" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                    <span class="input-group-text"><i class="bx bx-lock-alt text-secondary small"></i></span>
                                </div>
                                <div class="form-text text-danger ms-1" style="font-size: 0.7rem;">* Username tidak dapat diubah.</div>
                            </div>
                            
                            <div class="mb-4 col-12">
                                <label class="form-label fw-bold small text-muted ms-1">NAMA LENGKAP</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text text-primary"><i class="bx bx-rename"></i></span>
                                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required placeholder="Masukkan Nama Lengkap">
                                </div>
                            </div>

                            <div class="col-12 mb-2">
                                <div class="divider divider-dashed my-4">
                                    <div class="divider-text fw-bold text-warning small">
                                        <i class='bx bx-shield-lock me-1'></i> KEAMANAN AKUN
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4 col-12">
                                <label class="form-label fw-bold small text-muted ms-1">GANTI PASSWORD</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text text-primary"><i class="bx bx-lock-open"></i></span>
                                    <input type="password" class="form-control" name="new_password" placeholder="Isi jika ingin ganti sandi">
                                </div>
                                <div class="form-text text-muted ms-1" style="font-size: 0.75rem;">Abaikan jika tidak ingin mengubah kata sandi saat ini.</div>
                            </div>
                        </div>

                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill-custom shadow fw-bold py-2">
                                <i class='bx bx-save me-1'></i> SIMPAN PERUBAHAN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>