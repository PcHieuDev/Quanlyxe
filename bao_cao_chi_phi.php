<?php
require_once 'includes/header.php';

// Initialize filters
$tu_ngay = $_GET['tu_ngay'] ?? date('Y-m-01'); // First day of current month
$den_ngay = $_GET['den_ngay'] ?? date('Y-m-d'); // Today
$vehicle_id = $_GET['vehicle_id'] ?? 'all';

// Fetch all vehicles for dropdown
$vehicles = [];
try {
    $stmt = $conn->query("SELECT id, bien_kiem_soat, nhan_hieu FROM vehicles ORDER BY bien_kiem_soat ASC");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error silently or log
}

$reportData = [];
$totalCost = 0;

if (!empty($tu_ngay) && !empty($den_ngay)) {
    try {
        $params = [$tu_ngay, $den_ngay];
        $vehicleSql = "";
        if ($vehicle_id !== 'all') {
            $vehicleSql = " AND v.id = ? ";
            $params[] = $vehicle_id;
        }

        // 1. Repair Logs
        $sql = "SELECT 'Sửa chữa' as loai, r.ngay_sua_chua as ngay, r.noi_dung_sua_chua as noi_dung, 
                r.don_vi_tinh, r.so_luong, r.don_gia, r.thanh_tien, r.noi_thuc_hien,
                v.bien_kiem_soat, v.nhan_hieu, v.don_vi_quan_ly
                FROM repair_logs r
                JOIN vehicles v ON r.vehicle_id = v.id
                WHERE r.ngay_sua_chua BETWEEN ? AND ? $vehicleSql";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reportData[] = $row;
            $totalCost += ($row['thanh_tien'] ?? 0);
        }

        // 2. Maintenance Logs
        $sql = "SELECT 'Bảo dưỡng' as loai, m.ngay_bao_duong as ngay, m.noi_dung_vat_tu as noi_dung, 
                m.don_vi_tinh, m.so_luong, m.don_gia, m.thanh_tien, m.noi_thuc_hien,
                v.bien_kiem_soat, v.nhan_hieu, v.don_vi_quan_ly
                FROM maintenance_logs m
                JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.ngay_bao_duong BETWEEN ? AND ? $vehicleSql";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reportData[] = $row;
            $totalCost += ($row['thanh_tien'] ?? 0);
        }

        // 3. Tire Logs
        $sql = "SELECT 'Thay lốp' as loai, t.ngay_thay_the as ngay, 
                CONCAT('Thay lốp: ', t.quy_cach_lop, ' (', t.hang_sx_lop_moi, ')') as noi_dung, 
                'Cái' as don_vi_tinh, t.so_luong_thay as so_luong, 0 as don_gia, 0 as thanh_tien, '' as noi_thuc_hien,
                v.bien_kiem_soat, v.nhan_hieu, v.don_vi_quan_ly
                FROM tire_logs t
                JOIN vehicles v ON t.vehicle_id = v.id
                WHERE t.ngay_thay_the BETWEEN ? AND ? $vehicleSql";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reportData[] = $row;
        }

        // 4. Battery Logs
        $sql = "SELECT 'Thay ắc quy' as loai, b.ngay_thay_the as ngay, 
                CONCAT('Thay ắc quy: ', b.quy_cach_binh, ' (', b.hang_sx_moi, ')') as noi_dung, 
                'Bình' as don_vi_tinh, b.so_luong_thay as so_luong, 0 as don_gia, 0 as thanh_tien, '' as noi_thuc_hien,
                v.bien_kiem_soat, v.nhan_hieu, v.don_vi_quan_ly
                FROM battery_logs b
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE b.ngay_thay_the BETWEEN ? AND ? $vehicleSql";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reportData[] = $row;
        }

        // Sort by License Plate ASC, then by date ASC
        usort($reportData, function($a, $b) {
            $plateCompare = strcmp($a['bien_kiem_soat'], $b['bien_kiem_soat']);
            if ($plateCompare === 0) {
                 return strtotime($a['ngay']) - strtotime($b['ngay']);
            }
            return $plateCompare;
        });

    } catch (PDOException $e) {
        $error = "Lỗi dữ liệu: " . $e->getMessage();
    }
}
?>

<div class="container-fluid pb-5 no-print">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark rounded p-3 me-3">
                        <i class="fa-solid fa-file-invoice-dollar fa-2x"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-0">Báo cáo Thay thế - Sửa chữa</h2>
                        <div class="text-muted">Thống kê chi tiết vật tư, phụ tùng thay thế</div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="exportToExcel()" class="btn btn-success text-white">
                        <i class="fa-solid fa-file-excel me-2"></i>Xuất Excel
                    </button>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fa-solid fa-print me-2"></i>In Báo cáo / Lưu PDF
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Từ ngày</label>
                    <input type="date" class="form-control" name="tu_ngay" value="<?= htmlspecialchars($tu_ngay) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Đến ngày</label>
                    <input type="date" class="form-control" name="den_ngay" value="<?= htmlspecialchars($den_ngay) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Chọn xe</label>
                    <select class="form-select" name="vehicle_id">
                        <option value="all">-- Tất cả xe --</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= $vehicle_id == $v['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['bien_kiem_soat']) ?> - <?= htmlspecialchars($v['nhan_hieu']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-2"></i>Xem dữ liệu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Review -->
    <?php if (!empty($reportData)): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?= count($reportData) ?></h3>
                    <small>Tổng số hạng mục</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?= number_format($totalCost) ?> đ</h3>
                    <small>Tổng tiền trước thuế (ước tính)</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?= count(array_unique(array_column($reportData, 'bien_kiem_soat'))) ?></h3>
                    <small>Số đầu xe phát sinh</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Report Table (Visible in Print) -->
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?php if (empty($reportData)): ?>
                <div class="text-center py-5 text-muted no-print">
                    <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                    <p>Không có dữ liệu trong khoảng thời gian này</p>
                </div>
            <?php else: ?>
                <!-- Print Header (Formal) -->
                <div class="d-none d-print-block mb-4">
                    <div class="row">
                        <div class="col-6 text-center">
                            <img src="https://vietnampost.vn/apps/frontend/images/header/logo-fuild.png" alt="VNPost Logo" style="height: 50px; width: auto;" class="mb-2">
                            <h6 class="fw-bold mb-0 text-uppercase small">Tổng Công ty Bưu điện Việt Nam</h6>
                            <h6 class="fw-bold text-uppercase">Bưu điện Tỉnh Nghệ An</h6>
                            <hr class="w-50 mx-auto my-1 border-dark">
                        </div>
                        <div class="col-6 text-center">
                            <!-- Spacer to align text with left column (Logo height 50px + mb-2) -->
                            <div style="height: 58px;"></div>
                            <h6 class="fw-bold mb-0">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</h6>
                            <h6 class="fw-bold">Độc lập - Tự do - Hạnh phúc</h6>
                            <hr class="w-50 mx-auto my-1 border-dark">
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <h2 class="fw-bold">BÁO CÁO CHI PHÍ SỬA CHỮA, THAY THẾ VẬT TƯ</h2>
                        <p class="fst-italic">Thời gian: Từ ngày <?= date('d/m/Y', strtotime($tu_ngay)) ?> đến ngày <?= date('d/m/Y', strtotime($den_ngay)) ?></p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered border-dark mb-0" id="reportTable">
                        <thead class="align-middle text-center">
                            <tr class="bg-light">
                                <th width="50">STT</th>
                                <th width="100">Ngày</th>
                                <th width="100">Loại</th>
                                <th>Nội dung công việc / Vật tư</th>
                                <th width="60">ĐVT</th>
                                <th width="60">SL</th>
                                <th width="120">Thành tiền</th>
                                <th width="150">Nơi thực hiện</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $formattedData = [];
                            foreach ($reportData as $row) {
                                $formattedData[$row['bien_kiem_soat']][] = $row;
                            }
                            
                            $stt = 1;
                            foreach ($formattedData as $plate => $items):
                                $subTotal = 0;
                            ?>
                            <!-- Vehicle Header Row -->
                            <tr class="table-secondary border-dark fw-bold">
                                <td colspan="8" class="text-start bg-secondary bg-opacity-25 ps-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fa-solid fa-truck me-2"></i>
                                            XE: <?= htmlspecialchars($plate) ?> 
                                            <span class="fw-normal ms-2 fst-italic">(<?= htmlspecialchars($items[0]['nhan_hieu']) ?>)</span>
                                        </span>
                                        <span class="fw-normal me-3 fst-italic">
                                            Đơn vị: <?= htmlspecialchars($items[0]['don_vi_quan_ly'] ?? '---') ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            
                            <?php foreach ($items as $row): 
                                $subTotal += ($row['thanh_tien'] ?? 0);
                            ?>
                            <tr>
                                <td class="text-center align-middle"><?= $stt++ ?></td>
                                <td class="text-center align-middle"><?= date('d/m/Y', strtotime($row['ngay'])) ?></td>
                                <td class="text-center align-middle">
                                    <span class="badge border border-dark text-dark bg-light"><?= $row['loai'] ?></span>
                                </td>
                                <td class="align-middle"><?= htmlspecialchars($row['noi_dung']) ?></td>
                                <td class="text-center align-middle"><?= htmlspecialchars($row['don_vi_tinh']) ?></td>
                                <td class="text-center align-middle"><?= htmlspecialchars($row['so_luong']) ?></td>
                                <td class="text-end fw-bold align-middle">
                                    <?= ($row['thanh_tien'] > 0) ? number_format($row['thanh_tien']) : '-' ?>
                                </td>
                                <td class="align-middle"><?= htmlspecialchars($row['noi_thuc_hien']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Subtotal Row -->
                            <tr class="fw-bold bg-light">
                                <td colspan="6" class="text-end fst-italic align-middle">
                                    Tổng tiền xe <?= htmlspecialchars($plate) ?> (Trước thuế):<br>
                                    <span class="text-muted fw-normal small">Tổng tiền xe <?= htmlspecialchars($plate) ?> (Sau thuế 8%):</span>
                                </td>
                                <td class="text-end align-middle">
                                    <?= number_format($subTotal) ?> đ<br>
                                    <span class="text-danger"><?= number_format($subTotal * 1.08) ?> đ</span>
                                </td>
                                <td></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Separate Grand Total Section -->
                <div class="mt-4 mb-2" style="page-break-inside: avoid;">
                    <table class="table table-bordered border-dark w-100">
                        <tr class="bg-light">
                             <td class="text-uppercase fw-bold p-3" style="width: 70%;">Tổng hợp chi phí toàn bộ (Trước thuế)</td>
                             <td class="text-end fw-bold p-3 fs-5"><?= number_format($totalCost) ?> đ</td>
                        </tr>
                        <tr>
                            <td class="text-uppercase fw-bold p-3">Tổng VAT (8%) (Tạm tính)</td>
                            <td class="text-end fw-bold p-3 fs-5"><?= number_format($totalCost * 0.08) ?> đ</td>
                        </tr>
                        <tr class="table-active border-top border-dark border-3">
                            <td class="text-uppercase fw-bold p-4 fs-4">Tổng thanh toán toàn bộ (Bao gồm VAT)</td>
                            <td class="text-end fw-bold p-4 fs-4 text-danger"><?= number_format($totalCost * 1.08) ?> đ</td>
                        </tr>
                    </table>
                </div>
                
               
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    var table = document.getElementById("reportTable");
    if(!table) return;

    var html = table.outerHTML;
    var blob = new Blob(['\ufeff', `
        <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>
        <head><meta charset='utf-8'></head><body>${html}</body></html>
    `], { type: "application/vnd.ms-excel" });
    
    var url = URL.createObjectURL(blob);
    var a = document.createElement("a");
    a.href = url;
    a.download = "Bao_cao_chi_phi_<?= date('d_m_Y') ?>.xls";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>

<style>
@media print {
    @page { 
        size: A4 landscape;
        margin: 10mm 15mm; 
    }
    body {
        background: white !important;
        font-family: 'Times New Roman', Times, serif; /* Formal font for print */
        color: #000 !important;
        -webkit-print-color-adjust: exact !important;
    }
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table-bordered {
        border-color: #000 !important;
    }
    .table-bordered th, .table-bordered td {
        border : 1px solid #000 !important;
        padding: 4px 8px !important;
    }
    .badge {
        border: none !important;
        background: none !important;
        color: #000 !important;
        font-weight: normal;
        padding: 0;
    }
    /* Hide URL prints in some browsers */
    a[href]:after {
        content: none !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
