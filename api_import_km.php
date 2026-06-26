<?php
// Cho phép gọi API từ extension (Cross-Origin)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Xử lý preflight request (OPTIONS) của trình duyệt
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Đọc dữ liệu JSON
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input || !isset($input['data']) || !is_array($input['data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$thang = isset($input['month']) ? (int)$input['month'] : (int)date('n');
$nam = isset($input['year']) ? (int)$input['year'] : (int)date('Y');
$rows = $input['data'];

try {
    // Lấy tất cả xe từ DB để map Biển số -> ID
    $all_vehicles = [];
    $stmt_all = $conn->query("SELECT id, bien_kiem_soat FROM vehicles");
    while ($v = $stmt_all->fetch(PDO::FETCH_ASSOC)) {
        $normalized = preg_replace('/\s+/u', '', $v['bien_kiem_soat']);
        $all_vehicles[$normalized] = $v;
    }

    $success_count = 0;
    $warnings = [];

    $conn->beginTransaction();

    foreach ($rows as $row) {
        $bien_so_raw = trim($row['bien_so'] ?? '');
        $km_value = $row['km'] ?? 0;
        
        $km_thang_nay = (float)str_replace(',', '', $km_value);
        
        if (empty($bien_so_raw) || $km_thang_nay <= 0) continue;
        
        $bien_so_clean = preg_replace('/\s+/u', '', $bien_so_raw);
        
        if (!isset($all_vehicles[$bien_so_clean])) {
            $warnings[] = "Xe '$bien_so_raw' không tồn tại trong hệ thống (Đã thử: '$bien_so_clean')";
            continue;
        }
        
        $vehicle_id = $all_vehicles[$bien_so_clean]['id'];
        
        // Kiểm tra xem đã có dữ liệu tháng này chưa
        $check = $conn->prepare("SELECT id FROM operation_stats WHERE vehicle_id = ? AND nam = ? AND thang = ?");
        $check->execute([$vehicle_id, $nam, $thang]);
        
        if ($check->rowCount() > 0) {
            // Cập nhật
            $stmt = $conn->prepare("UPDATE operation_stats SET km_trong_thang = ? WHERE vehicle_id = ? AND nam = ? AND thang = ?");
            $stmt->execute([$km_thang_nay, $vehicle_id, $nam, $thang]);
        } else {
            // Thêm mới
            $stmt = $conn->prepare("INSERT INTO operation_stats (vehicle_id, nam, thang, km_trong_thang, km_tich_luy, so_chuyen_trong_thang) VALUES (?, ?, ?, ?, 0, 0)");
            $stmt->execute([$vehicle_id, $nam, $thang, $km_thang_nay]);
        }
        
        $success_count++;
    }
    
    $conn->commit();

    // Cập nhật lại số km tích lũy cho các xe vừa được import (ngoài transaction để an toàn)
    foreach ($rows as $row) {
        $bien_so_clean = preg_replace('/\s+/u', '', trim($row['bien_so'] ?? ''));
        if (isset($all_vehicles[$bien_so_clean])) {
            updateCumulativeKM($conn, $all_vehicles[$bien_so_clean]['id']);
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => "Đã import thành công $success_count xe!",
        'warnings' => $warnings,
        'imported_count' => $success_count
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}
