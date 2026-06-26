<?php
require_once 'includes/header.php';

// Check ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Không tìm thấy ID xe!</div></div>";
    require_once 'footer.php';
    exit;
}

$id = $_GET['id'];

// Check if user has permission to view this vehicle
if ($role === 'user' && $vehicle_id != $id) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Bạn không có quyền xem xe này!</div></div>";
    require_once 'footer.php';
    exit;
}

// --- XỬ LÝ POST REQUEST (THÊM MỚI CÁC BẢNG CON) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_tool') {
            $stmt = $conn->prepare("INSERT INTO vehicle_tools (vehicle_id, ten_dung_cu, so_luong, ngay_cap, ghi_chu) VALUES (?, ?, ?, ?, ?)");
            
            $names = $_POST['ten_dung_cu'] ?? [];
            if (!is_array($names)) {
                $names = [$names];
            }
            
            $quantities = $_POST['so_luong'] ?? [];
            $notes = $_POST['ghi_chu'] ?? [];
            $commonDate = $_POST['ngay_cap'] ?? date('Y-m-d');

            foreach ($names as $i => $name) {
                if (empty($name)) continue;
                $stmt->execute([
                    $id, 
                    $name, 
                    $quantities[$i] ?? 1, 
                    $commonDate, 
                    $notes[$i] ?? ''
                ]);
            }
            $_SESSION['message'] = "<div class='alert alert-success'>Thêm dụng cụ thành công!</div>";
            header("Location: chi_tiet.php?id=$id&tab=tools");
            exit;
        }
        elseif ($action === 'add_assignment') {
            $stmt = $conn->prepare("INSERT INTO vehicle_assignments (vehicle_id, loai_doi_tuong, ten_doi_tuong, tu_ngay, so_km_ban_giao, den_ngay, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $den_ngay = !empty($_POST['den_ngay']) ? $_POST['den_ngay'] : null;
            $so_km = !empty($_POST['so_km_ban_giao']) ? $_POST['so_km_ban_giao'] : null;
            $stmt->execute([$id, $_POST['loai_doi_tuong'], $_POST['ten_doi_tuong'], $_POST['tu_ngay'], $so_km, $den_ngay, $_POST['ghi_chu']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Giao xe thành công!</div>";
            header("Location: chi_tiet.php?id=$id&tab=assign");
            exit;
        }
        elseif ($action === 'add_inspection') {
            $stmt = $conn->prepare("INSERT INTO vehicle_inspections (vehicle_id, so_so_dang_kiem, hieu_luc_tu_ngay, hieu_luc_den_ngay, don_vi_dang_kiem) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $_POST['so_so_dang_kiem'], $_POST['hieu_luc_tu_ngay'], $_POST['hieu_luc_den_ngay'], $_POST['don_vi_dang_kiem']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Thêm lịch sử đăng kiểm thành công!</div>";
            header("Location: chi_tiet.php?id=$id&tab=insp");
            exit;
        }
        elseif ($action === 'add_maintenance') {
            $stmt = $conn->prepare("INSERT INTO maintenance_logs (vehicle_id, ngay_bao_duong, cap_bao_duong_km, km_thuc_te, noi_dung_vat_tu, don_vi_tinh, so_luong, don_gia, thanh_tien, noi_thuc_hien) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $thanh_tien = $_POST['so_luong'] * $_POST['don_gia'];
            $stmt->execute([$id, $_POST['ngay_bao_duong'], $_POST['cap_bao_duong_km'], $_POST['km_thuc_te'], $_POST['noi_dung_vat_tu'], $_POST['don_vi_tinh'], $_POST['so_luong'], $_POST['don_gia'], $thanh_tien, $_POST['noi_thuc_hien']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Ghi nhận bảo dưỡng thành công!</div>";
            header("Location: chi_tiet.php?id=$id&tab=maint");
            exit;
        }
        elseif ($action === 'add_repair') {
            // Upload logic
            $anh_bang_chung_paths = [];
            if (isset($_FILES['anh_bang_chung']) && !empty($_FILES['anh_bang_chung']['name'][0])) {
                $bks = "";
                $stmt_bks = $conn->prepare("SELECT bien_kiem_soat FROM vehicles WHERE id = ?");
                $stmt_bks->execute([$id]);
                $v = $stmt_bks->fetch(PDO::FETCH_ASSOC);
                if ($v) $bks = $v['bien_kiem_soat'];

                $count = count($_FILES['anh_bang_chung']['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['anh_bang_chung']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['anh_bang_chung']['tmp_name'][$i];
                        $file_name = $_FILES['anh_bang_chung']['name'][$i];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        $allowed_exts = ['jpg', 'jpeg', 'png'];
                        if (in_array($file_ext, $allowed_exts)) {
                            $new_file_name = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $bks) . '_SC_' . time() . '_' . $i . '.' . $file_ext;
                            $upload_dir = 'uploads/sua_chua/';
                            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                            $dest_path = $upload_dir . $new_file_name;
                            if (move_uploaded_file($file_tmp, $dest_path)) {
                                $anh_bang_chung_paths[] = $dest_path;
                            }
                        }
                    }
                }
            }
            $anh_bang_chung_json = !empty($anh_bang_chung_paths) ? json_encode($anh_bang_chung_paths) : null;

            $stmt = $conn->prepare("INSERT INTO repair_logs (vehicle_id, ngay_sua_chua, loai_sua_chua, noi_dung_sua_chua, xuat_xu_vat_tu, don_vi_tinh, so_luong, don_gia, thanh_tien, can_cu_to_trinh, noi_thuc_hien, anh_bang_chung) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $contents = $_POST['noi_dung_sua_chua'] ?? [];
            if (!is_array($contents)) {
                $contents = [$contents]; // Fallback for single entry if any
            }
            
            $origins = $_POST['xuat_xu_vat_tu'] ?? [];
            $units = $_POST['don_vi_tinh'] ?? [];
            $quantities = $_POST['so_luong'] ?? [];
            $prices = $_POST['don_gia'] ?? [];
            $places = $_POST['noi_thuc_hien'] ?? [];

            $valid_rows = [];
            foreach ($contents as $i => $content) {
                if (!empty(trim($content))) {
                    $valid_rows[] = $i;
                }
            }

            if (empty($valid_rows) && !empty($anh_bang_chung_paths)) {
                // User chỉ tải ảnh lên, không nhập nội dung
                $stmt->execute([
                    $id, 
                    $_POST['ngay_sua_chua'], 
                    $_POST['loai_sua_chua'], 
                    'Đính kèm ảnh bằng chứng', 
                    '', 
                    '', 
                    0, 
                    0, 
                    0, 
                    $_POST['can_cu_to_trinh'] ?? '', 
                    '',
                    $anh_bang_chung_json
                ]);
            } else {
                foreach ($valid_rows as $i) {
                    $content = $contents[$i];
                    $sl = $quantities[$i] ?? 0;
                    $dg = $prices[$i] ?? 0;
                    $thanh_tien = $sl * $dg;
                    
                    $stmt->execute([
                        $id, 
                        $_POST['ngay_sua_chua'], 
                        $_POST['loai_sua_chua'], 
                        $content, 
                        $origins[$i] ?? '', 
                        $units[$i] ?? '', 
                        $sl, 
                        $dg, 
                        $thanh_tien, 
                        $_POST['can_cu_to_trinh'], 
                        $places[$i] ?? '',
                        $anh_bang_chung_json
                    ]);
                }
            }

            $_SESSION['message'] = "<div class='alert alert-success'>Ghi nhận sửa chữa thành công!</div>";
            header("Location: chi_tiet.php?id=$id&tab=repair");
            exit;
        }
        elseif ($action === 'add_tire') {
            $stmt = $conn->prepare("INSERT INTO tire_logs (vehicle_id, ngay_thay_the, quy_cach_lop, so_luong_thay, km_khi_thay, km_da_chay, so_seri_lop_moi, hang_sx_lop_moi, so_lop_du_phong) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $_POST['ngay_thay_the'], $_POST['quy_cach_lop'], $_POST['so_luong_thay'], $_POST['km_khi_thay'], $_POST['km_da_chay'], $_POST['so_seri_lop_moi'], $_POST['hang_sx_lop_moi'], $_POST['so_lop_du_phong']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Ghi nhận thay lốp thành công!</div>";
            header("Location: chi_tiet.php?id=$id&tab=tire");
            exit;
        }
        elseif ($action === 'add_battery') {
            $stmt = $conn->prepare("INSERT INTO battery_logs (vehicle_id, ngay_thay_the, quy_cach_binh, so_luong_thay, thoi_gian_da_su_dung, so_seri_moi, hang_sx_moi, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $_POST['ngay_thay_the'], $_POST['quy_cach_binh'], $_POST['so_luong_thay'], $_POST['thoi_gian_da_su_dung'], $_POST['so_seri_moi'], $_POST['hang_sx_moi'], $_POST['ghi_chu']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Ghi nhận thay ắc quy thành công!</div>";
            header("Location: chi_tiet.php?id=$id&tab=battery");
            exit;
        }
        elseif ($action === 'add_stat') {
            // Check unique constraints: one record per month per vehicle
            $check = $conn->prepare("SELECT id FROM operation_stats WHERE vehicle_id = ? AND nam = ? AND thang = ?");
            $check->execute([$id, $_POST['nam'], $_POST['thang']]);
            if ($check->rowCount() > 0) {
                 $_SESSION['message'] = "<div class='alert alert-warning'>Dữ liệu tháng này đã tồn tại! Vui lòng chỉnh sửa thay vì thêm mới (Tính năng sửa đang cập nhật).</div>";
            } else {
                $stmt = $conn->prepare("INSERT INTO operation_stats (vehicle_id, nam, thang, km_trong_thang, so_chuyen_trong_thang) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $_POST['nam'], $_POST['thang'], $_POST['km_trong_thang'], $_POST['so_chuyen_trong_thang']]);
                $_SESSION['message'] = "<div class='alert alert-success'>Cập nhật nhật trình thành công!</div>";
                $_SESSION['message'] = "<div class='alert alert-success'>Cập nhật nhật trình thành công!</div>";
            }
            header("Location: chi_tiet.php?id=$id&tab=stat");
            exit;
        }
        elseif ($action === 'update_vehicle_info') {
            $update_file_sql = "";
            $params = [
                $_POST['don_vi_quan_ly'],
                $_POST['loai_xe'], $_POST['nhan_hieu'], $_POST['so_loai'], $_POST['so_may'], $_POST['so_khung'],
                $_POST['nam_san_xuat'] ?: null, $_POST['ngay_dang_ky_lan_dau'] ?: null, $_POST['nam_het_nien_han'] ?: null, $_POST['nguyen_gia'] ?: 0,
                $_POST['cong_thuc_banh_xe'], $_POST['vet_banh_xe'], $_POST['kich_thuoc_bao'], $_POST['kich_thuoc_long_thung'],
                $_POST['the_tich_thung'], $_POST['chieu_dai_co_so'], $_POST['trong_luong_ban_than'] ?: 0, $_POST['trong_tai_cho_phep'] ?: 0,
                $_POST['trong_luong_toan_bo'] ?: 0, $_POST['so_nguoi_cho_phep'] ?: 0, $_POST['loai_nhien_lieu'], $_POST['the_tich_lam_viec'],
                $_POST['cong_suat_lon_nhat'], $_POST['co_lop_truc_1'], $_POST['co_lop_truc_2'], $_POST['thong_so_ac_quy']
            ];

            $file_bao_hiem_paths = [];
            if (isset($_FILES['file_bao_hiem']) && !empty($_FILES['file_bao_hiem']['name'][0])) {
                $bks = "";
                $stmt_bks = $conn->prepare("SELECT bien_kiem_soat FROM vehicles WHERE id = ?");
                $stmt_bks->execute([$id]);
                $v = $stmt_bks->fetch(PDO::FETCH_ASSOC);
                if ($v) $bks = $v['bien_kiem_soat'];

                $count = count($_FILES['file_bao_hiem']['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['file_bao_hiem']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['file_bao_hiem']['tmp_name'][$i];
                        $file_name = $_FILES['file_bao_hiem']['name'][$i];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
                        if (in_array($file_ext, $allowed_exts)) {
                            $new_file_name = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $bks) . '_' . time() . '_' . $i . '.' . $file_ext;
                            $upload_dir = 'uploads/bao_hiem/';
                            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                            $dest_path = $upload_dir . $new_file_name;
                            if (move_uploaded_file($file_tmp, $dest_path)) {
                                $file_bao_hiem_paths[] = $dest_path;
                            }
                        }
                    }
                }
            }
            
            if (!empty($file_bao_hiem_paths)) {
                $update_file_sql = ", file_bao_hiem = ?";
                $params[] = json_encode($file_bao_hiem_paths);
            }

            $params[] = $id;

            // Update vehicle basic information
            $sql = "UPDATE vehicles SET 
                don_vi_quan_ly = ?, loai_xe = ?, nhan_hieu = ?, so_loai = ?, so_may = ?, so_khung = ?,
                nam_san_xuat = ?, ngay_dang_ky_lan_dau = ?, nam_het_nien_han = ?, nguyen_gia = ?,
                cong_thuc_banh_xe = ?, vet_banh_xe = ?, kich_thuoc_bao = ?, kich_thuoc_long_thung = ?,
                the_tich_thung = ?, chieu_dai_co_so = ?, trong_luong_ban_than = ?, trong_tai_cho_phep = ?,
                trong_luong_toan_bo = ?, so_nguoi_cho_phep = ?, loai_nhien_lieu = ?, the_tich_lam_viec = ?,
                cong_suat_lon_nhat = ?, co_lop_truc_1 = ?, co_lop_truc_2 = ?, thong_so_ac_quy = ?
                $update_file_sql
                WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $_SESSION['message'] = "<div class='alert alert-success'>Cập nhật thông tin xe thành công!</div>";
            header("Location: chi_tiet.php?id=$id");
            exit;
        }
        elseif ($action === 'delete_item') {
            // Only admin can delete
            if ($role !== 'admin') {
                $_SESSION['message'] = "<div class='alert alert-danger'>Bạn không có quyền xóa dữ liệu này!</div>";
            } else {
                $table_map = [
                    'tool' => 'vehicle_tools',
                    'assign' => 'vehicle_assignments',
                    'insp' => 'vehicle_inspections',
                    'maint' => 'maintenance_logs',
                    'repair' => 'repair_logs',
                    'tire' => 'tire_logs',
                    'battery' => 'battery_logs',
                    'stat' => 'operation_stats'
                ];
                
                $type = $_POST['type'] ?? '';
                $itemId = $_POST['item_id'] ?? 0;
                
                if (isset($table_map[$type]) && $itemId > 0) {
                    $table = $table_map[$type];
                    // Prepare statement to prevent SQL injection
                    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
                    $stmt->execute([$itemId]);
                    $_SESSION['message'] = "<div class='alert alert-success'>Xóa dữ liệu thành công!</div>";
                } else {
                    $_SESSION['message'] = "<div class='alert alert-danger'>Dữ liệu xóa không hợp lệ!</div>";
                }
                
                // Determine redirect tab based on type
                $redirectTab = 'info';
                switch($type) {
                    case 'tool': $redirectTab = 'tools'; break;
                    case 'assign': $redirectTab = 'assign'; break;
                    case 'insp': $redirectTab = 'insp'; break;
                    case 'maint': $redirectTab = 'maint'; break;
                    case 'repair': $redirectTab = 'repair'; break;
                    case 'tire': $redirectTab = 'tire'; break;
                    case 'battery': $redirectTab = 'battery'; break;
                    case 'stat': $redirectTab = 'stat'; break;
                }
            }
            header("Location: chi_tiet.php?id=$id&tab=$redirectTab");
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi: " . $e->getMessage() . "</div>";
        header("Location: chi_tiet.php?id=$id");
        exit;
    }
}


// --- FETCH DATA ---
try {
    // 1. Core Info
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        echo "<div class='container mt-5'><div class='alert alert-danger'>Xe không tồn tại!</div></div>";
        require_once 'footer.php';
        exit;
    }

    // 2. Sub-tables
    $tools = $conn->prepare("SELECT * FROM vehicle_tools WHERE vehicle_id = ? ORDER BY id DESC");
    $tools->execute([$id]); 
    
    $assigns = $conn->prepare("SELECT * FROM vehicle_assignments WHERE vehicle_id = ? ORDER BY tu_ngay DESC");
    $assigns->execute([$id]);

    $inspections = $conn->prepare("SELECT * FROM vehicle_inspections WHERE vehicle_id = ? ORDER BY hieu_luc_den_ngay DESC");
    $inspections->execute([$id]);

    $maintenances = $conn->prepare("SELECT * FROM maintenance_logs WHERE vehicle_id = ? ORDER BY ngay_bao_duong DESC");
    $maintenances->execute([$id]);
    
    $repairs = $conn->prepare("SELECT * FROM repair_logs WHERE vehicle_id = ? ORDER BY ngay_sua_chua DESC");
    $repairs->execute([$id]);

    $tires = $conn->prepare("SELECT * FROM tire_logs WHERE vehicle_id = ? ORDER BY ngay_thay_the DESC");
    $tires->execute([$id]);

    $batteries = $conn->prepare("SELECT * FROM battery_logs WHERE vehicle_id = ? ORDER BY ngay_thay_the DESC");
    $batteries->execute([$id]);

    $stats = $conn->prepare("SELECT * FROM operation_stats WHERE vehicle_id = ? ORDER BY nam DESC, thang DESC");
    $stats->execute([$id]);

} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}
?>

<div class="container pb-5">
    <?php 
    // Display message from session and clear it
    if (isset($_SESSION['message'])) {
        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
    ?>
    
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-3 me-3">
                <i class="fa-solid fa-truck-front fa-2x"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0"><?= htmlspecialchars($vehicle['bien_kiem_soat']) ?></h2>
                <div class="text-muted">
                    <?= htmlspecialchars($vehicle['loai_xe']) ?> - <?= htmlspecialchars($vehicle['nhan_hieu']) ?>
                    <?php if(isset($vehicle['trang_thai'])): ?>
                        <?php if($vehicle['trang_thai'] == 1): ?>
                            <span class="badge bg-success ms-2">Đang hoạt động</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2">Dừng hoạt động</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary me-2"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
            <button class="btn btn-primary"><i class="fa-solid fa-print"></i> In Hồ sơ</button>
        </div>
    </div>

    <!-- TABS NAVIGATION -->
    <ul class="nav nav-tabs nav-fill mb-4" id="vehicleTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active fw-bold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">Hồ sơ Cố định</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button">Đồ nghề</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign" type="button">Bàn giao sử dụng</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="insp-tab" data-bs-toggle="tab" data-bs-target="#insp" type="button">Đăng kiểm</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="maint-tab" data-bs-toggle="tab" data-bs-target="#maint" type="button">Bảo dưỡng</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="repair-tab" data-bs-toggle="tab" data-bs-target="#repair" type="button">Sửa chữa</button></li>
        
        <!-- Dropdown for extra tabs on small screens or just list them if space allows-->
        <li class="nav-item"><button class="nav-link fw-bold" id="tire-tab" data-bs-toggle="tab" data-bs-target="#tire" type="button">Lốp xe</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="battery-tab" data-bs-toggle="tab" data-bs-target="#battery" type="button">Ắc quy</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="stat-tab" data-bs-toggle="tab" data-bs-target="#stat" type="button">Nhật trình</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="insurance-tab" data-bs-toggle="tab" data-bs-target="#insurance" type="button">Bảo Hiểm</button></li>
    </ul>

    <!-- TABS CONTENT -->
    <div class="tab-content" id="vehicleTabsContent">
        
        <!-- TAB 1: INFO -->
        <div class="tab-pane fade show active" id="info" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Thông tin Hồ sơ Cố định</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditInfo"><i class="fa-solid fa-edit me-1"></i> Chỉnh sửa thông tin</button>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-bold">Thông tin Cơ bản</div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr><td class="text-muted w-50">Biển kiểm soát:</td><td class="fw-bold"><?= $vehicle['bien_kiem_soat'] ?></td></tr>
                                <tr><td class="text-muted">Đơn vị quản lý:</td><td><span class="badge bg-info"><?= $vehicle['don_vi_quan_ly'] ?: 'Chưa xác định' ?></span></td></tr>
                                <tr><td class="text-muted">Nhãn hiệu:</td><td><?= $vehicle['nhan_hieu'] ?></td></tr>
                                <tr><td class="text-muted">Loại xe:</td><td><?= $vehicle['loai_xe'] ?></td></tr>
                                <tr><td class="text-muted">Số loại:</td><td><?= $vehicle['so_loai'] ?></td></tr>
                                <tr><td class="text-muted">Số khung:</td><td><?= $vehicle['so_khung'] ?></td></tr>
                                <tr><td class="text-muted">Số máy:</td><td><?= $vehicle['so_may'] ?></td></tr>
                                <tr><td class="text-muted">Năm sản xuất:</td><td><?= $vehicle['nam_san_xuat'] ?></td></tr>
                                <tr><td class="text-muted">Nguyên giá:</td><td class="text-success fw-bold"><?= number_format($vehicle['nguyen_gia'] ?? 0) ?> đ</td></tr>
                                <tr><td class="text-muted">Giấy CN Bảo hiểm:</td><td>
                                    <?php 
                                    if (!empty($vehicle['file_bao_hiem'])): 
                                        $paths = json_decode($vehicle['file_bao_hiem'], true) ?: [$vehicle['file_bao_hiem']];
                                        echo "<span class='badge bg-info'>" . count($paths) . " file đính kèm</span>";
                                    else: 
                                    ?>
                                        <span class="text-muted fst-italic">Chưa có</span>
                                    <?php endif; ?>
                                </td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-bold">Thông số Kỹ thuật</div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm">
                                <tr><td class="text-muted w-50">Kích thước xe:</td><td><?= $vehicle['kich_thuoc_bao'] ?></td></tr>
                                <tr><td class="text-muted">Tự trọng:</td><td><?= $vehicle['trong_luong_ban_than'] ?? '' ?> kg</td></tr>
                                <tr><td class="text-muted">Tải trọng:</td><td><?= $vehicle['trong_tai_cho_phep'] ?? '' ?> kg</td></tr>
                                <tr><td class="text-muted">Số người:</td><td><?= $vehicle['so_nguoi_cho_phep'] ?></td></tr>
                                <tr><td class="text-muted">Nhiên liệu:</td><td><?= $vehicle['loai_nhien_lieu'] ?></td></tr>
                                <tr><td class="text-muted">Công suất:</td><td><?= $vehicle['cong_suat_lon_nhat'] ?></td></tr>
                                <tr><td class="text-muted">Vết bánh xe:</td><td><?= $vehicle['vet_banh_xe'] ?></td></tr>
                                <tr><td class="text-muted">Cỡ lốp:</td><td><?= $vehicle['co_lop_truc_1'] ?> / <?= $vehicle['co_lop_truc_2'] ?></td></tr>
                                <tr><td class="text-muted">Thông số Ắc quy:</td><td><?= $vehicle['thong_so_ac_quy'] ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: TOOLS -->
        <div class="tab-pane fade" id="tools" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Danh sách Dụng cụ theo xe</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTool">+ Thêm dụng cụ</button>
            </div>
            <table class="table table-bordered table-hover">
                <thead class="table-light"><tr><th>Tên dụng cụ</th><th>Số lượng</th><th>Ngày cấp</th><th>Ghi chú</th><?php if($role === 'admin'): ?><th width="50" class="text-center"><i class="fa-solid fa-cog"></i></th><?php endif; ?></tr></thead>
                <tbody>
                    <?php while($row = $tools->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ten_dung_cu']) ?></td>
                        <td><?= htmlspecialchars($row['so_luong']) ?></td>
                        <td><?= htmlspecialchars($row['ngay_cap']) ?></td>
                        <td><?= htmlspecialchars($row['ghi_chu']) ?></td>
                        <?php if($role === 'admin'): ?>
                        <td class="text-center">
                            <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa dụng cụ này?');">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="type" value="tool">
                                <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 3: ASSIGNMENTS -->
        <div class="tab-pane fade" id="assign" role="tabpanel">
             <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Lịch sử Điều chuyển & Bàn giao</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAssign">+ Bàn giao xe</button>
            </div>
            <table class="table table-bordered table-striped">
                <thead class="table-light"><tr><th>Đối tượng</th><th>Tên Đơn vị / Người lái</th><th>Từ ngày</th><th>Số KM bàn giao</th><th>Đến ngày</th><th>Trạng thái</th><?php if($role === 'admin'): ?><th width="50" class="text-center"><i class="fa-solid fa-cog"></i></th><?php endif; ?></tr></thead>
                <tbody>
                    <?php while($row = $assigns->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><span class="badge bg-info"><?= htmlspecialchars($row['loai_doi_tuong']) ?></span></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['ten_doi_tuong']) ?></td>
                        <td><?= htmlspecialchars($row['tu_ngay']) ?></td>
                        <td class="text-center">
                            <?php if($row['so_km_ban_giao']): ?>
                                <span class="badge bg-primary"><?= number_format($row['so_km_ban_giao']) ?> km</span>
                            <?php else: ?>
                                <span class="text-muted">---</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['den_ngay'] ?? 'Nay') ?></td>
                        <td>
                            <?php if(empty($row['den_ngay'])): ?>
                                <span class="badge bg-success">Đang quản lý</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Đã kết thúc</span>
                            <?php endif; ?>
                        </td>
                        <?php if($role === 'admin'): ?>
                        <td class="text-center">
                            <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa quyết định bàn giao này?');">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="type" value="assign">
                                <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 4: INSPECTIONS -->
        <div class="tab-pane fade" id="insp" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Theo dõi Đăng kiểm</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalInsp">+ Thêm phiếu ĐK</button>
            </div>
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>Số sổ ĐK</th><th>Ngày hiệu lực</th><th>Ngày hết hạn</th><th>Đơn vị thực hiện</th><th>Trạng thái</th><?php if($role === 'admin'): ?><th width="50" class="text-center"><i class="fa-solid fa-cog"></i></th><?php endif; ?></tr></thead>
                <tbody>
                    <?php while($row = $inspections->fetch(PDO::FETCH_ASSOC)): 
                        $exp = new DateTime($row['hieu_luc_den_ngay']);
                        $now = new DateTime();
                        $diff = $now->diff($exp)->format("%r%a"); // Số ngày còn lại
                    ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($row['so_so_dang_kiem']) ?></td>
                        <td><?= htmlspecialchars($row['hieu_luc_tu_ngay']) ?></td>
                        <td><?= htmlspecialchars($row['hieu_luc_den_ngay']) ?></td>
                        <td><?= htmlspecialchars($row['don_vi_dang_kiem']) ?></td>
                        <td>
                            <?php if((int)$diff < 0): ?>
                                <span class="badge bg-danger">Đã hết hạn</span>
                            <?php elseif((int)$diff < 30): ?>
                                <span class="badge bg-warning text-dark">Sắp hết hạn (<?= $diff ?> ngày)</span>
                            <?php else: ?>
                                <span class="badge bg-success">Còn hạn</span>
                            <?php endif; ?>
                        </td>
                        <?php if($role === 'admin'): ?>
                        <td class="text-center align-middle">
                            <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa phiếu đăng kiểm này?');">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="type" value="insp">
                                <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- TAB 5: MAINTENANCE -->
        <div class="tab-pane fade" id="maint" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Nhật ký Bảo dưỡng</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMaint">+ Ghi nhận Bảo dưỡng</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Ngày BD</th>
                            <th>Cấp BD (km)</th>
                            <th>Km thực tế</th>
                            <th>Nội dung / Vật tư</th>
                            <th>ĐVT</th>
                            <th>SL</th>
                            <th>Thành tiền</th>
                            <th>Nơi thực hiện</th>
                            <?php if($role === 'admin'): ?><th width="50" class="text-center"><i class="fa-solid fa-cog"></i></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $maintenances->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= $row['ngay_bao_duong'] ?></td>
                            <td><?= number_format($row['cap_bao_duong_km'] ?? 0) ?></td>
                            <td><?= number_format($row['km_thuc_te'] ?? 0) ?></td>
                            <td><?= $row['noi_dung_vat_tu'] ?></td>
                            <td><?= $row['don_vi_tinh'] ?></td>
                            <td><?= $row['so_luong'] ?></td>
                            <td class="fw-bold text-end"><?= number_format($row['thanh_tien'] ?? 0) ?></td>
                            <td><?= $row['noi_thuc_hien'] ?></td>
                            <?php if($role === 'admin'): ?>
                            <td class="text-center">
                                <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa mục bảo dưỡng này?');">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="type" value="maint">
                                    <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 6: REPAIRS -->
        <div class="tab-pane fade" id="repair" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Nhật ký Sửa chữa</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRepair">+ Ghi nhận Sửa chữa</button>
            </div>
            
            <?php 
            // Group repairs by date
            $repairsByDate = [];
            $repairs->execute([$id]); // Re-execute to reset cursor
            while($row = $repairs->fetch(PDO::FETCH_ASSOC)) {
                $date = $row['ngay_sua_chua'];
                if (!isset($repairsByDate[$date])) {
                    $repairsByDate[$date] = [];
                }
                $repairsByDate[$date][] = $row;
            }
            
            if (empty($repairsByDate)): ?>
                <div class="alert alert-info">Chưa có dữ liệu sửa chữa</div>
            <?php else: ?>
                <div class="accordion" id="repairAccordion">
                    <?php 
                    $index = 0;
                    foreach($repairsByDate as $date => $repairs): 
                        // Calculate total for this date
                        $dateTotal = 0;
                        foreach($repairs as $repair) {
                            $dateTotal += $repair['thanh_tien'] ?? 0;
                        }
                        $dateVAT = $dateTotal * 0.08;
                        $dateGrandTotal = $dateTotal + $dateVAT;
                        $index++;
                    ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                <button class="accordion-button <?= $index > 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="<?= $index == 1 ? 'true' : 'false' ?>" aria-controls="collapse<?= $index ?>">
                                    <div class="d-flex justify-content-between w-100 pe-3">
                                        <span>
                                            <i class="fa-solid fa-calendar-day me-2"></i>
                                            <strong>Ngày: <?= date('d/m/Y', strtotime($date)) ?></strong>
                                            <span class="badge bg-secondary ms-2"><?= count($repairs) ?> mục</span>
                                        </span>
                                        <span class="text-success fw-bold">
                                            Tổng cộng: <?= number_format($dateGrandTotal) ?> đ
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index == 1 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#repairAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Loại</th>
                                                    <th>Nội dung SC</th>
                                                    <th>Xuất xứ VT</th>
                                                    <th>ĐVT</th>
                                                    <th>SL</th>
                                                    <th>Đơn giá</th>
                                                    <th>Thành tiền</th>
                                                    <th>Căn cứ Tờ trình</th>
                                                    <th>Nơi thực hiện</th>
                                                    <th>Bằng chứng</th>
                                                    <?php if($role === 'admin'): ?><th width="50" class="text-center"><i class="fa-solid fa-cog"></i></th><?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($repairs as $row): ?>
                                                <tr>
                                                    <td><span class="badge bg-secondary"><?= $row['loai_sua_chua'] ?></span></td>
                                                    <td><?= $row['noi_dung_sua_chua'] ?></td>
                                                    <td><?= $row['xuat_xu_vat_tu'] ?></td>
                                                    <td><?= $row['don_vi_tinh'] ?></td>
                                                    <td><?= $row['so_luong'] ?></td>
                                                    <td class="text-end"><?= number_format($row['don_gia'] ?? 0) ?></td>
                                                    <td class="fw-bold text-end"><?= number_format($row['thanh_tien'] ?? 0) ?></td>
                                                    <td><?= $row['can_cu_to_trinh'] ?></td>
                                                    <td><?= $row['noi_thuc_hien'] ?></td>
                                                    <td class="text-center">
                                                        <?php if(!empty($row['anh_bang_chung'])): 
                                                            $anh_paths = json_decode($row['anh_bang_chung'], true);
                                                            if($anh_paths):
                                                        ?>
                                                            <button type="button" class="btn btn-sm btn-outline-info" onclick='showEvidence(<?= json_encode($anh_paths) ?>)' title="Xem ảnh"><i class="fa-solid fa-image"></i> (<?= count($anh_paths) ?>)</button>
                                                        <?php endif; endif; ?>
                                                    </td>
                                                    <?php if($role === 'admin'): ?>
                                                    <td class="text-center">
                                                        <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa mục sửa chữa này?');">
                                                            <input type="hidden" name="action" value="delete_item">
                                                            <input type="hidden" name="type" value="repair">
                                                            <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                                            <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                                                        </form>
                                                    </td>
                                                    <?php endif; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <td colspan="6" class="text-end fw-bold">Tổng thành tiền:</td>
                                                    <td class="text-end fw-bold text-primary"><?= number_format($dateTotal) ?> đ</td>
                                                    <td colspan="<?= $role === 'admin' ? '4' : '3' ?>"></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6" class="text-end fw-bold">VAT (8%):</td>
                                                    <td class="text-end fw-bold text-info"><?= number_format($dateVAT) ?> đ</td>
                                                    <td colspan="<?= $role === 'admin' ? '4' : '3' ?>"></td>
                                                </tr>
                                                <tr class="table-success">
                                                    <td colspan="6" class="text-end fw-bold">Tổng cộng:</td>
                                                    <td class="text-end fw-bold text-success"><?= number_format($dateGrandTotal) ?> đ</td>
                                                    <td colspan="<?= $role === 'admin' ? '4' : '3' ?>"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- TAB 7: TIRES -->
        <div class="tab-pane fade" id="tire" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Theo dõi Săm Lốp</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTire">+ Thay lốp mới</button>
            </div>
            <table class="table table-bordered table-striped">
                <thead class="table-light"><tr><th>Ngày thay</th><th>Quy cách</th><th>SL</th><th>Odo thay (km)</th><th>Odo lốp cũ (km)</th><th>Hãng SX (Mới)</th><?php if($role === 'admin'): ?><th width="50" class="text-center"><i class="fa-solid fa-cog"></i></th><?php endif; ?></tr></thead>
                <tbody>
                    <?php while($row = $tires->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ngay_thay_the']) ?></td>
                        <td><?= htmlspecialchars($row['quy_cach_lop']) ?></td>
                        <td><?= htmlspecialchars($row['so_luong_thay']) ?></td>
                        <td><?= number_format($row['km_khi_thay'] ?? 0) ?></td>
                        <td><?= number_format($row['km_da_chay'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($row['hang_sx_lop_moi']) ?></td>
                        <?php if($role === 'admin'): ?>
                        <td class="text-center">
                            <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa phiếu thay lốp này?');">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="type" value="tire">
                                <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 8: BATTERY -->
        <div class="tab-pane fade" id="battery" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Theo dõi Ắc Quy</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBattery">+ Thay Ắc quy</button>
            </div>
            <table class="table table-bordered table-striped">
                <thead class="table-light"><tr><th>Ngày thay</th><th>Thông số bình</th><th>SL</th><th>Tuổi thọ bình cũ</th><th>Hãng SX (Mới)</th><th>Ghi chú</th><?php if($role === 'admin'): ?><th width="50" class="text-center"><i class="fa-solid fa-cog"></i></th><?php endif; ?></tr></thead>
                <tbody>
                    <?php while($row = $batteries->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ngay_thay_the']) ?></td>
                        <td><?= htmlspecialchars($row['quy_cach_binh']) ?></td>
                        <td><?= htmlspecialchars($row['so_luong_thay']) ?></td>
                        <td><?= htmlspecialchars($row['thoi_gian_da_su_dung']) ?></td>
                        <td><?= htmlspecialchars($row['hang_sx_moi']) ?></td>
                        <td><?= htmlspecialchars($row['ghi_chu']) ?></td>
                        <?php if($role === 'admin'): ?>
                        <td class="text-center">
                            <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa phiếu thay ắc quy này?');">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="type" value="battery">
                                <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 9: STATS -->
        <div class="tab-pane fade" id="stat" role="tabpanel">
             <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Nhật trình Hoạt động</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalStat">+ Cập nhật Tháng</button>
            </div>
            <table class="table table-bordered text-center">
                <thead class="table-dark">
                    <tr><th>Năm - Tháng</th><th>Số chuyến</th><th>Số KM vận hành</th><th>KM tích lũy</th><?php if($role === 'admin'): ?><th width="50" class="text-center"><i class="fa-solid fa-cog"></i></th><?php endif; ?></tr>
                </thead>
                <tbody>
                    <?php while($row = $stats->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td class="fw-bold"><?= $row['thang'] ?> / <?= $row['nam'] ?></td>
                        <td><?= number_format($row['so_chuyen_trong_thang'] ?? 0) ?></td>
                        <td><?= number_format($row['km_trong_thang'] ?? 0) ?> km</td>
                        <td class="fw-bold text-success"><?= number_format($row['km_tich_luy'] ?? 0) ?> km</td>
                        <?php if($role === 'admin'): ?>
                        <td class="text-center">
                            <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa số liệu này?');">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="type" value="stat">
                                <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 10: INSURANCE -->
        <div class="tab-pane fade" id="insurance" role="tabpanel">
             <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Giấy Chứng Nhận Bảo Hiểm</h5>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (!empty($vehicle['file_bao_hiem'])): 
                        $paths = json_decode($vehicle['file_bao_hiem'], true) ?: [$vehicle['file_bao_hiem']];
                        foreach($paths as $path):
                            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])):
                        ?>
                            <div class="text-center mb-4">
                                <img src="<?= htmlspecialchars($path) ?>" class="img-fluid rounded border shadow-sm" alt="Giấy chứng nhận bảo hiểm">
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <iframe src="<?= htmlspecialchars($path) ?>" width="100%" height="800px" style="border: none;"></iframe>
                            </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Chưa có file chứng nhận bảo hiểm cho xe này. Bạn có thể thêm trong phần Chỉnh sửa thông tin Hồ sơ cố định.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div> <!-- End Tab Content -->
</div>

<!-- ================= MODALS ================= -->

<!-- Modal Add Tool -->
<div class="modal fade" id="modalTool" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" value="add_tool">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Dụng cụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Common Date -->
                 <div class="mb-3 row align-items-center">
                    <label class="col-sm-3 col-form-label fw-bold">Ngày cấp chung:</label>
                    <div class="col-sm-4">
                        <input type="date" class="form-control" name="ngay_cap" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-2">Danh sách Dụng cụ</h6>
                <div id="tool-items-container">
                    <div class="tool-item row border rounded p-2 mb-2 bg-white shadow-sm position-relative">
                         <div class="col-md-5 mb-2">
                            <label class="small text-muted fw-bold">Tên dụng cụ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ten_dung_cu[]" required placeholder="Tên kìm, búa...">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small text-muted">Số lượng</label>
                            <input type="number" class="form-control" name="so_luong[]" value="1">
                        </div>
                        <div class="col-md-5 mb-2">
                            <label class="small text-muted">Ghi chú</label>
                            <input type="text" class="form-control" name="ghi_chu[]" placeholder="Tình trạng, ghi chú...">
                        </div>
                    </div>
                </div>
                 <div class="d-flex justify-content-center mt-3">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4" onclick="addToolRow()"><i class="fa-solid fa-plus-circle me-1"></i> Thêm dòng dụng cụ</button>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu lại</button></div>
        </form>
    </div>
</div>

<!-- Modal Assignment -->
<div class="modal fade" id="modalAssign" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" value="add_assignment">
            <div class="modal-header">
                <h5 class="modal-title">Bàn giao Xe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Loại đối tượng</label>
                    <select class="form-select" name="loai_doi_tuong">
                        <option value="DON_VI">Đơn vị / Phòng ban</option>
                        <option value="NGUOI_LAI">Lái xe riêng</option>
                    </select>
                </div>
                <div class="mb-3"><label>Tên Đơn vị / Người nhận</label><input type="text" class="form-control" name="ten_doi_tuong" required></div>
                <div class="mb-3"><label>Từ ngày</label><input type="date" class="form-control" name="tu_ngay" value="<?= date('Y-m-d') ?>" required></div>
                <div class="mb-3">
                    <label>Số KM lúc bàn giao</label>
                    <input type="number" class="form-control" name="so_km_ban_giao" placeholder="VD: 25000">
                    <small class="text-muted">Số km đã chạy trên đồng hồ xe lúc bàn giao</small>
                </div>
                <div class="mb-3"><label>Đến ngày (Để trống nếu chưa biết)</label><input type="date" class="form-control" name="den_ngay"></div>
                <div class="mb-3"><label>Ghi chú</label><textarea class="form-control" name="ghi_chu"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu lại</button></div>
        </form>
    </div>
</div>

<!-- Modal Inspection -->
<div class="modal fade" id="modalInsp" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" value="add_inspection">
            <div class="modal-header">
                <h5 class="modal-title">Thêm phiếu Đăng kiểm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label>Số sổ Đăng kiểm</label><input type="text" class="form-control" name="so_so_dang_kiem" required></div>
                <div class="mb-3"><label>Hiệu lực từ</label><input type="date" class="form-control" name="hieu_luc_tu_ngay" required></div>
                <div class="mb-3"><label>Hết hạn ngày</label><input type="date" class="form-control" name="hieu_luc_den_ngay" required></div>
                <div class="mb-3"><label>Đơn vị thực hiện</label><input type="text" class="form-control" name="don_vi_dang_kiem"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu lại</button></div>
        </form>
    </div>
</div>

<!-- Modal Maintenance -->
<div class="modal fade" id="modalMaint" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" value="add_maintenance">
            <div class="modal-header">
                <h5 class="modal-title">Ghi nhận Bảo dưỡng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label>Ngày bảo dưỡng</label><input type="date" class="form-control" name="ngay_bao_duong" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-6 mb-3"><label>Nơi thực hiện</label><input type="text" class="form-control" name="noi_thuc_hien"></div>
                    <div class="col-md-6 mb-3"><label>Cấp bảo dưỡng (Km)</label><input type="number" class="form-control" name="cap_bao_duong_km" placeholder="VD: 5000"></div>
                    <div class="col-md-6 mb-3"><label>Số Km thực tế</label><input type="number" class="form-control" name="km_thuc_te"></div>
                    
                    <div class="col-12 mb-3"><label>Nội dung công việc / Vật tư</label><input type="text" class="form-control" name="noi_dung_vat_tu" required></div>
                    
                    <div class="col-md-4 mb-3"><label>Đơn vị tính</label><input type="text" class="form-control" name="don_vi_tinh" placeholder="Cái, Lít..."></div>
                    <div class="col-md-4 mb-3"><label>Số lượng</label><input type="number" step="0.01" class="form-control" name="so_luong" value="1"></div>
                    <div class="col-md-4 mb-3"><label>Đơn giá</label><input type="number" class="form-control" name="don_gia" placeholder="VNĐ"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu phiếu</button></div>
        </form>
    </div>
</div>

<!-- Modal Repair -->
<div class="modal fade" id="modalRepair" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_repair">
            <div class="modal-header">
                <h5 class="modal-title">Ghi nhận Sửa chữa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Common Info -->
                <div class="card bg-light mb-3">
                    <div class="card-body py-2">
                        <div class="row">
                            <div class="col-md-3 mb-2"><label class="fw-bold small">Ngày sửa chữa</label><input type="date" class="form-control" name="ngay_sua_chua" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-md-3 mb-2"><label class="fw-bold small">Phân loại</label>
                                <select class="form-select" name="loai_sua_chua">
                                    <option value="THUONG_XUYEN">Sửa chữa Thường xuyên</option>
                                    <option value="TAP_TRUNG">Sửa chữa Tập trung (Lớn)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2"><label class="fw-bold small">Căn cứ Tờ trình/QĐ</label><input type="text" class="form-control" name="can_cu_to_trinh" placeholder="Nhập số tờ trình..."></div>
                        </div>
                    </div>
                </div>

                <div class="card bg-light mb-3">
                    <div class="card-body py-2">
                        <label class="fw-bold small">Ảnh bằng chứng (Hóa đơn, hình xe hỏng...)</label>
                        <div class="p-2 border rounded bg-white position-relative" id="paste_area_repair" style="border: 2px dashed #0d6efd !important;">
                            <input type="file" class="form-control mb-2" name="anh_bang_chung[]" id="file_anh_sc" accept=".jpg,.jpeg,.png" multiple>
                            <div class="form-text text-center" style="font-size: 0.8rem;"><i class="fa-solid fa-paste me-1"></i> Có thể <strong>Ctrl+V</strong> dán nhiều ảnh</div>
                            <div class="text-center" id="preview_anh_sc_container">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <h6 class="fw-bold text-primary mb-2">Danh sách Nội dung Sửa chữa</h6>
                <div id="repair-items-container">
                    <!-- Default one item -->
                    <div class="repair-item row border rounded p-2 mb-2 bg-white shadow-sm position-relative">
                        <div class="col-md-12 mb-2">
                            <label class="small text-muted fw-bold">Nội dung công việc</label>
                            <textarea class="form-control" name="noi_dung_sua_chua[]" rows="1" placeholder="Nhập nội dung sửa chữa..."></textarea>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted">Xuất xứ / Vật tư</label>
                            <input type="text" class="form-control form-control-sm" name="xuat_xu_vat_tu[]" placeholder="Xuất xứ">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small text-muted">ĐVT</label>
                            <input type="text" class="form-control form-control-sm" name="don_vi_tinh[]" placeholder="Cái/Lít">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small text-muted">Số lượng</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="so_luong[]" value="1" onchange="calculateRepairTotal()" oninput="calculateRepairTotal()">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small text-muted">Đơn giá</label>
                            <input type="number" class="form-control form-control-sm" name="don_gia[]" placeholder="VNĐ" onchange="calculateRepairTotal()" oninput="calculateRepairTotal()">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted">Nơi thực hiện</label>
                            <input type="text" class="form-control form-control-sm" name="noi_thuc_hien[]" placeholder="Tên đơn vị thực hiện">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4" onclick="addRepairRow()"><i class="fa-solid fa-plus-circle me-1"></i> Thêm dòng nội dung</button>
                </div>
                
                <!-- Tổng tiền -->
                <div class="card bg-light mt-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 text-end">
                                <h6 class="mb-2">Tổng thành tiền:</h6>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-2 text-primary" id="subtotal-display">0 đ</h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 text-end">
                                <h6 class="mb-2">VAT (8%):</h6>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-2 text-info" id="vat-display">0 đ</h6>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-8 text-end">
                                <h5 class="fw-bold mb-0">Tổng cộng:</h5>
                            </div>
                            <div class="col-md-4">
                                <h5 class="fw-bold mb-0 text-success" id="total-display">0 đ</h5>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu phiếu</button></div>
        </form>
    </div>
</div>

<!-- Modal Tire -->
<div class="modal fade" id="modalTire" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" value="add_tire">
            <div class="modal-header">
                <h5 class="modal-title">Thay Lốp mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label>Ngày thay thế</label><input type="date" class="form-control" name="ngay_thay_the" value="<?= date('Y-m-d') ?>" required></div>
                <div class="mb-3"><label>Quy cách lốp</label><input type="text" class="form-control" name="quy_cach_lop" placeholder="VD: 10.00R20"></div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label>Số lượng thay</label><input type="number" class="form-control" name="so_luong_thay" value="1"></div>
                    <div class="col-md-6 mb-3"><label>Số lốp dự phòng</label><input type="number" class="form-control" name="so_lop_du_phong" value="1"></div>
                </div>
                <div class="row">
                     <div class="col-md-6 mb-3"><label>KM khi thay (Odo)</label><input type="number" class="form-control" name="km_khi_thay"></div>
                     <div class="col-md-6 mb-3"><label>Tuổi thọ lốp cũ (Km)</label><input type="number" class="form-control" name="km_da_chay"></div>
                </div>
                <div class="mb-3"><label>Hãng SX lốp mới</label><input type="text" class="form-control" name="hang_sx_lop_moi"></div>
                <div class="mb-3"><label>Số Seri lốp mới</label><input type="text" class="form-control" name="so_seri_lop_moi"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu lại</button></div>
        </form>
    </div>
</div>

<!-- Modal Battery -->
<div class="modal fade" id="modalBattery" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" value="add_battery">
            <div class="modal-header">
                <h5 class="modal-title">Thay Ắc quy mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
               <div class="mb-3"><label>Ngày thay thế</label><input type="date" class="form-control" name="ngay_thay_the" value="<?= date('Y-m-d') ?>" required></div>
               <div class="mb-3"><label>Quy cách bình</label><input type="text" class="form-control" name="quy_cach_binh" placeholder="VD: 12V-100Ah"></div>
               <div class="row">
                    <div class="col-md-6 mb-3"><label>Số lượng thay</label><input type="number" class="form-control" name="so_luong_thay" value="1"></div>
                    <div class="col-md-6 mb-3"><label>Thời gian đã dùng</label><input type="text" class="form-control" name="thoi_gian_da_su_dung" placeholder="VD: 12 tháng"></div>
               </div>
               <div class="mb-3"><label>Hãng SX mới</label><input type="text" class="form-control" name="hang_sx_moi"></div>
               <div class="mb-3"><label>Số seri mới</label><input type="text" class="form-control" name="so_seri_moi"></div>
               <div class="mb-3"><label>Ghi chú</label><input type="text" class="form-control" name="ghi_chu"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu lại</button></div>
        </form>
    </div>
</div>

<!-- Modal Stats -->
<div class="modal fade" id="modalStat" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" value="add_stat">
            <div class="modal-header">
                <h5 class="modal-title">Cập nhật Nhật trình Tháng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Tháng</label>
                        <select class="form-select" name="thang">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                     <div class="col-md-6 mb-3">
                        <label>Năm</label>
                        <input type="number" class="form-control" name="nam" value="<?= date('Y') ?>">
                    </div>
                </div>
                <div class="mb-3"><label>Số chuyến trong tháng</label><input type="number" class="form-control" name="so_chuyen_trong_thang" value="0"></div>
                <div class="mb-3"><label>Số KM vận hành trong tháng</label><input type="number" class="form-control" name="km_trong_thang" value="0"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu lại</button></div>
        </form>
    </div>
</div>
</div>

<!-- Modal Show Evidence -->
<div class="modal fade" id="modalEvidence" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ảnh Bằng Chứng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="evidence_body">
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Vehicle Info -->
<div class="modal fade" id="modalEditInfo" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_vehicle_info">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-edit me-2"></i>Chỉnh sửa Hồ sơ Cố định</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <!-- Section 1: Thông tin Định danh -->
                    <div class="col-12">
                        <h6 class="text-primary border-bottom pb-2"><i class="fa-solid fa-id-card me-2"></i>Thông tin Định danh</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Biển kiểm soát</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($vehicle['bien_kiem_soat']) ?>" disabled>
                        <small class="text-muted">Không thể thay đổi biển số</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Đơn vị quản lý</label>
                        <input type="text" class="form-control" name="don_vi_quan_ly" value="<?= htmlspecialchars($vehicle['don_vi_quan_ly'] ?? '') ?>" placeholder="VD: Bưu điện Hà Nội">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Loại xe</label>
                        <input type="text" class="form-control" name="loai_xe" value="<?= htmlspecialchars($vehicle['loai_xe'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nhãn hiệu</label>
                        <input type="text" class="form-control" name="nhan_hieu" value="<?= htmlspecialchars($vehicle['nhan_hieu'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Số loại (Model)</label>
                        <input type="text" class="form-control" name="so_loai" value="<?= htmlspecialchars($vehicle['so_loai'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Số khung</label>
                        <input type="text" class="form-control" name="so_khung" value="<?= htmlspecialchars($vehicle['so_khung'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Số máy</label>
                        <input type="text" class="form-control" name="so_may" value="<?= htmlspecialchars($vehicle['so_may'] ?? '') ?>">
                    </div>

                    <!-- Section 2: Tài chính & Thời hạn -->
                    <div class="col-12 mt-4">
                        <h6 class="text-success border-bottom pb-2"><i class="fa-solid fa-money-bill me-2"></i>Tài chính & Thời hạn</h6>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Năm sản xuất</label>
                        <input type="number" class="form-control" name="nam_san_xuat" value="<?= htmlspecialchars($vehicle['nam_san_xuat'] ?? '') ?>" min="1950" max="<?= date('Y') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Năm hết niên hạn</label>
                        <input type="number" class="form-control" name="nam_het_nien_han" value="<?= htmlspecialchars($vehicle['nam_het_nien_han'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ngày đăng ký lần đầu</label>
                        <input type="date" class="form-control" name="ngay_dang_ky_lan_dau" value="<?= htmlspecialchars($vehicle['ngay_dang_ky_lan_dau'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nguyên giá (VNĐ)</label>
                        <input type="number" class="form-control" name="nguyen_gia" value="<?= htmlspecialchars($vehicle['nguyen_gia'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Giấy CN Bảo hiểm (PDF/Ảnh)</label>
                        <div class="p-2 border rounded bg-light position-relative" id="paste_area_edit" style="border: 2px dashed #0d6efd !important;">
                            <input type="file" class="form-control mb-2" name="file_bao_hiem[]" id="file_bao_hiem_edit" accept=".pdf,.jpg,.jpeg,.png" multiple>
                            <div class="form-text text-center" style="font-size: 0.8rem;"><i class="fa-solid fa-paste me-1"></i> Có thể <strong>Ctrl+V</strong> dán nhiều ảnh</div>
                            <div class="text-center" id="preview_bao_hiem_edit_container">
                            </div>
                        </div>
                        <?php if (!empty($vehicle['file_bao_hiem'])): 
                            $paths = json_decode($vehicle['file_bao_hiem'], true) ?: [$vehicle['file_bao_hiem']];
                        ?>
                            <small class="text-muted d-block mt-1">Đang có <?= count($paths) ?> file (Tải lên để thay thế toàn bộ)</small>
                        <?php endif; ?>
                    </div>

                    <!-- Section 3: Kích thước & Trọng lượng -->
                    <div class="col-12 mt-4">
                        <h6 class="text-dark border-bottom pb-2"><i class="fa-solid fa-ruler-combined me-2"></i>Kích thước & Trọng lượng</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kích thước xe (D x R x C)</label>
                        <input type="text" class="form-control" name="kich_thuoc_bao" value="<?= htmlspecialchars($vehicle['kich_thuoc_bao'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kích thước lòng thùng</label>
                        <input type="text" class="form-control" name="kich_thuoc_long_thung" value="<?= htmlspecialchars($vehicle['kich_thuoc_long_thung'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Chiều dài cơ sở</label>
                        <input type="text" class="form-control" name="chieu_dai_co_so" value="<?= htmlspecialchars($vehicle['chieu_dai_co_so'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vết bánh xe</label>
                        <input type="text" class="form-control" name="vet_banh_xe" value="<?= htmlspecialchars($vehicle['vet_banh_xe'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Công thức bánh xe</label>
                        <input type="text" class="form-control" name="cong_thuc_banh_xe" value="<?= htmlspecialchars($vehicle['cong_thuc_banh_xe'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Trọng lượng bản thân (kg)</label>
                        <input type="number" step="0.01" class="form-control" name="trong_luong_ban_than" value="<?= htmlspecialchars($vehicle['trong_luong_ban_than'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tải trọng cho phép (kg)</label>
                        <input type="number" step="0.01" class="form-control" name="trong_tai_cho_phep" value="<?= htmlspecialchars($vehicle['trong_tai_cho_phep'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tổng trọng lượng (kg)</label>
                        <input type="number" step="0.01" class="form-control" name="trong_luong_toan_bo" value="<?= htmlspecialchars($vehicle['trong_luong_toan_bo'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số người cho phép</label>
                        <input type="number" class="form-control" name="so_nguoi_cho_phep" value="<?= htmlspecialchars($vehicle['so_nguoi_cho_phep'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Thể tích thùng (m3)</label>
                        <input type="text" class="form-control" name="the_tich_thung" value="<?= htmlspecialchars($vehicle['the_tich_thung'] ?? '') ?>">
                    </div>

                    <!-- Section 4: Động cơ & Nhiên liệu -->
                    <div class="col-12 mt-4">
                        <h6 class="text-warning border-bottom pb-2"><i class="fa-solid fa-gas-pump me-2"></i>Động cơ & Nhiên liệu</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Loại nhiên liệu</label>
                        <select class="form-select" name="loai_nhien_lieu">
                            <option value="Diesel" <?= $vehicle['loai_nhien_lieu'] == 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                            <option value="Xăng" <?= $vehicle['loai_nhien_lieu'] == 'Xăng' ? 'selected' : '' ?>>Xăng</option>
                            <option value="Điện" <?= $vehicle['loai_nhien_lieu'] == 'Điện' ? 'selected' : '' ?>>Điện</option>
                            <option value="Khác" <?= $vehicle['loai_nhien_lieu'] == 'Khác' ? 'selected' : '' ?>>Khác</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Thể tích làm việc</label>
                        <input type="text" class="form-control" name="the_tich_lam_viec" value="<?= htmlspecialchars($vehicle['the_tich_lam_viec'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Công suất lớn nhất</label>
                        <input type="text" class="form-control" name="cong_suat_lon_nhat" value="<?= htmlspecialchars($vehicle['cong_suat_lon_nhat'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Thông số Ắc quy</label>
                        <input type="text" class="form-control" name="thong_so_ac_quy" value="<?= htmlspecialchars($vehicle['thong_so_ac_quy'] ?? '') ?>">
                    </div>

                    <!-- Section 5: Thông số Lốp -->
                    <div class="col-12 mt-4">
                        <h6 class="text-info border-bottom pb-2"><i class="fa-solid fa-circle-notch me-2"></i>Thông số Lốp</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cỡ lốp trục 1</label>
                        <input type="text" class="form-control" name="co_lop_truc_1" value="<?= htmlspecialchars($vehicle['co_lop_truc_1'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cỡ lốp trục 2</label>
                        <input type="text" class="form-control" name="co_lop_truc_2" value="<?= htmlspecialchars($vehicle['co_lop_truc_2'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-active tab based on URL parameter
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tabName = urlParams.get('tab');
    
    if (tabName) {
        const triggerEl = document.querySelector(`button[data-bs-target="#${tabName}"]`);
        if (triggerEl) {
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
});
</script>
<script>
function addToolRow() {
    const container = document.getElementById('tool-items-container');
    const row = document.createElement('div');
    row.className = 'tool-item row border rounded p-2 mb-2 bg-white shadow-sm position-relative';
    row.innerHTML = `
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Close" onclick="this.closest('.tool-item').remove()"></button>
         <div class="col-md-5 mb-2">
            <label class="small text-muted fw-bold">Tên dụng cụ <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="ten_dung_cu[]" required placeholder="Tên kìm, búa...">
        </div>
        <div class="col-md-2 mb-2">
            <label class="small text-muted">Số lượng</label>
            <input type="number" class="form-control" name="so_luong[]" value="1">
        </div>
        <div class="col-md-5 mb-2">
            <label class="small text-muted">Ghi chú</label>
            <input type="text" class="form-control" name="ghi_chu[]" placeholder="Tình trạng, ghi chú...">
        </div>
    `;
    container.appendChild(row);
}
</script>
<script>
function calculateRepairTotal() {
    const container = document.getElementById('repair-items-container');
    const items = container.querySelectorAll('.repair-item');
    let subtotal = 0;
    
    items.forEach(item => {
        const quantity = parseFloat(item.querySelector('input[name="so_luong[]"]')?.value || 0);
        const price = parseFloat(item.querySelector('input[name="don_gia[]"]')?.value || 0);
        subtotal += quantity * price;
    });
    
    const vat = subtotal * 0.08;
    const total = subtotal + vat;
    
    document.getElementById('subtotal-display').textContent = subtotal.toLocaleString('vi-VN') + ' đ';
    document.getElementById('vat-display').textContent = vat.toLocaleString('vi-VN') + ' đ';
    document.getElementById('total-display').textContent = total.toLocaleString('vi-VN') + ' đ';
}

function addRepairRow() {
    const container = document.getElementById('repair-items-container');
    const row = document.createElement('div');
    row.className = 'repair-item row border rounded p-2 mb-2 bg-white shadow-sm position-relative';
    row.innerHTML = `
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Close" onclick="this.closest('.repair-item').remove(); calculateRepairTotal();"></button>
        <div class="col-md-12 mb-2 pe-5">
            <label class="small text-muted fw-bold">Nội dung công việc</label>
            <textarea class="form-control" name="noi_dung_sua_chua[]" rows="1" placeholder="Nhập nội dung sửa chữa..."></textarea>
        </div>
        <div class="col-md-3 mb-2">
            <label class="small text-muted">Xuất xứ / Vật tư</label>
            <input type="text" class="form-control form-control-sm" name="xuat_xu_vat_tu[]" placeholder="Xuất xứ">
        </div>
        <div class="col-md-2 mb-2">
            <label class="small text-muted">ĐVT</label>
            <input type="text" class="form-control form-control-sm" name="don_vi_tinh[]" placeholder="Cái/Lít">
        </div>
        <div class="col-md-2 mb-2">
            <label class="small text-muted">Số lượng</label>
            <input type="number" step="0.01" class="form-control form-control-sm repair-quantity" name="so_luong[]" value="1" onchange="calculateRepairTotal()">
        </div>
        <div class="col-md-2 mb-2">
            <label class="small text-muted">Đơn giá</label>
            <input type="number" class="form-control form-control-sm repair-price" name="don_gia[]" placeholder="VNĐ" onchange="calculateRepairTotal()">
        </div>
        <div class="col-md-3 mb-2">
            <label class="small text-muted">Nơi thực hiện</label>
            <input type="text" class="form-control form-control-sm" name="noi_thuc_hien[]" placeholder="Tên đơn vị thực hiện">
        </div>
    `;
    container.appendChild(row);
}

// Add event listeners to existing repair items when modal opens
document.getElementById('modalRepair')?.addEventListener('shown.bs.modal', function() {
    const container = document.getElementById('repair-items-container');
    const quantityInputs = container.querySelectorAll('input[name="so_luong[]"]');
    const priceInputs = container.querySelectorAll('input[name="don_gia[]"]');
    
    quantityInputs.forEach(input => {
        input.addEventListener('change', calculateRepairTotal);
        input.addEventListener('input', calculateRepairTotal);
    });
    
    priceInputs.forEach(input => {
        input.addEventListener('change', calculateRepairTotal);
        input.addEventListener('input', calculateRepairTotal);
    });
    
    calculateRepairTotal();
});
</script>
<script>
function addToolRow() {
    const container = document.getElementById('tool-items-container');
    const row = document.createElement('div');
    row.className = 'tool-item row border rounded p-2 mb-2 bg-white shadow-sm position-relative';
    row.innerHTML = `
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Close" onclick="this.closest('.tool-item').remove()"></button>
         <div class="col-md-5 mb-2">
            <label class="small text-muted fw-bold">Tên dụng cụ <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="ten_dung_cu[]" required placeholder="Tên kìm, búa...">
        </div>
        <div class="col-md-2 mb-2">
            <label class="small text-muted">Số lượng</label>
            <input type="number" class="form-control" name="so_luong[]" value="1">
        </div>
        <div class="col-md-5 mb-2">
            <label class="small text-muted">Ghi chú</label>
            <input type="text" class="form-control" name="ghi_chu[]" placeholder="Tình trạng, ghi chú...">
        </div>
    `;
    container.appendChild(row);
}
</script>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInputEdit = document.getElementById('file_bao_hiem_edit');
    const previewContainerEdit = document.getElementById('preview_bao_hiem_edit_container');
    const pasteAreaEdit = document.getElementById('paste_area_edit');
    let dataTransferEdit = new DataTransfer();

    if (fileInputEdit) {
        document.addEventListener('paste', function(e) {
            const editModal = document.getElementById('modalEditInfo');
            if (!editModal || !editModal.classList.contains('show')) return;

            if (e.clipboardData && e.clipboardData.files.length > 0) {
                let hasNewFiles = false;
                for (let i = 0; i < e.clipboardData.files.length; i++) {
                    const file = e.clipboardData.files[i];
                    if (file.type.startsWith('image/') || file.type === 'application/pdf') {
                        dataTransferEdit.items.add(file);
                        hasNewFiles = true;
                    }
                }
                
                if (hasNewFiles) {
                    fileInputEdit.files = dataTransferEdit.files;
                    fileInputEdit.dispatchEvent(new Event('change'));
                    
                    pasteAreaEdit.classList.add('bg-success', 'bg-opacity-10');
                    setTimeout(() => pasteAreaEdit.classList.remove('bg-success', 'bg-opacity-10'), 500);
                }
            }
        });

        fileInputEdit.addEventListener('change', function(e) {
            if (this.files !== dataTransferEdit.files) {
                dataTransferEdit = new DataTransfer();
                for (let i = 0; i < this.files.length; i++) {
                    dataTransferEdit.items.add(this.files[i]);
                }
                fileInputEdit.files = dataTransferEdit.files;
            }

            previewContainerEdit.innerHTML = '';
            if (this.files && this.files.length > 0) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const img = document.createElement('img');
                            img.src = event.target.result;
                            img.style.cssText = 'max-height: 80px; margin: 3px; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.2);';
                            previewContainerEdit.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        const span = document.createElement('span');
                        span.className = 'badge bg-primary m-1 d-inline-block';
                        span.style.padding = '5px';
                        span.innerHTML = '<i class="fa-solid fa-file-pdf"></i> ' + file.name;
                        previewContainerEdit.appendChild(span);
                    }
                }
            }
        });
    }

    // Logic for Repair Evidence Upload/Paste
    const fileInputRepair = document.getElementById('file_anh_sc');
    const previewContainerRepair = document.getElementById('preview_anh_sc_container');
    const pasteAreaRepair = document.getElementById('paste_area_repair');
    let dataTransferRepair = new DataTransfer();

    if (fileInputRepair) {
        document.addEventListener('paste', function(e) {
            const repairModal = document.getElementById('modalRepair');
            if (!repairModal || !repairModal.classList.contains('show')) return;

            if (e.clipboardData && e.clipboardData.files.length > 0) {
                let hasNewFiles = false;
                for (let i = 0; i < e.clipboardData.files.length; i++) {
                    const file = e.clipboardData.files[i];
                    if (file.type.startsWith('image/')) {
                        dataTransferRepair.items.add(file);
                        hasNewFiles = true;
                    }
                }
                
                if (hasNewFiles) {
                    fileInputRepair.files = dataTransferRepair.files;
                    fileInputRepair.dispatchEvent(new Event('change'));
                    
                    pasteAreaRepair.classList.add('bg-success', 'bg-opacity-10');
                    setTimeout(() => pasteAreaRepair.classList.remove('bg-success', 'bg-opacity-10'), 500);
                }
            }
        });

        fileInputRepair.addEventListener('change', function(e) {
            if (this.files !== dataTransferRepair.files) {
                dataTransferRepair = new DataTransfer();
                for (let i = 0; i < this.files.length; i++) {
                    dataTransferRepair.items.add(this.files[i]);
                }
                fileInputRepair.files = dataTransferRepair.files;
            }

            previewContainerRepair.innerHTML = '';
            if (this.files && this.files.length > 0) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const img = document.createElement('img');
                            img.src = event.target.result;
                            img.style.cssText = 'max-height: 80px; margin: 3px; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.2);';
                            previewContainerRepair.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            }
        });
    }
});

function showEvidence(paths) {
    const container = document.getElementById('evidence_body');
    container.innerHTML = '';
    paths.forEach(path => {
        container.innerHTML += `<img src="${path}" class="img-fluid mb-3 rounded border shadow-sm" style="max-width: 100%;">`;
    });
    const modal = new bootstrap.Modal(document.getElementById('modalEvidence'));
    modal.show();
}
</script>
