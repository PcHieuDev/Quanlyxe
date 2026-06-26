<?php
// Bắt đầu session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// require config if not already included
require_once __DIR__ . '/config.php';

// Get user info
$role = $_SESSION['role'] ?? 'user';
$username = $_SESSION['username'] ?? '';
$vehicle_id = $_SESSION['vehicle_id'] ?? null;

// Force password change if required
if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password']) {
    $current_page = basename($_SERVER['PHP_SELF']);
    // Allow access to change_password.php and logout.php
    if ($current_page !== 'change_password.php' && $current_page !== 'logout.php') {
        header('Location: change_password.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Xe & Vận hành</title>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top no-print">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="https://vietnampost.vn/apps/frontend/images/header/logo-fuild.png" alt="VNPost Logo" height="40" class="me-2">
                <span>QUẢN LÝ XE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="fa-solid fa-gauge me-1"></i> Dashboard</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="them_xe.php"><i class="fa-solid fa-plus-circle me-1"></i> Thêm xe mới</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="import_km.php"><i class="fa-solid fa-file-import me-1"></i> Quản lý KM</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bao_cao_hoat_dong.php"><i class="fa-solid fa-calendar-days me-1"></i> Báo cáo hoạt động</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bao_cao_chi_phi.php"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Báo cáo Sửa Chữa</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="chi_tiet.php?id=<?= $vehicle_id ?>"><i class="fa-solid fa-car me-1"></i> Xe của tôi</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fa-solid fa-bell me-1"></i> Nhắc lịch</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle me-1"></i> 
                            <?= $role === 'admin' ? 'Admin' : htmlspecialchars($username) ?>
                            <?php if ($role === 'admin'): ?>
                                <span class="badge bg-danger ms-1">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-info ms-1">User</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user me-2"></i>Thông tin</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Wrapper for content -->
    <div class="main-content py-4">
