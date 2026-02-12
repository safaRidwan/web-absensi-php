<?php
require 'app/config.php';
require 'app/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF Token Invalid");
    }

    $username = preg_replace("/[^a-zA-Z0-9_]/", "", $_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

        // Bagian proses login setelah verifikasi password berhasil
        if ($user && md5($password) === $user['password_hash']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            // Redirect ke Admin Panel jika dia Admin atau Pengurus
            if (in_array($user['role'], ['admin', 'pengurus'])) {
                header("Location: index.php");
            } else {
                header("Location: member.php");
            }
            exit;
    } else {
        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login - Karang Taruna</title>
    
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="assets/vendor/css/core.css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" />
    <link rel="icon" type="image/x-icon" href="assets/img/logo_kartar2.jpg" />

    <style>
        body { background: #f5f5f9; }
        .container-login { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card-login { width: 100%; max-width: 400px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); border-radius: 12px; }
        .app-brand { display: flex; justify-content: center; margin-bottom: 20px; }
        @media (max-width: 576px) { .card-login { border: none; background: transparent; box-shadow: none; } }
    </style>
</head>
<body>
    <div class="container-login">
        <div class="card card-login">
            <div class="card-body">
                <div class="app-brand">
                    <img src="assets/img/logo_kartar.png" alt="Logo" style="width: 80px; height: auto;">
                </div>

                <h4 class="mb-2 fw-bold text-center">Selamat Datang 👋</h4>
                <p class="mb-4 text-center text-muted small">Silahkan login sistem absensi <br><strong>Karang Taruna Manunggal Putra</strong></p>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger p-2 small auto-dismiss" role="alert">
                        <i class='bx bx-error-circle me-1'></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'logout'): ?>
                    <div class="alert alert-success p-2 small auto-dismiss" role="alert">
                        <i class='bx bx-check-circle me-1'></i> Anda berhasil logout.
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-user"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="············" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-4">
                        <button class="btn btn-primary d-grid w-100 btn-lg shadow" type="submit">LOGIN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>

    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $(".auto-dismiss").fadeTo(500, 0).slideUp(500, function(){
                    $(this).remove(); 
                });
            }, 3000);
        });
    </script>
    <script>
    // Menghapus parameter pesan dari URL tanpa refresh
    if (window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.delete('msg'); // hapus parameter msg
        window.history.replaceState({path: url.href}, '', url.href);
    }
</script>
</body>
</html>