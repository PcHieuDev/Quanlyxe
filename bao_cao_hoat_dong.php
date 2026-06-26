<?php
require_once 'includes/header.php';

// Initialize date range
$tu_ngay = $_GET['tu_ngay'] ?? date('Y-m-01'); // First day of current month
$den_ngay = $_GET['den_ngay'] ?? date('Y-m-d'); // Today

$activities = [];

if (!empty($tu_ngay) && !empty($den_ngay)) {
    try {
        // 1. Maintenance logs
        $stmt = $conn->prepare("
            SELECT 'Bảo dưỡng' as loai, m.*, v.bien_kiem_soat, m.ngay_bao_duong as ngay_thuc_hien
            FROM maintenance_logs m
            JOIN vehicles v ON m.vehicle_id = v.id
            WHERE m.ngay_bao_duong BETWEEN ? AND ?
            ORDER BY m.ngay_bao_duong DESC
        ");
        $stmt->execute([$tu_ngay, $den_ngay]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = $row;
        }

        // 2. Repair logs
        $stmt = $conn->prepare("
            SELECT 'Sửa chữa' as loai, r.*, v.bien_kiem_soat, r.ngay_sua_chua as ngay_thuc_hien
            FROM repair_logs r
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE r.ngay_sua_chua BETWEEN ? AND ?
            ORDER BY r.ngay_sua_chua DESC
        ");
        $stmt->execute([$tu_ngay, $den_ngay]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = $row;
        }

        // 3. Tool additions
        $stmt = $conn->prepare("
            SELECT 'Đồ nghề' as loai, t.*, v.bien_kiem_soat, t.ngay_cap as ngay_thuc_hien
            FROM vehicle_tools t
            JOIN vehicles v ON t.vehicle_id = v.id
            WHERE t.ngay_cap BETWEEN ? AND ?
            ORDER BY t.ngay_cap DESC
        ");
        $stmt->execute([$tu_ngay, $den_ngay]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = $row;
        }

        // 4. Tire changes
        $stmt = $conn->prepare("
            SELECT 'Thay lốp' as loai, t.*, v.bien_kiem_soat, t.ngay_thay_the as ngay_thuc_hien
            FROM tire_logs t
            JOIN vehicles v ON t.vehicle_id = v.id
            WHERE t.ngay_thay_the BETWEEN ? AND ?
            ORDER BY t.ngay_thay_the DESC
        ");
        $stmt->execute([$tu_ngay, $den_ngay]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = $row;
        }

        // 5. Battery changes
        $stmt = $conn->prepare("
            SELECT 'Thay ắc quy' as loai, b.*, v.bien_kiem_soat, b.ngay_thay_the as ngay_thuc_hien
            FROM battery_logs b
            JOIN vehicles v ON b.vehicle_id = v.id
            WHERE b.ngay_thay_the BETWEEN ? AND ?
            ORDER BY b.ngay_thay_the DESC
        ");
        $stmt->execute([$tu_ngay, $den_ngay]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = $row;
        }

        // 6. Inspections
        $stmt = $conn->prepare("
            SELECT 'Đăng kiểm' as loai, i.*, v.bien_kiem_soat, i.hieu_luc_tu_ngay as ngay_thuc_hien
            FROM vehicle_inspections i
            JOIN vehicles v ON i.vehicle_id = v.id
            WHERE i.hieu_luc_tu_ngay BETWEEN ? AND ?
            ORDER BY i.hieu_luc_tu_ngay DESC
        ");
        $stmt->execute([$tu_ngay, $den_ngay]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = $row;
        }

        // 7. Assignments
        $stmt = $conn->prepare("
            SELECT 'Bàn giao' as loai, a.*, v.bien_kiem_soat, a.tu_ngay as ngay_thuc_hien
            FROM vehicle_assignments a
            JOIN vehicles v ON a.vehicle_id = v.id
            WHERE a.tu_ngay BETWEEN ? AND ?
            ORDER BY a.tu_ngay DESC
        ");
        $stmt->execute([$tu_ngay, $den_ngay]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = $row;
        }

        // Sort all activities by date
        usort($activities, function($a, $b) {
            return strtotime($b['ngay_thuc_hien']) - strtotime($a['ngay_thuc_hien']);
        });

    } catch (PDOException $e) {
        die("Lỗi: " . $e->getMessage());
    }
}
?>

<div class="container-fluid pb-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3">
                <div class="d-flex align-items-center">
                    <div class="bg-info text-white rounded p-3 me-3">
                        <i class="fa-solid fa-calendar-days fa-2x"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-0">Báo cáo Hoạt động</h2>
                        <div class="text-muted">Theo dõi các thay đổi theo khoảng thời gian</div>
                    </div>
                </div>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Từ ngày</label>
                    <input type="date" class="form-control" name="tu_ngay" value="<?= htmlspecialchars($tu_ngay) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Đến ngày</label>
                    <input type="date" class="form-control" name="den_ngay" value="<?= htmlspecialchars($den_ngay) ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-2"></i>Lọc dữ liệu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php if (!empty($activities)): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?= count($activities) ?></h3>
                    <small>Tổng hoạt động</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?= count(array_unique(array_column($activities, 'bien_kiem_soat'))) ?></h3>
                    <small>Xe có thay đổi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?= count(array_filter($activities, fn($a) => $a['loai'] == 'Sửa chữa')) ?></h3>
                    <small>Lần sửa chữa</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?= count(array_filter($activities, fn($a) => $a['loai'] == 'Bảo dưỡng')) ?></h3>
                    <small>Lần bảo dưỡng</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Activities Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-list me-2"></i>Chi tiết Hoạt động
                <?php if (!empty($tu_ngay) && !empty($den_ngay)): ?>
                    <span class="badge bg-primary"><?= date('d/m/Y', strtotime($tu_ngay)) ?> - <?= date('d/m/Y', strtotime($den_ngay)) ?></span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($activities)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                    <p>Không có hoạt động nào trong khoảng thời gian này</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="100">Ngày</th>
                                <th width="120">Loại</th>
                                <th width="120">Biển số</th>
                                <th>Nội dung chi tiết</th>
                                <th width="80" class="text-center">Xem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td class="fw-bold"><?= date('d/m/Y', strtotime($activity['ngay_thuc_hien'])) ?></td>
                                <td>
                                    <?php
                                    $badges = [
                                        'Bảo dưỡng' => 'bg-info',
                                        'Sửa chữa' => 'bg-warning text-dark',
                                        'Đồ nghề' => 'bg-secondary',
                                        'Thay lốp' => 'bg-dark',
                                        'Thay ắc quy' => 'bg-danger',
                                        'Đăng kiểm' => 'bg-success',
                                        'Bàn giao' => 'bg-primary'
                                    ];
                                    $badgeClass = $badges[$activity['loai']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $activity['loai'] ?></span>
                                </td>
                                <td>
                                    <a href="chi_tiet.php?id=<?= $activity['vehicle_id'] ?>" class="text-decoration-none fw-bold">
                                        <?= htmlspecialchars($activity['bien_kiem_soat']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    // Display details based on activity type
                                    switch ($activity['loai']) {
                                        case 'Bảo dưỡng':
                                            echo "<strong>Nội dung:</strong> " . htmlspecialchars($activity['noi_dung_vat_tu'] ?? 'N/A');
                                            echo "<br><small class='text-muted'>Cấp BD: " . number_format($activity['cap_bao_duong_km'] ?? 0) . " km | ";
                                            echo "Nơi thực hiện: " . htmlspecialchars($activity['noi_thuc_hien'] ?? 'N/A') . "</small>";
                                            if (!empty($activity['thanh_tien'])) {
                                                echo "<br><small class='text-success fw-bold'>Thành tiền: " . number_format($activity['thanh_tien']) . " đ</small>";
                                            }
                                            break;
                                        
                                        case 'Sửa chữa':
                                            echo "<strong>Nội dung:</strong> " . htmlspecialchars($activity['noi_dung_sua_chua'] ?? 'N/A');
                                            echo "<br><small class='text-muted'>Loại: " . ($activity['loai_sua_chua'] == 'THUONG_XUYEN' ? 'Thường xuyên' : 'Tập trung') . " | ";
                                            echo "Nơi thực hiện: " . htmlspecialchars($activity['noi_thuc_hien'] ?? 'N/A') . "</small>";
                                            if (!empty($activity['thanh_tien'])) {
                                                echo "<br><small class='text-success fw-bold'>Thành tiền: " . number_format($activity['thanh_tien']) . " đ</small>";
                                            }
                                            break;
                                        
                                        case 'Đồ nghề':
                                            echo "<strong>Dụng cụ:</strong> " . htmlspecialchars($activity['ten_dung_cu'] ?? 'N/A');
                                            echo " <span class='badge bg-secondary'>" . ($activity['so_luong'] ?? 1) . " cái</span>";
                                            if (!empty($activity['ghi_chu'])) {
                                                echo "<br><small class='text-muted'>" . htmlspecialchars($activity['ghi_chu']) . "</small>";
                                            }
                                            break;
                                        
                                        case 'Thay lốp':
                                            echo "<strong>Quy cách:</strong> " . htmlspecialchars($activity['quy_cach_lop'] ?? 'N/A');
                                            echo " | <strong>SL:</strong> " . ($activity['so_luong_thay'] ?? 0) . " lốp";
                                            echo "<br><small class='text-muted'>Hãng SX: " . htmlspecialchars($activity['hang_sx_lop_moi'] ?? 'N/A');
                                            echo " | Km khi thay: " . number_format($activity['km_khi_thay'] ?? 0) . "</small>";
                                            break;
                                        
                                        case 'Thay ắc quy':
                                            echo "<strong>Quy cách:</strong> " . htmlspecialchars($activity['quy_cach_binh'] ?? 'N/A');
                                            echo " | <strong>SL:</strong> " . ($activity['so_luong_thay'] ?? 0);
                                            echo "<br><small class='text-muted'>Hãng SX: " . htmlspecialchars($activity['hang_sx_moi'] ?? 'N/A');
                                            if (!empty($activity['thoi_gian_da_su_dung'])) {
                                                echo " | Tuổi thọ cũ: " . htmlspecialchars($activity['thoi_gian_da_su_dung']);
                                            }
                                            echo "</small>";
                                            break;
                                        
                                        case 'Đăng kiểm':
                                            echo "<strong>Số sổ:</strong> " . htmlspecialchars($activity['so_so_dang_kiem'] ?? 'N/A');
                                            echo "<br><small class='text-muted'>Hiệu lực: " . date('d/m/Y', strtotime($activity['hieu_luc_tu_ngay']));
                                            echo " → " . date('d/m/Y', strtotime($activity['hieu_luc_den_ngay']));
                                            echo " | ĐV: " . htmlspecialchars($activity['don_vi_dang_kiem'] ?? 'N/A') . "</small>";
                                            break;
                                        
                                        case 'Bàn giao':
                                            echo "<strong>Đối tượng:</strong> " . htmlspecialchars($activity['ten_doi_tuong'] ?? 'N/A');
                                            echo " <span class='badge bg-info'>" . ($activity['loai_doi_tuong'] == 'DON_VI' ? 'Đơn vị' : 'Người lái') . "</span>";
                                            if (!empty($activity['so_km_ban_giao'])) {
                                                echo "<br><small class='text-muted'>Km bàn giao: " . number_format($activity['so_km_ban_giao']) . "</small>";
                                            }
                                            break;
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="chi_tiet.php?id=<?= $activity['vehicle_id'] ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết xe">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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

<?php require_once 'includes/footer.php'; ?>
