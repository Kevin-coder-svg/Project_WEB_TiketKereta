<?php
session_start();
require 'db_config.php';


if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    
     header('Location: login.php');
     exit;
}

$current_admin_id = $_SESSION['user_id']; 
$message = "";

if (isset($_POST['submit_answer'])) {
    $qna_id = $_POST['qna_id'];
    $answer = trim($_POST['answer']);

    if (!empty($answer)) {
       
        $sql_update = "UPDATE qna SET answer = ?, admin_id = ?, status = 'answered', updated_at = NOW() WHERE qna_id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("sii", $answer, $current_admin_id, $qna_id);
        
        if ($stmt->execute()) {
            $message = '
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> Jawaban berhasil disimpan!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal menyimpan jawaban. SQL Error.</div>';
        }
    }
}


$sql_all = "SELECT qna.*, users.full_name 
            FROM qna 
            JOIN users ON qna.user_id = users.user_id 
            ORDER BY status ASC, created_at DESC";
$result = $conn->query($sql_all);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Pertanyaan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-header { background-color: #343a40; color: white; }
        .user-meta { font-size: 0.85rem; color: #6c757d; }
        .question-text { font-weight: 500; font-size: 1rem; color: #212529; }
        
        .answer-area {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="admin.php"><i class="bi bi-shield-lock-fill"></i> Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="QnA_Admin.php">Manajemen Tanya Jawab</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="admin.php" class="btn btn-outline-light btn-sm">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        
        <h3 class="mb-3">Manajemen Tanya Jawab</h3>
        
        <?php echo $message; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="m-0"><i class="bi bi-inbox"></i> Daftar Pertanyaan Masuk</h5>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">User & Tanggal</th>
                                <th width="35%">Pertanyaan</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="40%">Tindakan / Jawaban</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary">
                                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($row['full_name']); ?>
                                            </div>
                                            <div class="user-meta mt-1">
                                                <i class="bi bi-clock"></i> <?php echo date('d M Y', strtotime($row['created_at'])); ?><br>
                                                <small><?php echo date('H:i', strtotime($row['created_at'])); ?> WIB</small>
                                            </div>
                                        </td>

                                        <td>
                                            <p class="question-text mb-0">
                                                "<?php echo nl2br(htmlspecialchars($row['question'])); ?>"
                                            </p>
                                        </td>

                                        <td class="text-center">
                                            <?php if($row['status'] == 'pending'): ?>
                                                <span class="badge bg-warning text-dark border border-warning">
                                                    <i class="bi bi-hourglass-split"></i> Pending
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success border border-success">
                                                    <i class="bi bi-check-lg"></i> Dijawab
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($row['status'] == 'pending'): ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="qna_id" value="<?php echo $row['qna_id']; ?>">
                                                    <div class="mb-2">
                                                        <textarea name="answer" class="form-control form-control-sm" rows="3" placeholder="Tulis jawaban admin di sini..." required></textarea>
                                                    </div>
                                                    <button type="submit" name="submit_answer" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-send-fill"></i> Kirim Jawaban
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="answer-area">
                                                    <strong class="text-success"><i class="bi bi-reply-fill"></i> Jawaban Anda:</strong>
                                                    <p class="mb-1 mt-1 text-dark">
                                                        <?php echo nl2br(htmlspecialchars($row['answer'])); ?>
                                                    </p>
                                                    <small class="text-muted fst-italic">
                                                        Dibalas pada: <?php echo date('d M Y, H:i', strtotime($row['updated_at'])); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox-fill display-4"></i>
                                        <p class="mt-2">Tidak ada pertanyaan saat ini.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted small">
                Menampilkan semua pertanyaan dari database.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>