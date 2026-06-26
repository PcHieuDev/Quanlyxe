<?php
require_once 'includes/config.php';

session_start();
// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = $_POST['vehicle_id'] ?? null;
    $month = $_POST['month'] ?? null;
    $year = $_POST['year'] ?? null;
    $status = $_POST['status'] ?? 0; // 1: Đã thay, 0: Chưa thay

    if (!$vehicle_id || !$month || !$year) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    try {
        // --- AUTO MIGRATION: Check if column 'da_thay_dau' exists ---
        $checkCol = $conn->query("SHOW COLUMNS FROM operation_stats LIKE 'da_thay_dau'");
        if (!$checkCol->fetch()) {
            $conn->exec("ALTER TABLE operation_stats ADD COLUMN da_thay_dau TINYINT(1) DEFAULT 0 AFTER km_tich_luy");
        }
        // -------------------------------------------------------------

        $stmt = $conn->prepare("UPDATE operation_stats 
                               SET da_thay_dau = ? 
                               WHERE vehicle_id = ? AND nam = ? AND thang = ?");
        $stmt->execute([$status, $vehicle_id, $year, $month]);

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
