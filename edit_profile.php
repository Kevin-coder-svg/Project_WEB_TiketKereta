<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: HTML_login.html');
    exit;
}
$conn = new mysqli('localhost', 'root', '', 'tiket kereta');
if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = ""; 

if (isset($_POST['update_profil'])) {
    $email  = htmlspecialchars($_POST['email']);
    $phone  = htmlspecialchars($_POST['phone']);
    $alamat = htmlspecialchars($_POST['alamat']);
    $password_baru = $_POST['password']; 

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert alert-danger'>Format email tidak valid.</div>";
    } else {
        if (!empty($password_baru)) {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET email=?, phone=?, alamat=?, password=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $email, $phone, $alamat, $hashed_password, $user_id);
        } else {
            $sql = "UPDATE users SET email=?, phone=?, alamat=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $email, $phone, $alamat, $user_id);
        }

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Profil berhasil diperbarui!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal: " . $conn->error . "</div>";
        }
        $stmt->close();
    }
}
$sql_select = "SELECT * FROM users WHERE user_id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param('i', $user_id);
$stmt_select->execute();
$result_select = $stmt_select->get_result();

if ($result_select->num_rows === 0) {
    die('User tidak ditemukan.');
}

$user = $result_select->fetch_assoc();
$stmt_select->close();
$conn->close();
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil - KAI Tiket Kereta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        body {
            background-image: url('background.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-color: #f4f6f8;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.9) 0%, rgba(13, 110, 253, 0.2) 100%) !important;
            border-bottom: 3px solid #0d6efd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            padding: 15px 20px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            margin-left: 0 !important;
        }

        .sidebar {
            position: fixed;
            left: -300px;
            top: 0;
            width: 300px;
            height: 100vh;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.95) 0%, rgba(13, 110, 253, 0.1) 100%);
            transition: left 0.3s ease;
            z-index: 2000;
            overflow-y: auto;
            border-right: 2px solid #0d6efd;
            padding-top: 20px;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            color: white;
            font-size: 1.3rem;
            padding: 20px;
            border-bottom: 1px solid #0d6efd;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            border-bottom: 1px solid rgba(13, 110, 253, 0.2);
        }

        .sidebar-menu a {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 1.1rem;
        }

        .sidebar-menu a:hover {
            background-color: #0d6efd;
            padding-left: 30px;
        }

        .sidebar-menu a.active {
            background-color: #0d6efd;
            border-left: 4px solid white;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            margin-right: 15px;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 1999;
        }

        .sidebar-overlay.active {
            display: block;
        }
        .profile-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 20px;
        }

        .profile-header h3 {
            font-weight: bold;
            color: #333;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }

        .info-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-control {
            border: 1px solid rgba(13, 110, 253, 0.3);
            padding: 12px;
            font-size: 1.1rem;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #0d6efd;
        }

        input:disabled {
            background-color: #f1f3f5;
            color: #6c757d;
            cursor: not-allowed;
            border-color: #dee2e6;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-custom {
            padding: 12px 30px;
            font-size: 1rem;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
            display: inline-block;
        }

        .btn-edit {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
        }
        
        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
            color: white;
        }

        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            color: white;
        }

        footer {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            ☰ Menu
        </div>
        <ul class="sidebar-menu">
            <li><a href="home.php">🏠 Home</a></li>
            <li><a href="search.php">📅 Cari Jadwal</a></li>
            <li><a href="history.php">📋 Riwayat Pemesanan</a></li>
            <li><a href="profile.php" class="active">👤 Profil Saya</a></li>
            <li><a href="QnA.php">💭 QnA</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;">🚪 Logout</a></li>
        </ul>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="sidebar-toggle" id="sidebarToggle" type="button">
                ☰
            </button>
            <a class="navbar-brand fw-bold" href="home.php">
                🚂 KAI - Tiket Kereta
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h3>✏️ Edit Informasi Pribadi</h3>
                <p class="text-muted">Perbarui data diri Anda di bawah ini.</p>
            </div>

            <?php echo $message; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="info-label">Nama Lengkap</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
                    <small class="text-muted">Nama tidak dapat diubah.</small>
                </div>

                <div class="form-group">
                    <label class="info-label">NIK (Nomor Induk Kependudukan)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nik']); ?>" disabled>
                    <small class="text-danger fw-bold">* NIK tidak dapat diubah (Identitas Utama).</small>
                </div>

                <div class="form-group">
                    <label class="info-label">Alamat Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="info-label">Nomor Telepon</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" maxlength="20" required>
                </div>

                <div class="form-group">
                    <label class="info-label">Alamat Domisili</label>
                    <textarea name="alamat" class="form-control" rows="3" required><?php echo htmlspecialchars($user['alamat']); ?></textarea>
                </div>

                <div class="form-group mt-4">
                    <label class="info-label">Password Baru (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tidak ingin mengganti password">
                </div>

                <div class="action-buttons">
                    <a href="profile.php" class="btn-custom btn-back">Batal</a>
                    <button type="submit" name="update_profil" class="btn-custom btn-edit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p class="mb-0">&copy; 2025 PT. KAI (Persero). All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    
    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu a');
        sidebarMenuItems.forEach(item => {
            item.addEventListener('click', function () {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        });
    </script>
</body>

</html>