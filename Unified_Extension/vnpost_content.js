console.log("VNPOST Tools Extension: Script execution started!");
// Tránh chèn nhiều lần nếu script chạy lại
if (!document.getElementById("vnpost-export-footer")) {
  let lastReportData = null;
  const footer = document.createElement("div");
  footer.id = "vnpost-export-footer";

  // Thiết lập ngày mặc định (hôm nay)
  const today = new Date();
  const dateString = today.toISOString().split('T')[0];

  footer.innerHTML = `
    <div id="vnpost-export-left">
      <div style="font-weight:bold; font-size: 16px; margin-bottom: 5px;">📊 VNPOST Tools</div>
      <div class="vnpost-flex-row">
        <div class="form-group">
          <label>Mã Đơn vị:</label>
          <input type="text" id="ext-unitCode" value="46" style="width: 50px;">
        </div>
        <div class="form-group">
          <label>Từ ngày:</label>
          <input type="date" id="ext-fromDate" value="${dateString}">
        </div>
        <div class="form-group">
          <label>Đến ngày (KHHH):</label>
          <input type="date" id="ext-toDate" value="${dateString}">
        </div>
      </div>
      <div class="vnpost-flex-row" style="margin-top: 10px; flex-wrap: wrap; gap: 8px;">
        <button id="ext-exportBtn" class="btn">⬇️ Excel</button>
        <button id="ext-composeBtn" class="btn btn-green">💬 Soạn</button>
        <button id="ext-pushSheetBtn" class="btn" style="background-color: #d97706;">☁️ Sheets</button>
        <button id="ext-pushJsonBtn" class="btn" style="background-color: #6366f1;">📁 JSON</button>
      </div>
      <div id="export-status" style="margin-top: 10px;">Sẵn sàng</div>
    </div>
    <div id="vnpost-export-middle" style="display:flex; flex-direction:column; gap:10px; width: 320px; flex-shrink: 0; padding-left: 15px; border-left: 1px solid rgba(255,255,255,0.1);">
      <div style="font-weight:bold; font-size: 14px; margin-bottom: 5px; color: #f43f5e;">🚀 Đồng bộ Hàng loạt</div>
      <div class="vnpost-flex-row" style="margin-bottom: 5px;">
        <div class="form-group">
          <label>Từ ngày:</label>
          <input type="date" id="ext-batchFromDate" value="${dateString}">
        </div>
        <div class="form-group">
          <label>Đến ngày:</label>
          <input type="date" id="ext-batchToDate" value="${dateString}">
        </div>
      </div>
      <button id="ext-batchSyncBtn" class="btn" style="background-color: #e11d48; width: 100%;">🚀 Tự động Đồng bộ (Nhiều ngày)</button>
    </div>
    <div id="vnpost-export-right">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
        <label style="font-weight:bold;">Kết quả tin nhắn báo cáo:</label>
        <button id="ext-copyBtn" class="btn" style="padding:4px 8px; font-size:12px; background-color:#475569; width:auto; flex-grow:0;">📋 Sao chép</button>
      </div>
      <textarea id="ext-messageOutput" readonly placeholder="Nhấn nút 'Soạn' để hệ thống tự động tính toán dữ liệu..."></textarea>
    </div>
  `;

  document.body.appendChild(footer);
  document.body.style.paddingBottom = "300px"; // Trả lại padding-bottom 300px

  const exportBtn = document.getElementById("ext-exportBtn");
  const composeBtn = document.getElementById("ext-composeBtn");
  const pushSheetBtn = document.getElementById("ext-pushSheetBtn");
  const pushJsonBtn = document.getElementById("ext-pushJsonBtn");
  const batchSyncBtn = document.getElementById("ext-batchSyncBtn");
  const statusDiv = document.getElementById("export-status");
  const unitCodeInput = document.getElementById("ext-unitCode");
  const fromDateInput = document.getElementById("ext-fromDate");
  const toDateInput = document.getElementById("ext-toDate");
  const batchFromDateInput = document.getElementById("ext-batchFromDate");
  const batchToDateInput = document.getElementById("ext-batchToDate");
  const messageOutput = document.getElementById("ext-messageOutput");
  const copyBtn = document.getElementById("ext-copyBtn");

  copyBtn.addEventListener("click", () => {
    if (!messageOutput.value) return;
    navigator.clipboard.writeText(messageOutput.value);
    const oldText = copyBtn.innerText;
    copyBtn.innerText = "✅ Đã copy!";
    setTimeout(() => copyBtn.innerText = oldText, 2000);
  });

  function setStatus(msg, type = "") {
    statusDiv.textContent = msg;
    statusDiv.className = type ? `export-${type}` : "";
  }

  function getToken() {
    let token = localStorage.getItem("access_token") || sessionStorage.getItem("access_token");
    if (!token) {
      for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        if (key.includes("oidc.user")) {
          try {
            const data = JSON.parse(sessionStorage.getItem(key));
            if (data && data.access_token) return data.access_token;
          } catch (e) { }
        }
      }
    }
    return token;
  }

  async function fetchDataForRange(token, unitCode, fromStr, toStr, isBackground = false) {
    let allData = [];
    let pageIndex = 1;
    const pageSize = 10000; // Load tối đa để tính toán cho nhanh
    let hasMore = true;

    while (hasMore) {
      if (!isBackground) setStatus(`Đang tải trang ${pageIndex}...`, "loading");

      let apiUrl = "https://report.vnpost.vn/gw/crmbetaapi/Item/GetsReportRevenueOutputByUnit";
      let payload = {
        unitCodeSend: unitCode,
        typeService: "",
        fromDate: fromStr,
        toDate: toStr,
        pageIndex: pageIndex,
        pageSize: pageSize
      };

      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Authorization": `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      });

      if (!response.ok) throw new Error(`Lỗi Server HTTP ${response.status}`);

      const result = await response.json();
      if (!result.success || !result.data) throw new Error("Cấu trúc API trả về lỗi.");

      const items = result.data;
      if (items.length === 0) break;

      allData = allData.concat(items);
      if (items.length < pageSize) hasMore = false;
      else pageIndex++;
    }
    return allData;
  }

  // --- LOGIC ĐẨY LÊN SHEETS ---
  pushSheetBtn.addEventListener("click", async () => {
    if (!lastReportData) {
      setStatus("Vui lòng bấm 'Soạn' trước để tính toán dữ liệu tổng hợp!", "error");
      return;
    }

    exportBtn.disabled = true;
    composeBtn.disabled = true;
    pushSheetBtn.disabled = true;
    pushJsonBtn.disabled = true;

    const token = getToken();
    if (!token) {
      setStatus("Không tìm thấy token!", "error");
      exportBtn.disabled = false; composeBtn.disabled = false; pushSheetBtn.disabled = false; pushJsonBtn.disabled = false;
      return;
    }

    try {
      const fromStr = new Date(`${fromDateInput.value}T00:00:00+07:00`).toISOString();
      const toStr = new Date(`${toDateInput.value}T00:00:00+07:00`).toISOString();

      setStatus("Đang tải dữ liệu để đẩy lên Sheets...", "loading");
      const allData = await fetchDataForRange(token, unitCodeInput.value.trim(), fromStr, toStr);

      if (allData.length === 0) {
        setStatus("Không có dữ liệu.", "error");
        return;
      }

      const mappedDataForSheets = mapDataKeys(allData).rows;
      setStatus(`Đang đẩy ${mappedDataForSheets.length} dòng lên Sheets...`, "loading");

      const payload = {
        type: "sync",
        report: lastReportData
      };

      const sheetsUrl = "https://script.google.com/macros/s/AKfycbw16hPG8dasu4dO_ocR68fNaHjrbPfmVX7k7iJ4xqBjel0cmvLoHbXMzSLGP23tG-odGg/exec";
      const response = await fetch(sheetsUrl, {
        method: "POST",
        mode: "no-cors",
        headers: { "Content-Type": "text/plain;charset=utf-8" },
        body: JSON.stringify(payload)
      });

      // Khi dùng no-cors, response sẽ là dạng opaque, ta không thể đọc nội dung response (status sẽ = 0)
      // Nên ta mặc định báo thành công nếu fetch không văng lỗi mạng.
      setStatus(`Đã đẩy yêu cầu lên Sheets! (Nếu không thấy dữ liệu, vui lòng kiểm tra quyền truy cập Apps Script)`, "success");

    } catch (e) {
      console.error(e);
      setStatus("Lỗi đẩy Sheets: " + e.message, "error");
    } finally {
      exportBtn.disabled = false; composeBtn.disabled = false; pushSheetBtn.disabled = false; pushJsonBtn.disabled = false;
    }
  });

  // --- LOGIC ĐẨY LÊN JSONBIN ---
  pushJsonBtn.addEventListener("click", async () => {
    if (!lastReportData) {
      setStatus("Vui lòng bấm 'Soạn' trước để tính toán dữ liệu!", "error");
      return;
    }

    exportBtn.disabled = true;
    composeBtn.disabled = true;
    pushSheetBtn.disabled = true;
    pushJsonBtn.disabled = true;

    try {
      setStatus("Đang đọc dữ liệu hiện tại từ JSONBin...", "loading");
      const binId = "6a1d9b4addf5aa59f7805677";
      const apiKey = "$2a$10$FvjY8ltYSrSb5imGs4SJBu8sNvfNszkcxl0yNNS71LgcXcAu1hdJG";

      // GET current data
      const getResponse = await fetch(`https://api.jsonbin.io/v3/b/${binId}`, {
        method: "GET",
        headers: {
          "X-Master-Key": apiKey
        }
      });

      if (!getResponse.ok) {
        throw new Error(`Lỗi tải dữ liệu JSONBin: HTTP ${getResponse.status}`);
      }

      const resData = await getResponse.json();
      let currentList = [];
      if (resData.record) {
        if (Array.isArray(resData.record)) {
          currentList = resData.record;
        } else if (Array.isArray(resData.record.reports)) {
          currentList = resData.record.reports;
        } else {
          if (Object.keys(resData.record).length > 0) {
            currentList = [resData.record];
          }
        }
      }

      // Update or append report
      const existingIndex = currentList.findIndex(r => r.date === lastReportData.date && r.unitCode === lastReportData.unitCode);
      if (existingIndex > -1) {
        currentList[existingIndex] = lastReportData;
      } else {
        currentList.push(lastReportData);
      }

      setStatus("Đang lưu dữ liệu mới lên JSONBin...", "loading");

      // PUT updated list
      const putResponse = await fetch(`https://api.jsonbin.io/v3/b/${binId}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          "X-Master-Key": apiKey
        },
        body: JSON.stringify(currentList)
      });

      if (!putResponse.ok) {
        throw new Error(`Lỗi lưu dữ liệu JSONBin: HTTP ${putResponse.status}`);
      }

      setStatus("Đã đẩy dữ liệu tin nhắn lên JSONBin thành công!", "success");

    } catch (e) {
      console.error(e);
      setStatus("Lỗi JSONBin: " + e.message, "error");
    } finally {
      exportBtn.disabled = false;
      composeBtn.disabled = false;
      pushSheetBtn.disabled = false;
      pushJsonBtn.disabled = false;
    }
  });

  // --- LOGIC XUẤT EXCEL ---
  exportBtn.addEventListener("click", async () => {
    exportBtn.disabled = true;
    composeBtn.disabled = true;
    pushSheetBtn.disabled = true;
    pushJsonBtn.disabled = true;
    const token = getToken();
    if (!token) {
      setStatus("Không tìm thấy token!", "error");
      exportBtn.disabled = false; composeBtn.disabled = false; pushSheetBtn.disabled = false; pushJsonBtn.disabled = false;
      return;
    }

    try {
      const fromStr = new Date(`${fromDateInput.value}T00:00:00+07:00`).toISOString();
      const toStr = new Date(`${toDateInput.value}T00:00:00+07:00`).toISOString();

      const allData = await fetchDataForRange(token, unitCodeInput.value.trim(), fromStr, toStr);

      if (allData.length === 0) {
        setStatus("Không có dữ liệu.", "error");
      } else {
        generateExcel(allData);
        setStatus(`Hoàn tất tải ${allData.length} dòng!`, "success");
      }
    } catch (e) {
      setStatus(e.message, "error");
    } finally {
      exportBtn.disabled = false; composeBtn.disabled = false; pushSheetBtn.disabled = false; pushJsonBtn.disabled = false;
    }
  });

  // --- HÀM TẠO BÁO CÁO DÙNG CHUNG ---
  async function generateReportForDate(token, unitCode, targetDateStr, isBackground = false) {
    const baseDate = new Date(targetDateStr);

    const d1Date = new Date(baseDate);
    d1Date.setDate(d1Date.getDate() - 1);

    const d5Date = new Date(baseDate);
    d5Date.setDate(d5Date.getDate() - 5);

    const dStr = new Date(`${targetDateStr}T00:00:00+07:00`).toISOString();
    const d1Str = new Date(`${d1Date.toISOString().split('T')[0]}T00:00:00+07:00`).toISOString();
    const d5Str = new Date(`${d5Date.toISOString().split('T')[0]}T00:00:00+07:00`).toISOString();

    if (!isBackground) setStatus("Đang tải dữ liệu Ngày báo cáo (D)...", "loading");
    const dataD = await fetchDataForRange(token, unitCode, dStr, dStr, true);

    if (!isBackground) setStatus("Đang tải dữ liệu Hôm trước (D-1)...", "loading");
    const dataD1 = await fetchDataForRange(token, unitCode, d1Str, d1Str, true);

    if (!isBackground) setStatus("Đang tải dữ liệu 5 ngày liền kề để tính KH rụng...", "loading");
    const dataD5 = await fetchDataForRange(token, unitCode, d5Str, d1Str, true);

    if (!isBackground) setStatus("Đang phân tích số liệu...", "loading");

    // Tính tổng sản lượng & cước
    const outD = dataD.reduce((s, i) => s + (i.totalOutput || 0), 0);
    const outD1 = dataD1.reduce((s, i) => s + (i.totalOutput || 0), 0);

    const revD = dataD.reduce((s, i) => s + (i.totalRealFee || i.totalFreightDiscountVAT || 0), 0);
    const revD1 = dataD1.reduce((s, i) => s + (i.totalRealFee || i.totalFreightDiscountVAT || 0), 0);

    // Tăng giảm SL
    const outDiff = outD - outD1;
    let outGrowth = "tương đương";
    if (outD1 > 0 && outDiff !== 0) {
      const percent = (Math.abs(outDiff) / outD1 * 100).toFixed(1);
      outGrowth = outDiff > 0 ? `tăng ${percent}%` : `giảm ${percent}%`;
    }

    // Tỷ lệ doanh thu
    const revRatio = revD1 === 0 ? 0 : (revD / revD1 * 100).toFixed(1);

    // Hàm kiểm tra xem dòng dữ liệu có thực sự phát sinh doanh thu hay sản lượng không
    const hasRevenue = (i) => {
      return (i.totalRealFee > 0) || (i.totalFreightDiscountVAT > 0) || (i.totalOutput > 0);
    };

    // KH rụng
    const cusD = new Set();
    dataD.forEach(i => {
      if (i.cmsCode && hasRevenue(i)) cusD.add(i.cmsCode.trim());
    });

    const cusD5 = new Set();
    dataD5.forEach(i => {
      if (i.cmsCode && hasRevenue(i)) cusD5.add(i.cmsCode.trim());
    });

    let dropped = 0;
    cusD5.forEach(c => { if (!cusD.has(c)) dropped++; });
    const churnRate = cusD5.size === 0 ? 0 : (dropped / cusD5.size * 100).toFixed(1);

    // Bưu cục giảm > 20%
    const unitOutD = {};
    const unitOutD1 = {};
    const unitNames = {};

    dataD.forEach(i => {
      const dCode = i.districtCodeSend || i.districtNameSend || "";
      const pCode = i.posCodeSend || i.cmsCode || i.posNameSend || i.customerName || "";
      
      const dName = i.districtNameSend || dCode;
      const pName = i.posNameSend || i.customerName || pCode;

      if (dCode) {
        unitOutD[dCode] = (unitOutD[dCode] || 0) + (i.totalOutput || 0);
        unitNames[dCode] = dName;
      }
      if (pCode) {
        unitOutD[pCode] = (unitOutD[pCode] || 0) + (i.totalOutput || 0);
        unitNames[pCode] = pName;
      }
    });

    dataD1.forEach(i => {
      const dCode = i.districtCodeSend || i.districtNameSend || "";
      const pCode = i.posCodeSend || i.cmsCode || i.posNameSend || i.customerName || "";
      
      const dName = i.districtNameSend || dCode;
      const pName = i.posNameSend || i.customerName || pCode;

      if (dCode) {
        unitOutD1[dCode] = (unitOutD1[dCode] || 0) + (i.totalOutput || 0);
        unitNames[dCode] = dName;
      }
      if (pCode) {
        unitOutD1[pCode] = (unitOutD1[pCode] || 0) + (i.totalOutput || 0);
        unitNames[pCode] = pName;
      }
    });

    const badUnits = [];
    for (const [code, oldVal] of Object.entries(unitOutD1)) {
      const name = unitNames[code] || code;
      if (oldVal > 0 && name.trim().length > 0 && name !== "Tổng") {
        const newVal = unitOutD[code] || 0;
        const drop = (oldVal - newVal) / oldVal;
        if (drop >= 0.2) {
          badUnits.push({
            text: `- ${name}: giảm ${(drop * 100).toFixed(1)}%`,
            drop: drop
          });
        }
      }
    }

    // Sắp xếp giảm dần theo tỷ lệ giảm sản lượng để đưa các đơn vị giảm sâu nhất lên đầu
    badUnits.sort((a, b) => b.drop - a.drop);

    // Lọc trùng lặp Bưu cục nếu thông báo bị trùng
    const uniqueBadUnitTexts = [...new Set(badUnits.map(item => item.text))];

    // Tính toán Top 20 Sản lượng ngày D
    const allUnitsD = [];
    for (const [code, val] of Object.entries(unitOutD)) {
      if (code.trim() && unitNames[code] && unitNames[code] !== "Tổng") {
        if (!allUnitsD.find(u => u.name === unitNames[code])) {
          allUnitsD.push({ code: code, name: unitNames[code], output: val });
        }
      }
    }
    allUnitsD.sort((a, b) => a.output - b.output); // Tăng dần (thấp nhất lên đầu)
    
    const top20Lowest = allUnitsD.slice(0, 20);
    const top20Highest = [...allUnitsD].reverse().slice(0, 20);

    // Sinh tin nhắn
    const today = new Date();
    const todayStr = `${String(today.getDate()).padStart(2, '0')}.${String(today.getMonth() + 1).padStart(2, '0')}.${today.getFullYear()}`;
    const rDateStr = `${baseDate.getDate()}/${baseDate.getMonth() + 1}`;

    let msg = `${todayStr}_Bản tin ĐH số 3\n\n`;
    msg += `ĐÁNH GIÁ CÔNG TÁC QUẢN LÝ KHHH\n`;
    msg += `TTVH kính gửi A/c báo cáo Sản lượng, doanh thu các KHHH ngày ${rDateStr}\n`;
    msg += `1.Tổng quan các chỉ số chính \n`;
    msg += `Toàn địa bàn Nghệ An:\n`;
    msg += `- Sản lượng: ${(outD / 1000).toFixed(1)}K đơn (${outGrowth} so với D-1)\n`;
    msg += `2. Đánh giá biến động KHHH tại BĐT Nghệ An\n`;
    msg += `- Tỷ lệ mã CMS KH không PS DT / tổng cộng mã KHPS 5 ngày trước liền kề = ${churnRate}%, \n`;
    msg += `- Tỷ lệ biến động DT ngày báo cáo/kỳ trước liền kề = ${revRatio}%\n`;
    msg += `3. Các Đơn vị có sản lượng đơn giảm trên 20% so với D-1 cần lưu ý:\n`;

    if (uniqueBadUnitTexts.length > 0) {
      msg += uniqueBadUnitTexts.join("\n");
    } else {
      msg += "- Không có đơn vị nào giảm quá 20%";
    }

    const reportData = {
      date: targetDateStr,
      unitCode: unitCode,
      outD: outD,
      outD1: outD1,
      revD: revD,
      revD1: revD1,
      outGrowth: outGrowth,
      revRatio: parseFloat(revRatio) || 0,
      churnRate: parseFloat(churnRate) || 0,
      badUnits: uniqueBadUnitTexts,
      top20Highest: top20Highest,
      top20Lowest: top20Lowest,
      message: msg,
      timestamp: new Date().toISOString()
    };

    return reportData;
  }

  // --- LOGIC SOẠN TIN NHẮN KHHH (1 Ngày) ---
  composeBtn.addEventListener("click", async () => {
    exportBtn.disabled = true;
    composeBtn.disabled = true;
    pushSheetBtn.disabled = true;
    pushJsonBtn.disabled = true;
    batchSyncBtn.disabled = true;
    messageOutput.value = "";

    const token = getToken();
    if (!token) {
      setStatus("Không tìm thấy token!", "error");
      exportBtn.disabled = false; composeBtn.disabled = false; pushSheetBtn.disabled = false; pushJsonBtn.disabled = false; batchSyncBtn.disabled = false;
      return;
    }

    try {
      const unitCode = unitCodeInput.value.trim();
      const targetDateStr = toDateInput.value;

      lastReportData = await generateReportForDate(token, unitCode, targetDateStr, false);
      messageOutput.value = lastReportData.message;
      setStatus("Đã soạn xong tin nhắn!", "success");

    } catch (e) {
      console.error(e);
      setStatus("Lỗi soạn tin: " + e.message, "error");
    } finally {
      exportBtn.disabled = false; composeBtn.disabled = false; pushSheetBtn.disabled = false; pushJsonBtn.disabled = false; batchSyncBtn.disabled = false;
    }
  });

  // --- LOGIC ĐỒNG BỘ HÀNG LOẠT (Nhiều ngày) ---
  batchSyncBtn.addEventListener("click", async () => {
    const token = getToken();
    if (!token) {
      setStatus("Không tìm thấy token!", "error");
      return;
    }

    const fromD = new Date(batchFromDateInput.value);
    const toD = new Date(batchToDateInput.value);

    if (fromD > toD) {
      setStatus("Từ ngày không được lớn hơn Đến ngày!", "error");
      return;
    }

    exportBtn.disabled = true;
    composeBtn.disabled = true;
    pushSheetBtn.disabled = true;
    pushJsonBtn.disabled = true;
    batchSyncBtn.disabled = true;

    try {
      const unitCode = unitCodeInput.value.trim();
      const sheetsUrl = "https://script.google.com/macros/s/AKfycbw16hPG8dasu4dO_ocR68fNaHjrbPfmVX7k7iJ4xqBjel0cmvLoHbXMzSLGP23tG-odGg/exec";
      
      // JSONBin configs
      const binId = "6a1d9b4addf5aa59f7805677";
      const apiKey = "$2a$10$FvjY8ltYSrSb5imGs4SJBu8sNvfNszkcxl0yNNS71LgcXcAu1hdJG";
      
      // Lấy toàn bộ danh sách hiện tại từ JSONBin một lần trước
      let currentJsonList = [];
      try {
        setStatus("Đang tải dữ liệu JSONBin hiện tại...", "loading");
        const getResponse = await fetch(`https://api.jsonbin.io/v3/b/${binId}`, {
          method: "GET",
          headers: { "X-Master-Key": apiKey }
        });
        if (getResponse.ok) {
          const resData = await getResponse.json();
          if (resData.record) {
            currentJsonList = Array.isArray(resData.record) ? resData.record : (resData.record.reports || [resData.record]);
          }
        }
      } catch (e) {
        console.warn("Không tải được JSONBin hiện tại, sẽ tạo mới.", e);
      }

      // Vòng lặp từng ngày
      let currentDate = new Date(fromD);
      let totalProcessed = 0;

      while (currentDate <= toD) {
        const dateStr = currentDate.toISOString().split('T')[0];
        setStatus(`Đang xử lý ngày ${dateStr}...`, "loading");

        // 1. Tính toán
        const reportData = await generateReportForDate(token, unitCode, dateStr, true);
        lastReportData = reportData; // Lưu lại ngày cuối cùng

        // 2. Đẩy Google Sheets
        setStatus(`Đang đẩy ngày ${dateStr} lên Sheets...`, "loading");
        const payload = { type: "sync", report: reportData };
        await fetch(sheetsUrl, {
          method: "POST",
          mode: "no-cors",
          headers: { "Content-Type": "text/plain;charset=utf-8" },
          body: JSON.stringify(payload)
        });

        // 3. Gộp vào danh sách JSONBin
        const existingIndex = currentJsonList.findIndex(r => r.date === reportData.date && r.unitCode === reportData.unitCode);
        if (existingIndex > -1) {
          currentJsonList[existingIndex] = reportData;
        } else {
          currentJsonList.push(reportData);
        }

        totalProcessed++;
        
        // Tăng ngày lên 1
        currentDate.setDate(currentDate.getDate() + 1);
        
        // Nghỉ 1 giây giữa các ngày để tránh bị chặn IP
        if (currentDate <= toD) {
          await new Promise(resolve => setTimeout(resolve, 1000));
        }
      }

      // 4. Lưu toàn bộ lên JSONBin
      setStatus("Đang lưu tất cả lên JSONBin...", "loading");
      await fetch(`https://api.jsonbin.io/v3/b/${binId}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json", "X-Master-Key": apiKey },
        body: JSON.stringify(currentJsonList)
      });

      setStatus(`Đã đồng bộ thành công ${totalProcessed} ngày!`, "success");

    } catch (e) {
      console.error(e);
      setStatus("Lỗi đồng bộ hàng loạt: " + e.message, "error");
    } finally {
      exportBtn.disabled = false; composeBtn.disabled = false; pushSheetBtn.disabled = false; pushJsonBtn.disabled = false; batchSyncBtn.disabled = false;
    }
  });

  function mapDataKeys(data) {
    if (data.length === 0) return { columns: [], rows: [] };

    const COLUMN_MAP = [
      { label: "STT", fields: ["stt"] },
      { label: "Mã tỉnh chấp nhận", fields: ["provinceCodeSend", "provinceCode", "province_code"] },
      { label: "Tên tỉnh chấp nhận", fields: ["provinceNameSend", "provinceName", "province_name"] },
      { label: "Mã huyện chấp nhận", fields: ["districtCodeSend", "districtCode", "district_code"] },
      { label: "Tên huyện chấp nhận", fields: ["districtNameSend", "districtName", "district_name"] },
      { label: "Mã bưu cục chấp nhận", fields: ["posCodeSend", "posCode", "pos_code"] },
      { label: "Tên bưu cục chấp nhận", fields: ["posNameSend", "posName", "pos_name"] },
      { label: "Mã CRM", fields: ["crmCode", "crm_code"] },
      { label: "Mã CMS", fields: ["cmsCode", "cms_code", "customerCode", "customer_code"] },
      { label: "Tên khách hàng", fields: ["customerName", "customer_name"] },
      { label: "Đối tượng", fields: ["cmsCustomerType", "customerType", "customer_type"] },
      { label: "Nhóm khách hàng", fields: ["cmsCustomerGroupId", "customerGroupCode", "customer_group_code"] },
      { label: "Loại KH", fields: ["cmsCustomerGroupType", "customerGroupType", "customer_group_type"] },
      { label: "Sản lượng (cái)", fields: ["totalOutput", "quantity", "output", "count"] },
      { label: "Khối lượng thực (G)", fields: ["totalRealWeight", "realWeight", "weight"] },
      { label: "Khối lượng tính cước (G)", fields: ["totalCalculatedWeight", "totalChargeWeight", "chargeWeight"] },
      { label: "Khối lượng quy đổi (G)", fields: ["totalConvertWeight", "totalConvertedWeight", "convertedWeight"] },
      { label: "Cước công bố (Đồng)", fields: ["totalPublicationFee", "totalPublishedFee", "publishedFee"] },
      { label: "Cước thực thu(Bảng cước KH) (Đồng)", fields: ["totalRealFee", "realFee", "revenue"] },
      { label: "Cước GTGT (Đồng)", fields: ["totalGTGTFee", "totalFreightDiscountVAT", "freightDiscountVAT"] },
      { label: "Phụ cước (Đồng)", fields: ["totalExtraFee", "totalSurcharge", "surcharge"] },
      { label: "Thu COD (Đồng)", fields: ["totalCODFee", "totalCOD", "cod"] },
      { label: "Thu khác (Đồng)", fields: ["totalOtherFee", "otherFee"] }
    ];

    const rawKeys = Object.keys(data[0]);
    const mappedRawKeys = new Set();
    const finalColumns = [];

    // Map the 23 standard columns and track which raw keys are consumed
    COLUMN_MAP.forEach(col => {
      let matchedKey = null;
      for (const field of col.fields) {
        const found = rawKeys.find(k => k.toLowerCase() === field.toLowerCase());
        if (found) {
          matchedKey = found;
          mappedRawKeys.add(found);
          break;
        }
      }
      finalColumns.push({
        label: col.label,
        key: matchedKey,
        isMapped: true,
        fields: col.fields
      });
    });

    // Append any raw keys that were not mapped at the end of the columns to prevent data loss
    rawKeys.forEach(key => {
      if (!mappedRawKeys.has(key)) {
        finalColumns.push({
          label: key,
          key: key,
          isMapped: false
        });
      }
    });

    const mappedRows = data.map(row => {
      const newRow = {};
      finalColumns.forEach(col => {
        let val = "";
        if (col.key) {
          val = row[col.key];
        } else if (col.isMapped) {
          // Double check case-insensitive match if key wasn't resolved initially
          for (const field of col.fields) {
            const foundKey = Object.keys(row).find(k => k.toLowerCase() === field.toLowerCase());
            if (foundKey) {
              val = row[foundKey];
              break;
            }
          }
        }
        if (val === null || val === undefined) val = "";
        newRow[col.label] = val;
      });
      return newRow;
    });

    return { columns: finalColumns.map(c => c.label), rows: mappedRows };
  }

  function generateExcel(data) {
    if (data.length === 0) return;

    const { columns, rows } = mapDataKeys(data);

    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head><meta charset="utf-8"></head><body><table border="1">`;

    // Header row
    html += "<tr>";
    columns.forEach(col => {
      html += `<th style="background-color: #4f81bd; color: white;">${col}</th>`;
    });
    html += "</tr>";

    // Data rows
    rows.forEach(row => {
      html += "<tr>";
      columns.forEach(col => {
        html += `<td>${row[col]}</td>`;
      });
      html += "</tr>";
    });
    html += "</table></body></html>";

    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `VNPOST_Report_${new Date().getTime()}.xls`;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
  }
}
