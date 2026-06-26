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

    // 3. Xử lý sự kiện click
    document.getElementById('qx-submit-btn').addEventListener('click', function() {
        const btn = this;
        const msgDiv = document.getElementById('qx-message');
        const month = parseInt(document.getElementById('qx-month').value);
        const year = parseInt(document.getElementById('qx-year').value);

        const currentTable = document.getElementById('gvw1_DXMainTable');
        if (!currentTable) {
            msgDiv.innerHTML = '<span style="color: red;">❌ Không tìm thấy bảng dữ liệu! Hãy bấm Xem báo cáo trước.</span>';
            return;
        }

        const rows = currentTable.querySelectorAll('tr[id^="gvw1_DXDataRow"]');
        const data = [];

        rows.forEach(row => {
            const tds = row.querySelectorAll('td');
            if (tds.length >= 5) {
                let plateColIndex = 2; 
                let plate = tds[plateColIndex].innerText.trim();
                if (!/[A-Z]/.test(plate) && /[A-Z]/.test(tds[1].innerText.trim())) {
                    plateColIndex = 1;
                    plate = tds[plateColIndex].innerText.trim();
                }
                const total = tds[tds.length - 1].innerText.trim();

                if (plate && total) {
                    data.push({ bien_so: plate, km: total });
                }
            }
        });

        if (data.length === 0) {
            msgDiv.innerHTML = '<span style="color: red;">❌ Bảng không có dữ liệu xe nào!</span>';
            return;
        }

        btn.innerHTML = '⏳ Đang đồng bộ...';
        btn.disabled = true;
        msgDiv.innerHTML = '';

        fetch('https://script.google.com/macros/s/AKfycbwoBZZFON_3u0V8b9Pak8_vvK8lE99haAOD1X533PfTyf_TN5jTywNDD38ANtAY8L_L/exec', {
            method: 'POST',
            // Sử dụng text/plain để tránh bị Google chặn preflight CORS
            headers: { 'Content-Type': 'text/plain;charset=utf-8' },
            body: JSON.stringify({ month: month, year: year, data: data })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                msgDiv.innerHTML = '<span style="color: green;">✅ ' + res.message + '</span>';
                if (res.warnings && res.warnings.length > 0) {
                    msgDiv.innerHTML += '<br><span style="color: orange; font-size: 12px;">' + res.warnings.join('<br>') + '</span>';
                }
            } else {
                msgDiv.innerHTML = '<span style="color: red;">❌ Lỗi: ' + res.message + '</span>';
            }
        })
        .catch(err => {
            console.error(err);
            msgDiv.innerHTML = '<span style="color: red;">❌ Lỗi kết nối tới 10.42.40.20:8388!</span>';
        })
        .finally(() => {
            btn.innerHTML = '🚀 Bắt đầu Đồng bộ';
            btn.disabled = false;
        });
    });
}

setTimeout(createSyncPanel, 1500);
