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

<div class="container">
    <!-- Welcome Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Trang chủ</h2>
            <p class="text-muted">Tổng hợp tình hình phương tiện & vận hành</p>
        </div>
        <a href="them_xe.php" class="btn btn-primary shadow-sm">
            <i class="fa-solid fa-plus me-2"></i>Thêm Hồ sơ xe
        </a>
    </div>

    <!-- Floating Stats Sidebar (VNPost Style) -->
    <div class="floating-stats-wrapper">
        <!-- Card 1: Tổng số xe -->
        <div class="stats-box-mini" title="Tổng phương tiện">
            <i class="fa-solid fa-car"></i>
            <span class="stat-value"><?= number_format($totalVehicles) ?></span>
            <span class="stat-label">Tổng xe</span>
        </div>

        <!-- Card 2: Đang hoạt động -->
        <div class="stats-box-mini" title="Đang hoạt động">
            <i class="fa-solid fa-road"></i>
            <span class="stat-value"><?= number_format($activeVehicles) ?></span>
            <span class="stat-label">Hoạt động</span>
        </div>

        <!-- Card 3: Sắp hết đăng kiểm -->
        <div class="stats-box-mini" onclick="filterInspExpiring()" title="Sắp hết hạn Đăng kiểm">
            <i class="fa-solid fa-clipboard-check"></i>
            <?php if($warningInspections > 0): ?>
                <span class="stat-notify-badge"></span>
            <?php endif; ?>
            <span class="stat-value text-danger"><?= number_format($warningInspections) ?></span>
            <span class="stat-label">Đăng kiểm</span>
        </div>

        <!-- Card 4: Cần thay dầu -->
        <div class="stats-box-mini" onclick="filterOilChange()" title="Xe cần thay dầu (>5000km/tháng)" style="cursor: pointer;">
            <i class="fa-solid fa-oil-can" style="color: #ff6b6b;"></i>
            <?php if($needOilChange > 0): ?>
                <span class="stat-notify-badge"></span>
            <?php endif; ?>
            <span class="stat-value <?= $needOilChange > 0 ? 'text-danger' : '' ?>"><?= number_format($needOilChange) ?></span>
            <span class="stat-label">Thay dầu</span>
        </div>
    </div>

    <div class="row">
        <!-- Main Content Column: Vehicles List (Full Width) -->
        <div class="col-12">
            <!-- Vehicles List Table -->
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fa-solid fa-list me-2"></i>Danh sách Phương tiện</h5>
                    <div class="d-flex ms-auto">
                        <div class="input-group" style="max-width: 350px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Tìm biển số, loại xe..." autocomplete="off">
                            <button class="btn btn-outline-danger" type="button" onclick="clearSearch()" id="clearBtn" style="display:none;"><i class="fa-solid fa-times"></i></button>
                        </div>
                    </div>
                    <div id="searchNotification" class="alert alert-warning mt-2 mb-0" style="display:none; position:absolute; top:60px; right:20px; z-index:1000;"></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Biển kiểm soát</th>
                                <th>Đơn vị quản lý</th>
                                <th>Loại xe & Nhãn hiệu</th>
                                <th>Năm SX</th>
                                <th>Số khung / Số máy</th>
                                <th>Giá trị (VNĐ)</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($vehicles) > 0): ?>
                                <?php foreach($vehicles as $xe): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle p-2 me-3 text-primary">
                                                <i class="fa-solid fa-truck"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($xe['bien_kiem_soat']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info"><?= htmlspecialchars($xe['don_vi_quan_ly'] ?: 'Chưa xác định') ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold d-block"><?= htmlspecialchars($xe['loai_xe'] ?? '---') ?></span>
                                        <small class="text-secondary"><?= htmlspecialchars($xe['nhan_hieu'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($xe['nam_san_xuat'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-sm text-secondary">
                                            <div>K: <?= htmlspecialchars($xe['so_khung'] ?? '---') ?></div>
                                            <div>M: <?= htmlspecialchars($xe['so_may'] ?? '---') ?></div>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?= $xe['nguyen_gia'] ? number_format($xe['nguyen_gia']) : '0' ?>
                                    </td>
                                    <td>
                                        <?php if($xe['trang_thai'] == 1): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">Dừng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="chi_tiet.php?id=<?= $xe['id'] ?>" class="btn btn-sm btn-outline-primary shadow-sm" title="Xem chi tiết">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái xe này?');">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="vehicle_id" value="<?= $xe['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $xe['trang_thai'] ?>">
                                                <?php if($xe['trang_thai'] == 1): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success shadow-sm" title="Đang hoạt động - Bấm để Dừng">
                                                        <i class="fa-solid fa-toggle-on"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary shadow-sm" title="Dừng hoạt động - Bấm để Kích hoạt">
                                                        <i class="fa-solid fa-toggle-off"></i>
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
                                        <div class="text-muted">
                                            <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-25"></i>
                                            <p>Chưa có phương tiện nào trong hệ thống.</p>
                                            <a href="them_xe.php" class="btn btn-primary btn-sm mt-2">Thêm mới ngay</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="card-footer bg-white py-3">
                    <nav>
                        <ul class="pagination justify-content-end mb-0">
                            <li class="page-item disabled"><a class="page-link" href="#">Trước</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">Sau</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
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
        notification.innerHTML = '<i class="fa-solid fa-exclamation-triangle me-2"></i>Xe không tồn tại';
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
    
    // Update UI to show filter state
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
    
    // Update UI to show filter state
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
                    <div class="text-muted">
                        <i class="fa-solid fa-search fa-3x mb-3 opacity-25"></i>
                        <p>Không tìm thấy xe phù hợp với từ khóa tìm kiếm.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    currentDisplayedVehicles.forEach(xe => {
        const statusBadge = xe.trang_thai == 1 
            ? '<span class="badge bg-success bg-opacity-10 text-success">Hoạt động</span>'
            : '<span class="badge bg-secondary bg-opacity-10 text-secondary">Dừng</span>';
        
        const toggleBtn = xe.trang_thai == 1
            ? `<button type="submit" class="btn btn-sm btn-outline-success shadow-sm" title="Đang hoạt động - Bấm để Dừng">
                   <i class="fa-solid fa-toggle-on"></i>
               </button>`
            : `<button type="submit" class="btn btn-sm btn-outline-secondary shadow-sm" title="Dừng hoạt động - Bấm để Kích hoạt">
                   <i class="fa-solid fa-toggle-off"></i>
               </button>`;

        // --- Render Oil Warnings ---
        let oilWarningsHtml = '';
        if (oilWarningsMap[xe.id] && oilWarningsMap[xe.id].length > 0) {
            oilWarningsMap[xe.id].forEach(w => {
                oilWarningsHtml += `
                    <div class="d-flex align-items-center gap-2 mt-1 alert alert-danger p-1 mb-0 small" style="width: fit-content;">
                        <span><i class="fa-solid fa-oil-can me-1"></i>Thay dầu T${w.thang}/${w.nam}</span>
                        <button type="button" class="btn btn-xs btn-light border btn-sm py-0 px-1" 
                                onclick="markOilChanged(${xe.id}, ${w.thang}, ${w.nam}, this)" 
                                title="Đánh dấu đã thay">
                            <i class="fa-solid fa-check text-success"></i>
                        </button>
                    </div>
                `;
            });
        }
        // ---------------------------
        
        html += `
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded-circle p-2 me-3 text-primary">
                            <i class="fa-solid fa-truck"></i>
                        </div>
                        <div>
                            <span class="fw-bold text-dark d-block">${escapeHtml(xe.bien_kiem_soat)}</span>
                            <small class="text-muted">ID: #${xe.id}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-info bg-opacity-10 text-info">${escapeHtml(xe.don_vi_quan_ly || 'Chưa xác định')}</span>
                </td>
                <td>
                    <span class="fw-semibold d-block">${escapeHtml(xe.loai_xe || '---')}</span>
                    <small class="text-secondary">${escapeHtml(xe.nhan_hieu || '')}</small>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">${escapeHtml(xe.nam_san_xuat || 'N/A')}</span>
                </td>
                <td>
                    <div class="text-sm text-secondary">
                        <div>K: ${escapeHtml(xe.so_khung || '---')}</div>
                        <div>M: ${escapeHtml(xe.so_may || '---')}</div>
                    </div>
                </td>
                <td class="fw-bold text-success">
                    ${xe.nguyen_gia ? Number(xe.nguyen_gia).toLocaleString('vi-VN') : '0'}
                </td>
                <td>
                    ${statusBadge}
                    ${oilWarningsHtml}
                </td>
                <td class="text-end pe-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="chi_tiet.php?id=${xe.id}" class="btn btn-sm btn-outline-primary shadow-sm" title="Xem chi tiết">
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

    // Disable button
    const container = btn.closest('.alert');
    btn.disabled = true;

    fetch('ajax_update_oil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the warning from UI
            container.remove();
            
            // Update the global map so re-renders don't show it again
            if (oilWarningsMap[vId]) {
                oilWarningsMap[vId] = oilWarningsMap[vId].filter(w => !(w.thang == month && w.nam == year));
                if (oilWarningsMap[vId].length === 0) {
                    // Update counters if needed, but page refresh is easier to fully sync top stats
                    // For now, just remove from map
                }
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

// Enable search on Enter key
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchVehicles();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>