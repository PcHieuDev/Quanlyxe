<?php
require_once 'includes/header.php';

// Only admin can access index page
if ($role !== 'admin') {
    header('Location: chi_tiet.php?id=' . $vehicle_id);
    exit;
}

// --- XỬ LÝ POST REQUEST (ĐỔI TRẠNG THÁI) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $vId = $_POST['vehicle_id'];
    $newStatus = $_POST['current_status'] == 1 ? 0 : 1;
    $stmt = $conn->prepare("UPDATE vehicles SET trang_thai = ? WHERE id = ?");
    $stmt->execute([$newStatus, $vId]);
    header("Location: index.php"); // Refresh để cập nhật
    exit;
}

// --- Data Fetching ---
try {
    // --- AUTO MIGRATION: Đảm bảo cột 'da_thay_dau' tồn tại ---
    try {
        $checkCol = $conn->query("SHOW COLUMNS FROM operation_stats LIKE 'da_thay_dau'");
        if (!$checkCol->fetch()) {
            $conn->exec("ALTER TABLE operation_stats ADD COLUMN da_thay_dau TINYINT(1) DEFAULT 0 AFTER km_tich_luy");
        }
    } catch (Exception $e) {
        // Ignored: Có thể lỗi do bảng chưa có dữ liệu hoặc lỗi quyền hạn, nhưng cứ tiếp tục
    }
    // -------------------------------------------------------------

    // 1. Tổng số xe
    $stmtTotal = $conn->query("SELECT COUNT(*) FROM vehicles");
    $totalVehicles = $stmtTotal->fetchColumn();

    // 2. Xe đang hoạt động (dựa trên cột trang_thai)
    $stmtActive = $conn->query("SELECT COUNT(*) FROM vehicles WHERE trang_thai = 1");
    $activeVehicles = $stmtActive->fetchColumn();

    // 3. Sắp hết hạn đăng kiểm (trong 30 ngày tới)
    // Lấy danh sách ID xe để filter
    $sqlInspection = "SELECT vehicle_id FROM (
                        SELECT vehicle_id, MAX(hieu_luc_den_ngay) as exp_date 
                        FROM vehicle_inspections 
                        GROUP BY vehicle_id
                      ) as sub 
                      WHERE exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $stmtInsp = $conn->query($sqlInspection);
    $warningVehicleIds = $stmtInsp->fetchAll(PDO::FETCH_COLUMN); // Mảng ID [1, 2, 5...]
    $warningInspections = count($warningVehicleIds);

    // 4. Xe cần thay dầu (đi >5000km trong tháng bất kỳ mà chưa thay)
    // Lấy danh sách chi tiết để hiển thị nút check
    $sqlOilChange = "SELECT vehicle_id, thang, nam 
                     FROM operation_stats 
                     WHERE km_trong_thang > 5000 
                     AND (da_thay_dau = 0 OR da_thay_dau IS NULL)
                     ORDER BY nam DESC, thang DESC";
    
    $stmtOil = $conn->prepare($sqlOilChange);
    $stmtOil->execute();
    $oilChangeData = $stmtOil->fetchAll(PDO::FETCH_ASSOC); // [{vehicle_id:1, thang:1, nam:2026}, ...]
    
    // Group by vehicle_id
    $oilWarnings = [];
    $oilChangeVehicleIds = []; 
    foreach ($oilChangeData as $item) {
        $vID = $item['vehicle_id'];
        if (!isset($oilWarnings[$vID])) {
            $oilWarnings[$vID] = [];
            $oilChangeVehicleIds[] = $vID; // Unique IDs for filtering
        }
        $oilWarnings[$vID][] = ['thang' => $item['thang'], 'nam' => $item['nam']];
    }
    
    $needOilChange = count($oilChangeVehicleIds);

    // 5. Lấy danh sách xe
    $search = $_GET['q'] ?? '';
    if ($search) {
        // Tìm kiếm theo biển số hoặc loại xe
        $stmtList = $conn->prepare("SELECT * FROM vehicles WHERE bien_kiem_soat LIKE ? OR loai_xe LIKE ? ORDER BY id DESC");
        $stmtList->execute(["%$search%", "%$search%"]);
    } else {
        $stmtList = $conn->prepare("SELECT * FROM vehicles ORDER BY id DESC");
        $stmtList->execute();
    }
    $vehicles = $stmtList->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Lỗi truy vấn: " . $e->getMessage() . "</div>";
    die();
}
?>

<style>
/* Dashboard Specific UI Enhancements */
.hover-lift {
    transition: transform 0.25s cubic-bezier(.02,.01,.47,1), box-shadow 0.25s cubic-bezier(.02,.01,.47,1);
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.1) !important;
}
.badge-soft {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
}
.table-modern > :not(caption) > * > * {
    padding: 1rem 1.25rem;
    background-color: transparent;
    border-bottom: 1px solid rgba(0,0,0,.04);
}
.table-modern tbody tr:hover {
    background-color: #f8f9fa;
    border-radius: 10px;
}
</style>

<div class="container pb-5">
    <!-- Welcome Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <div>
            <h2 class="fw-bolder text-dark mb-1" style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">Bảng điều khiển</h2>
            <p class="text-secondary mb-0">Tổng quan tình hình phương tiện & hoạt động</p>
        </div>
        <a href="them_xe.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold" style="background: linear-gradient(135deg, #00467f, #005599); border:none;">
            <i class="fa-solid fa-plus me-2"></i>Thêm Hồ sơ xe
        </a>
    </div>

    <!-- Main Stats Row -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-5">
        <!-- Card 1: Tổng số xe -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm hover-lift" style="border-radius: 20px; background: linear-gradient(135deg, #00467f, #0066b3);">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-white text-primary rounded-circle shadow-sm me-4 d-flex justify-content-center align-items-center" style="width: 58px; height: 58px; font-size: 1.5rem;">
                        <i class="fa-solid fa-car"></i>
                    </div>
                    <div>
                        <p class="text-white text-opacity-75 mb-1 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Tổng phương tiện</p>
                        <h2 class="mb-0 text-white fw-bolder"><?= number_format($totalVehicles) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Đang hoạt động -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm hover-lift" style="border-radius: 20px; background: linear-gradient(135deg, #198754, #20c997);">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-white text-success rounded-circle shadow-sm me-4 d-flex justify-content-center align-items-center" style="width: 58px; height: 58px; font-size: 1.5rem;">
                        <i class="fa-solid fa-road"></i>
                    </div>
                    <div>
                        <p class="text-white text-opacity-75 mb-1 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Đang hoạt động</p>
                        <h2 class="mb-0 text-white fw-bolder"><?= number_format($activeVehicles) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Sắp hết đăng kiểm -->
        <div class="col" onclick="filterInspExpiring()" style="cursor: pointer;">
            <div class="card h-100 border-0 shadow-sm hover-lift" style="border-radius: 20px; background: linear-gradient(135deg, #fdb913, #f5a623);">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-white text-warning rounded-circle shadow-sm me-4 d-flex justify-content-center align-items-center position-relative" style="width: 58px; height: 58px; font-size: 1.5rem;">
                        <i class="fa-solid fa-clipboard-check"></i>
                        <?php if($warningInspections > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle" style="border-width: 3px !important;"></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-dark text-opacity-75 mb-1 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Hết đăng kiểm</p>
                        <h2 class="mb-0 text-dark fw-bolder"><?= number_format($warningInspections) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Cần thay dầu -->
        <div class="col" onclick="filterOilChange()" style="cursor: pointer;">
            <div class="card h-100 border-0 shadow-sm hover-lift" style="border-radius: 20px; background: linear-gradient(135deg, #dc3545, #e05e5e);">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-white text-danger rounded-circle shadow-sm me-4 d-flex justify-content-center align-items-center position-relative" style="width: 58px; height: 58px; font-size: 1.5rem;">
                        <i class="fa-solid fa-oil-can"></i>
                        <?php if($needOilChange > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle p-2 bg-warning border border-light rounded-circle" style="border-width: 3px !important;"></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-white text-opacity-75 mb-1 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Cần thay dầu</p>
                        <h2 class="mb-0 text-white fw-bolder"><?= number_format($needOilChange) ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicles List Table Container -->
    <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
        <!-- Card Header & Search -->
        <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <h4 class="mb-0 text-dark fw-bold d-flex align-items-center">
                <i class="fa-solid fa-list-check text-primary me-3 bg-primary bg-opacity-10 p-2 rounded-3"></i> 
                Danh sách Phương tiện
            </h4>
            
            <div class="d-flex w-100 w-md-auto justify-content-md-end position-relative">
                <div class="input-group input-group-lg shadow-sm" style="border-radius: 50px; overflow: hidden; border: 1px solid #eaeaea; background: #fff; max-width: 400px;">
                    <span class="input-group-text bg-white border-0 pe-2"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-0 ps-1" placeholder="Tìm biển số, đơn vị, loại xe..." style="box-shadow: none;" autocomplete="off">
                    <button class="btn btn-white border-0 px-3" type="button" onclick="clearSearch()" id="clearBtn" style="display:none; background: #fff;"><i class="fa-solid fa-times text-danger"></i></button>
                </div>
                <div id="searchNotification" class="alert alert-danger py-2 px-3 shadow-sm mb-0" style="display:none; position:absolute; top: 110%; right: 0; z-index:100; border-radius: 12px; min-width: 200px;"></div>
            </div>
        </div>

        <!-- Table Data -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-modern align-middle mb-0 w-100">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th class="ps-4 text-secondary fw-semibold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">Biển kiểm soát</th>
                            <th class="text-secondary fw-semibold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">Đơn vị quản lý</th>
                            <th class="text-secondary fw-semibold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">Loại & Nhãn hiệu</th>
                            <th class="text-secondary fw-semibold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">Năm SX</th>
                            <th class="text-secondary fw-semibold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">Khung/Máy</th>
                            <th class="text-secondary fw-semibold text-uppercase text-end" style="font-size: 0.8rem; letter-spacing: 0.5px;">Giá trị (VNĐ)</th>
                            <th class="text-secondary fw-semibold text-uppercase text-center" style="font-size: 0.8rem; letter-spacing: 0.5px;">Trạng thái</th>
                            <th class="text-secondary fw-semibold text-uppercase text-end pe-4" style="font-size: 0.8rem; letter-spacing: 0.5px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($vehicles) > 0): ?>
                            <?php foreach($vehicles as $xe): ?>
                            <tr style="transition: all 0.2s;">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                            <i class="fa-solid fa-truck fs-5"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bolder text-dark d-block fs-6"><?= htmlspecialchars($xe['bien_kiem_soat']) ?></span>
                                            <span class="text-muted small">ID: #<?= $xe['id'] ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info fw-semibold border border-info border-opacity-25 px-3 py-2 rounded-pill"><?= htmlspecialchars($xe['don_vi_quan_ly'] ?: 'Chưa xác định') ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($xe['loai_xe'] ?? '---') ?></span>
                                    <span class="text-secondary small"><?= htmlspecialchars($xe['nhan_hieu'] ?? '') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                                        <?= htmlspecialchars($xe['nam_san_xuat'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-secondary font-monospace">
                                        <div title="Số Khung"><span class="text-muted">K:</span> <?= htmlspecialchars($xe['so_khung'] ?? '---') ?></div>
                                        <div title="Số Máy"><span class="text-muted">M:</span> <?= htmlspecialchars($xe['so_may'] ?? '---') ?></div>
                                    </div>
                                </td>
                                <td class="fw-bold text-success text-end">
                                    <?= $xe['nguyen_gia'] ? number_format($xe['nguyen_gia']) : '0' ?>
                                </td>
                                <td class="text-center">
                                    <?php if($xe['trang_thai'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold border border-success border-opacity-25 px-3 py-2 rounded-pill mb-1 d-inline-block"><i class="fa-solid fa-circle-check me-1"></i>Hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold border border-secondary border-opacity-25 px-3 py-2 rounded-pill mb-1 d-inline-block"><i class="fa-solid fa-circle-minus me-1"></i>Đã Dừng</span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Hiển thị cảnh báo thay dầu (chips)
                                    if(isset($oilWarnings[$xe['id']])): 
                                        foreach($oilWarnings[$xe['id']] as $w): 
                                    ?>
                                        <div class="d-inline-flex align-items-center bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-pill px-2 py-1 mt-1">
                                            <i class="fa-solid fa-oil-can text-danger small me-1"></i>
                                            <span class="text-danger small fw-bold me-2">T<?= $w['thang'] ?>/<?= $w['nam'] ?></span>
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 20px; height: 20px;" onclick="markOilChanged(<?= $xe['id'] ?>, <?= $w['thang'] ?>, <?= $w['nam'] ?>, this)" title="Xác nhận đã thay dầu">
                                                <i class="fa-solid fa-check" style="font-size: 10px;"></i>
                                            </button>
                                        </div>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="chi_tiet.php?id=<?= $xe['id'] ?>" class="btn btn-light text-primary shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Xem chi tiết">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái xe này?');">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="vehicle_id" value="<?= $xe['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $xe['trang_thai'] ?>">
                                            <?php if($xe['trang_thai'] == 1): ?>
                                                <button type="submit" class="btn btn-light text-danger shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Tạm dừng xe">
                                                    <i class="fa-solid fa-pause"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-light text-success shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Kích hoạt xe">
                                                    <i class="fa-solid fa-play"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="noResultRow">
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted d-flex flex-column align-items-center">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                            <i class="fa-solid fa-folder-open fs-1 text-secondary opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark">Chưa có phương tiện nào</h5>
                                        <p>Hệ thống hiện tại chưa có dữ liệu hoặc không tìm thấy kết quả.</p>
                                        <a href="them_xe.php" class="btn btn-primary rounded-pill px-4 shadow-sm mt-2">Thêm xe mới ngay</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="card-footer bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center" style="border-top: 1px solid #f4f6f9 !important;">
            <div class="text-muted small">
                Hiển thị <span class="fw-bold text-dark">Tất cả</span> phương tiện
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0 shadow-sm rounded-pill overflow-hidden">
                    <li class="page-item disabled"><a class="page-link border-0 text-muted" href="#">Trước</a></li>
                    <li class="page-item active"><a class="page-link border-0" href="#">1</a></li>
                    <li class="page-item"><a class="page-link border-0 text-dark" href="#">2</a></li>
                    <li class="page-item"><a class="page-link border-0 text-dark" href="#">Sau</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
let allVehicles = <?= json_encode($vehicles) ?>;
let expiringVehicleIds = <?= json_encode($warningVehicleIds) ?>;
let oilChangeVehicleIds = <?= json_encode($oilChangeVehicleIds) ?>;
let oilWarningsMap = <?= json_encode($oilWarnings) ?>; // Map: {vid: [{thang:1, nam:2026}, ...]}
let currentDisplayedVehicles = allVehicles;

function searchVehicles() {
    const searchTerm = document.getElementById('searchInput').value.trim().toLowerCase();
    const notification = document.getElementById('searchNotification');
    const clearBtn = document.getElementById('clearBtn');
    
    if (searchTerm === '') {
        currentDisplayedVehicles = allVehicles;
        renderVehicles();
        clearBtn.style.display = 'none';
        notification.style.display = 'none';
        return;
    }
    
    // Filter vehicles
    currentDisplayedVehicles = allVehicles.filter(xe => {
        return (xe.bien_kiem_soat && xe.bien_kiem_soat.toLowerCase().includes(searchTerm)) ||
               (xe.loai_xe && xe.loai_xe.toLowerCase().includes(searchTerm)) ||
               (xe.nhan_hieu && xe.nhan_hieu.toLowerCase().includes(searchTerm)) ||
               (xe.don_vi_quan_ly && xe.don_vi_quan_ly.toLowerCase().includes(searchTerm));
    });
    
    if (currentDisplayedVehicles.length === 0) {
        notification.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i>Không tìm thấy xe!';
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    } else {
        notification.style.display = 'none';
    }
    
    renderVehicles();
    clearBtn.style.display = 'inline-block';
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearBtn').style.display = 'none';
    document.getElementById('searchNotification').style.display = 'none';
    currentDisplayedVehicles = allVehicles;
    renderVehicles();
}

function filterInspExpiring() {
    if (expiringVehicleIds.length === 0) {
        alert('Tuyệt vời! Hiện tại không có xe nào sắp hết hạn đăng kiểm trong 30 ngày tới.');
        return;
    }
    
    currentDisplayedVehicles = allVehicles.filter(xe => expiringVehicleIds.includes(xe.id));
    
    document.getElementById('searchInput').value = 'Lọc: Sắp hết đăng kiểm (' + expiringVehicleIds.length + ' xe)';
    document.getElementById('clearBtn').style.display = 'inline-block';
    
    renderVehicles();
}

function filterOilChange() {
    if (oilChangeVehicleIds.length === 0) {
        alert('Tuyệt vời! Hiện tại không có xe nào cần thay dầu.');
        return;
    }
    
    currentDisplayedVehicles = allVehicles.filter(xe => oilChangeVehicleIds.includes(xe.id));
    
    document.getElementById('searchInput').value = 'Lọc: Cần thay dầu (' + oilChangeVehicleIds.length + ' xe)';
    document.getElementById('clearBtn').style.display = 'inline-block';
    
    renderVehicles();
}

function renderVehicles() {
    const tbody = document.querySelector('table tbody');
    
    if (currentDisplayedVehicles.length === 0) {
        tbody.innerHTML = `
            <tr id="noResultRow">
                <td colspan="8" class="text-center py-5">
                    <div class="text-muted d-flex flex-column align-items-center">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fa-solid fa-search fs-1 text-secondary opacity-50"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Không tìm thấy kết quả</h5>
                        <p>Vui lòng thử lại với từ khóa khác.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    currentDisplayedVehicles.forEach(xe => {
        const statusBadge = xe.trang_thai == 1 
            ? '<span class="badge bg-success bg-opacity-10 text-success fw-semibold border border-success border-opacity-25 px-3 py-2 rounded-pill mb-1 d-inline-block"><i class="fa-solid fa-circle-check me-1"></i>Hoạt động</span>'
            : '<span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold border border-secondary border-opacity-25 px-3 py-2 rounded-pill mb-1 d-inline-block"><i class="fa-solid fa-circle-minus me-1"></i>Đã Dừng</span>';
        
        const toggleBtn = xe.trang_thai == 1
            ? `<button type="submit" class="btn btn-light text-danger shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Tạm dừng xe">
                   <i class="fa-solid fa-pause"></i>
               </button>`
            : `<button type="submit" class="btn btn-light text-success shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Kích hoạt xe">
                   <i class="fa-solid fa-play"></i>
               </button>`;

        // --- Render Oil Warnings ---
        let oilWarningsHtml = '';
        if (oilWarningsMap[xe.id] && oilWarningsMap[xe.id].length > 0) {
            oilWarningsMap[xe.id].forEach(w => {
                oilWarningsHtml += `
                    <div class="d-inline-flex align-items-center bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-pill px-2 py-1 mt-1">
                        <i class="fa-solid fa-oil-can text-danger small me-1"></i>
                        <span class="text-danger small fw-bold me-2">T${w.thang}/${w.nam}</span>
                        <button type="button" class="btn btn-sm btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 20px; height: 20px;" onclick="markOilChanged(${xe.id}, ${w.thang}, ${w.nam}, this)" title="Xác nhận đã thay dầu">
                            <i class="fa-solid fa-check" style="font-size: 10px;"></i>
                        </button>
                    </div>
                `;
            });
        }
        
        html += `
            <tr style="transition: all 0.2s;">
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="fa-solid fa-truck fs-5"></i>
                        </div>
                        <div>
                            <span class="fw-bolder text-dark d-block fs-6">${escapeHtml(xe.bien_kiem_soat)}</span>
                            <span class="text-muted small">ID: #${xe.id}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-info bg-opacity-10 text-info fw-semibold border border-info border-opacity-25 px-3 py-2 rounded-pill">${escapeHtml(xe.don_vi_quan_ly || 'Chưa xác định')}</span>
                </td>
                <td>
                    <span class="fw-bold text-dark d-block">${escapeHtml(xe.loai_xe || '---')}</span>
                    <span class="text-secondary small">${escapeHtml(xe.nhan_hieu || '')}</span>
                </td>
                <td>
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                        ${escapeHtml(xe.nam_san_xuat || 'N/A')}
                    </span>
                </td>
                <td>
                    <div class="small text-secondary font-monospace">
                        <div title="Số Khung"><span class="text-muted">K:</span> ${escapeHtml(xe.so_khung || '---')}</div>
                        <div title="Số Máy"><span class="text-muted">M:</span> ${escapeHtml(xe.so_may || '---')}</div>
                    </div>
                </td>
                <td class="fw-bold text-success text-end">
                    ${xe.nguyen_gia ? Number(xe.nguyen_gia).toLocaleString('vi-VN') : '0'}
                </td>
                <td class="text-center">
                    ${statusBadge}<br>
                    ${oilWarningsHtml}
                </td>
                <td class="text-end pe-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="chi_tiet.php?id=${xe.id}" class="btn btn-light text-primary shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Xem chi tiết">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái xe này?');">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="vehicle_id" value="${xe.id}">
                            <input type="hidden" name="current_status" value="${xe.trang_thai}">
                            ${toggleBtn}
                        </form>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function markOilChanged(vId, month, year, btn) {
    if (!confirm(`Xác nhận đánh dấu ĐÃ THAY DẦU cho tháng ${month}/${year}?`)) return;

    const formData = new FormData();
    formData.append('vehicle_id', vId);
    formData.append('month', month);
    formData.append('year', year);
    formData.append('status', 1); // 1 = Đã thay

    const container = btn.closest('.d-inline-flex');
    btn.disabled = true;

    fetch('ajax_update_oil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.remove();
            
            if (oilWarningsMap[vId]) {
                oilWarningsMap[vId] = oilWarningsMap[vId].filter(w => !(w.thang == month && w.nam == year));
            }
        } else {
            alert('Lỗi: ' + (data.message || 'Unknown'));
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi kết nối!');
        btn.disabled = false;
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchVehicles();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>