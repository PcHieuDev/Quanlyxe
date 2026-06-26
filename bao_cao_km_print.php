<?php
// bao_cao_km_print.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/config.php';

// Get params
$thang = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$nam = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Fetch data
$stmt = $conn->prepare("
    SELECT 
        v.id as vehicle_id,
        v.bien_kiem_soat,
        v.loai_xe,
        v.don_vi_quan_ly,
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
$stmt->execute([$nam, $thang]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_vehicles = count($data);
$total_km_month = 0;
$vehicles_need_oil_pending = 0;
$vehicles_over_limit = 0;

foreach ($data as $row) {
    $total_km_month += $row['km_trong_thang'];
    if ($row['km_trong_thang'] > 5000) {
        $vehicles_over_limit++;
        if ($row['da_thay_dau'] != 1) {
            $vehicles_need_oil_pending++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Tổng Hợp KM - Tháng <?= $thang ?>/<?= $nam ?></title>
    
    <!-- Google Fonts: Roboto for distinct professional look -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 (only for grid/utilities, simplified) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --print-color: #000;
        }
        body {
            font-family: 'Roboto', 'Times New Roman', serif; /* Hybrid professional look */
            font-size: 13px; /* Compact for print */
            color: #000;
            background: #fff;
            line-height: 1.4;
        }

        /* A4 Page Setup */
        @page {
            size: A4;
            margin: 1.5cm;
        }

        /* Titles and Headers */
        .company-name {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 5px;
        }
        .report-subtitle {
            text-align: center;
            font-style: italic;
            margin-bottom: 20px;
            font-size: 12px;
        }

        /* Summary Section - List Style */
        .summary-section {
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 40px;
            font-weight: 500;
        }
        .text-value {
            font-weight: bold;
            font-size: 14px;
        }

        /* Table Styling - Professional Grid */
        .table-report {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .table-report th, 
        .table-report td {
            border: 1px solid #000; /* Full black borders */
            padding: 6px 8px;
            vertical-align: middle;
        }
        .table-report th {
            background-color: #f2f2f2 !important; /* Light grey header */
            color: #000;
            text-align: center;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            white-space: nowrap;
        }
        .col-stt { width: 40px; text-align: center; }
        .col-bs { width: 90px; font-weight: bold; }
        .col-lx { width: 120px; }
        .col-dv { }
        .col-num { text-align: right; width: 90px; }
        .col-note { width: 120px; text-align: center; }

        /* Status colors for screen, B&W for print usually, but we keep bold */
        .status-ok { color: #006400; }
        .status-warn { color: #d00; font-weight: bold; }

        /* Signature Section */
        .signature-grid {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-cell {
            text-align: center;
            width: 30%;
        }
        .signature-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .signature-role {
            font-style: italic;
            font-size: 11px;
            margin-bottom: 60px; /* Space for signature */
        }

        /* Screen-only Toolbar */
        .no-print-bar {
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .body-content {
            margin-top: 60px; /* offset for fixed bar */
        }

        @media print {
            .no-print-bar { display: none; }
            .body-content { margin-top: 0; }
            a { text-decoration: none; color: #000; }
            body { background: #fff; }
        }
    </style>
</head>
<body>

    <!-- Toolbar (Hidden on Print) -->
    <div class="no-print-bar">
        <div class="fw-bold text-primary">
            <i class="fa-solid fa-file-pdf me-2"></i>Xem trước bản in
        </div>
        <div>
            <a href="import_km.php?month=<?= $thang ?>&year=<?= $nam ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i class="fa-solid fa-arrow-left"></i> Quay lại
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-print"></i> In ngay
            </button>
        </div>
    </div>

    <div class="container-fluid body-content">
        
        <!-- Official Header -->
        <div class="row">
           
           
        </div>

        <h1 class="report-title">BẢNG KÊ CHI TIẾT KM HOẠT ĐỘNG XE Ô TÔ</h1>
        <div class="report-subtitle">Tháng <?= sprintf("%02d", $thang) ?> năm <?= $nam ?></div>

        <!-- Professional Text-based Summary -->
        <div class="summary-section">
            <div class="summary-item">
                Tổng số phương tiện: <span class="text-value"><?= $total_vehicles ?></span> xe
            </div>
            <div class="summary-item">
                Tổng KM thực hiện: <span class="text-value"><?= number_format($total_km_month) ?></span> km
            </div>
            <div class="summary-item">
                Xe cần thay dầu (>5000km): <span class="text-value" style="<?= $vehicles_need_oil_pending > 0 ? 'color: #d00;' : '' ?>"><?= $vehicles_need_oil_pending ?></span> xe
                <?php if ($vehicles_over_limit > $vehicles_need_oil_pending): ?>
                    <small class="text-muted">(Đã thay: <?= $vehicles_over_limit - $vehicles_need_oil_pending ?>)</small>
                <?php endif; ?>
            </div>
        </div>

        <table class="table-report">
            <thead>
                <tr>
                    <th rowspan="2" class="col-stt">STT</th>
                    <th rowspan="2" class="col-bs">Biển kiểm soát</th>
                    <th rowspan="2" class="col-lx">Loại xe / Nhãn hiệu</th>
                    <th rowspan="2" class="col-dv">Đơn vị quản lý</th>
                    <th colspan="3">Số liệu hoạt động (Km)</th>
                    <th rowspan="2" class="col-note">Ghi chú / Trạng thái</th>
                </tr>
                <tr>
                    <th class="col-num">KM bàn giao</th>
                    <th class="col-num">KM tháng này</th>
                    <th class="col-num">KM tích lũy</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4">Không có dữ liệu cho tháng này.</td>
                </tr>
                <?php else: ?>
                    <?php 
                    $stt = 1;
                    foreach ($data as $row): 
                        $km_thang = $row['km_trong_thang'];
                        $is_over = $km_thang > 5000;
                        $da_thay = isset($row['da_thay_dau']) && $row['da_thay_dau'] == 1;
                        
                        // Row highlight styling? No, clean white for print.
                    ?>
                    <tr>
                        <td class="col-stt"><?= $stt++ ?></td>
                        <td class="col-bs"><?= htmlspecialchars($row['bien_kiem_soat']) ?></td>
                        <td class="col-lx"><?= htmlspecialchars($row['loai_xe']) ?></td>
                        <td class="col-dv"><?= htmlspecialchars($row['don_vi_quan_ly']) ?></td>
                        <td class="col-num"><?= number_format($row['so_km_ban_giao'] ?? 0) ?></td>
                        <td class="col-num fw-bold"><?= number_format($km_thang) ?></td>
                        <td class="col-num"><?= number_format($row['km_tich_luy']) ?></td>
                        <td class="col-note">
                            <?php if ($is_over): ?>
                                <?php if ($da_thay): ?>
                                    <span class="status-ok">Đã thay dầu</span>
                                <?php else: ?>
                                    <span class="status-warn">⚠ Cần thay dầu</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Signature Block -->
      

        <div class="text-end mt-2 no-print" style="font-size: 10px; color: #888;">
            Được xuất từ Hệ thống Quản lý Xe ngày <?= date('d/m/Y') ?>
        </div>
    </div>

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</body>
</html>
