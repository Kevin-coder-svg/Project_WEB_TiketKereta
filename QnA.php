<?php
session_start();
require 'db_config.php'; 


if (!isset($_SESSION['user_id'])) {
    die('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
         <div class="container mt-5"><div class="alert alert-danger">Error: Anda belum login. Silakan <a href="login.php" class="alert-link">login</a> terlebih dahulu.</div></div>');
}

$user_id = $_SESSION['user_id'];
$message = "";


if (isset($_POST['submit_question'])) {
    $question = trim($_POST['question']);

    if (!empty($question)) {
        $sql = "INSERT INTO qna (user_id, question, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $question);
        
        if ($stmt->execute()) {
            $message = '
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Berhasil!</strong> Pertanyaanmu telah dikirim. Admin akan segera menjawab.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal mengirim pertanyaan. Silakan coba lagi.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Pertanyaan tidak boleh kosong.</div>';
    }
}


$sql_history = "SELECT * FROM qna WHERE user_id = ? ORDER BY created_at DESC";
$stmt_hist = $conn->prepare($sql_history);
$stmt_hist->bind_param("i", $user_id);
$stmt_hist->execute();
$result = $stmt_hist->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Tanya Jawab - KAI</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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
            color: white !important;
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

        
        .main-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .header-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 20px;
        }

       
        .card-question { 
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03); 
            margin-bottom: 20px; 
            border-radius: 8px;
            transition: transform 0.2s;
        }

        .card-question:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .admin-reply { 
            background-color: #f1f8e9; 
            border-left: 4px solid #198754; 
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
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
            <li><a href="profile.php">👤 Profil Saya</a></li>
            <li><a href="QnA.php" class="active">💭 QnA</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;">🚪 Logout</a></li>
        </ul>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="sidebar-toggle" id="sidebarToggle" type="button">
                ☰
            </button>
            <a class="navbar-brand fw-bold" href="home.php">
                🚂 KAI - Help Center
            </a>
        </div>
    </nav>

    <div class="container">
        
        <div class="main-container">
            <h2 class="header-title"><i class="bi bi-chat-dots-fill"></i> Pusat Bantuan & Tanya Jawab</h2>

            <?php echo $message; ?>

            <div class="card shadow-sm mb-5 border-0 bg-light">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3 text-primary"><i class="bi bi-pencil-square"></i> Ajukan Pertanyaan Baru</h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <textarea name="question" id="question" class="form-control" rows="4" placeholder="Tulis keluhan atau pertanyaan Anda di sini..." required></textarea>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="submit_question" class="btn btn-primary px-4">
                                <i class="bi bi-send"></i> Kirim Pertanyaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <hr class="my-5">

            <h4 class="mb-4"><i class="bi bi-clock-history"></i> Riwayat Pertanyaan Saya</h4>

            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="card card-question">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event"></i> <?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?>
                                </small>
                                <?php if($row['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Menunggu Jawaban</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Dijawab</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="card-text fs-5 mb-0"><?php echo nl2br(htmlspecialchars($row['question'])); ?></p>

                            <?php if ($row['status'] == 'answered' && !empty($row['answer'])): ?>
                                <div class="admin-reply">
                                    <div class="d-flex align-items-center mb-2 text-success">
                                        <i class="bi bi-person-badge-fill me-2"></i> <strong>Admin Support</strong>
                                        <small class="ms-auto text-muted" style="font-size: 0.8em;">
                                            Dijawab: <?php echo date('d M Y, H:i', strtotime($row['updated_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-dark">
                                        <?php echo nl2br(htmlspecialchars($row['answer'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-2">Anda belum pernah mengajukan pertanyaan.</p>
                </div>
            <?php endif; ?>
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