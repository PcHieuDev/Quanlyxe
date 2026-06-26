function createSyncPanel() {
    // 1. Tự động nhận diện Tháng/Năm từ tiêu đề bảng (VD: "2026-05-01 -> 2026-05-31")
    let defaultMonth = new Date().getMonth() + 1;
    let defaultYear = new Date().getFullYear();
    
    const table = document.getElementById('gvw1_DXMainTable');
    if (table) {
        const headerText = table.innerText || "";
        const dateMatch = headerText.match(/(\d{4})-(\d{2})-\d{2}\s*->/);
        if (dateMatch) {
            defaultYear = parseInt(dateMatch[1]);
            defaultMonth = parseInt(dateMatch[2]);
        }
    }

    // 2. Tạo UI Panel
    const panel = document.createElement('div');
    panel.id = 'quanlyxe-sync-panel';
    panel.innerHTML = `
        <div class="qx-header">
            <img src="https://vietnampost.vn/apps/frontend/images/header/logo-fuild.png" alt="Logo" height="24">
            <span>Đồng bộ KM</span>
        </div>
        <div class="qx-body">
            <p class="qx-desc">Dữ liệu trên màn hình sẽ được gửi về hệ thống nội bộ của bạn.</p>
            <div class="qx-form-group">
                <label>Tháng:</label>
                <input type="number" id="qx-month" value="${defaultMonth}" min="1" max="12">
            </div>
            <div class="qx-form-group">
                <label>Năm:</label>
                <input type="number" id="qx-year" value="${defaultYear}" min="2000" max="2100">
            </div>
            <button id="qx-submit-btn">🚀 Bắt đầu Đồng bộ</button>
        </div>
        <div id="qx-message"></div>
    `;
    document.body.appendChild(panel);

    // 3. (Bỏ tính năng tự động điền ngày vì DevExpress chặn JS can thiệp từ bên ngoài)


    // Hàm tách dữ liệu và đẩy lên API (được gọi sau khi đã có bảng dữ liệu)
    function extractAndSync(currentTable, month, year, btn, msgDiv) {
        if (!currentTable) {
            msgDiv.innerHTML = '<span style="color: red;">❌ Không tìm thấy bảng dữ liệu!</span>';
            btn.innerHTML = '🚀 Bắt đầu Đồng bộ';
            btn.disabled = false;
            return;
        }

        const rows = currentTable.querySelectorAll('tr[id^="gvw1_DXDataRow"]');
        const data = [];

        rows.forEach(row => {
            const tds = row.querySelectorAll('td');
            if (tds.length >= 4) {
                let plate = "";
                for (let i = 0; i < tds.length - 1; i++) {
                    let text = tds[i].textContent.trim();
                    if (text.length > 5 && /[0-9]/.test(text) && /[A-Z]/.test(text) && text.includes('-')) {
                        plate = text;
                        break;
                    }
                }

                const total = tds[tds.length - 1].textContent.trim();

                if (plate && total) {
                    data.push({ bien_so: plate, km: total });
                }
            }
        });

        if (data.length === 0) {
            msgDiv.innerHTML = '<span style="color: red;">❌ Bảng không có dữ liệu xe nào!</span>';
            btn.innerHTML = '🚀 Bắt đầu Đồng bộ';
            btn.disabled = false;
            return;
        }

        msgDiv.innerHTML = '<span style="color: blue;">⏳ Đang đẩy dữ liệu lên Google Sheet...</span>';

        // Gửi data cho background.js để thực hiện fetch (tránh lỗi CORS của trình duyệt)
        chrome.runtime.sendMessage({
            action: 'sync_to_google_sheet',
            month: month,
            year: year,
            data: data
        }, response => {
            btn.innerHTML = '🚀 Bắt đầu Đồng bộ';
            btn.disabled = false;

            if (!response) {
                msgDiv.innerHTML = '<span style="color: red;">❌ Lỗi: Không thể kết nối với Background Script!</span>';
                return;
            }

            if (response.success) {
                msgDiv.innerHTML = '<span style="color: green;">✅ ' + response.message + '</span>';
                if (response.warnings && response.warnings.length > 0) {
                    msgDiv.innerHTML += '<br><span style="color: orange; font-size: 12px;">' + response.warnings.join('<br>') + '</span>';
                }
            } else {
                msgDiv.innerHTML = '<span style="color: red;">❌ Lỗi kết nối tới Google Sheet: ' + (response.error || 'Unknown error') + '</span>';
            }
        });
    }

    // 4. Xử lý sự kiện click đồng bộ
    document.getElementById('qx-submit-btn').addEventListener('click', function() {
        const btn = this;
        const msgDiv = document.getElementById('qx-message');
        const month = parseInt(document.getElementById('qx-month').value);
        const year = parseInt(document.getElementById('qx-year').value);

        if (!month || !year) return;

        const currentTable = document.getElementById('gvw1_DXMainTable');
        
        btn.innerHTML = '⏳ Đang đồng bộ...';
        btn.disabled = true;
        
        extractAndSync(currentTable, month, year, btn, msgDiv);
    });
}

setTimeout(createSyncPanel, 1500);
