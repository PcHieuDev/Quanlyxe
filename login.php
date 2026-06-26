<?php
session_start();
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        try {
            // Check admin login
            if ($username === 'admin') {
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['vehicle_id'] = null;
                    
                    // Check default password for admin
                    if (password_verify('123456', $user['password'])) {
                        $_SESSION['must_change_password'] = true;
                        header('Location: change_password.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $error = 'Tài khoản hoặc mật khẩu không đúng!';
                }
            } else {
                // Check user login (username = bien_kiem_soat)
                // Normalize license plate: remove dashes and dots for flexible matching
                $normalizedUsername = str_replace(['-', '.', ' '], '', $username); // Added space removal just in case
                
                // Try exact match first
                $stmt = $conn->prepare("SELECT * FROM vehicles WHERE bien_kiem_soat = ?");
                $stmt->execute([$username]);
                $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // If no exact match, try normalized match
                if (!$vehicle) {
                    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE REPLACE(REPLACE(REPLACE(bien_kiem_soat, '-', ''), '.', ''), ' ', '') = ?");
                    $stmt->execute([$normalizedUsername]);
                    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                $loginSuccess = false;
                $userId = null;
                $mustChangePass = false;

                if ($vehicle) {
                    // Check if user account exists
                    $checkUser = $conn->prepare("SELECT * FROM users WHERE username = ?");
                    $checkUser->execute([$username]); // Use the entered username (or should we use vehicle plate?)
                    // Note: Current logic uses entered username. To be consistent, we continue this.
                    // But ideally, we should use vehicle['bien_kiem_soat'] as the canonical username.
                    // However, preventing breaking changes, let's look for exact match first.
                    
                    $existingUser = $checkUser->fetch(PDO::FETCH_ASSOC);

                    if ($existingUser) {
                        // User exists, verify password
                        if (password_verify($password, $existingUser['password'])) {
                            $loginSuccess = true;
                            $userId = $existingUser['id'];
                            
                            // Check if password is still default '123456'
                            // We verify again against the default hash logic, or just duplicate the verify check
                            if (password_verify('123456', $existingUser['password'])) {
                                $mustChangePass = true;
                            }
                        }
                    } else {
                        // User does not exist in users table
                        // Check if password provided is default '123456'
                        if ($password === '123456') {
                            // Create new user
                            $hashedPwd = password_hash('123456', PASSWORD_DEFAULT);
                            $insertUser = $conn->prepare("INSERT INTO users (username, password, role, vehicle_id) VALUES (?, ?, 'user', ?)");
                            $insertUser->execute([$username, $hashedPwd, $vehicle['id']]);
                            $userId = $conn->lastInsertId();
                            
                            $loginSuccess = true;
                            $mustChangePass = true; // New user with default password
                        }
                    }
                }

                if ($loginSuccess) {
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    $_SESSION['vehicle_id'] = $vehicle['id'];
                    
                    if ($mustChangePass) {
                         $_SESSION['must_change_password'] = true;
                         header('Location: change_password.php');
                    } else {
                         header('Location: chi_tiet.php?id=' . $vehicle['id']);
                    }
                    exit;
                } else {
                    $error = 'Biển số xe không tồn tại hoặc mật khẩu không đúng!';
                }
            }
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
    <title>Đăng nhập - Hệ thống Quản lý Xe</title>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            /* VNPost Yellow Background */
            background-color: #fdb913;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Vietnam_Post_Logo.svg/1200px-Vietnam_Post_Logo.svg.png'); /* Optional watermark or keep plain */
            background-blend-mode: overlay;
            background-size: 50%;
            background-repeat: no-repeat;
            background-position: center;
        }
        .login-card {
            max-width: 450px;
            width: 100%;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.2);
            border: none;
            overflow: hidden;
        }
        .login-header {
            background-color: #ffffff;
            color: #00467f;
            padding: 2rem;
            text-align: center;
            border-bottom: 4px solid #fdb913;
        }
        .login-body {
            padding: 2rem;
            background: #ffffff;
        }
        .form-control:focus {
            border-color: #00467f;
            box-shadow: 0 0 0 0.25rem rgba(0, 70, 127, 0.25);
        }
        .btn-login {
            background-color: #00467f;
            border: none;
            padding: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background-color: #003366;
            transform: translateY(-1px);
        }
        .text-vnpost {
            color: #00467f;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="https://vietnampost.vn/apps/frontend/images/header/logo-fuild.png" alt="VNPost Logo" height="50" class="mb-3">
            <h4 class="mb-0">HỆ THỐNG QUẢN LÝ XE</h4>
            <small>Đăng nhập để tiếp tục</small>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-exclamation-circle me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fa-solid fa-user me-2"></i>Tài khoản
                    </label>
                    <input type="text" class="form-control form-control-lg" name="username" 
                           placeholder="admin hoặc Biển số xe" required autofocus>
                    <small class="text-muted">Admin: admin | User: Biển số xe (VD: 29A-123.45)</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        <i class="fa-solid fa-lock me-2"></i>Mật khẩu
                    </label>
                    <input type="password" class="form-control form-control-lg" name="password" 
                           placeholder="Nhập mật khẩu" required>
                    <small class="text-muted">Mật khẩu mặc định: 123456</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100 btn-lg">
                    <i class="fa-solid fa-sign-in-alt me-2"></i>Đăng nhập
                </button>
            </form>
            
            <hr class="my-4">
            
            <!-- <div class="text-center text-muted small">
                <p class="mb-1"><i class="fa-solid fa-info-circle me-1"></i> Hướng dẫn đăng nhập:</p>
                <p class="mb-0"><strong>Admin:</strong> admin / 123456</p>
                <p class="mb-0"><strong>User:</strong> [Biển số xe] / 123456</p>
            </div> -->
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
