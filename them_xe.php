<?php
require_once 'includes/header.php';

// Only admin can add new vehicles
if ($role !== 'admin') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Bạn không có quyền truy cập trang này!</div></div>";
    require_once 'footer.php';
    exit;
}

$message = '';

// Xử lý Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Lấy dữ liệu từ form
        $bks = $_POST['bien_kiem_soat'] ?? '';
        
        // Validate cơ bản
        if (empty($bks)) {
            throw new Exception("Biển kiểm soát không được để trống!");
        }

        // Xử lý upload file bảo hiểm
        $file_bao_hiem_paths = [];
        if (isset($_FILES['file_bao_hiem']) && !empty($_FILES['file_bao_hiem']['name'][0])) {
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
        $file_bao_hiem_json = !empty($file_bao_hiem_paths) ? json_encode($file_bao_hiem_paths) : null;

        // Chuẩn bị câu lệnh SQL
        $sql = "INSERT INTO vehicles (
            bien_kiem_soat, don_vi_quan_ly, loai_xe, nhan_hieu, so_loai, so_may, so_khung,
            nam_san_xuat, ngay_dang_ky_lan_dau, nam_het_nien_han, nguyen_gia,
            cong_thuc_banh_xe, vet_banh_xe, kich_thuoc_bao, kich_thuoc_long_thung,
            the_tich_thung, chieu_dai_co_so, trong_luong_ban_than, trong_tai_cho_phep,
            trong_luong_toan_bo, so_nguoi_cho_phep, loai_nhien_lieu, the_tich_lam_viec,
            cong_suat_lon_nhat, co_lop_truc_1, co_lop_truc_2, thong_so_ac_quy, file_bao_hiem, trang_thai
        ) VALUES (
            :bks, :don_vi, :loai_xe, :nhan_hieu, :so_loai, :so_may, :so_khung,
            :nam_sx, :ngay_dk, :nam_hh, :nguyen_gia,
            :ct_banh, :vet_banh, :kt_bao, :kt_thung,
            :tt_thung, :cd_co_so, :tl_ban_than, :tt_cho_phep,
            :tl_toan_bo, :so_nguoi, :loai_nhien_lieu, :tt_lam_viec,
            :cong_suat, :lop_1, :lop_2, :ac_quy, :file_bao_hiem, 1
        )";

        $stmt = $conn->prepare($sql);
        
        $stmt->execute([
            ':bks' => $bks,
            ':don_vi' => $_POST['don_vi_quan_ly'],
            ':loai_xe' => $_POST['loai_xe'],
            ':nhan_hieu' => $_POST['nhan_hieu'],
            ':so_loai' => $_POST['so_loai'],
            ':so_may' => $_POST['so_may'],
            ':so_khung' => $_POST['so_khung'],
            ':nam_sx' => $_POST['nam_san_xuat'] ?: null,
            ':ngay_dk' => $_POST['ngay_dang_ky_lan_dau'] ?: null,
            ':nam_hh' => $_POST['nam_het_nien_han'] ?: null,
            ':nguyen_gia' => $_POST['nguyen_gia'] ?: 0,
            ':ct_banh' => $_POST['cong_thuc_banh_xe'],
            ':vet_banh' => $_POST['vet_banh_xe'],
            ':kt_bao' => $_POST['kich_thuoc_bao'],
            ':kt_thung' => $_POST['kich_thuoc_long_thung'],
            ':tt_thung' => $_POST['the_tich_thung'],
            ':cd_co_so' => $_POST['chieu_dai_co_so'],
            ':tl_ban_than' => $_POST['trong_luong_ban_than'] ?: 0,
            ':tt_cho_phep' => $_POST['trong_tai_cho_phep'] ?: 0,
            ':tl_toan_bo' => $_POST['trong_luong_toan_bo'] ?: 0,
            ':so_nguoi' => $_POST['so_nguoi_cho_phep'] ?: 0,
            ':loai_nhien_lieu' => $_POST['loai_nhien_lieu'],
            ':tt_lam_viec' => $_POST['the_tich_lam_viec'],
            ':cong_suat' => $_POST['cong_suat_lon_nhat'],
            ':lop_1' => $_POST['co_lop_truc_1'],
            ':lop_2' => $_POST['co_lop_truc_2'],
            ':ac_quy' => $_POST['thong_so_ac_quy'],
            ':file_bao_hiem' => $file_bao_hiem_json
        ]);

        $newId = $conn->lastInsertId();
        // Redirect to detail page
        echo "<script>alert('Thêm mới thành công!'); window.location.href='chi_tiet.php?id=$newId';</script>";
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            $message = "<div class='alert alert-danger'>Lỗi: Biển số xe này đã tồn tại trong hệ thống!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Lỗi hệ thống: " . $e->getMessage() . "</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-0">Thêm Hồ sơ Xe mới</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Thêm mới</li>
                </ol>
            </nav>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <?= $message ?>

    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="row g-4">
            
            <!-- COT 1: Thông tin Chung & Tài chính -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white text-primary">
                        <i class="fa-solid fa-id-card me-2"></i> 1. Thông tin Định danh
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Biển kiểm soát <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg text-uppercase" name="bien_kiem_soat" placeholder="Ví dụ: 29A-123.45" required>
                            <div class="form-text">Nhập chính xác biển số xe</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Đơn vị quản lý</label>
                            <input type="text" class="form-control" name="don_vi_quan_ly" placeholder="VD: Bưu điện Hà Nội">
                            <div class="form-text">Đơn vị đang sử dụng/quản lý xe</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Loại xe</label>
                            <input type="text" class="form-control" name="loai_xe" placeholder="VD: Ô tô tải (thùng kín)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nhãn hiệu</label>
                            <input type="text" class="form-control" name="nhan_hieu" placeholder="VD: ISUZU, THACO...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số loại (Model)</label>
                            <input type="text" class="form-control" name="so_loai">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số khung</label>
                            <input type="text" class="form-control" name="so_khung">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số máy</label>
                            <input type="text" class="form-control" name="so_may">
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white text-success">
                        <i class="fa-solid fa-money-bill me-2"></i> 2. Tài chính & Thời hạn
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                             <div class="col-6 mb-3">
                                <label class="form-label">Năm sản xuất</label>
                                <input type="number" class="form-control" name="nam_san_xuat" placeholder="YYYY" min="1950" max="<?= date('Y') ?>">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Năm hết niên hạn</label>
                                <input type="number" class="form-control" name="nam_het_nien_han" placeholder="YYYY">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ngày đăng ký lần đầu</label>
                            <input type="date" class="form-control" name="ngay_dang_ky_lan_dau">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nguyên giá (VNĐ)</label>
                            <div class="input-group">
                                <input type="number" class="form-control fw-bold" name="nguyen_gia" placeholder="0">
                                <span class="input-group-text">₫</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giấy chứng nhận bảo hiểm (PDF/Ảnh)</label>
                            <div class="p-3 border rounded bg-light position-relative" id="paste_area" style="border: 2px dashed #0d6efd !important;">
                                <input type="file" class="form-control mb-2" name="file_bao_hiem[]" id="file_bao_hiem" accept=".pdf,.jpg,.jpeg,.png" multiple>
                                <div class="form-text text-center"><i class="fa-solid fa-paste me-1"></i> Có thể nhấn <strong>Ctrl+V</strong> để dán nhiều ảnh/PDF trực tiếp vào đây</div>
                                <div class="text-center" id="preview_bao_hiem_container">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COT 2: Thông số Kỹ Thuật -->
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white text-dark">
                        <i class="fa-solid fa-cogs me-2"></i> 3. Thông số Kỹ Thuật
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <h6 class="text-primary border-bottom pb-2 mt-3">Kích thước & Trọng lượng</h6>
                            
                            <div class="col-md-6">
                                <label class="form-label">Kích thước xe (D x R x C)</label>
                                <input type="text" class="form-control" name="kich_thuoc_bao" placeholder="VD: 6000 x 2200 x 3000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kích thước lòng thùng</label>
                                <input type="text" class="form-control" name="kich_thuoc_long_thung">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Chiều dài cơ sở</label>
                                <input type="text" class="form-control" name="chieu_dai_co_so">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vết bánh xe</label>
                                <input type="text" class="form-control" name="vet_banh_xe">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label">Công thức bánh xe</label>
                                <input type="text" class="form-control" name="cong_thuc_banh_xe" placeholder="VD: 4x2">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Trọng lượng bản thân (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="trong_luong_ban_than">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tải trọng cho phép (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="trong_tai_cho_phep">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tổng trọng lượng (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="trong_luong_toan_bo">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Số người cho phép</label>
                                <input type="number" class="form-control" name="so_nguoi_cho_phep" value="2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Thể tích thùng (m3)</label>
                                <input type="text" class="form-control" name="the_tich_thung">
                            </div>

                            <h6 class="text-primary border-bottom pb-2 mt-4">Động cơ & Nhiên liệu</h6>
                            <div class="col-md-6">
                                <label class="form-label">Loại nhiên liệu</label>
                                <select class="form-select" name="loai_nhien_lieu">
                                    <option value="Diesel">Diesel</option>
                                    <option value="Xăng">Xăng</option>
                                    <option value="Điện">Điện</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Thể tích làm việc</label>
                                <input type="text" class="form-control" name="the_tich_lam_viec">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Công suất lớn nhất</label>
                                <input type="text" class="form-control" name="cong_suat_lon_nhat">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Thông số Ắc quy</label>
                                <input type="text" class="form-control" name="thong_so_ac_quy">
                            </div>

                            <h6 class="text-primary border-bottom pb-2 mt-4">Thông số Lốp</h6>
                             <div class="col-md-6">
                                <label class="form-label">Cỡ lốp trục 1</label>
                                <input type="text" class="form-control" name="co_lop_truc_1">
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Cỡ lốp trục 2</label>
                                <input type="text" class="form-control" name="co_lop_truc_2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4 mb-5">
            <button type="reset" class="btn btn-light border me-3 px-4">Làm lại</button>
            <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold text-uppercase shadow">
                <i class="fa-solid fa-save me-2"></i> Lưu hồ sơ xe
            </button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file_bao_hiem');
    const previewContainer = document.getElementById('preview_bao_hiem_container');
    const pasteArea = document.getElementById('paste_area');
    let dataTransfer = new DataTransfer();

    // Handle paste event anywhere on document or specifically in the area
    document.addEventListener('paste', function(e) {
        if (e.clipboardData && e.clipboardData.files.length > 0) {
            let hasNewFiles = false;
            for (let i = 0; i < e.clipboardData.files.length; i++) {
                const file = e.clipboardData.files[i];
                if (file.type.startsWith('image/') || file.type === 'application/pdf') {
                    dataTransfer.items.add(file);
                    hasNewFiles = true;
                }
            }
            
            if (hasNewFiles) {
                fileInput.files = dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
                
                pasteArea.classList.add('bg-success', 'bg-opacity-10');
                setTimeout(() => pasteArea.classList.remove('bg-success', 'bg-opacity-10'), 500);
            }
        }
    });

    // Handle file selection (both manual and via paste)
    fileInput.addEventListener('change', function(e) {
        if (this.files !== dataTransfer.files) {
            // Manual selection clears and replaces pasted items to match standard file input behavior
            dataTransfer = new DataTransfer();
            for (let i = 0; i < this.files.length; i++) {
                dataTransfer.items.add(this.files[i]);
            }
            fileInput.files = dataTransfer.files;
        }

        previewContainer.innerHTML = '';
        if (this.files && this.files.length > 0) {
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.style.cssText = 'max-height: 120px; margin: 5px; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.2);';
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    const span = document.createElement('span');
                    span.className = 'badge bg-primary m-1 d-inline-block';
                    span.style.padding = '8px';
                    span.innerHTML = '<i class="fa-solid fa-file-pdf me-1"></i> ' + file.name;
                    previewContainer.appendChild(span);
                }
            }
        }
    });
});
</script>
