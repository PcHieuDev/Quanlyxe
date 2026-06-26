<?php
require_once 'includes/header.php';

// Only admin can access
if ($role !== 'admin') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Bạn không có quyền truy cập trang này!</div></div>";
    require_once 'includes/footer.php';
    exit;
}

// --- AUTO MIGRATION CHECK ---
try {
    $checkCol = $conn->query("SHOW COLUMNS FROM operation_stats LIKE 'km_tich_luy'");
    if (!$checkCol->fetch()) {
        $conn->exec("ALTER TABLE operation_stats ADD COLUMN km_tich_luy DECIMAL(10,2) DEFAULT 0 AFTER km_trong_thang");
        // Update old data
        $conn->exec("UPDATE operation_stats os
                     LEFT JOIN (
                         SELECT vehicle_id, so_km_ban_giao
                         FROM vehicle_assignments
                         WHERE den_ngay IS NULL
                         ORDER BY tu_ngay DESC
                     ) va ON os.vehicle_id = va.vehicle_id
                     SET os.km_tich_luy = COALESCE(va.so_km_ban_giao, 0) + COALESCE(os.km_trong_thang, 0)");
    }
    
    // Check da_thay_dau column
    $checkCol2 = $conn->query("SHOW COLUMNS FROM operation_stats LIKE 'da_thay_dau'");
    if (!$checkCol2->fetch()) {
        $conn->exec("ALTER TABLE operation_stats ADD COLUMN da_thay_dau TINYINT(1) DEFAULT 0 AFTER km_tich_luy");
    }
    
} catch (Exception $e) {
    // Silent fail or log
}
// ----------------------------

$message = '';
$warnings = [];
$success_count = 0;

// Load library from Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load helper functions
require_once __DIR__ . '/includes/functions.php';

// ... AUTO MIGRATION logic ...

// Xử lý Upload File Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        $file = $_FILES['excel_file'];
        
        // Kiểm tra file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Lỗi upload file!");
        }
        
        // Kiểm tra extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            throw new Exception("Chỉ chấp nhận file Excel (.xlsx, .xls) hoặc CSV!");
        }
        
        $rows = [];

        // 1. Xử lý file XLSX
        if ($ext === 'xlsx') {
            if (class_exists('Shuchkin\SimpleXLSX')) {
                if ($xlsx = Shuchkin\SimpleXLSX::parse($file['tmp_name'])) {
                    $rows = $xlsx->rows();
                    
                    // Tự động bỏ qua dòng header nếu có
                    if (!empty($rows) && count($rows[0]) >= 2) {
                        $first_cell = strtolower(trim($rows[0][0]));
                        // Nếu dòng đầu chứa "xe", "biển", "bks"... thì là header
                        if (preg_match('/(xe|bien|bks|biển|số)/i', $first_cell)) {
                            array_shift($rows); // Bỏ dòng header
                        }
                    }
                } else {
                    throw new Exception(Shuchkin\SimpleXLSX::parseError());
                }
            } else {
                 throw new Exception("Lỗi: Thư viện đọc Excel chưa được cài đặt. Vui lòng chạy 'composer require shuchkin/simplexlsx' hoặc dùng file CSV.");
            }
        } 
        // 2. Xử lý file CSV (Logic cũ)
        elseif ($ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) throw new Exception("Không thể đọc file CSV!");
            
            // Đọc dòng đầu để kiểm tra
            $first_row = fgetcsv($handle);
            if ($first_row && count($first_row) >= 2) {
                $first_cell = strtolower(trim($first_row[0]));
                // Nếu KHÔNG phải header, thêm vào $rows
                if (!preg_match('/(xe|bien|bks|biển|số)/i', $first_cell)) {
                    $rows[] = [$first_row[0], $first_row[1]];
                }
            }
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 2) {
                    $rows[] = [$data[0], $data[1]];
                }
            }
            fclose($handle);
        }

        // --- XỬ LÝ DỮ LIỆU TỪ $rows ---
        // Lấy tháng/năm từ form upload (nếu không có thì mặc định hiện tại)
        $thang = isset($_POST['upload_month']) ? (int)$_POST['upload_month'] : (int)date('n');
        $nam = isset($_POST['upload_year']) ? (int)$_POST['upload_year'] : (int)date('Y');

        // Lấy tất cả xe từ database để so sánh (tránh lỗi encoding trong SQL)
        $all_vehicles = [];
        $stmt_all = $conn->query("SELECT id, bien_kiem_soat FROM vehicles");
        while ($v = $stmt_all->fetch(PDO::FETCH_ASSOC)) {
            // Chuẩn hóa biển số từ DB
            $normalized = preg_replace('/\s+/u', '', $v['bien_kiem_soat']);
            $all_vehicles[$normalized] = $v;
        }

        foreach ($rows as $idx => $data) {
            // Tự động xác định cột biển số và cột km
            // Nếu có 3 cột: [STT, Biển số, KM]
            // Nếu có 2 cột: [Biển số, KM]
            
            $bien_so_raw = '';
            $km_value = '';
            
            if (count($data) >= 3) {
                // File có 3 cột: STT, Biển số, KM
                $bien_so_raw = trim($data[1]); // Cột thứ 2
                $km_value = trim($data[2]);     // Cột thứ 3
            } elseif (count($data) >= 2) {
                // File có 2 cột: Biển số, KM
                $bien_so_raw = trim($data[0]); // Cột thứ 1
                $km_value = trim($data[1]);     // Cột thứ 2
            } else {
                continue;
            }
            
            // Xử lý số liệu: loại bỏ dấu phẩy nếu có
            $km_thang_nay = (float)str_replace(',', '', $km_value);
            
            if (empty($bien_so_raw) || $km_thang_nay <= 0) continue;
            
            // Chuẩn hóa biển số từ Excel: Loại bỏ TẤT CẢ khoảng trắng
            $bien_so_clean = preg_replace('/\s+/u', '', $bien_so_raw);
            
            // Tìm xe trong danh sách đã chuẩn hóa
            if (!isset($all_vehicles[$bien_so_clean])) {
                $warnings[] = "❌ Xe '$bien_so_raw' không tồn tại (Đã thử: '$bien_so_clean')";
                continue;
            }
            
            $vehicle = $all_vehicles[$bien_so_clean];
            
            $vehicle_id = $vehicle['id'];
            
            // Lấy số km bàn giao gần nhất (đang quản lý)
            $stmt = $conn->prepare("SELECT so_km_ban_giao FROM vehicle_assignments 
                                   WHERE vehicle_id = ? AND den_ngay IS NULL 
                                   ORDER BY tu_ngay DESC LIMIT 1");
            $stmt->execute([$vehicle_id]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $km_ban_giao = $assignment ? (float)$assignment['so_km_ban_giao'] : 0;
            
            // Kiểm tra nếu xe đi quá 5000km trong tháng
            if ($km_thang_nay > 5000) {
                $warnings[] = "⚠️ XE $bien_so_raw ĐÃ ĐI $km_thang_nay KM TRONG THÁNG - CẦN THAY DẦU!";
            }
            
            // Kiểm tra xem đã có dữ liệu tháng này chưa
             $check = $conn->prepare("SELECT id FROM operation_stats 
                                     WHERE vehicle_id = ? AND nam = ? AND thang = ?");
             $check->execute([$vehicle_id, $nam, $thang]);
             
             if ($check->rowCount() > 0) {
                 // Cập nhật km_trong_thang
                 $stmt = $conn->prepare("UPDATE operation_stats 
                                        SET km_trong_thang = ?
                                        WHERE vehicle_id = ? AND nam = ? AND thang = ?");
                 $stmt->execute([$km_thang_nay, $vehicle_id, $nam, $thang]);
             } else {
                 // Thêm mới (lưu tạm km_tich_luy = 0, sẽ update sau)
                 $stmt = $conn->prepare("INSERT INTO operation_stats 
                                        (vehicle_id, nam, thang, km_trong_thang, km_tich_luy, so_chuyen_trong_thang) 
                                        VALUES (?, ?, ?, ?, 0, 0)");
                 $stmt->execute([$vehicle_id, $nam, $thang, $km_thang_nay]);
             }
             
             // QUAN TRỌNG: Tính toán lại toàn bộ số km tích lũy từ đầu đến giờ
             updateCumulativeKM($conn, $vehicle_id);
             
             $success_count++;
        }
        
        if ($success_count > 0) {
            $message = "<div class='alert alert-success'><i class='fa-solid fa-check-circle me-2'></i>Đã import thành công $success_count xe!</div>";
        }
        
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'><i class='fa-solid fa-exclamation-triangle me-2'></i>" . $e->getMessage() . "</div>";
    }
}

// Xử lý logic xem dữ liệu tháng (Filter)
$thang_hien_tai = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$nam_hien_tai = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$stmt = $conn->prepare("
    SELECT 
        v.id as vehicle_id,
        v.bien_kiem_soat,
        v.loai_xe,
        os.km_trong_thang,
        os.km_tich_luy,
        os.da_thay_dau,
        va.so_km_ban_giao,
        os.nam,
        os.thang
    FROM operation_stats os
    JOIN vehicles v ON os.vehicle_id = v.id
    LEFT JOIN (
        SELECT vehicle_id, so_km_ban_giao, 
               ROW_NUMBER() OVER (PARTITION BY vehicle_id ORDER BY tu_ngay DESC) as rn
        FROM vehicle_assignments 
        WHERE den_ngay IS NULL
    ) va ON os.vehicle_id = va.vehicle_id AND va.rn = 1
    WHERE os.nam = ? AND os.thang = ?
    ORDER BY v.bien_kiem_soat
");
$stmt->execute([$nam_hien_tai, $thang_hien_tai]);
$current_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-0">
                <i class="fa-solid fa-file-import me-2"></i>Quản lý Số KM
            </h2>
            <p class="text-muted">Xem và nhập dữ liệu số km xe chạy hàng tháng</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <?= $message ?>

    <?php if (!empty($warnings)): ?>
        <div class="alert alert-warning">
            <h5 class="alert-heading"><i class="fa-solid fa-bell me-2"></i>Cảnh báo & Thông báo:</h5>
            <ul class="mb-0">
                <?php foreach ($warnings as $warning): ?>
                    <li><?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Upload -->
        <div class="col-lg-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-upload me-2"></i>Import Dữ Liệu</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Tháng</label>
                                <select name="upload_month" class="form-select">
                                    <?php for($m=1; $m<=12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Năm</label>
                                <select name="upload_year" class="form-select">
                                    <?php 
                                    $curYear = date('Y');
                                    for($y=$curYear-1; $y<=$curYear+1; $y++): 
                                    ?>
                                        <option value="<?= $y ?>" <?= $y == $curYear ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Chọn file Excel/CSV</label>
                            <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                File .xlsx, .xls, .csv
                            </div>
                        </div>

                        <div class="alert alert-info py-2">
                            <h6 class="fw-bold small mb-1"><i class="fa-solid fa-lightbulb me-1"></i>Định dạng:</h6>
                            <p class="mb-0 small">Cột 1: Biển số | Cột 2: Số KM</p>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-cloud-upload-alt me-2"></i>Upload
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-warning small">
                <i class="fa-solid fa-exclamation-triangle me-1"></i>
                Lưu ý: Dữ liệu sẽ được import vào Tháng/Năm bạn chọn ở trên.
            </div>
        </div>

        <!-- Dữ liệu tháng -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="mb-0 text-dark">
                            <i class="fa-solid fa-table me-2"></i>Dữ liệu Tháng <?= $thang_hien_tai ?>/<?= $nam_hien_tai ?>
                        </h5>
                        
                        <!-- Filter Form -->
                        <form method="GET" class="d-flex gap-2 align-items-center">
                            <select name="month" class="form-select form-select-sm" style="width: auto;">
                                <?php for($i=1; $i<=12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $thang_hien_tai ? 'selected' : '' ?>>
                                        Tháng <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="year" class="form-select form-select-sm" style="width: auto;">
                                <?php 
                                $currentYear = date('Y');
                                for($y=$currentYear-2; $y<=$currentYear+1; $y++): 
                                ?>
                                    <option value="<?= $y ?>" <?= $y == $nam_hien_tai ? 'selected' : '' ?>>
                                        Năm <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-filter"></i> Xem
                            </button>
                            <a href="bao_cao_km_print.php?month=<?= $thang_hien_tai ?>&year=<?= $nam_hien_tai ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-file-pdf"></i> Xuất Báo Cáo
                            </a>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="d-flex justify-content-end px-3 py-2 bg-light border-bottom">
                         <span class="badge bg-secondary"><?= count($current_data) ?> phương tiện</span>
                    </div>
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Biển số</th>
                                    <th>Loại xe</th>
                                    <th class="text-end">KM bàn giao</th>
                                    <th class="text-end">KM tháng này</th>
                                    <th class="text-end">KM tích lũy</th>
                                    <th class="text-center">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($current_data)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-inbox fa-3x mb-3 opacity-25"></i>
                                            <p>Chưa có dữ liệu tháng này. Vui lòng import file!</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($current_data as $row): ?>
                                        <?php 
                                        $need_oil_change = $row['km_trong_thang'] > 5000;
                                        $row_class = $need_oil_change ? 'table-warning' : '';
                                        ?>
                                        <tr class="<?= $row_class ?>">
                                            <td class="fw-bold"><?= htmlspecialchars($row['bien_kiem_soat']) ?></td>
                                            <td><small><?= htmlspecialchars($row['loai_xe']) ?></small></td>
                                            <td class="text-end">
                                                <span class="badge bg-secondary">
                                                    <?= number_format($row['so_km_ban_giao'] ?? 0) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary">
                                                    <?= number_format($row['km_trong_thang']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                <?= number_format($row['km_tich_luy']) ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($need_oil_change): ?>
                                                    <!-- Toggle Switch cho việc thay dầu -->
                                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                                        <input class="form-check-input" type="checkbox" style="cursor: pointer;"
                                                               id="oil_switch_<?= $row['vehicle_id'] ?>" 
                                                               <?= isset($row['da_thay_dau']) && $row['da_thay_dau'] == 1 ? 'checked' : '' ?>
                                                               onchange="updateOilStatus(<?= $row['vehicle_id'] ?>, <?= $row['thang'] ?>, <?= $row['nam'] ?>, this)">
                                                        <label class="form-check-label badge <?= isset($row['da_thay_dau']) && $row['da_thay_dau'] == 1 ? 'bg-success' : 'bg-danger' ?>" 
                                                               for="oil_switch_<?= $row['vehicle_id'] ?>" 
                                                               id="oil_label_<?= $row['vehicle_id'] ?>"
                                                               style="cursor: pointer; min-width: 80px;">
                                                            <?= isset($row['da_thay_dau']) && $row['da_thay_dau'] == 1 ? 'Đã thay' : 'Cần thay' ?>
                                                        </label>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fa-solid fa-check"></i> OK
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

 
</div>

<style>
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>

<script>
function updateOilStatus(vehicleId, month, year, checkbox) {
    const status = checkbox.checked ? 1 : 0;
    const label = document.getElementById('oil_label_' + vehicleId);
    
    // Update UI immediately (optimistic update)
    if (status) {
        label.classList.remove('bg-danger');
        label.classList.add('bg-success');
        label.innerText = 'Đã thay';
    } else {
        label.classList.remove('bg-success');
        label.classList.add('bg-danger');
        label.innerText = 'Cần thay';
    }

    // Call API
    const formData = new FormData();
    formData.append('vehicle_id', vehicleId);
    formData.append('month', month);
    formData.append('year', year);
    formData.append('status', status);

    fetch('ajax_update_oil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Lỗi cập nhật: ' + (data.message || 'Unknown error'));
            // Revert UI on error
            checkbox.checked = !status;
            if (status) { // Revert back to unchecked (danger)
                label.classList.remove('bg-success');
                label.classList.add('bg-danger');
                label.innerText = 'Cần thay';
            } else { // Revert back to checked (success)
                label.classList.remove('bg-danger');
                label.classList.add('bg-success');
                label.innerText = 'Đã thay';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Lỗi kết nối server!');
        checkbox.checked = !status;
        // ... revert logic ...
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
