<?php
// Thông tin kết nối
$servername = "localhost";
$username = "root"; // Mặc định của Laragon
$password = "matkhau";     // Mật khẩu phpMyAdmin
$dbname = "quan_ly_xe_oto"; // Tên database bạn vừa tạo

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Thiết lập lỗi PDO thành ngoại lệ
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Kết nối thành công!"; // Bỏ comment dòng này để test, sau đó comment lại
} catch(PDOException $e) {
    echo "Kết nối thất bại: " . $e->getMessage();
    die();
}
?>