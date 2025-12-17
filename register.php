<?php
session_start();
require 'db_config.php'; 

$errors = [];
$success = false;
$success_name = '';

$full_name = '';
$nip = '';
$email = '';
$phone = '';
$alamat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $full_name = trim($_POST['full_name'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $password_input = $_POST['password'] ?? '';

    if (strlen($full_name) < 2) $errors[] = 'Nama minimal 2 karakter.';
    if (!ctype_digit($nip) || strlen($nip) < 6 || strlen($nip) > 20) $errors[] = 'NIP harus angka (6-20 digit).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!ctype_digit($phone) || strlen($phone) < 8 || strlen($phone) > 15) $errors[] = 'No. Telepon harus angka (8-15 digit).';
    if (strlen($alamat) < 5) $errors[] = 'Alamat terlalu pendek (min 5 karakter).';
    if (strlen($password_input) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE nik = ? LIMIT 1");
        $stmt->bind_param('s', $nip);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = 'NIP sudah terdaftar.';
        $stmt->close();

        $stmt2 = $conn->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
        $stmt2->bind_param('s', $email);
        $stmt2->execute();
        if ($stmt2->get_result()->num_rows > 0) $errors[] = 'Email sudah terdaftar.';
        $stmt2->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password_input, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (full_name, password, nik, email, phone, alamat, role, created_at) VALUES (?, ?, ?, ?, ?, ?, 'user', NOW())";
        
        $stmtInsert = $conn->prepare($sql);
        if ($stmtInsert) {
            $stmtInsert->bind_param('ssssss', $full_name, $hashed_password, $nip, $email, $phone, $alamat);
            if ($stmtInsert->execute()) {
                $success = true;
                $success_name = $full_name;
            } else {
                $errors[] = "Gagal menyimpan data: " . $stmtInsert->error;
            }
            $stmtInsert->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}
$conn->close();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - KAI Tiket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body {
        background-image: url('background.jpg');
        background-size: cover;
        background-attachment: fixed;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
      }
      .card-register { max-width: 520px; width: 100%; }
      .bg-glass { background: rgba(255, 255, 255, 0.95) !important; }
    </style>
  </head>
  <body>
    
    <div class="container card-register">
      <div class="card shadow-lg bg-glass">
        <div class="card-header text-center bg-primary text-white">
          <h3 class="mb-0">Daftar Akun KAI</h3>
        </div>
        <div class="card-body p-4">

          <?php if ($success): ?>
            <div class="text-center py-4">
                <div class="mb-3">
                    <span style="font-size: 4rem;">✅</span>
                </div>
                <h3 class="text-success">Registrasi Berhasil!</h3>
                <p class="lead">Halo, <strong><?= htmlspecialchars($success_name) ?></strong>.</p>
                <p class="text-muted">Akun Anda telah dibuat. Mengalihkan ke halaman login...</p>
                <a href="login.php" class="btn btn-primary w-100 mt-3">Masuk Sekarang (Manual)</a>
                
                <script>
                    setTimeout(() => { window.location.href = 'login.php'; }, 3000);
                </script>
            </div>

          <?php else: ?>
            <p class="text-muted text-center">Buat akun untuk memesan tiket kereta.</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="" novalidate>
                <div class="mb-3">
                  <label class="form-label">Nama Lengkap</label>
                  <input name="full_name" type="text" class="form-control" placeholder="Nama lengkap" value="<?= htmlspecialchars($full_name) ?>" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">NIK / Nomor Identitas</label>
                  <input name="nip" type="text" class="form-control" placeholder="Angka (Min. 6 digit)" value="<?= htmlspecialchars($nip) ?>" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input name="email" type="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">No. Telepon</label>
                  <input name="phone" type="text" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($phone) ?>" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Alamat</label>
                  <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($alamat) ?></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label">Password</label>
                  <input name="password" type="password" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn btn-primary btn-lg">Daftar Sekarang</button>
                </div>
            </form>
          <?php endif; ?>

        </div>
        <?php if (!$success): ?>
        <div class="card-footer text-center bg-light">
          <small class="text-muted">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold">Masuk di sini</a></small>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>