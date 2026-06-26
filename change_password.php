<?php
session_start();
require_once 'includes/config.php';

// If not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $old_password = $_POST['old_password'] ?? '';

    // Validate
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($new_password === '123456') {
        $error = 'Vui lòng chọn mật khẩu khác mật khẩu mặc định!';
    } else {
        try {
            // Verify old password (optional security step, but good practice)
            // But since they might contain '123456', and we want to enforce change...
            // Let's just update.
            
            $userId = $_SESSION['user_id'];
            $hashedPwd = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPwd, $userId]);
            
            // Clear the flag
            unset($_SESSION['must_change_password']);
            
            $success = 'Đổi mật khẩu thành công! Đang chuyển hướng...';
            
            // Redirect after 2 seconds
            echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 1500);</script>';
            
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi Mật Khẩu - Hệ thống Quản lý Xe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #86b7fe 0%, #faf555 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-custom {
            max-width: 500px;
            width: 100%;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.2);
            border: none;
            overflow: hidden;
        }
        .card-header {
            background: #fff;
            padding: 2rem 2rem 1rem;
            border-bottom: none;
            text-align: center;
        }
        .card-body {
            padding: 2rem;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="card card-custom">
        <div class="card-header">
            <h4 class="mb-2 fw-bold text-primary"><i class="fa-solid fa-key me-2"></i>Đổi Mật Khẩu</h4>
            <p class="text-muted mb-0">Vì lý do bảo mật, bạn vui lòng đổi mật khẩu mặc định.</p>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-exclamation-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check-circle me-2"></i><?= $success ?></div>
            <?php else: ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Tài khoản đang đăng nhập</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" disabled>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Mật khẩu mới</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="form-control" name="new_password" required placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Xác nhận mật khẩu mới</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-check-double"></i></span>
                        <input type="password" class="form-control" name="confirm_password" required placeholder="Nhập lại mật khẩu mới">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fa-solid fa-save me-2"></i>Đổi mật khẩu & Tiếp tục
                </button>
            </form>
            <?php endif; ?>
            
            <div class="mt-4 text-center">
                <a href="logout.php" class="text-muted text-decoration-none small"><i class="fa-solid fa-sign-out-alt me-1"></i> Đăng xuất</a>
            </div>
        </div>
    </div>
</body>
</html>
