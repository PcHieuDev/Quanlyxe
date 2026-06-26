# Hệ Thống Quản Lý Xe & Vận Hành

## Tính năng mới: Import Số KM từ Excel

### 📋 Tổng quan
Hệ thống đã được bổ sung chức năng **Import số km** từ file Excel/CSV, giúp:
- ✅ Import hàng loạt dữ liệu số km xe đã đi trong tháng
- ✅ Tự động so sánh với số km bàn giao
- ✅ Cảnh báo xe cần thay dầu (đi quá 5000km/tháng)
- ✅ Tự động tính km tích lũy cho tháng sau
- ✅ Theo dõi lịch sử km qua các tháng

### 🚀 Cách sử dụng nhanh

#### Bước 1: Chạy script SQL
Mở phpMyAdmin và chạy file: `setup/add_km_tich_luy.sql`

```sql
ALTER TABLE operation_stats 
ADD COLUMN km_tich_luy DECIMAL(10,2) DEFAULT 0 AFTER km_trong_thang;
```

#### Bước 2: Chuẩn bị file CSV
Chuyển file Excel của bạn sang CSV với 2 cột:
- Cột 1: Biển số xe
- Cột 2: Số km đã đi trong tháng

Xem file mẫu: `sample_km.csv`

#### Bước 3: Import
1. Đăng nhập admin
2. Vào menu **Import KM**
3. Chọn file CSV
4. Nhấn **Upload & Import**

### 📊 Luồng hoạt động

```
Tháng 1:
├─ Bàn giao xe: 50,000 km
├─ Import km tháng 1: 1,500 km
└─ KM tích lũy: 51,500 km

Tháng 2:
├─ KM đầu tháng: 51,500 km (tự động lấy từ tháng 1)
├─ Import km tháng 2: 2,000 km
└─ KM tích lũy: 53,500 km

Tháng 3:
├─ KM đầu tháng: 53,500 km (tự động lấy từ tháng 2)
├─ Import km tháng 3: 5,200 km ⚠️ CẢNH BÁO THAY DẦU
└─ KM tích lũy: 58,700 km
```

### 📁 Files mới

| File | Mô tả |
|------|-------|
| `import_km.php` | Trang import số km từ Excel/CSV |
| `setup/add_km_tich_luy.sql` | Script thêm cột km_tich_luy |
| `sample_km.csv` | File CSV mẫu để test |
| `HUONG_DAN_IMPORT_KM.md` | Hướng dẫn chi tiết |

### 🔧 Thay đổi Database

**Bảng: `operation_stats`**
- Thêm cột mới: `km_tich_luy` (DECIMAL 10,2)
- Lưu tổng km tích lũy sau mỗi tháng

### 📸 Screenshots

#### Trang Import KM
- Form upload file CSV
- Bảng hiển thị dữ liệu tháng hiện tại
- Cảnh báo xe cần thay dầu (màu đỏ)

#### Tab Nhật trình (chi_tiet.php)
- Thêm cột "KM tích lũy" để theo dõi

### ⚠️ Lưu ý quan trọng

1. **Số km bàn giao** phải được nhập trước trong tab "Bàn giao sử dụng"
2. Hệ thống chỉ hỗ trợ file **CSV** (chưa hỗ trợ trực tiếp .xlsx)
3. Cảnh báo thay dầu dựa trên **km trong tháng > 5000**, không phải km tích lũy
4. Biển số trong file CSV phải **khớp chính xác** với biển số trong hệ thống

### 📞 Hỗ trợ

Nếu gặp vấn đề, vui lòng kiểm tra:
- File CSV có đúng định dạng không?
- Đã chạy script SQL chưa?
- Các xe đã có số km bàn giao chưa?

---

**Phát triển bởi:** Phan Công Hiếu  
**Liên hệ:** 0972.848.538 (Zalo/Viber/Tele)
