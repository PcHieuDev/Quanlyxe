const API_URLS = {
  phatThanhCongBuuCuc: "https://dkcl.vnpost.vn/kpi/chat-luong-phat-thanh-cong-cua-buu-cuc",
  thuGomLienTinh: "https://dkcl.vnpost.vn/kpi/chat-luong-thu-gom-cua-nvtg",
  noiTinhF11: "https://dkcl.vnpost.vn/kpi/chat-luong-toan-trinh-buu-gui-noi-tinh",
  thuGomLienTinhF12: "https://dkcl.vnpost.vn/kpi/chat-luong-thu-gom-buu-lien-tinh",
  phatLienTinhF13: "https://dkcl.vnpost.vn/kpi/chat-luong-phat-buu-gui-lien-tinh",
  quanLyKhachHang: "https://dkcl.vnpost.vn/2026/quan-ly-khah-hang/theo-tinh"
};

const EXCEL_COLUMNS = [
  "STT",
  "Mã tỉnh",
  "Tên tỉnh",
  "Sản lượng PTC/ Nộp tiền/ CH",
  "Sản lượng bưu gửi PTC tại bưu cục trong thời gian 8 tiếng (có quét TMS)",
  "Tỷ lệ TMĐT",
  "TT TMĐT",
  "Sản lượng PTC/ Nộp tiền/ CH",
  "Sản lượng bưu gửi PTC tại bưu cục trong thời gian 8 tiếng (có quét TMS)",
  "Tỷ lệ Truyền thống",
  "TT Truyền thống",
  "Sản lượng thu gom thành công",
  "Sản lượng thu gom thành công đúng QĐ (≤6 giờ)",
  "Tỷ lệ Thu gom",
  "TT Thu gom",
  "Tỷ lệ bình quân đạt được",
  "TT bình quân"
];

const TEMPLATE_EXCEL_COLUMNS = [
  "STT",
  "Mã tỉnh chấp nhận",
  "Tên tỉnh chấp nhận",
  "Sản lượng PTC/ Nộp tiền/ CH",
  "Sản lượng bưu gửi PTC tại bưu cục trong thời gian 6 tiếng (có quét TMS)",
  "Tỷ lệ gửi PTC tại bưu cục trong thời gian 6 tiếng (có quét TMS)",
  "TT mạng lưới",
  "Sản lượng PTC/ Nộp tiền/ CH",
  "Sản lượng bưu gửi PTC tại bưu cục trong thời gian 6 tiếng (có quét TMS)",
  "Tỷ lệ gửi PTC tại bưu cục trong thời gian 6 tiếng (có quét TMS)",
  "TT mạng lưới",
  "Sản lượng thu gom thành công",
  "Sản lượng thu gom thành công đúng QĐ (≤6 giờ)",
  "Tỷ lệ TG bưu gửi đúng thời gian QĐ (≤6 giờ)",
  "TT mạng lưới",
  "Tỷ lệ BQ",
  "TT"
];

const V2_EXCEL_COLUMNS = [
  ...TEMPLATE_EXCEL_COLUMNS,
  "Sản lượng PTC/ Nộp tiền/ CH",
  "Sản lượng bưu gửi PTC tại bưu cục trong thời gian 8 tiếng (có quét TMS)",
  "Tỷ lệ gửi PTC tại bưu cục trong thời gian 8 tiếng (có quét TMS)",
  "TT mạng lưới",
  "SL bưu gửi phát thành công/Nộp tiền/chuyển hoàn",
  "Sản lượng bưu gửi PTC/nộp tiền",
  "Sản lượng bưu gửi PTC/nộp tiền đúng QĐ <= 24h",
  "Tỷ lệ bưu gửi PTC/Nộp tiền đúng QĐ",
  "TT mạng lưới",
  "SL bưu gửi có BĐ10 đóng đi tại khai thác tỉnh chấp nhận",
  "SL bưu gửi có BD10 quét TMS lên tại Khai thác tỉnh hoặc BC khớp nối",
  "SL đúng thời gian quy định <= 10 giờ",
  "Tỷ lệ đúng thời gian quy định",
  "TT mạng lưới",
  "SL bưu gửi phát thành công/Nộp tiền/CH",
  "Sản lượng bưu gửi PTC/nộp tiền đúng thời gian QĐ 2026",
  "Tỷ lệ bưu gửi PTC/Nộp tiền đúng QĐ theo chi tiêu 2026",
  "TT mạng lưới",
  "Tỷ lệ BQ( TGLT+ PTC LT)",
  "TT"
];

const TEMPLATE_GROUP_HEADERS = [
  { label: "", colspan: 3 },
  { label: "Phát thành công TMĐT", colspan: 4 },
  { label: "Phát thành công Truyền thống", colspan: 4 },
  { label: "Thu gom bưu gửi", colspan: 4 },
  { label: "Tỷ lệ trung bình chung", colspan: 2 }
];

const V2_GROUP_HEADERS = [
  ...TEMPLATE_GROUP_HEADERS,
  { label: "Chất lượng phát thành công tại bưu cục (F4.1)", colspan: 4 },
  { label: "Nội tỉnh F1.1", colspan: 5 },
  { label: "Thu gom bưu gửi đi liên tỉnh (F1.2)", colspan: 5 },
  { label: "Chất lượng phát bưu gửi liên tỉnh (F1.3)", colspan: 4 },
  { label: "Tỷ lệ trung bình chung chất lượng (TGLT,PTC LT)", colspan: 2 }
];

const GROUP_HEADERS = [
  { label: "", colspan: 3 },
  { label: "Phát thành công TMĐT", colspan: 4 },
  { label: "Phát thành công Truyền thống", colspan: 4 },
  { label: "Thu gom", colspan: 4 },
  { label: "Bình quân", colspan: 2 }
];

const SOURCE_CONFIGS = [
  {
    key: "phatThanhCongBuuCuc",
    name: "Phát thành công TMĐT",
    status: "Đang gọi curl Phát thành công TMĐT...",
    buildParams: buildPhatThanhCongTmdtParams,
    columns: [
      { source: "total", target: 3 },
      { source: "success8h", target: 4 },
      { source: "rate", target: 5, type: "percent" },
      { source: "rank", target: 6 }
    ]
  },
  {
    key: "phatThanhCongBuuCuc",
    name: "Phát thành công Truyền thống",
    status: "Đang gọi curl Phát thành công Truyền thống...",
    buildParams: buildPhatThanhCongTruyenThongParams,
    columns: [
      { source: "total", target: 7 },
      { source: "success8h", target: 8 },
      { source: "rate", target: 9, type: "percent" },
      { source: "rank", target: 10 }
    ]
  },
  {
    key: "thuGomLienTinh",
    name: "Thu gom bưu gửi đi liên tỉnh",
    status: "Đang gọi curl Thu gom bưu gửi đi liên tỉnh...",
    buildParams: buildThuGomParams,
    columns: [
      { source: "total", target: 11 },
      { source: "onTime", target: 12 }
    ]
  }
];

const V2_SOURCE_CONFIGS = [
  { key: "phatThanhCongBuuCuc", name: "Chất lượng phát thành công tại bưu cục (F4.1)", status: "Đang gọi curl V2 F4.1...", buildParams: buildPhatThanhCongV2Params, values: { total: 10, ptcNopTien: 11, ptc8hLanDauAll: 34, ptc8hLanDauTms: 36 }, targets: { total: 17, ptcNopTien: 37, ptc8hLanDauAll: 38, ptc8hLanDauTms: 39 } },
  { key: "noiTinhF11", name: "Nội tỉnh F1.1", status: "Đang gọi curl V2 Nội tỉnh F1.1...", buildParams: buildNoiTinhF11Params, codeIndex: 1, nameIndex: 2, values: { total: 10, subTotal: 11, onTime: 12 }, targets: { total: 21, subTotal: 22, onTime: 23, rate: 24, rank: 25 } },
  { key: "thuGomLienTinhF12", name: "Thu gom bưu gửi đi liên tỉnh (F1.2)", status: "Đang gọi curl V2 Thu gom liên tỉnh F1.2...", buildParams: buildThuGomLienTinhF12Params, codeIndex: 1, nameIndex: 2, values: { total: 5, subTotal: 6, onTime: 7 }, targets: { total: 26, subTotal: 27, onTime: 28, rate: 29, rank: 30 } },
  { key: "phatLienTinhF13", name: "Chất lượng phát bưu gửi liên tỉnh (F1.3)", status: "Đang gọi curl V2 Phát liên tỉnh F1.3...", buildParams: buildPhatLienTinhF13Params, codeIndex: 1, nameIndex: 2, values: { total: 8, onTime: 16 }, targets: { total: 31, onTime: 32, rate: 33, rank: 34 } }
];

const PROVINCE_CODE_INDEX = 1;
const PROVINCE_NAME_INDEX = 2;
const form = document.getElementById("report-form");
const fromDateInput = document.getElementById("from-date");
const toDateInput = document.getElementById("to-date");
const nextFromDateInput = document.getElementById("next-from-date");
const nextToDateInput = document.getElementById("next-to-date");
const dateDisplayMap = new Map([
  [fromDateInput, document.getElementById("from-date-display")],
  [toDateInput, document.getElementById("to-date-display")],
  [nextFromDateInput, document.getElementById("next-from-date-display")],
  [nextToDateInput, document.getElementById("next-to-date-display")]
]);
const displayDateMap = new Map([...dateDisplayMap.entries()].map(([nativeInput, displayInput]) => [displayInput, nativeInput]));
const statusBox = document.getElementById("status");
const reportPanel = document.getElementById("report-panel");
const reportBadge = document.getElementById("report-badge");
const emptyState = document.getElementById("empty-state");
const reportSummary = document.getElementById("report-summary");
const reportText = document.getElementById("report-text");
const copyBtnContainer = document.getElementById("copy-btn-container");
const copyMessageBtn = document.getElementById("copy-message-btn");
const pushCloudBtn = document.getElementById("push-cloud-btn");
const jsonbinIdInput = document.getElementById("jsonbin-id");
const jsonbinKeyInput = document.getElementById("jsonbin-key");
const sheetsUrlInput = document.getElementById("sheets-url");
const pushKhSheetsBtn = document.getElementById("push-kh-sheets");

let currentReportPayload = null;
const weightTmdtInput = document.getElementById("weight-tmdt");
const weightTraditionalInput = document.getElementById("weight-traditional");
const weightPickupInput = document.getElementById("weight-pickup");
const templateReportButton = document.getElementById("export-province-template");
const composeMessageBtn = document.getElementById("compose-message-btn");
const composeKhhhMessageBtn = document.getElementById("compose-khhh-message-btn");
const v2Buttons = [...document.querySelectorAll("button[data-v2-group]")];
const khButtons = [...document.querySelectorAll("button[data-kh-group]")];
const khDetailButtons = [...document.querySelectorAll("button[data-kh-detail-group]")];
const buttons = [...document.querySelectorAll("button[data-group]")];
const datePickerButtons = [...document.querySelectorAll(".date-picker-btn")];
const allActionButtons = templateReportButton ? [...buttons, templateReportButton, composeMessageBtn, ...v2Buttons, ...khButtons, ...khDetailButtons] : [...buttons, composeMessageBtn, ...v2Buttons, ...khButtons, ...khDetailButtons];
const pinUiButton = document.getElementById("pin-ui");
const PIN_STORAGE_KEY = "dkclSidebarPinned";

initDefaultDates();

if (jsonbinIdInput) {
  const savedId = localStorage.getItem("jsonbin-id");
  if (savedId) jsonbinIdInput.value = savedId;
  jsonbinIdInput.addEventListener("input", () => localStorage.setItem("jsonbin-id", jsonbinIdInput.value));
}
if (jsonbinKeyInput) {
  const savedKey = localStorage.getItem("jsonbin-key");
  if (savedKey) jsonbinKeyInput.value = savedKey;
  jsonbinKeyInput.addEventListener("input", () => localStorage.setItem("jsonbin-key", jsonbinKeyInput.value));
}
if (pushCloudBtn) {
  pushCloudBtn.addEventListener("click", pushToCloud);
}
if (sheetsUrlInput) {
  const savedUrl = localStorage.getItem("sheets-url");
  if (savedUrl) sheetsUrlInput.value = savedUrl;
  sheetsUrlInput.addEventListener("input", () => localStorage.setItem("sheets-url", sheetsUrlInput.value));
}
if (pushKhSheetsBtn) {
  pushKhSheetsBtn.addEventListener("click", () => pushQuanLyKhachHangToSheets(pushKhSheetsBtn.dataset.sheetsGroup || "TINH"));
}

if (composeMessageBtn) composeMessageBtn.addEventListener("click", generateReportMessage);
if (composeKhhhMessageBtn) composeKhhhMessageBtn.addEventListener("click", generateKhhhReportMessage);
buttons.forEach((button) => button.addEventListener("click", () => exportReport(button.dataset.group)));
v2Buttons.forEach((button) => button.addEventListener("click", () => exportReportV2(button.dataset.v2Group, button.dataset.v2Mode || "data")));
khButtons.forEach((button) => button.addEventListener("click", () => exportQuanLyKhachHang(button.dataset.khGroup)));
khDetailButtons.forEach((button) => button.addEventListener("click", () => exportQuanLyKhachHangDetail(button.dataset.khDetailGroup)));
if (templateReportButton) templateReportButton.addEventListener("click", exportProvinceTemplateReport);
if (pinUiButton) {
  initPinnedUi();
  pinUiButton.addEventListener("click", togglePinnedUi);
}
if (copyMessageBtn && copyBtnContainer && reportText) {
  copyMessageBtn.addEventListener("click", () => {
    const textToCopy = reportText.textContent;
    if (!textToCopy) return;

    const onSuccess = () => {
      const originalText = copyMessageBtn.innerHTML;
      copyMessageBtn.innerHTML = "✅ Đã copy";
      setTimeout(() => copyMessageBtn.innerHTML = originalText, 2000);
      setStatus("Đã copy tin nhắn vào clipboard!", "ok");
    };

    const fallbackCopy = () => {
      try {
        const textarea = document.createElement("textarea");
        textarea.value = textToCopy;
        textarea.style.position = "fixed";  // Avoid scrolling to bottom
        document.body.appendChild(textarea);
        textarea.select();
        const successful = document.execCommand("copy");
        document.body.removeChild(textarea);

        if (successful) {
          onSuccess();
        } else {
          setStatus("Lỗi copy: Trình duyệt không hỗ trợ fallback", "error");
        }
      } catch (err) {
        setStatus("Lỗi copy fallback: " + err, "error");
      }
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(textToCopy)
        .then(onSuccess)
        .catch(err => {
          console.warn("Clipboard API failed, trying fallback...", err);
          fallbackCopy();
        });
    } else {
      fallbackCopy();
    }
  });
  const observer = new MutationObserver(() => {
    copyBtnContainer.hidden = reportText.hidden;
  });
  observer.observe(reportText, { attributes: true, attributeFilter: ['hidden'] });
}
[fromDateInput, toDateInput, nextFromDateInput, nextToDateInput].forEach((input) => {
  input.addEventListener("change", () => syncDateDisplay(input));
  input.addEventListener("input", () => syncDateDisplay(input));
});
displayDateMap.forEach((nativeInput, displayInput) => {
  displayInput.addEventListener("input", () => syncNativeDateFromDisplay(displayInput));
  displayInput.addEventListener("change", () => syncNativeDateFromDisplay(displayInput, { showError: true }));
  displayInput.addEventListener("blur", () => syncNativeDateFromDisplay(displayInput, { showError: true, normalize: true }));
});
datePickerButtons.forEach((button) => {
  button.addEventListener("click", (event) => {
    event.preventDefault();
    const nativeInput = button.parentElement?.querySelector(".date-native");
    if (!nativeInput) return;
    nativeInput.focus({ preventScroll: true });
    if (typeof nativeInput.showPicker === "function") nativeInput.showPicker();
    else nativeInput.click();
  });
});

function initPinnedUi() {
  const pinned = localStorage.getItem(PIN_STORAGE_KEY) === "true";
  updatePinUiButton(pinned);
  notifyParentPinnedState(pinned);
}

function togglePinnedUi() {
  const pinned = pinUiButton.getAttribute("aria-pressed") !== "true";
  localStorage.setItem(PIN_STORAGE_KEY, String(pinned));
  updatePinUiButton(pinned);
  notifyParentPinnedState(pinned);
  setStatus(pinned ? "Đã ghim giao diện: sidebar sẽ luôn mở." : "Đã bỏ ghim giao diện: có thể ẩn/hiện sidebar bằng nút DKCL.", "ok");
}

function updatePinUiButton(pinned) {
  pinUiButton.classList.toggle("is-pinned", pinned);
  pinUiButton.setAttribute("aria-pressed", String(pinned));
  pinUiButton.title = pinned ? "Bỏ ghim giao diện" : "Ghim giao diện luôn mở";
  const text = pinUiButton.querySelector(".pin-text");
  if (text) text.textContent = pinned ? "Đã ghim" : "Ghim";
}

function notifyParentPinnedState(pinned) {
  if (window.parent === window) return;
  window.parent.postMessage({ source: "dkcl-report-popup", type: "pin-state", pinned }, "*");
}

function initDefaultDates() {
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  const nextFirstDay = addDays(today, 1);
  const nextLastDay = new Date(nextFirstDay.getFullYear(), nextFirstDay.getMonth() + 1, 0);

  fromDateInput.value = toInputDate(firstDay);
  toDateInput.value = toInputDate(today);
  nextFromDateInput.value = toInputDate(nextFirstDay);
  nextToDateInput.value = toInputDate(nextLastDay);
  syncAllDateDisplays();
}

async function exportReport(tuyChonGR) {
  if (!form.reportValidity()) return;

  const from = parseControlDate(fromDateInput.value);
  const to = parseControlDate(toDateInput.value);
  const nextFrom = parseControlDate(nextFromDateInput.value);
  const nextTo = parseControlDate(nextToDateInput.value);
  if (!validateRequiredDates(from, to, nextFrom, nextTo) || !validatePeriodDates(from, to, nextFrom, nextTo)) return;

  setBusy(true);
  setStatus(`Đang lấy dữ liệu tổng hợp theo đúng mẫu filebaocao.xlsx (${tuyChonGR})...`);

  try {
    const rows = await fetchCombinedReport(tuyChonGR, from, to);
    if (!rows.length) {
      throw new Error("Không tìm thấy dữ liệu trong các curl. Vui lòng kiểm tra đăng nhập hoặc khoảng ngày.");
    }

    const weights = readWeights();
    let finalRows = finalizeRows(rows, weights, tuyChonGR);

    if (tuyChonGR === "TINH") {
      const selectedProv = getSelectedProvince();
      finalRows = finalRows.filter((row) => String(row[PROVINCE_CODE_INDEX]) === selectedProv);
    }

    console.log(`[DKCL][${tuyChonGR}] joined rows`, rows.length, "final rows", finalRows.length, finalRows.slice(0, 5));
    if (!finalRows.length) {
      throw new Error("Đã join được dữ liệu nhưng không tìm thấy dữ liệu của tỉnh đã chọn.");
    }
    const fileName = `filebaocao_${tuyChonGR}_${toFileDate(from)}_${toFileDate(to)}.xls`;
    renderReportPreview({ rows: finalRows, tuyChonGR, from, to, fileName, weights });
    downloadExcel({ rows: finalRows, tuyChonGR, from, to, fileName });
    setStatus(`Hoàn tất: đã gọi ${SOURCE_CONFIGS.length} curl, hiển thị ${finalRows.length} dòng và xuất ${fileName}.`, "ok");
  } catch (error) {
    console.error(error);
    setStatus(error.message || "Có lỗi khi lấy dữ liệu.", "error");
  } finally {
    setBusy(false);
  }
}

async function exportProvinceTemplateReport() {
  if (!form.reportValidity()) return;

  const from = parseControlDate(fromDateInput.value);
  const to = parseControlDate(toDateInput.value);
  const compareFrom = parseControlDate(nextFromDateInput.value);
  const compareTo = parseControlDate(nextToDateInput.value);
  if (!validateRequiredDates(from, to, compareFrom, compareTo) || !validatePeriodDates(from, to, compareFrom, compareTo)) return;

  setBusy(true);
  setStatus("Đang lấy dữ liệu Tỉnh cho kỳ báo cáo và kỳ so sánh...");

  try {
    const tuyChonGR = "TINH";
    const weights = readWeights();

    setStatus("Đang gọi dữ liệu Tỉnh: kỳ báo cáo...");
    const currentRows = finalizeRows(await fetchCombinedReport(tuyChonGR, from, to, "kỳ báo cáo"), weights, tuyChonGR);

    setStatus("Đang gọi dữ liệu Tỉnh: kỳ so sánh...");
    const compareRows = finalizeRows(await fetchCombinedReport(tuyChonGR, compareFrom, compareTo, "kỳ so sánh"), weights, tuyChonGR);

    if (!currentRows.length || !compareRows.length) {
      throw new Error("Không đủ dữ liệu cấp Tỉnh cho 2 kỳ để lập báo cáo so sánh.");
    }

    const comparisonRows = buildProvinceComparisonRows(currentRows, compareRows);
    if (!comparisonRows.length) {
      throw new Error("Không tìm thấy tỉnh trùng mã giữa kỳ báo cáo và kỳ so sánh.");
    }

    const provinceCode = getSelectedProvince();
    const provinceComparison = comparisonRows.find((row) => String(row.code) === provinceCode);
    if (!provinceComparison) {
      throw new Error("Không tìm thấy dữ liệu tỉnh đã chọn trong 2 kỳ báo cáo.");
    }

    // Chỉ giữ lại dữ liệu của tỉnh đã chọn
    const filteredRows = comparisonRows.filter((row) => String(row.code) === provinceCode);
    const filteredCurrentRows = currentRows.filter((row) => String(row[PROVINCE_CODE_INDEX]) === provinceCode);
    const filteredCompareRows = compareRows.filter((row) => String(row[PROVINCE_CODE_INDEX]) === provinceCode);
    const fileName = `baocao_so_sanh_ky_tinh_${toFileDate(from)}_${toFileDate(to)}_vs_${toFileDate(compareFrom)}_${toFileDate(compareTo)}.xls`;
    renderTemplateReportPreview({ rows: filteredRows, provinceComparison, from, to, compareFrom, compareTo, fileName, weights });
    downloadProvinceTemplateExcel({
      currentRows: filteredCurrentRows,
      compareRows: filteredCompareRows,
      comparisonRows: filteredRows,
      from,
      to,
      compareFrom,
      compareTo,
      fileName
    });
    setStatus(`Hoàn tất báo cáo so sánh kỳ Tỉnh: ${filteredRows.length} đơn vị, đã xuất ${fileName}.`, "ok");
  } catch (error) {
    console.error(error);
    setStatus(error.message || "Có lỗi khi lấy dữ liệu báo cáo so sánh kỳ Tỉnh.", "error");
  } finally {
    setBusy(false);
  }
}

async function exportReportV2(tuyChonGR, mode = "data") {
  if (!form.reportValidity()) return;

  const from = parseControlDate(fromDateInput.value);
  const to = parseControlDate(toDateInput.value);
  const compareFrom = parseControlDate(nextFromDateInput.value);
  const compareTo = parseControlDate(nextToDateInput.value);
  if (!validateRequiredDates(from, to, compareFrom, compareTo) || !validatePeriodDates(from, to, compareFrom, compareTo)) return;

  setBusy(true);
  setStatus(mode === "compare" ? "Đang lấy dữ liệu V2 cho 2 kỳ so sánh..." : `Đang lấy dữ liệu filebaocaov2.xlsx (${tuyChonGR})...`);

  try {
    const weights = readWeights();
    setStatus("Đang gọi dữ liệu V2: kỳ báo cáo...");
    const currentRows = await fetchV2ReportRows(tuyChonGR, from, to, weights, mode === "compare" ? "kỳ báo cáo" : "");

    if (mode === "compare") {
      setStatus("Đang gọi dữ liệu V2: kỳ so sánh...");
      const compareRows = await fetchV2ReportRows(tuyChonGR, compareFrom, compareTo, weights, "kỳ so sánh");
      const comparisonRows = buildV2ComparisonRows(currentRows, compareRows);
      const provinceComparison = comparisonRows.find((row) => String(row.code) === getSelectedProvince());
      if (!provinceComparison) {
        throw new Error("Không tìm thấy dữ liệu tỉnh đã chọn trong 2 kỳ báo cáo V2.");
      }
      // Chỉ giữ lại dữ liệu của tỉnh đã chọn
      const selectedProv = getSelectedProvince();
      const filteredV2Rows = comparisonRows.filter((row) => String(row.code) === selectedProv);
      const filteredCurrentRows = currentRows.filter((row) => String(row[PROVINCE_CODE_INDEX]) === selectedProv);
      const filteredCompareRows = compareRows.filter((row) => String(row[PROVINCE_CODE_INDEX]) === selectedProv);
      const fileName = `filebaocaov2_so_sanh_ky_${toFileDate(from)}_${toFileDate(to)}_vs_${toFileDate(compareFrom)}_${toFileDate(compareTo)}.xls`;
      renderV2ReportPreview({ rows: filteredV2Rows, tuyChonGR, from, to, compareFrom, compareTo, fileName, mode: "compare", provinceComparison });
      downloadV2ComparisonExcel({ currentRows: filteredCurrentRows, compareRows: filteredCompareRows, comparisonRows: filteredV2Rows, from, to, compareFrom, compareTo, fileName });
      setStatus(`Hoàn tất Báo cáo so sánh kỳ V2: ${filteredV2Rows.length} dòng, đã xuất ${fileName}.`, "ok");
      return;
    }

    const reportName = tuyChonGR === "TINH" ? "tinh" : "buu_cuc";
    const fileName = `filebaocaov2_${reportName}_${toFileDate(from)}_${toFileDate(to)}.xls`;

    let reportRows = currentRows;
    if (tuyChonGR === "TINH") {
      const selectedProv = getSelectedProvince();
      reportRows = reportRows.filter((row) => String(row[PROVINCE_CODE_INDEX]) === selectedProv);
    }

    renderV2ReportPreview({ rows: reportRows, tuyChonGR, from, to, fileName, mode: "data" });
    downloadV2Excel({ rows: reportRows, tuyChonGR, from, to, fileName });
    setStatus(`Hoàn tất Báo cáo ${tuyChonGR === "TINH" ? "theo tỉnh" : "theo bưu cục"} V2: ${reportRows.length} dòng, đã xuất ${fileName}.`, "ok");
  } catch (error) {
    console.error(error);
    setStatus(error.message || "Có lỗi khi lấy dữ liệu V2.", "error");
  } finally {
    setBusy(false);
  }
}

async function generateKhhhReportMessage() {
  if (!form.reportValidity()) return;

  const from = parseControlDate(fromDateInput.value);
  const to = parseControlDate(toDateInput.value);
  if (!validateRequiredDates(from, to) || !validatePeriodDates(from, to, from, to)) return;

  setBusy(true);
  setStatus("Đang lấy dữ liệu để soạn tin nhắn Bản tin ĐH số 3...");

  const toDateObj = new Date(to);
  const D = toDateObj;

  // Calculate D-1
  const D1 = new Date(toDateObj);
  D1.setDate(D1.getDate() - 1);

  // Calculate D-5
  const D5 = new Date(toDateObj);
  D5.setDate(D5.getDate() - 5);

  const selectedProv = getSelectedProvince();
  const provinceName = "BĐ " + (selectedProv === "46" ? "Nghệ An" : "Hà Nội");

  const safeNum = (val) => {
    const str = String(val || "").replace(/,/g, "").trim();
    return Number(str) || 0;
  };

  try {
    // 1. Fetch Summary TINH for D
    setStatus("Đang lấy tổng quan ngày báo cáo...");
    const tinhD = await fetchQuanLyKhachHangSummary("TINH", D, D);

    // 2. Fetch Summary TINH for D-1
    setStatus("Đang lấy tổng quan ngày hôm qua...");
    const tinhD1 = await fetchQuanLyKhachHangSummary("TINH", D1, D1);

    // 3. Fetch Summary BC for D
    setStatus("Đang lấy dữ liệu bưu cục ngày báo cáo...");
    const bcD = await fetchQuanLyKhachHangSummary("BC", D, D);

    // 4. Fetch Summary BC for D-1
    setStatus("Đang lấy dữ liệu bưu cục ngày hôm qua...");
    const bcD1 = await fetchQuanLyKhachHangSummary("BC", D1, D1);

    // 5. Fetch Detail for D-5 to D-1 (active customers)
    setStatus("Đang quét khách hàng 5 ngày trước (có thể mất 10-20s)...");
    const detail5Days = await fetchQuanLyKhachHangDetailData(D5, D1);

    // 6. Fetch Detail for D (active customers today)
    setStatus("Đang quét khách hàng ngày báo cáo...");
    const detailD = await fetchQuanLyKhachHangDetailData(D, D);

    // ==========================================
    // Tính toán số liệu
    // ==========================================

    // Tổng quan (Cột 9, 11, 13 là Sản lượng; 10, 12, 14 là Doanh thu)
    const sl_D = tinhD.length > 0 ? safeNum(tinhD[0][9]) + safeNum(tinhD[0][11]) + safeNum(tinhD[0][13]) : 0;
    const sl_D1 = tinhD1.length > 0 ? safeNum(tinhD1[0][9]) + safeNum(tinhD1[0][11]) + safeNum(tinhD1[0][13]) : 0;

    const dt_D = tinhD.length > 0 ? safeNum(tinhD[0][10]) + safeNum(tinhD[0][12]) + safeNum(tinhD[0][14]) : 0;
    const dt_D1 = tinhD1.length > 0 ? safeNum(tinhD1[0][10]) + safeNum(tinhD1[0][12]) + safeNum(tinhD1[0][14]) : 0;

    const volDiff = sl_D1 > 0 ? ((sl_D - sl_D1) / sl_D1 * 100).toFixed(1) : 0;
    const revDiff = dt_D1 > 0 ? (dt_D / dt_D1 * 100).toFixed(1) : 0;

    const tangGiamVol = sl_D >= sl_D1 ? `tăng ${volDiff}%` : `giảm ${Math.abs(volDiff)}%`;

    // Danh sách Bưu cục giảm > 20%
    const bcDrops = [];
    const bcMapD1 = new Map();
    bcD1.forEach(row => {
      const maBC = row[3];
      const sl = safeNum(row[9]) + safeNum(row[11]) + safeNum(row[13]);
      bcMapD1.set(maBC, sl);
    });

    bcD.forEach(row => {
      const maBC = row[3];
      const tenBC = row[4];
      const sl = safeNum(row[9]) + safeNum(row[11]) + safeNum(row[13]);
      const sl_truoc = bcMapD1.get(maBC) || 0;
      if (sl_truoc > 0) {
        const drop = (sl_truoc - sl) / sl_truoc * 100;
        if (drop > 20) {
          bcDrops.push({ name: tenBC, drop: drop.toFixed(1) });
        }
      }
    });

    bcDrops.sort((a, b) => b.drop - a.drop);

    // Tỷ lệ KH rụng
    const activeCustomers5Days = new Set();
    detail5Days.forEach(row => {
      // Trong Detail: Cột 6 là Mã CRM, Cột 5 là Mã CMR
      const maKH = row[6] || row[5];
      if (maKH && maKH.trim() !== "") activeCustomers5Days.add(maKH.trim());
    });

    const activeCustomersToday = new Set();
    detailD.forEach(row => {
      const maKH = row[6] || row[5];
      if (maKH && maKH.trim() !== "") activeCustomersToday.add(maKH.trim());
    });

    let missingCustomers = 0;
    activeCustomers5Days.forEach(kh => {
      if (!activeCustomersToday.has(kh)) {
        missingCustomers++;
      }
    });

    const tyLeRung = activeCustomers5Days.size > 0 ? (missingCustomers / activeCustomers5Days.size * 100).toFixed(1) : 0;

    // Format message
    const titleDate = `${String(toDateObj.getDate()).padStart(2, '0')}.${String(toDateObj.getMonth() + 1).padStart(2, '0')}.${toDateObj.getFullYear()}`;
    const reportDateStr = `${toDateObj.getDate()}/${toDateObj.getMonth() + 1}`;

    let msg = `${titleDate}_Bản tin ĐH số 3\n\n`;
    msg += `ĐÁNH GIÁ CÔNG TÁC QUẢN LÝ KHHH\n`;
    msg += `TTVH kính gửi A/c báo cáo Sản lượng, doanh thu các KHHH ngày ${reportDateStr}\n`;
    msg += `1.Tổng quan các chỉ số chính \n`;
    msg += `Toàn địa bàn ${provinceName}:\n`;
    msg += `- Sản lượng: ${(sl_D / 1000).toFixed(1)}K đơn (${tangGiamVol} so với D-1)\n`;
    msg += `2. Đánh giá biến động KHHH tại ${provinceName}\n`;
    msg += `- Tỷ lệ mã CMS KH không PS DT / tổng cộng mã KHPS 5 ngày trước liền kề =${tyLeRung} %,\n`;
    msg += `- Tỷ lệ biến động DT ngày báo cáo/kỳ trước liền kề =${revDiff}%\n`;
    msg += `3. Các TTDV có sản lượng đơn giảm trên 20% so với D-1 cần lưu ý:\n`;

    if (bcDrops.length > 0) {
      bcDrops.forEach(bc => {
        msg += `- ${bc.name.replace("BCP ", "").trim()}: giảm ${bc.drop}%\n`;
      });
    } else {
      msg += `- Không có TTDV nào giảm trên 20%\n`;
    }

    const khhhParsedData = {
      type: "KHHH",
      province: provinceName,
      reportDate: reportDateStr,
      metrics: {
        sl_D: sl_D,
        sl_D1: sl_D1,
        tangGiamVol: tangGiamVol,
        tyLeRung: tyLeRung,
        revDiff: revDiff
      },
      bcDrops: bcDrops
    };
    currentReportPayload = khhhParsedData;

    reportPanel.classList.remove("is-empty");
    reportBadge.textContent = "Bản tin ĐH số 3";
    emptyState.hidden = true;
    reportSummary.hidden = true;
    reportText.hidden = false;

    reportText.textContent = msg;
    setStatus(`Soạn tin nhắn hoàn tất! Bạn có thể copy nội dung bên dưới.`, "ok");

  } catch (error) {
    console.error(error);
    setStatus(error.message || "Có lỗi khi soạn tin nhắn.", "error");
  } finally {
    setBusy(false);
  }
}

async function generateReportMessage() {
  if (!form.reportValidity()) return;

  const from = parseControlDate(fromDateInput.value);
  const to = parseControlDate(toDateInput.value);
  const compareFrom = parseControlDate(nextFromDateInput.value);
  const compareTo = parseControlDate(nextToDateInput.value);
  if (!validateRequiredDates(from, to, compareFrom, compareTo) || !validatePeriodDates(from, to, compareFrom, compareTo)) return;

  setBusy(true);
  setStatus("Đang lấy dữ liệu để soạn tin nhắn báo cáo V2...");

  try {
    const weights = readWeights();

    setStatus("Đang tải dữ liệu V2 song song (có thể mất vài giây)...");
    
    // Tối ưu tốc độ: Tải đồng thời cả 3 báo cáo (Tỉnh kỳ này, Tỉnh kỳ trước, Bưu cục kỳ này)
    const [tinhCurrent, tinhCompare, bcCurrent] = await Promise.all([
      fetchV2ReportRows("TINH", from, to, weights, "kỳ báo cáo"),
      fetchV2ReportRows("TINH", compareFrom, compareTo, weights, "kỳ so sánh"),
      fetchV2ReportRows("BC", from, to, weights, "bưu cục kỳ báo cáo")
    ]);

    const provinceComparisonRows = buildV2ComparisonRows(tinhCurrent, tinhCompare);
    const selectedProv = getSelectedProvince();
    const provinceComparison = provinceComparisonRows.find((row) => String(row.code) === selectedProv);
    if (!provinceComparison) {
      throw new Error("Không tìm thấy dữ liệu tỉnh đã chọn trong 2 kỳ báo cáo V2.");
    }

    const reportData = buildFullMessageText({
      provinceComparison,
      totalUnits: provinceComparisonRows.length,
      bcCurrent,
      from, to, compareFrom, compareTo
    });

    currentReportPayload = reportData.parsedData;

    reportPanel.classList.remove("is-empty");
    reportBadge.textContent = "Tin nhắn Báo cáo (V2)";
    emptyState.hidden = true;
    reportSummary.hidden = true;
    reportText.hidden = false;

    reportText.textContent = reportData.text;
    setStatus(`Soạn tin nhắn hoàn tất! Bạn có thể copy nội dung bên dưới.`, "ok");
  } catch (error) {
    console.error(error);
    setStatus(error.message || "Có lỗi khi soạn tin nhắn.", "error");
  } finally {
    setBusy(false);
  }
}

function buildFullMessageText({ provinceComparison, totalUnits, bcCurrent, from, to, compareFrom, compareTo }) {
  const periodText = `${toApiDate(from)} - ${toApiDate(to)}`;
  const comparePeriodText = `${toApiDate(compareFrom)} - ${toApiDate(compareTo)}`;

  const toDateObj = new Date(to);
  const titleDate = `${String(toDateObj.getDate()).padStart(2, '0')}.${toDateObj.getMonth() + 1}.${toDateObj.getFullYear()}`;
  const header = `*${titleDate}_Bản tin ĐH số 1*\n*KẾT QUẢ THỰC HIỆN CHỈ TIÊU CHẤT LƯỢNG CÁC CÔNG ĐOẠN THEO TIÊU CHÍ ĐO KIỂM 2026*`;
  const reportIntro = `TTVH BĐ Tỉnh Nghệ An – Kết quả xếp hạng ${provinceComparison.name} (So sánh ${totalUnits} đơn vị)\nKỳ báo cáo: ${periodText}\nSo sánh với kỳ: ${comparePeriodText}\nso sánh 2 dữ liệu của 2 kỳ báo cáo và xếp hạng 2 dữ liệu của 2 kỳ báo cáo`;

  const targetMetrics = [
    { label: "🔹 F1.1 – Nội tỉnh", rateIndex: 24, totalIndex: 21, metricLabel: "F1.1 – Nội tỉnh" },
    { label: "🔹 F1.2 – Thu gom bưu gửi đi liên tỉnh", rateIndex: 29, totalIndex: 26, metricLabel: "F1.2 – Thu gom bưu gửi đi liên tỉnh" },
    { label: "🔹 F1.3 – Chất lượng phát bưu gửi liên tỉnh", rateIndex: 33, totalIndex: 31, metricLabel: "F1.3 – Chất lượng phát bưu gửi liên tỉnh", excludes: ["Con Cuông"] },
    { label: "🔹 F4.1 – Tỷ lệ PTC/ Nộp tiền", rateIndex: 40, totalIndex: 17, metricLabel: "F4.1 – Tỷ lệ PTC/ Nộp tiền", noRank: true },
    { label: "🔹 F4.1 – Tỷ lệ gửi PTC 8 giờ lần đầu (Có+Không TMS)", rateIndex: 41, totalIndex: 17, metricLabel: "F4.1 – Tỷ lệ gửi PTC 8 giờ lần đầu (Có+Không TMS)", noRank: true },
    { label: "🔹 F4.1 – Tỷ lệ gửi PTC 8 giờ lần đầu (Có TMS)", rateIndex: 42, totalIndex: 17, metricLabel: "F4.1 – Tỷ lệ gửi PTC 8 giờ lần đầu (Có TMS)", noRank: true },
    { label: "🔹 *Tỷ lệ trung bình chung chất lượng (TGLT,PTC LT)*", rateIndex: 35, totalIndex: 15, metricLabel: "Tỷ lệ trung bình chung chất lượng (TGLT,PTC LT)", noBottomList: true, isOverall: true }
  ];

  const parsedData = {
    header: {
      kyBaoCao: periodText,
      soSanh: comparePeriodText
    },
    overall: { score: "", trend: "", rank: "" },
    sections: []
  };

  const metricBlocks = targetMetrics.map(target => {
    const metric = provinceComparison.metrics.find(m => m.label === target.metricLabel);
    if (!metric) return "";

    const isAverage = target.label.includes("trung bình");
    const rateLabel = isAverage ? "Tỷ lệ bình quân" : "Tỷ lệ";

    let block = `${target.label}\n${rateLabel}: ${formatPercentValue(metric.currentRate)} (${rateTrendText(metric)})`;

    let sectionData = {
      title: target.metricLabel,
      tyLe: formatPercentValue(metric.currentRate),
      tyLeTrend: rateTrendText(metric),
      xepHang: "",
      xepHangTrend: "",
      bcpList: []
    };

    if (!target.noRank) {
      block += `\nXếp hạng: ${formatRank(metric.currentRank, totalUnits)} (${rankTrendText(metric)})`;
      sectionData.xepHang = `${formatRank(metric.currentRank, totalUnits).replace(" ", "")}`;
      sectionData.xepHangTrend = rankTrendText(metric);
    }

    if (!target.noBottomList) {
      const bottom5 = getBottom5BC(bcCurrent, target.rateIndex, target.totalIndex, target.excludes || []);
      if (bottom5.length > 0) {
        block += `\n❌ 05 BCP có tỷ lệ thấp:\n` + bottom5.map(bc => `${bc.name}: ${formatPercentValue(bc.rate)}`).join("\n");
        sectionData.bcpList = bottom5.map(bc => ({ name: bc.name.replace("BCP ", "").trim(), value: bc.rate }));
      }
    }

    if (target.isOverall) {
      parsedData.overall.score = sectionData.tyLe;
      parsedData.overall.trend = sectionData.tyLeTrend;
      parsedData.overall.rank = sectionData.xepHang;
    } else {
      parsedData.sections.push(sectionData);
    }

    return block;
  }).filter(Boolean);

  const footer = `Bưu Điện Tỉnh Nghệ An sẽ mời họp cầu đối với 05 BC có tỷ lệ thấp nhất trong mỗi chỉ tiêu đánh giá.\nTrân trọng!`;
  const text = [header, reportIntro, metricBlocks.join("\n\n"), footer].join("\n\n");

  return { text, parsedData };
}

async function fetchQuanLyKhachHangSummary(tuyChonGR, fromDate, toDate) {
  const params = {
    TuyChonGR: tuyChonGR,
    stMaHuyenChapNhan: "",
    stMaTinhChapNhan: getSelectedProvince(),
    stMaBuuCucChapNhan: tuyChonGR === "BC" ? "ALL" : "NULL",
    stLoaiDichVu: "ALL",
    iFrom: toApiDate(fromDate),
    iTo: toApiDate(toDate)
  };
  const requestUrl = buildApiRequestUrl("quanLyKhachHang", params);
  const records = await executeFetchSummaryViaContentScript(requestUrl);
  return records || [];
}

async function fetchQuanLyKhachHangDetailData(fromDate, toDate) {
  const params = {
    TuyChonGR: "TINH",
    stMaHuyenChapNhan: "",
    stMaTinhChapNhan: getSelectedProvince(),
    stMaBuuCucChapNhan: "NULL",
    stLoaiDichVu: "ALL",
    iFrom: toApiDate(fromDate),
    iTo: toApiDate(toDate)
  };
  const requestUrl = buildApiRequestUrl("quanLyKhachHang", params);
  const records = await executeFetchViaContentScript(requestUrl);
  return records || [];
}

async function exportQuanLyKhachHang(tuyChonGR) {
  const from = parseControlDate(fromDateInput.value);
  const to = parseControlDate(toDateInput.value);

  if (!from || !to) {
    setStatus("Vui lòng chọn Từ ngày và Đến ngày của kỳ báo cáo.", "error");
    return;
  }
  if (from > to) {
    setStatus("Từ ngày không được lớn hơn Đến ngày.", "error");
    return;
  }

  setBusy(true);
  setStatus(`Đang lấy dữ liệu Quản lý khách hàng (${tuyChonGR})...`);

  try {
    const fromStr = toApiDate(from);
    const toStr = toApiDate(to);
    const selectedProv = getSelectedProvince();

    const params = {
      TuyChonGR: tuyChonGR,
      stMaHuyenChapNhan: "",
      stMaTinhChapNhan: tuyChonGR === "BC" ? selectedProv : selectedProv,
      stMaBuuCucChapNhan: tuyChonGR === "BC" ? "ALL" : "NULL",
      stLoaiDichVu: "ALL",
      iFrom: fromStr,
      iTo: toStr
    };

    const requestUrl = buildApiRequestUrl("quanLyKhachHang", params);

    const response = await fetch(requestUrl, {
      method: "GET",
      credentials: "include",
      headers: {
        accept: "*/*",
        "x-requested-with": "XMLHttpRequest"
      }
    });

    if (!response.ok) {
      throw new Error(`Lỗi HTTP ${response.status}. Hãy đăng nhập dkcl.vnpost.vn rồi thử lại.`);
    }

    const json = await response.json();
    if (!json || !json.data) {
      throw new Error("Không nhận được dữ liệu HTML từ API QLKH.");
    }

    setStatus("Đang đóng gói file Excel...", "ok");

    // Parse directly from HTML (Cách "Cây nhà lá vườn" cực kỳ hiệu quả)
    const records = parseQuanLyKhachHangRows(json.data);
    if (records.length === 0) {
      throw new Error("Không có dữ liệu Quản lý khách hàng trong khoảng ngày này.");
    }

    const fileName = `QLKH_${tuyChonGR}_${fromStr.replace(/\//g, "")}-${toStr.replace(/\//g, "")}.xls`;
    downloadSimpleExcel(records, fileName, tuyChonGR);

    setStatus("Đã tạo và tải file Quản lý khách hàng thành công!", "ok");
  } catch (error) {
    console.error(error);
    setStatus(error.message || "Có lỗi khi xử lý dữ liệu QLKH.", "error");
  } finally {
    setBusy(false);
  }
}

function parseQuanLyKhachHangRows(htmlString) {
  const dom = new DOMParser().parseFromString(`<table><tbody>${htmlString}</tbody></table>`, "text/html");
  const rows = [...dom.querySelectorAll("tr.row_tong_quan")];
  return rows.map(tr => {
    const tds = [...tr.querySelectorAll("td")];
    return tds.map(td => td.textContent.trim());
  });
}

function downloadSimpleExcel(records, fileName, tuyChonGR) {
  // Cột mặc định cho bảng
  const headerLabels = [
    "STT", "Mã tỉnh", "Tên tỉnh", "Mã BC", "Tên BC",
    "Khách hàng lũy kế", "Khách hàng phát sinh", "KH ngừng phát sinh", "Tỷ lệ tăng/giảm (%)", "Sản lượng",
    "Cước thực thu", "Chỉ tiêu 1", "Chỉ tiêu 2", "Chỉ tiêu 3", "Chỉ tiêu 4"
  ];

  const headerHtml = `<tr>${headerLabels.map(h => `<th>${escapeHtml(h)}</th>`).join("")}</tr>`;
  const rowsHtml = records.map(row => {
    return `<tr>${row.map((cell, idx) => {
      // Ép kiểu số nếu cột chứa số liệu
      const isNum = idx >= 5 && /^[0-9.,]+%?$/.test(cell.replace(/,/g, ""));
      const tdAttr = isNum ? ` style="mso-number-format:'\\@'; text-align: right;"` : "";
      return `<td${tdAttr}>${escapeHtml(cell)}</td>`;
    }).join("")}</tr>`;
  }).join("");

  const tableHtml = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <meta charset="utf-8" />
      <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 13px; }
        th, td { border: 1px solid #000000; padding: 5px; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
      </style>
    </head>
    <body>
      <table>
        <thead>${headerHtml}</thead>
        <tbody>${rowsHtml}</tbody>
      </table>
    </body>
    </html>
  `;

  const blob = new Blob([tableHtml], { type: "application/vnd.ms-excel" });
  saveBlobAsFile(blob, fileName);
}

// Hàm thực thi qua Content Script
function executeFetchViaContentScript(summaryUrl) {
  return new Promise((resolve, reject) => {
    const id = Date.now().toString();

    const handler = (event) => {
      if (event.data?.source === "dkcl-content-script" && event.data?.type === "fetch-detail-response" && event.data?.id === id) {
        window.removeEventListener("message", handler);
        if (event.data.result.error) reject(new Error(event.data.result.error));
        else resolve(event.data.result.data);
      }
    };

    window.addEventListener("message", handler);
    window.parent.postMessage({
      source: "dkcl-report-popup",
      type: "fetch-detail",
      id: id,
      request: { summaryUrl }
    }, "*");

    setTimeout(() => {
      window.removeEventListener("message", handler);
      reject(new Error("Quá thời gian tải dữ liệu chi tiết từ máy chủ (5 phút). Vui lòng thử lại với khoảng ngày ngắn hơn."));
    }, 300000);
  });
}

function executeFetchSummaryViaContentScript(summaryUrl) {
  return new Promise((resolve, reject) => {
    const id = Date.now().toString();

    const handler = (event) => {
      if (event.data?.source === "dkcl-content-script" && event.data?.type === "fetch-summary-response" && event.data?.id === id) {
        window.removeEventListener("message", handler);
        if (event.data.result.error) reject(new Error(event.data.result.error));
        else resolve(event.data.result.data);
      }
    };

    window.addEventListener("message", handler);
    window.parent.postMessage({
      source: "dkcl-report-popup",
      type: "fetch-summary",
      id: id,
      request: { summaryUrl }
    }, "*");

    setTimeout(() => {
      window.removeEventListener("message", handler);
      reject(new Error("Quá thời gian tải dữ liệu tổng hợp từ máy chủ (30 giây). Vui lòng thử lại."));
    }, 30000);
  });
}

// Hàm mới: Xuất báo cáo KH Chi Tiết 1700+ dòng
async function exportQuanLyKhachHangDetail(tuyChonGR) {
  const from = parseControlDate(fromDateInput.value);
  const to = parseControlDate(toDateInput.value);

  if (!from || !to) {
    setStatus("Vui lòng chọn Từ ngày và Đến ngày của kỳ báo cáo.", "error");
    return;
  }
  if (from > to) {
    setStatus("Từ ngày không được lớn hơn Đến ngày.", "error");
    return;
  }

  setBusy(true);
  setStatus(`Đang truy xuất dữ liệu KH Chi tiết (${tuyChonGR}) từ máy chủ...`);

  try {
    const fromStr = toApiDate(from);
    const toStr = toApiDate(to);
    const selectedProv = getSelectedProvince();

    const params = {
      TuyChonGR: tuyChonGR,
      stMaHuyenChapNhan: "",
      stMaTinhChapNhan: tuyChonGR === "BC" ? selectedProv : selectedProv,
      stMaBuuCucChapNhan: tuyChonGR === "BC" ? "ALL" : "NULL",
      stLoaiDichVu: "ALL",
      iFrom: fromStr,
      iTo: toStr
    };

    const requestUrl = buildApiRequestUrl("quanLyKhachHang", params);

    // Giao tiếp với Content Script đang chạy trên trang web gốc để lấy dữ liệu (vượt qua CORS)
    const records = await executeFetchViaContentScript(requestUrl);

    if (!records || records.length === 0) {
      throw new Error("Không có dữ liệu chi tiết nào được tìm thấy.");
    }

    // Lọc bỏ các dòng không có TT hợp lệ nếu cần thiết
    const validRecords = records.filter(row => row.length > 5 && row[0].trim() !== "");

    setStatus(`Đã tải xong ${validRecords.length} dòng! Đang tạo file Excel...`, "ok");

    const fileName = `QLKH_ChiTiet_${tuyChonGR}_${fromStr.replace(/\//g, "")}-${toStr.replace(/\//g, "")}.xls`;

    // Tạo bảng Excel chi tiết
    const headerLabels = [
      "TT", "Mã tỉnh", "Tên tỉnh", "Mã BC", "Tên BC", "Mã CMR", "Mã CRM",
      "Tên KHL", "Số hiệu BG", "Dịch vụ", "Loại dịch vụ", "Mã SPDV", "Tên SPDV",
      "Cước thực thu", "Thời gian chấp nhận", "Khối lượng"
    ];

    const headerHtml = `<tr>${headerLabels.map(h => `<th>${escapeHtml(h)}</th>`).join("")}</tr>`;
    const rowsHtml = validRecords.map(row => {
      return `<tr>${row.map(cell => {
        // Cột cước thực thu (index 13) và Khối lượng (index 15) thường là số, tránh bị lỗi format
        const tdAttr = /^[0-9.,]+$/.test(cell.replace(/,/g, "")) ? ` style="mso-number-format:'\\@'; text-align: right;"` : "";
        return `<td${tdAttr}>${escapeHtml(cell)}</td>`;
      }).join("")}</tr>`;
    }).join("");

    const tableHtml = `
      <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta charset="utf-8" />
        <style>
          table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 13px; }
          th, td { border: 1px solid #000000; padding: 5px; }
          th { background-color: #d9e1f2; font-weight: bold; text-align: center; }
        </style>
      </head>
      <body>
        <table><thead>${headerHtml}</thead><tbody>${rowsHtml}</tbody></table>
      </body>
      </html>
    `;

    const blob = new Blob([tableHtml], { type: "application/vnd.ms-excel" });
    saveBlobAsFile(blob, fileName);

    setStatus("Đã tạo và tải file Khách hàng Chi tiết thành công!", "ok");
  } catch (error) {
    console.error(error);
    setStatus(error.message || "Có lỗi khi xử lý dữ liệu KH Chi tiết.", "error");
  } finally {
    setBusy(false);
  }
}

function getBottom5BC(bcCurrentRows, rateIndex, totalIndex, excludes = []) {
  // Báo cáo BC đã tự động chỉ lấy bưu cục của Tỉnh được chọn, nên không cần filter theo Mã tỉnh nữa.
  // Trong cấu trúc nội bộ của bcCurrentRows (tuyChonGR = "BC"): 
  // row[1] là Mã Bưu cục, row[2] là Tên Bưu cục.
  const withRates = bcCurrentRows.map(row => {
    const rawRate = Number(normalizePercent(row[rateIndex]));
    const total = toNumberValue(row[totalIndex]);
    return {
      name: row[2], // Tên Bưu cục
      rate: Number.isFinite(rawRate) ? rawRate : 0,
      total: total
    };
  }).filter(item => {
    if (!item.name || !item.name.toUpperCase().includes("BCP") || item.total <= 0) return false;
    // Bỏ qua các BCP có tỉ lệ 0% (được miễn trừ)
    if (item.rate <= 0) return false;
    // Kiểm tra xem tên bưu cục có nằm trong danh sách loại trừ không
    if (excludes.length > 0 && excludes.some(ex => item.name.toUpperCase().includes(ex.toUpperCase()))) {
      return false;
    }
    return true;
  }); // Lọc BCP, sản lượng > 0 và không bị loại trừ

  withRates.sort((a, b) => a.rate - b.rate);
  return withRates.slice(0, 5);
}

async function fetchCombinedReport(tuyChonGR, from, to, periodLabel = "") {
  const sourceTables = [];

  for (const config of SOURCE_CONFIGS) {
    setStatus(config.status);
    const requestUrl = buildApiRequestUrl(config.key, config.buildParams(tuyChonGR, from, to));
    const payload = await fetchApi(config.key, config.buildParams(tuyChonGR, from, to));
    const records = parseApiRows(payload.data || "", config, tuyChonGR);
    console.log(`[DKCL][${tuyChonGR}] ${config.name}: parsed records`, records.length, records.slice(0, 3));
    if (!records.length) {
      throw new Error(buildMissingSourceDataMessage(config.name, periodLabel, from, to, requestUrl));
    }
    sourceTables.push({ config, records });
  }

  return joinProvinceTables(sourceTables, tuyChonGR);
}

function buildMissingSourceDataMessage(sourceName, periodLabel, from, to, requestUrl = "") {
  const periodText = periodLabel ? `${periodLabel} ` : "";
  const curlText = requestUrl ? `\nLink curl: ${requestUrl}` : "";
  return `Biểu "${sourceName}" không có dữ liệu cho ${periodText}(${toApiDate(from)} - ${toApiDate(to)}).${curlText}\nVui lòng kiểm tra đăng nhập, khoảng ngày hoặc điều kiện lọc.`;
}

async function fetchV2ReportRows(tuyChonGR, from, to, weights, periodLabel = "") {
  const combinedRows = await fetchCombinedReport(tuyChonGR, from, to, periodLabel);
  const finalizedBaseRows = finalizeRows(combinedRows, weights, tuyChonGR);
  if (!finalizedBaseRows.length) {
    throw new Error(buildMissingSourceDataMessage(SOURCE_CONFIGS.map((config) => config.name).join(" / "), periodLabel, from, to));
  }

  const baseRows = finalizedBaseRows
    .map((row) => {
      const v2Row = Array(V2_EXCEL_COLUMNS.length).fill("");
      TEMPLATE_EXCEL_COLUMNS.forEach((_, index) => (v2Row[index] = row[index] ?? ""));
      return v2Row;
    });
  const rowMap = new Map(baseRows.map((row) => [String(row[PROVINCE_CODE_INDEX]), row]));
  const joinCodeSet = new Set(rowMap.keys());

  console.log(`[DKCL][V2][${tuyChonGR}] base rows for join`, {
    rows: baseRows.length,
    joinCodes: [...joinCodeSet].slice(0, 30),
    sampleRows: baseRows.slice(0, 5)
  });

  for (const config of V2_SOURCE_CONFIGS) {
    setStatus(config.status);
    const requestUrl = buildApiRequestUrl(config.key, config.buildParams(tuyChonGR, from, to));
    const payload = await fetchApi(config.key, config.buildParams(tuyChonGR, from, to));
    const records = parseV2ApiRows(payload.data || "", config, tuyChonGR, joinCodeSet);
    const aggregatedRecords = aggregateV2RecordsByCode(records, config);
    console.log(`[DKCL][V2][${tuyChonGR}] ${config.name}: parsed records`, records.length, records.slice(0, 3));
    console.log(`[DKCL][V2][${tuyChonGR}] ${config.name}: aggregated records before join`, aggregatedRecords.length, aggregatedRecords.slice(0, 5));
    if (!aggregatedRecords.length) {
      throw new Error(buildMissingSourceDataMessage(config.name, periodLabel, from, to, requestUrl));
    }
    for (const record of aggregatedRecords) {
      if (!Number.isInteger(record.provinceCodeInt)) continue;
      if (tuyChonGR === "TINH" && [1, 8].includes(record.provinceCodeInt)) continue;
      const row = rowMap.get(String(record.provinceCodeInt));
      if (!row) {
        console.warn(`[DKCL][V2][${tuyChonGR}] ${config.name}: missing join row for code`, record.provinceCodeInt, record);
        continue;
      }
      Object.entries(config.targets).forEach(([field, target]) => {
        if (field === "rate" || field === "rank") return;
        row[target] = zeroIfBlank(record[field]);
      });
    }
  }

  finalizeV2Rows(baseRows);
  console.log(`[DKCL][V2][${tuyChonGR}] final rows after V2 join`, baseRows.length, baseRows.slice(0, 10));
  return baseRows;
}

function parseV2ApiRows(html, config, tuyChonGR, joinCodeSet = null) {
  const documentHtml = new DOMParser().parseFromString(`<table><tbody>${html}</tbody></table>`, "text/html");
  const rows = [...documentHtml.querySelectorAll("tr")]
    .filter((tr) => tr.children.length > 2 && !tr.classList.contains("tr_tong"));
  const cellRows = rows.map((tr) => [...tr.children].map((td) => cleanText(td.textContent)));
  console.log(`[DKCL][V2][${tuyChonGR}] ${config.name}: raw rows`, rows.length, "sample cells", cellRows.slice(0, 10));

  return cellRows
    .map((cells) => {
      const provinceCodeText = pickV2JoinCodeText(cells, config, tuyChonGR, joinCodeSet);
      const provinceCodeInt = toIntegerCode(provinceCodeText);
      const provinceName = pickReportName(cells, config, tuyChonGR);
      const record = {
        cells,
        provinceCodeText,
        provinceCodeInt,
        provinceName
      };
      Object.entries(config.values).forEach(([field, index]) => {
        record[field] = Number.isInteger(index) ? (cells[index] || "") : "";
      });
      return record;
    })
    .filter((record) => Number.isInteger(record.provinceCodeInt));
}

function pickV2JoinCodeText(cells, config, tuyChonGR, joinCodeSet) {
  if (tuyChonGR === "BC" && joinCodeSet?.size) {
    const matchedCode = cells
      .map((cell) => String(toIntegerCode(cell)))
      .find((code) => code !== "10" && joinCodeSet.has(code));
    if (matchedCode) return matchedCode;
  }

  return pickReportCodeText(cells, config, tuyChonGR);
}

function aggregateV2RecordsByCode(records, config) {
  const recordMap = new Map();

  for (const record of records) {
    const key = String(record.provinceCodeInt);
    if (!recordMap.has(key)) {
      const initialAggregate = { ...record };
      Object.keys(config.values).forEach(field => {
        initialAggregate[field] = "0";
      });
      recordMap.set(key, initialAggregate);
    }

    const aggregate = recordMap.get(key);
    Object.keys(config.values).forEach(field => {
      aggregate[field] = sumNumberText(aggregate[field], record[field]);
    });
  }

  return [...recordMap.values()];
}

function sumNumberText(a, b) {
  return String(toNumberValue(a) + toNumberValue(b));
}

function toNumberValue(value) {
  const number = Number(cleanNumber(value));
  return Number.isFinite(number) ? number : 0;
}

function finalizeV2Rows(rows) {
  V2_SOURCE_CONFIGS.forEach((config) => {
    rows.forEach((row) => {
      // Ensure all target fields are zeroed if blank
      Object.entries(config.targets).forEach(([key, targetIndex]) => {
        if (key !== 'rate' && key !== 'rank') {
          row[targetIndex] = zeroIfBlank(row[targetIndex]);
        }
      });

      if (config.key === "phatThanhCongBuuCuc") {
        row[40] = percentFrom(row[config.targets.ptcNopTien], row[config.targets.total]);
        row[41] = percentFrom(row[config.targets.ptc8hLanDauAll], row[config.targets.total]);
        row[42] = percentFrom(row[config.targets.ptc8hLanDauTms], row[config.targets.total]);
      } else {
        const denominator = row[config.targets.total];
        const numerator = row[config.targets.onTime];
        row[config.targets.rate] = percentFrom(numerator, denominator);
      }
    });

    if (config.key === "phatThanhCongBuuCuc") {
      // Rank by one of the metrics if needed, or leave unranked. 
    } else {
      assignRankByColumn(rows, config.targets.rate, config.targets.rank);
    }
  });
  rows.forEach((row) => {
    const adVal = toNumberValue(row[29]);
    const ahVal = toNumberValue(row[33]);
    row[35] = String((adVal + ahVal) / 2);
  });
  assignRankByColumn(rows, 35, 36);
  rows.forEach((row, index) => (row[0] = String(index + 1)));
}

function buildPhatThanhCongV2Params(tuyChonGR, from, to) {
  return {
    TuyChonGR: tuyChonGR,
    stMaTinhPhat: tuyChonGR === "BC" ? getSelectedProvince() : "ALL",
    stMaLoaiBCPhat: tuyChonGR === "BC" ? "ALL" : "NULL",
    stMaBuuCucPhat: "ALL",
    stLoaiDichVu: "ALL",
    stNhomLoaiKH: "ALL",
    stPhamViTinh: "NULL",
    stLoaiTuyenPhat: "NULL",
    stLoaiPhuongXa: "NULL",
    iFrom: toApiDate(from),
    iTo: toApiDate(to),
    iPageSize: "50000",
    iPage: "1"
  };
}

function buildNoiTinhF11Params(tuyChonGR, from, to) {
  return {
    TuyChonGR: tuyChonGR,
    stMaTinhChapNhan: tuyChonGR === "BC" ? getSelectedProvince() : "ALL",
    stMaBuuCucNhan: "NULL",
    stMaTinhPhat: "ALL",
    stMaBCKTTinhChapNhan: "NULL",
    stMaBCKTTinhPhat: "NULL",
    stMaLoaiBCKT: "NULL",
    stMaBuuCucPhat: "NULL",
    stLoaiDichVu: "ALL",
    stNhomLoaiKH: "ALL",
    iFrom: toApiDate(from),
    iTo: toApiDate(to),
    iPageSize: "50000",
    iPage: "1"
  };
}

function buildThuGomLienTinhF12Params(tuyChonGR, from, to) {
  return {
    TuyChonGR: tuyChonGR,
    stLoaiDichVu: "ALL",
    stNhomLoaiKH: "ALL",
    iFrom: toApiDate(from),
    iTo: toApiDate(to),
    stMaTinhNhan: tuyChonGR === "BC" ? getSelectedProvince() : "ALL",
    stMaBuuCucNhan: "ALL",
    stMaBCKTTinhNhan: "ALL"
  };
}

function buildPhatLienTinhF13Params(tuyChonGR, from, to) {
  return {
    TuyChonGR: tuyChonGR,
    stMaTinhPhat: tuyChonGR === "BC" ? getSelectedProvince() : "ALL",
    stMaBCKTTinhPhat: "ALL",
    stMaBuuCucPhat: "ALL",
    stLoaiDichVu: "ALL",
    stNhomLoaiKH: "ALL",
    iFrom: toApiDate(from),
    iTo: toApiDate(to),
    iPageSize: "50000",
    iPage: "1"
  };
}

function joinProvinceTables(sourceTables, tuyChonGR) {
  const rowMap = new Map();

  for (const { config, records } of sourceTables) {
    for (const record of records) {
      if (!Number.isInteger(record.provinceCodeInt)) continue;
      if (tuyChonGR === "TINH" && [1, 8].includes(record.provinceCodeInt)) continue;
      if (!cleanText(record.provinceName)) continue;

      const row = ensureReportRow(rowMap, record.provinceCodeInt, record.provinceCodeText, record.provinceName);
      config.columns.forEach(({ source, target, type }) => {
        row[target] = normalizeCell(record[source], type);
      });
    }
  }

  return [...rowMap.values()];
}

function buildApiRequestUrl(apiKey, paramsObject) {
  const params = new URLSearchParams(paramsObject);
  return `${API_URLS[apiKey]}?${params.toString()}`;
}

async function fetchApi(apiKey, paramsObject) {
  const requestUrl = buildApiRequestUrl(apiKey, paramsObject);
  console.log(`[DKCL] Request ${apiKey}:`, requestUrl, paramsObject);

  const response = await fetch(requestUrl, {
    method: "GET",
    credentials: "include",
    headers: {
      accept: "*/*",
      "accept-language": "en-US,en;q=0.9,vi;q=0.8",
      referer: "https://dkcl.vnpost.vn/",
      "x-requested-with": "XMLHttpRequest"
    }
  });

  if (!response.ok) {
    throw new Error(`${apiKey}: API trả về lỗi HTTP ${response.status}. Hãy đăng nhập dkcl.vnpost.vn rồi thử lại.`);
  }

  const text = await response.text();
  console.log(`[DKCL] Response ${apiKey}:`, {
    status: response.status,
    ok: response.ok,
    length: text.length,
    preview: text.slice(0, 500)
  });

  try {
    return JSON.parse(text);
  } catch {
    throw new Error(`${apiKey}: Response không phải JSON. Có thể phiên đăng nhập đã hết hạn.`);
  }
}

function buildPhatThanhCongTmdtParams(tuyChonGR, from, to) {
  return {
    TuyChonGR: tuyChonGR,
    stMaHuyenPhat: "",
    stMaTinhPhat: tuyChonGR === "BC" ? getSelectedProvince() : "ALL",
    stMaLoaiBCPhat: tuyChonGR === "BC" ? "ALL" : "NULL",
    stMaBuuCucPhat: "ALL",
    stLoaiDichVu: "ALL",
    "stNhomLoaiBuuGui[]": "TMĐT",
    stNhomLoaiKH: "ALL",
    stPhamViTinh: "NULL",
    stLoaiTuyenPhat: "NULL",
    stLoaiPhuongXa: "NULL",
    iFrom: toApiDate(from),
    iTo: toApiDate(to),
    iPageSize: tuyChonGR === "BC" ? "50000" : "10000",
    iPage: "1"
  };
}

function buildPhatThanhCongTruyenThongParams(tuyChonGR, from, to) {
  const params = buildPhatThanhCongTmdtParams(tuyChonGR, from, to);
  params["stNhomLoaiBuuGui[]"] = "Truyền thống";
  return params;
}

function buildThuGomParams(tuyChonGR, from, to) {
  return {
    TuyChonGR: tuyChonGR,
    stMaTinhThuGom: tuyChonGR === "BC" ? getSelectedProvince() : "ALL",
    stMaBuuCucThuGom: "ALL",
    stMaBCKTTinhNhan: "NULL",
    stLoaiDichVu: "ALL",
    stNhomLoaiKH: "ALL",
    stPhamViTinh: "NULL",
    iFrom: toApiDate(from),
    iTo: toApiDate(to),
    iPageSize: tuyChonGR === "BC" ? "50000" : "10000",
    iPage: "1"
  };
}

function parseApiRows(html, config, tuyChonGR) {
  const documentHtml = new DOMParser().parseFromString(`<table><tbody>${html}</tbody></table>`, "text/html");
  const rows = [...documentHtml.querySelectorAll("tr")]
    .filter((tr) => tr.children.length > 2 && !tr.classList.contains("tr_tong"));
  const cellRows = rows.map((tr) => [...tr.children].map((td) => cleanText(td.textContent)));

  console.log(`[DKCL][${tuyChonGR}] ${config.name}: raw rows`, rows.length, "sample cells", cellRows.slice(0, 3));

  return cellRows
    .map((cells) => mapRecordBySource(cells, config, tuyChonGR))
    .filter((record) => Number.isInteger(record.provinceCodeInt));
}

function mapRecordBySource(cells, config, tuyChonGR) {
  const provinceCodeText = pickReportCodeText(cells, config, tuyChonGR);
  const provinceCodeInt = toIntegerCode(provinceCodeText);
  const provinceName = pickReportName(cells, config, tuyChonGR);

  if (config.key === "thuGomLienTinh") {
    return mapThuGomRecord(cells, provinceCodeText, provinceCodeInt, provinceName);
  }

  return mapPhatThanhCongRecord(cells, provinceCodeText, provinceCodeInt, provinceName);
}

function mapPhatThanhCongRecord(cells, provinceCodeText, provinceCodeInt, provinceName) {
  return {
    cells,
    provinceCodeText,
    provinceCodeInt,
    provinceName,
    total: cells[10] || "",
    success8h: cells[25] || ""
  };
}

function mapThuGomRecord(cells, provinceCodeText, provinceCodeInt, provinceName) {
  return {
    cells,
    provinceCodeText,
    provinceCodeInt,
    provinceName,
    total: cells[8] || "",
    onTime: cells[9] || ""
  };
}

function ensureReportRow(rowMap, provinceCodeInt, provinceCodeText, provinceName) {
  if (!rowMap.has(provinceCodeInt)) {
    const row = Array(EXCEL_COLUMNS.length).fill("");
    row[PROVINCE_CODE_INDEX] = provinceCodeInt;
    row[PROVINCE_NAME_INDEX] = provinceName;
    rowMap.set(provinceCodeInt, row);
  }
  const row = rowMap.get(provinceCodeInt);
  if (!row[PROVINCE_NAME_INDEX] && provinceName) row[PROVINCE_NAME_INDEX] = provinceName;
  if (!row[PROVINCE_CODE_INDEX] && provinceCodeText) row[PROVINCE_CODE_INDEX] = provinceCodeInt;
  return row;
}

function finalizeRows(rows, weights, tuyChonGR) {
  rows.forEach((row) => {
    row[3] = zeroIfBlank(row[3]);
    row[4] = zeroIfBlank(row[4]);
    row[7] = zeroIfBlank(row[7]);
    row[8] = zeroIfBlank(row[8]);
    row[11] = zeroIfBlank(row[11]);
    row[12] = zeroIfBlank(row[12]);
    row[5] = percentFrom(row[4], row[3]);
    row[9] = percentFrom(row[8], row[7]);
    row[13] = percentFrom(row[12], row[11]);
    row[15] = weightedAveragePercent([row[5], row[9], row[13]], weights);
  });

  const finalRows = tuyChonGR === "BC" ? rows : rows.filter(hasAllPositiveRateColumns);

  assignRankByColumn(finalRows, 5, 6);
  assignRankByColumn(finalRows, 9, 10);
  assignRankByColumn(finalRows, 13, 14);
  assignRankByColumn(finalRows, 15, 16);

  finalRows.sort((a, b) => compareNumberOrText(a[PROVINCE_CODE_INDEX], b[PROVINCE_CODE_INDEX]));
  finalRows.forEach((row, index) => {
    row[0] = String(index + 1);
  });

  return finalRows;
}

function hasAllPositiveRateColumns(row) {
  return [5, 9, 13, 15].every((index) => Number(row[index]) > 0);
}

function renderReportPreview({ rows, tuyChonGR, from, to, fileName, weights }) {
  const reportName = tuyChonGR === "TINH" ? "Theo tỉnh" : "Theo bưu cục";

  reportPanel.classList.remove("is-empty");
  reportBadge.textContent = reportName;
  emptyState.hidden = true;
  reportSummary.hidden = false;
  reportText.hidden = false;

  reportSummary.innerHTML = [
    metricTemplate("Loại báo cáo", reportName),
    metricTemplate("Số dòng", rows.length.toLocaleString("vi-VN")),
    metricTemplate("Trọng số", `TMĐT ${weights.tmdt}% · TT ${weights.traditional}% · TG ${weights.pickup}%`),
    metricTemplate("File Excel", fileName)
  ].join("");

  reportText.textContent = `Đã lấy ${rows.length.toLocaleString("vi-VN")} dòng dữ liệu ${reportName.toLowerCase()}.\nKỳ báo cáo: ${toApiDate(from)} - ${toApiDate(to)}\nFile Excel: ${fileName}`;
}

function renderTemplateReportPreview({ rows, provinceComparison, from, to, compareFrom, compareTo, fileName, weights }) {
  reportPanel.classList.remove("is-empty");
  reportBadge.textContent = "So sánh kỳ Tỉnh";
  emptyState.hidden = true;
  reportSummary.hidden = false;
  reportText.hidden = false;

  reportSummary.innerHTML = [
    metricTemplate("Loại báo cáo", "TTVH BĐ Tỉnh Nghệ An  · So sánh kỳ theo tỉnh"),
    metricTemplate("Mã tỉnh", `${provinceComparison.code} · ${provinceComparison.name}`),
    metricTemplate("Kỳ báo cáo", `${toApiDate(from)} - ${toApiDate(to)}`),
    metricTemplate("So sánh với kỳ", `${toApiDate(compareFrom)} - ${toApiDate(compareTo)}`),
    metricTemplate("Số đơn vị xếp hạng", rows.length.toLocaleString("vi-VN")),
    metricTemplate("File Excel", fileName)
  ].join("");

  reportText.textContent = buildProvinceComparisonText({
    rows,
    provinceComparison,
    from,
    to,
    compareFrom,
    compareTo
  });
}

function buildProvinceComparisonText({ rows, provinceComparison, from, to, compareFrom, compareTo }) {
  const totalUnits = rows.length || 36;
  const periodText = `${toApiDate(from)} - ${toApiDate(to)}`;
  const comparePeriodText = `${toApiDate(compareFrom)} - ${toApiDate(compareTo)}`;
  const metricBlocks = provinceComparison.metrics.map((metric) => {
    const isAverage = metric.label.toLowerCase().includes("trung bình");
    const rateLabel = isAverage ? "Tỷ lệ bình quân" : "Tỷ lệ";
    return `🔹 ${metric.label}\n${rateLabel}: ${formatPercentValue(metric.currentRate)} (${rateTrendText(metric)})\nXếp hạng: ${formatRank(metric.currentRank, totalUnits)} (${rankTrendText(metric)})`;
  });

  return [
    `📊 TTVH – Kết quả xếp hạng BĐ Nghệ An (So sánh ${totalUnits} đơn vị)`,
    `Kỳ báo cáo: ${periodText}`,
    `So sánh với kỳ: ${comparePeriodText}`,
    "so sánh 2 dữ liệu của 2 kỳ báo cáo và xếp hạng 2 dữ liệu của 2 kỳ báo cáo",
    "",
    metricBlocks.join("\n\n")
  ].join("\n");
}

function metricTemplate(label, value) {
  return `<div class="metric"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`;
}

function downloadExcel({ rows, tuyChonGR, from, to, fileName }) {
  const html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <meta charset="UTF-8" />
      <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Tinh</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
      <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        th { font-weight: 700; text-align: center; white-space: normal; }
        td { mso-number-format:"\\@"; }
        .number { mso-number-format:"#,##0"; text-align: right; }
        .percent { mso-number-format:"0.00%"; text-align: right; }
        .group { background: #f8cbad; }
        .head { background: #d9e1f2; }
      </style>
    </head>
    <body>
      <table>
        ${renderExcelHeaderRows()}
        ${rows.map((row) => `<tr>${row.map((cell, index) => renderExcelCell(cell, index)).join("")}</tr>`).join("")}
      </table>
    </body>
    </html>`;

  const blob = new Blob(["\ufeff", html], { type: "application/vnd.ms-excel;charset=utf-8" });
  saveBlobAsFile(blob, fileName);
}

function downloadProvinceTemplateExcel({ currentRows, compareRows, comparisonRows, from, to, compareFrom, compareTo, fileName }) {
  const workbookXml = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:html="http://www.w3.org/TR/REC-html40">
  <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
    <Author>DKCL Export Sidebar</Author>
    <Created>${new Date().toISOString()}</Created>
  </DocumentProperties>
  <Styles>
    <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10"/></Style>
    <Style ss:ID="Title"><Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/></Style>
    <Style ss:ID="Header"><Font ss:FontName="Arial" ss:Bold="1"/><Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/><Borders>${excelXmlBorders()}</Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/></Style>
    <Style ss:ID="Group"><Font ss:FontName="Arial" ss:Bold="1"/><Interior ss:Color="#F8CBAD" ss:Pattern="Solid"/><Borders>${excelXmlBorders()}</Borders></Style>
    <Style ss:ID="Cell"><Borders>${excelXmlBorders()}</Borders></Style>
    <Style ss:ID="Number"><Borders>${excelXmlBorders()}</Borders><NumberFormat ss:Format="#,##0"/><Alignment ss:Horizontal="Right"/></Style>
    <Style ss:ID="Percent"><Borders>${excelXmlBorders()}</Borders><NumberFormat ss:Format="0.00%"/><Alignment ss:Horizontal="Right"/></Style>
    <Style ss:ID="Text"><Borders>${excelXmlBorders()}</Borders><NumberFormat ss:Format="@"/></Style>
    <Style ss:ID="Up"><Borders>${excelXmlBorders()}</Borders><Font ss:FontName="Arial" ss:Bold="1" ss:Color="#008000"/><NumberFormat ss:Format="@"/></Style>
    <Style ss:ID="Down"><Borders>${excelXmlBorders()}</Borders><Font ss:FontName="Arial" ss:Bold="1" ss:Color="#C00000"/><NumberFormat ss:Format="@"/></Style>
    <Style ss:ID="Same"><Borders>${excelXmlBorders()}</Borders><Font ss:FontName="Arial" ss:Bold="1" ss:Color="#666666"/><NumberFormat ss:Format="@"/></Style>
  </Styles>
  ${renderDataWorksheet("Du lieu dau ky", currentRows, `Dữ liệu đầu kỳ: ${toApiDate(from)} - ${toApiDate(to)}`)}
  ${renderDataWorksheet("Ky so sanh", compareRows, `Dữ liệu kỳ so sánh: ${toApiDate(compareFrom)} - ${toApiDate(compareTo)}`)}
  ${renderComparisonWorksheet(comparisonRows, from, to, compareFrom, compareTo)}
</Workbook>`;

  const blob = new Blob([workbookXml], { type: "application/vnd.ms-excel;charset=utf-8" });
  saveBlobAsFile(blob, fileName);
}

function renderV2ReportPreview({ rows, tuyChonGR, from, to, compareFrom, compareTo, fileName, mode, provinceComparison }) {
  const reportName = mode === "compare" ? "So sánh kỳ V2" : tuyChonGR === "TINH" ? "Theo tỉnh V2" : "Theo bưu cục V2";
  reportPanel.classList.remove("is-empty");
  reportBadge.textContent = reportName;
  emptyState.hidden = true;
  reportSummary.hidden = false;
  reportText.hidden = false;

  const summaryItems = [
    metricTemplate("Loại báo cáo", reportName),
    metricTemplate("Số dòng", rows.length.toLocaleString("vi-VN")),
    metricTemplate("Kỳ báo cáo", `${toApiDate(from)} - ${toApiDate(to)}`)
  ];
  if (mode === "compare" && compareFrom && compareTo) {
    summaryItems.push(metricTemplate("So sánh với kỳ", `${toApiDate(compareFrom)} - ${toApiDate(compareTo)}`));
  }
  summaryItems.push(metricTemplate("File Excel", fileName));
  reportSummary.innerHTML = summaryItems.join("");

  if (mode === "compare" && provinceComparison) {
    reportText.textContent = buildProvinceComparisonText({
      rows,
      provinceComparison,
      from,
      to,
      compareFrom,
      compareTo
    });
    return;
  }

  reportText.textContent = `Đã lấy ${rows.length.toLocaleString("vi-VN")} dòng dữ liệu ${reportName}.\nFile Excel: ${fileName}`;
}

function downloadV2Excel({ rows, tuyChonGR, from, to, fileName }) {
  const workbookXml = buildV2WorkbookXml({ rows, tuyChonGR, from, to });
  const blob = new Blob([workbookXml], { type: "application/vnd.ms-excel;charset=utf-8" });
  saveBlobAsFile(blob, fileName);
}

function downloadV2ComparisonExcel({ currentRows, compareRows, comparisonRows, from, to, compareFrom, compareTo, fileName }) {
  const workbookXml = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:html="http://www.w3.org/TR/REC-html40">
  ${renderWorkbookPropertiesAndStyles()}
  ${renderV2DataWorksheet("Du lieu dau ky V2", currentRows, `Dữ liệu V2 đầu kỳ: ${toApiDate(from)} - ${toApiDate(to)}`)}
  ${renderV2DataWorksheet("Ky so sanh V2", compareRows, `Dữ liệu V2 kỳ so sánh: ${toApiDate(compareFrom)} - ${toApiDate(compareTo)}`)}
  ${renderV2ComparisonWorksheet(comparisonRows, from, to, compareFrom, compareTo)}
</Workbook>`;
  const blob = new Blob([workbookXml], { type: "application/vnd.ms-excel;charset=utf-8" });
  saveBlobAsFile(blob, fileName);
}

function buildV2WorkbookXml({ rows, tuyChonGR, from, to }) {
  return `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:html="http://www.w3.org/TR/REC-html40">
  ${renderWorkbookPropertiesAndStyles()}
  ${renderV2DataWorksheet(tuyChonGR === "TINH" ? "Tinh V2" : "Buu cuc V2", rows, `Dữ liệu filebaocaov2.xlsx: ${toApiDate(from)} - ${toApiDate(to)}`)}
</Workbook>`;
}

function renderWorkbookPropertiesAndStyles() {
  return `<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
    <Author>DKCL Export Sidebar</Author>
    <Created>${new Date().toISOString()}</Created>
  </DocumentProperties>
  <Styles>
    <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10"/></Style>
    <Style ss:ID="Title"><Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/></Style>
    <Style ss:ID="Header"><Font ss:FontName="Arial" ss:Bold="1"/><Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/><Borders>${excelXmlBorders()}</Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/></Style>
    <Style ss:ID="Group"><Font ss:FontName="Arial" ss:Bold="1"/><Interior ss:Color="#F8CBAD" ss:Pattern="Solid"/><Borders>${excelXmlBorders()}</Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/></Style>
    <Style ss:ID="Cell"><Borders>${excelXmlBorders()}</Borders></Style>
    <Style ss:ID="Number"><Borders>${excelXmlBorders()}</Borders><NumberFormat ss:Format="#,##0"/><Alignment ss:Horizontal="Right"/></Style>
    <Style ss:ID="Percent"><Borders>${excelXmlBorders()}</Borders><NumberFormat ss:Format="0.00%"/><Alignment ss:Horizontal="Right"/></Style>
    <Style ss:ID="Text"><Borders>${excelXmlBorders()}</Borders><NumberFormat ss:Format="@"/></Style>
    <Style ss:ID="Up"><Borders>${excelXmlBorders()}</Borders><Font ss:FontName="Arial" ss:Bold="1" ss:Color="#008000"/><NumberFormat ss:Format="@"/></Style>
    <Style ss:ID="Down"><Borders>${excelXmlBorders()}</Borders><Font ss:FontName="Arial" ss:Bold="1" ss:Color="#C00000"/><NumberFormat ss:Format="@"/></Style>
    <Style ss:ID="Same"><Borders>${excelXmlBorders()}</Borders><Font ss:FontName="Arial" ss:Bold="1" ss:Color="#666666"/><NumberFormat ss:Format="@"/></Style>
  </Styles>`;
}

function renderV2DataWorksheet(sheetName, rows, title) {
  return `<Worksheet ss:Name="${escapeXml(sheetName)}">
    <Table>
      ${renderColumnWidths([46, 78, 190, 115, 150, 90, 70, 115, 150, 90, 70, 115, 150, 90, 70, 90, 70, 115, 150, 90, 70, 120, 120, 150, 90, 70, 140, 150, 150, 90, 70, 140, 160, 100, 70, 150, 70])}
      <Row><Cell ss:MergeAcross="36" ss:StyleID="Title"><Data ss:Type="String">${escapeXml(title)}</Data></Cell></Row>
      ${renderExcelXmlGroupHeaderRow(V2_GROUP_HEADERS)}
      <Row>${V2_EXCEL_COLUMNS.map((column) => excelXmlCell(column, "String", "Header")).join("")}</Row>
      ${rows.map((row) => `<Row>${row.map((cell, index) => excelXmlCell(cell, getV2ExcelXmlDataType(cell, index), getV2ExcelXmlStyle(index))).join("")}</Row>`).join("")}
    </Table>
    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>3</SplitHorizontal><TopRowBottomPane>3</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions>
  </Worksheet>`;
}

function buildV2ComparisonRows(currentRows, compareRows) {
  const compareMap = new Map(compareRows.map((row) => [String(row[PROVINCE_CODE_INDEX]), row]));
  const rows = currentRows.map((currentRow) => {
    const compareRow = compareMap.get(String(currentRow[PROVINCE_CODE_INDEX]));
    if (!compareRow) return null;
    return {
      code: currentRow[PROVINCE_CODE_INDEX],
      name: currentRow[PROVINCE_NAME_INDEX],
      metrics: [
        buildMetricComparison("F4.1 – PTC TMĐT", currentRow, compareRow, 5, 6),
        buildMetricComparison("F4.1 – PTC Truyền thống", currentRow, compareRow, 9, 10),
        buildMetricComparison("F3.3 – Thu gom", currentRow, compareRow, 13, 14),
        buildMetricComparison("📊 Trung bình", currentRow, compareRow, 15, 16),
        buildMetricComparison("F4.1 – Tỷ lệ PTC/ Nộp tiền", currentRow, compareRow, 40, null),
        buildMetricComparison("F4.1 – Tỷ lệ gửi PTC 8 giờ lần đầu (Có+Không TMS)", currentRow, compareRow, 41, null),
        buildMetricComparison("F4.1 – Tỷ lệ gửi PTC 8 giờ lần đầu (Có TMS)", currentRow, compareRow, 42, null),
        buildMetricComparison("F1.1 – Nội tỉnh", currentRow, compareRow, 24, 25),
        buildMetricComparison("F1.2 – Thu gom bưu gửi đi liên tỉnh", currentRow, compareRow, 29, 30),
        buildMetricComparison("F1.3 – Chất lượng phát bưu gửi liên tỉnh", currentRow, compareRow, 33, 34),
        buildMetricComparison("Tỷ lệ trung bình chung chất lượng (TGLT,PTC LT)", currentRow, compareRow, 35, 36)
      ]
    };
  }).filter(Boolean).sort((a, b) => compareNumberOrText(a.code, b.code));

  rows.forEach((row) => (row.totalUnits = rows.length));
  return rows;
}

function renderV2ComparisonWorksheet(rows, from, to, compareFrom, compareTo) {
  return `<Worksheet ss:Name="SoSanhV2">
    <Table>
      ${renderColumnWidths([46, 78, 190, 210, 90, 190, 90, 190])}
      <Row><Cell ss:MergeAcross="7" ss:StyleID="Title"><Data ss:Type="String">📊 TTVH – Kết quả xếp hạng BĐ Nghệ An (So sánh ${rows.length} đơn vị)</Data></Cell></Row>
      <Row><Cell ss:MergeAcross="7" ss:StyleID="Text"><Data ss:Type="String">Kỳ báo cáo: ${escapeXml(toApiDate(from))} - ${escapeXml(toApiDate(to))}</Data></Cell></Row>
      <Row><Cell ss:MergeAcross="7" ss:StyleID="Text"><Data ss:Type="String">So sánh với kỳ: ${escapeXml(toApiDate(compareFrom))} - ${escapeXml(toApiDate(compareTo))}</Data></Cell></Row>
      <Row>${["STT", "Mã", "Tên", "Chỉ số", "Tỷ lệ", "So sánh tỷ lệ", "Xếp hạng", "So sánh xếp hạng"].map((column) => excelXmlCell(column, "String", "Header")).join("")}</Row>
      ${rows.map(renderComparisonXmlRows).join("")}
    </Table>
    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>4</SplitHorizontal><TopRowBottomPane>4</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions>
  </Worksheet>`;
}

function renderDataWorksheet(sheetName, rows, title) {
  return `<Worksheet ss:Name="${escapeXml(sheetName)}">
    <Table>
      ${renderColumnWidths([46, 78, 190, 115, 150, 90, 70, 115, 150, 90, 70, 115, 150, 90, 70, 90, 70])}
      <Row><Cell ss:MergeAcross="16" ss:StyleID="Title"><Data ss:Type="String">${escapeXml(title)}</Data></Cell></Row>
      ${renderExcelXmlGroupHeaderRow(GROUP_HEADERS)}
      <Row>${EXCEL_COLUMNS.map((column) => excelXmlCell(column, "String", "Header")).join("")}</Row>
      ${rows.map((row) => `<Row>${row.map((cell, index) => excelXmlCell(cell, getExcelXmlDataType(cell, index), getExcelXmlStyle(index))).join("")}</Row>`).join("")}
    </Table>
    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>3</SplitHorizontal><TopRowBottomPane>3</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions>
  </Worksheet>`;
}

function renderComparisonWorksheet(rows, from, to, compareFrom, compareTo) {
  return `<Worksheet ss:Name="SoSanh">
    <Table>
      ${renderColumnWidths([46, 78, 190, 170, 90, 190, 90, 190])}
      <Row><Cell ss:MergeAcross="7" ss:StyleID="Title"><Data ss:Type="String">📊 TTVH – Kết quả xếp hạng BĐ Nghệ An (So sánh ${rows.length} đơn vị)</Data></Cell></Row>
      <Row><Cell ss:MergeAcross="7" ss:StyleID="Text"><Data ss:Type="String">Kỳ báo cáo: ${escapeXml(toApiDate(from))} - ${escapeXml(toApiDate(to))}</Data></Cell></Row>
      <Row><Cell ss:MergeAcross="7" ss:StyleID="Text"><Data ss:Type="String">So sánh với kỳ: ${escapeXml(toApiDate(compareFrom))} - ${escapeXml(toApiDate(compareTo))}</Data></Cell></Row>
      <Row>${["STT", "Mã tỉnh", "Tên tỉnh", "Chỉ số", "Tỷ lệ", "So sánh tỷ lệ", "Xếp hạng", "So sánh xếp hạng"].map((column) => excelXmlCell(column, "String", "Header")).join("")}</Row>
      ${rows.map(renderComparisonXmlRows).join("")}
    </Table>
    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>4</SplitHorizontal><TopRowBottomPane>4</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions>
  </Worksheet>`;
}

function renderComparisonXmlRows(row, index) {
  const totalUnits = row.totalUnits || row.metricsTotal || 36;
  return row.metrics
    .map((metric, metricIndex) => {
      return `<Row>
        ${excelXmlCell(index + 1, "Number", "Number")}
        ${excelXmlCell(row.code, "String", "Text")}
        ${excelXmlCell(row.name, "String", "Text")}
        ${excelXmlCell(metric.label, "String", "Group")}
        ${excelXmlCell(metric.currentRate, "Number", "Percent")}
        ${excelXmlCell(rateTrendText(metric, { plain: true }), "String", trendStyle(metric.rateDiffPoints))}
        ${excelXmlCell(formatRank(metric.currentRank, totalUnits), "String", "Text")}
        ${excelXmlCell(rankTrendText(metric, { plain: true }), "String", trendStyle(metric.rankDiff))}
      </Row>`;
    })
    .join("");
}

function renderColumnWidths(widths) {
  return widths.map((width) => `<Column ss:Width="${width}"/>`).join("");
}

function renderExcelXmlGroupHeaderRow(groups) {
  return `<Row>${groups.map(({ label, colspan }) => `<Cell ss:MergeAcross="${colspan - 1}" ss:StyleID="Group"><Data ss:Type="String">${escapeXml(label)}</Data></Cell>`).join("")}</Row>`;
}

function excelXmlCell(value, type = "String", styleId = "Cell") {
  const safeType = type === "Number" && Number.isFinite(Number(value)) ? "Number" : "String";
  const safeValue = safeType === "Number" ? String(Number(value)) : escapeXml(value);
  return `<Cell ss:StyleID="${styleId}"><Data ss:Type="${safeType}">${safeValue}</Data></Cell>`;
}

function getExcelXmlStyle(index) {
  if (isPercentColumn(index)) return "Percent";
  if (index === PROVINCE_NAME_INDEX) return "Text";
  return isNumberColumn(index) ? "Number" : "Text";
}

function getV2ExcelXmlStyle(index) {
  if (isV2PercentColumn(index)) return "Percent";
  if (index === PROVINCE_NAME_INDEX) return "Text";
  return isV2NumberColumn(index) ? "Number" : "Text";
}

function getExcelXmlDataType(cell, index) {
  if (isPercentColumn(index) || isNumberColumn(index)) return "Number";
  return "String";
}

function getV2ExcelXmlDataType(cell, index) {
  if (isV2PercentColumn(index) || isV2NumberColumn(index)) return "Number";
  return "String";
}

function excelXmlBorders() {
  return '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
}

function escapeXml(value) {
  return String(value ?? "")
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&apos;");
}

function saveBlobAsFile(blob, fileName) {
  const url = URL.createObjectURL(blob);
  const cleanup = () => setTimeout(() => URL.revokeObjectURL(url), 20_000);

  if (typeof chrome !== "undefined" && chrome.downloads && typeof chrome.downloads.download === "function") {
    chrome.downloads.download({ url, filename: fileName, saveAs: true }, cleanup);
    return;
  }

  const link = document.createElement("a");
  link.href = url;
  link.download = fileName;
  link.style.display = "none";
  document.body.appendChild(link);
  link.click();
  link.remove();
  cleanup();
}

function renderHeaderRows() {
  return `${renderGroupHeaderRow()}<tr>${EXCEL_COLUMNS.map((column) => `<th>${escapeHtml(column)}</th>`).join("")}</tr>`;
}

function renderExcelHeaderRows() {
  return `${renderGroupHeaderRow("group")}<tr>${EXCEL_COLUMNS.map((column) => `<th class="head">${escapeHtml(column)}</th>`).join("")}</tr>`;
}

function renderGroupHeaderRow(className = "") {
  return `<tr>${GROUP_HEADERS.map(({ label, colspan }) => `<th class="${className}" colspan="${colspan}">${escapeHtml(label)}</th>`).join("")}</tr>`;
}

function renderTemplateHeaderRows() {
  return `${renderTemplateGroupHeaderRow()}<tr>${TEMPLATE_EXCEL_COLUMNS.map((column) => `<th>${escapeHtml(column)}</th>`).join("")}</tr>`;
}

function renderTemplateExcelHeaderRows() {
  return `${renderTemplateGroupHeaderRow("group")}<tr>${TEMPLATE_EXCEL_COLUMNS.map((column) => `<th class="head">${escapeHtml(column)}</th>`).join("")}</tr>`;
}

function renderTemplateGroupHeaderRow(className = "") {
  return `<tr>${TEMPLATE_GROUP_HEADERS.map(({ label, colspan }) => `<th class="${className}" colspan="${colspan}">${escapeHtml(label)}</th>`).join("")}</tr>`;
}

function renderExcelCell(cell, index) {
  const className = isPercentColumn(index) ? "percent" : isNumberColumn(index) ? "number" : "";
  return `<td class="${className}">${escapeHtml(displayCell(cell))}</td>`;
}

function isNumberColumn(index) {
  return ![PROVINCE_NAME_INDEX, 5, 9, 13, 15].includes(index);
}

function isPercentColumn(index) {
  return [5, 9, 13, 15].includes(index);
}

function isV2PercentColumn(index) {
  return [5, 9, 13, 15, 19, 24, 29, 33, 35].includes(index);
}

function isV2NumberColumn(index) {
  return index !== PROVINCE_NAME_INDEX && !isV2PercentColumn(index);
}

function normalizeCell(value, type) {
  if (type === "percent") return normalizePercent(value);
  return cleanNumber(value);
}

function cleanNumber(value) {
  return String(value ?? "").replace(/,/g, "").trim();
}

function normalizePercent(value) {
  const raw = cleanNumber(value).replace("%", "");
  if (!raw) return "";
  const number = Number(raw);
  if (!Number.isFinite(number)) return raw;
  return number > 1 ? String(number / 100) : String(number);
}

function readWeights() {
  return {
    tmdt: Number(weightTmdtInput.value || 0),
    traditional: Number(weightTraditionalInput.value || 0),
    pickup: Number(weightPickupInput.value || 0)
  };
}

function zeroIfBlank(value) {
  const raw = cleanNumber(value);
  if (!raw) return "0";
  const number = Number(raw);
  return Number.isFinite(number) ? String(number) : "0";
}

function percentFrom(numerator, denominator) {
  const top = Number(zeroIfBlank(numerator));
  const bottom = Number(zeroIfBlank(denominator));
  if (!Number.isFinite(top) || !Number.isFinite(bottom) || bottom === 0) return "0";
  return String(top / bottom);
}

function weightedAveragePercent(values, weights) {
  const weightValues = [weights.tmdt, weights.traditional, weights.pickup];
  let totalWeight = 0;
  let totalScore = 0;

  values.forEach((value, index) => {
    const percent = Number(normalizePercent(value));
    const weight = Number(weightValues[index]);
    if (Number.isFinite(percent) && Number.isFinite(weight) && weight > 0) {
      totalScore += percent * weight;
      totalWeight += weight;
    }
  });

  return totalWeight ? String(totalScore / totalWeight) : "";
}

function buildProvinceComparisonRows(currentRows, compareRows) {
  const compareMap = new Map(compareRows.map((row) => [String(row[PROVINCE_CODE_INDEX]), row]));

  return currentRows
    .map((currentRow) => {
      const compareRow = compareMap.get(String(currentRow[PROVINCE_CODE_INDEX]));
      if (!compareRow) return null;
      return {
        code: currentRow[PROVINCE_CODE_INDEX],
        name: currentRow[PROVINCE_NAME_INDEX],
        metrics: [
          buildMetricComparison("F4.1 – PTC TMĐT", currentRow, compareRow, 5, 6),
          buildMetricComparison("F4.1 – PTC Truyền thống", currentRow, compareRow, 9, 10),
          buildMetricComparison("F3.3 – Thu gom", currentRow, compareRow, 13, 14),
          buildMetricComparison("📊 Trung bình", currentRow, compareRow, 15, 16)
        ]
      };
    })
    .filter(Boolean)
    .sort((a, b) => compareNumberOrText(a.code, b.code));
}

function buildMetricComparison(label, currentRow, compareRow, rateIndex, rankIndex) {
  const currentRate = Number(normalizePercent(currentRow[rateIndex]));
  const compareRate = Number(normalizePercent(compareRow[rateIndex]));
  const currentRank = rankIndex != null && currentRow[rankIndex] ? Number(currentRow[rankIndex]) : null;
  const compareRank = rankIndex != null && compareRow[rankIndex] ? Number(compareRow[rankIndex]) : null;
  return {
    label,
    currentRate,
    compareRate,
    rateDiffPoints: (currentRate - compareRate) * 100,
    currentRank,
    compareRank,
    rankDiff: (currentRank !== null && compareRank !== null) ? compareRank - currentRank : null
  };
}

function renderComparisonHeaderRows() {
  return `
    <tr>
      <th>STT</th><th>Mã tỉnh</th><th>Tên tỉnh</th><th>Chỉ số</th>
      <th>Tỷ lệ kỳ báo cáo</th><th>Tỷ lệ kỳ so sánh</th><th>Biến động tỷ lệ</th>
      <th>Hạng kỳ báo cáo</th><th>Hạng kỳ so sánh</th><th>Biến động hạng</th>
    </tr>`;
}

function renderComparisonPreviewRow(row, index) {
  return row.metrics
    .map((metric, metricIndex) => `
      <tr>
        ${metricIndex === 0 ? `<td rowspan="4">${index + 1}</td><td rowspan="4">${escapeHtml(row.code)}</td><td rowspan="4">${escapeHtml(row.name)}</td>` : ""}
        <td>${escapeHtml(metric.label)}</td>
        <td>${formatPercentValue(metric.currentRate)}</td>
        <td>${formatPercentValue(metric.compareRate)}</td>
        <td>${renderRateTrend(metric)}</td>
        <td>${formatRank(metric.currentRank, row.metrics.length ? row.metricsTotal : null)}</td>
        <td>${formatRank(metric.compareRank, row.metrics.length ? row.metricsTotal : null)}</td>
        <td>${renderRankTrend(metric)}</td>
      </tr>`)
    .join("");
}

function renderComparisonExcelHeaderRows() {
  return `
    <tr>
      <th>STT</th><th>Mã tỉnh</th><th>Tên tỉnh</th><th>Chỉ số</th>
      <th>Tỷ lệ</th><th>So sánh tỷ lệ</th><th>Xếp hạng</th><th>So sánh xếp hạng</th>
    </tr>`;
}

function renderComparisonExcelRow(row, index) {
  const totalUnits = "36";
  return row.metrics
    .map((metric, metricIndex) => `
      <tr>
        ${metricIndex === 0 ? `<td rowspan="4" class="number">${index + 1}</td><td rowspan="4">${escapeHtml(row.code)}</td><td rowspan="4">${escapeHtml(row.name)}</td>` : ""}
        <td class="metric">🔹 ${escapeHtml(metric.label)}</td>
        <td class="percent">${formatPercentValue(metric.currentRate)}</td>
        <td>${renderRateTrend(metric)}</td>
        <td>${formatRank(metric.currentRank, totalUnits)}</td>
        <td>${renderRankTrend(metric)}</td>
      </tr>`)
    .join("");
}

function renderRateTrend(metric) {
  return `<span class="${trendCssClass(metric.rateDiffPoints)}">${escapeHtml(rateTrendText(metric))}</span>`;
}

function renderRankTrend(metric) {
  return `<span class="${trendCssClass(metric.rankDiff)}">${escapeHtml(rankTrendText(metric))}</span>`;
}

function rateTrendText(metric, options = {}) {
  const diff = metric.rateDiffPoints;
  const direction = getTrendDirection(diff);
  const verb = direction > 0 ? "tăng" : direction < 0 ? "giảm" : "không đổi";
  const icon = options.plain ? "" : direction > 0 ? "🟩▲ " : direction < 0 ? "🟥▼ " : "➖ ";
  const connector = direction > 0 ? "lên" : direction < 0 ? "xuống" : "đến";
  return `${icon}${verb} ${formatPointDiff(Math.abs(diff))}, từ ${formatPercentValue(metric.compareRate)} ${connector} ${formatPercentValue(metric.currentRate)}`;
}

function rankTrendText(metric, options = {}) {
  const diff = metric.rankDiff;
  const direction = getTrendDirection(diff);
  const verb = direction > 0 ? "tăng" : direction < 0 ? "giảm" : "không đổi";
  const icon = options.plain ? "" : direction > 0 ? "🟩▲ " : direction < 0 ? "🟥▼ " : "➖ ";
  const rankText = direction === 0 ? "0 bậc" : `${Math.abs(diff)} bậc`;
  return `${icon}${verb} ${rankText}, hạng ${formatRankNumber(metric.compareRank)} -> ${formatRankNumber(metric.currentRank)}`;
}

function trendCssClass(diff) {
  const direction = getTrendDirection(diff);
  return direction > 0 ? "up" : direction < 0 ? "down" : "same";
}

function trendStyle(diff) {
  const direction = getTrendDirection(diff);
  return direction > 0 ? "Up" : direction < 0 ? "Down" : "Same";
}

function getTrendDirection(diff) {
  if (!Number.isFinite(diff) || Math.abs(diff) < 0.005) return 0;
  return diff > 0 ? 1 : -1;
}

function formatPercentValue(value) {
  return Number.isFinite(value) ? `${(value * 100).toLocaleString("vi-VN", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%` : "";
}

function formatPointDiff(value) {
  return `${value.toLocaleString("vi-VN", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} điểm %`;
}

function formatRank(rank, totalUnits = "36") {
  return Number.isFinite(rank) ? `${rank} / ${totalUnits || "36"}` : "";
}

function formatRankNumber(rank) {
  return Number.isFinite(rank) ? rank : "";
}

function assignRankByColumn(rows, valueIndex, rankIndex) {
  const sorted = [...rows]
    .filter((row) => Number.isFinite(Number(row[valueIndex])) && Number(row[valueIndex]) > 0)
    .sort((a, b) => Number(b[valueIndex]) - Number(a[valueIndex]) || compareNumberOrText(a[PROVINCE_CODE_INDEX], b[PROVINCE_CODE_INDEX]));

  rows.forEach((row) => {
    row[rankIndex] = "";
  });

  sorted.forEach((row, index) => {
    row[rankIndex] = String(index + 1);
  });
}

function isPercentLike(value) {
  const raw = cleanNumber(value).replace("%", "");
  const number = Number(raw);
  return Number.isFinite(number) && number >= 0 && number <= 1;
}

function compareNumberOrText(a, b) {
  const numberA = Number(a);
  const numberB = Number(b);
  if (Number.isFinite(numberA) && Number.isFinite(numberB)) return numberA - numberB;
  return String(a).localeCompare(String(b), "vi");
}

function displayCell(value) {
  return String(value ?? "");
}

function toInputDate(date) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function toFileDate(date) {
  return toInputDate(date);
}

function parseControlDate(value) {
  const match = String(value ?? "").trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) return null;

  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);
  const date = new Date(year, month - 1, day);

  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
  return date;
}

function toDisplayDate(date) {
  return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;
}

function syncAllDateDisplays() {
  [fromDateInput, toDateInput, nextFromDateInput, nextToDateInput].forEach(syncDateDisplay);
}

function syncDateDisplay(input) {
  const displayInput = dateDisplayMap.get(input);
  if (!displayInput) return;

  const date = parseControlDate(input.value);
  displayInput.value = date ? toDisplayDate(date) : "";
}

function syncNativeDateFromDisplay(displayInput, options = {}) {
  const nativeInput = displayDateMap.get(displayInput);
  if (!nativeInput) return false;

  const raw = cleanText(displayInput.value);
  if (!raw) {
    nativeInput.value = "";
    return false;
  }

  const date = parseDisplayDate(raw);
  if (!date) {
    if (options.showError) setStatus("Ngày nhập chưa đúng định dạng dd/MM/yyyy.", "error");
    return false;
  }

  nativeInput.value = toInputDate(date);
  if (options.normalize) displayInput.value = toDisplayDate(date);
  return true;
}

function parseDisplayDate(value) {
  const match = String(value ?? "").trim().match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/);
  if (!match) return null;

  const day = Number(match[1]);
  const month = Number(match[2]);
  const year = Number(match[3]);
  const date = new Date(year, month - 1, day);

  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
  return date;
}

function toApiDate(date) {
  return `${pad(date.getMonth() + 1)}/${pad(date.getDate())}/${date.getFullYear()}`;
}

function addDays(date, days) {
  const result = new Date(date);
  result.setDate(result.getDate() + days);
  return result;
}

function validateRequiredDates(...dates) {
  if (dates.every(Boolean)) return true;
  setStatus("Vui lòng chọn ngày hợp lệ.", "error");
  return false;
}

function validatePeriodDates(from, to, nextFrom, nextTo) {
  if (from > to) {
    setStatus("Ngày bắt đầu của Kỳ báo cáo không được lớn hơn ngày kết thúc.", "error");
    return false;
  }

  if (nextFrom > nextTo) {
    setStatus("Ngày bắt đầu của Kỳ so sánh không được lớn hơn ngày kết thúc.", "error");
    return false;
  }

  return true;
}

function pad(value) {
  return String(value).padStart(2, "0");
}

function cleanText(value) {
  return String(value ?? "").replace(/\s+/g, " ").trim();
}

function pickReportCodeText(cells, config, tuyChonGR) {
  const codeIndex = getReportCodeIndex(cells, config, tuyChonGR);
  if (Number.isInteger(toIntegerCode(cells[codeIndex]))) return cells[codeIndex];
  return cells.find((cell, index) => index > codeIndex && Number.isInteger(toIntegerCode(cell))) || "";
}

function isGenericPrefix(name) {
  return ["BCP", "BC", "BDH", "BĐH", "TTVH"].includes(name.trim().toUpperCase());
}

function extractRealName(cells, nameIndex) {
  let name = cells[nameIndex];
  if (name && !Number.isInteger(toIntegerCode(name))) {
    if (isGenericPrefix(name) && cells[nameIndex + 1]) {
      const nextCell = cells[nameIndex + 1].trim();
      if (nextCell.toUpperCase().startsWith(name.trim().toUpperCase())) return nextCell;
      return name.trim() + " " + nextCell;
    }
    return name;
  }
  return null;
}

function pickReportName(cells, config, tuyChonGR) {
  const codeIndex = getReportCodeIndex(cells, config, tuyChonGR);
  const nameIndex = Number.isInteger(config.nameIndex) ? config.nameIndex : codeIndex + 1;
  const extracted = extractRealName(cells, nameIndex);
  if (extracted) return extracted;
  return cells.find((cell, index) => index > codeIndex && /[A-Za-zÀ-ỹ]/.test(cell) && !isPercentLike(cell)) || "";
}

function getReportCodeIndex(cells, config, tuyChonGR) {
  if (Number.isInteger(config.codeIndex)) return config.codeIndex;
  if (tuyChonGR === "BC") return (config.key === "thuGomLienTinh" || config.key === "thuGomLienTinhF12") ? 3 : 5;
  return getProvinceCodeIndex(cells);
}

function pickPostOfficeCodeText(cells, config) {
  const codeIndex = getPostOfficeCodeIndex(cells, config);
  if (Number.isInteger(toIntegerCode(cells[codeIndex]))) return cells[codeIndex];
  return cells.find((cell, index) => index > codeIndex && Number.isInteger(toIntegerCode(cell))) || "";
}

function pickPostOfficeName(cells, config) {
  const codeIndex = getPostOfficeCodeIndex(cells, config);
  const nameIndex = codeIndex + 1;
  const extracted = extractRealName(cells, nameIndex);
  if (extracted) return extracted;
  return cells.find((cell, index) => index > codeIndex && /[A-Za-zÀ-ỹ]/.test(cell) && !isPercentLike(cell)) || "";
}

function getPostOfficeCodeIndex(cells, config) {
  return (config.key === "thuGomLienTinh" || config.key === "thuGomLienTinhF12") ? 3 : 5;
}

function pickProvinceCodeText(cells) {
  if (Number.isInteger(toIntegerCode(cells[1]))) return cells[1];
  if (Number.isInteger(toIntegerCode(cells[2]))) return cells[2];
  if (Number.isInteger(toIntegerCode(cells[3]))) return cells[3];
  return cells.find((cell, index) => index > 0 && Number.isInteger(toIntegerCode(cell))) || "";
}

function pickProvinceName(cells) {
  const codeIndex = getProvinceCodeIndex(cells);
  if (codeIndex >= 0 && cells[codeIndex + 1] && !Number.isInteger(toIntegerCode(cells[codeIndex + 1]))) {
    return cells[codeIndex + 1];
  }
  return cells.find((cell, index) => index > 0 && /[A-Za-zÀ-ỹ]/.test(cell) && !isPercentLike(cell)) || "";
}

function pickDataValuesAfterProvince(cells) {
  const codeIndex = getProvinceCodeIndex(cells);
  const startIndex = codeIndex >= 0 ? codeIndex + 2 : 3;
  return cells.slice(startIndex).filter((cell) => isNumericLike(cell) && !isPercentLike(cell));
}

function getProvinceCodeIndex(cells) {
  if (Number.isInteger(toIntegerCode(cells[1]))) return 1;
  if (Number.isInteger(toIntegerCode(cells[2]))) return 2;
  if (Number.isInteger(toIntegerCode(cells[3]))) return 3;
  return cells.findIndex((cell, index) => index > 0 && Number.isInteger(toIntegerCode(cell)));
}

function toIntegerCode(value) {
  const raw = String(value ?? "").trim();
  if (!/^\d+$/.test(raw)) return NaN;
  return Number.parseInt(raw, 10);
}

function isNumericLike(value) {
  const raw = cleanNumber(value);
  return raw !== "" && Number.isFinite(Number(raw));
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function setStatus(message, type = "") {
  statusBox.textContent = message;
  statusBox.className = `status ${type}`.trim();
}

function setBusy(isBusy) {
  allActionButtons.forEach((button) => (button.disabled = isBusy));
}

// Helper: read selected province from UI
function getSelectedProvince() {
  const el = document.getElementById('province-select');
  return el ? el.value : '46';
}

// ==========================
// JSONBIN CLOUD INTEGRATION
// ==========================
async function pushToCloud() {
  if (!currentReportPayload) {
    setStatus("Chưa có dữ liệu báo cáo để đẩy. Hãy soạn tin nhắn trước.", "error");
    return;
  }

  const binId = jsonbinIdInput?.value?.trim();
  const apiKey = jsonbinKeyInput?.value?.trim();

  if (!binId || !apiKey) {
    setStatus("Vui lòng cấu hình Bin ID và API Key trong phần Thiết lập tham số.", "error");
    return;
  }

  const text = reportText.textContent;
  let finalParsedData = currentReportPayload;
  
  // Parse trực tiếp từ text giống hệt logic của Dashboard để đảm bảo tương thích 100%
  try {
    const data = { header: {}, overall: {}, sections: [] };
    const kyBaoCaoMatch = text.match(/Kỳ báo cáo:\s*(.*)/);
    const soSanhMatch = text.match(/So sánh với kỳ:\s*(.*)/);
    if (kyBaoCaoMatch) data.header.kyBaoCao = kyBaoCaoMatch[1].trim();
    if (soSanhMatch) data.header.soSanh = soSanhMatch[1].trim();

    const parts = text.split('🔹').map(p => p.trim()).filter(p => p);
    parts.forEach(part => {
        const lines = part.split('\n').map(l => l.trim()).filter(l => l);
        const title = lines[0];

        if (title.toLowerCase().includes('tỷ lệ trung bình chung')) {
            const tlMatch = part.match(/Tỷ lệ bình quân:\s*([\d,]+%)\s*\((.*?)\)/);
            const xhMatch = part.match(/Xếp hạng:\s*([\d]+)\s*\/\s*([\d]+)/); 
            if (tlMatch) {
                data.overall.score = tlMatch[1];
                data.overall.trend = tlMatch[2];
            }
            if (xhMatch) {
                data.overall.rank = `${xhMatch[1]}/${xhMatch[2]}`;
            } else {
                const xhLine = lines.find(l => l.startsWith('Xếp hạng:'));
                if (xhLine) {
                    const num = xhLine.replace(/[^\d/]/g, '');
                    if(num) data.overall.rank = num;
                }
            }
        } else {
            const section = { title: title, tyLe: '', tyLeTrend: '', xepHang: '', xepHangTrend: '', bcpList: [] };
            const tlMatch = part.match(/Tỷ lệ:\s*([\d,]+%)\s*\((.*?)\)/);
            if (tlMatch) {
                section.tyLe = tlMatch[1];
                section.tyLeTrend = tlMatch[2];
            }
            const xhMatch = part.match(/Xếp hạng:\s*([\d]+)\s*\/\s*([\d]+)\s*\((.*?)\)/);
            if (xhMatch) {
                section.xepHang = `${xhMatch[1]}/${xhMatch[2]}`;
                section.xepHangTrend = xhMatch[3];
            }
            let inBcpList = false;
            for (let i = 1; i < lines.length; i++) {
                const line = lines[i];
                if (line.includes('BCP có tỷ lệ thấp')) {
                    inBcpList = true;
                    continue;
                }
                if (inBcpList || line.startsWith('BCP')) {
                    const bcpMatch = line.match(/(BCP.*?):\s*([\d,]+%)/);
                    if (bcpMatch) {
                        section.bcpList.push({
                            name: bcpMatch[1].replace('BCP ', ''),
                            value: parseFloat(bcpMatch[2].replace(',', '.'))
                        });
                    }
                }
            }
            if (section.tyLe || section.bcpList.length > 0) {
                data.sections.push(section);
            }
        }
    });
    finalParsedData = data;
  } catch (e) {
    console.error("Lỗi khi parse text:", e);
  }

  const payload = {
    rawText: text,
    parsedData: finalParsedData
  };

  const originalText = pushCloudBtn.innerHTML;
  pushCloudBtn.innerHTML = "⏳ Đang đẩy...";
  pushCloudBtn.disabled = true;
  setStatus("Đang đẩy dữ liệu lên JSONBin...");

  try {
    const response = await fetch(`https://api.jsonbin.io/v3/b/${binId}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        "X-Master-Key": apiKey
      },
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Lỗi JSONBin (${response.status}): ${errorText}`);
    }

    pushCloudBtn.innerHTML = "✅ Đã đẩy Cloud";
    setStatus("Đã đồng bộ báo cáo lên Cloud thành công!", "ok");
  } catch (error) {
    console.error("Lỗi đẩy cloud:", error);
    pushCloudBtn.innerHTML = "❌ Lỗi đẩy Cloud";
    setStatus(error.message, "error");
  } finally {
    setTimeout(() => {
      pushCloudBtn.innerHTML = originalText;
      pushCloudBtn.disabled = false;
    }, 3000);
  }
}

// ==========================
// GOOGLE SHEETS INTEGRATION
// ==========================

const testSheetsBtn = document.getElementById("test-sheets-btn");
if (testSheetsBtn) {
  testSheetsBtn.addEventListener("click", async () => {
    const sheetsUrl = document.getElementById("sheets-url")?.value?.trim();
    if (!sheetsUrl) {
      setStatus("Vui lòng nhập Google Apps Script URL.", "error");
      return;
    }
    const originalText = testSheetsBtn.innerHTML;
    testSheetsBtn.innerHTML = "⏳ Đang test...";
    testSheetsBtn.disabled = true;
    try {
      const dummyData = [
        { "TT": "1", "MaTinh": "46", "TenTinh": "BĐ Nghệ An", "TenKHL": "Khách hàng Test 01" },
        { "TT": "2", "MaTinh": "46", "TenTinh": "BĐ Nghệ An", "TenKHL": "Kết nối thành công!" }
      ];
      const response = await fetch(sheetsUrl, {
        method: "POST",
        headers: { "Content-Type": "text/plain;charset=utf-8" },
        body: JSON.stringify(dummyData)
      });
      if (!response.ok) throw new Error("HTTP error " + response.status);
      const resData = await response.json();
      if (resData.status === "success") {
        testSheetsBtn.innerHTML = "✅ Test OK!";
        setStatus("Kết nối Google Sheets thành công! Dữ liệu mẫu đã được ghi vào Sheet.", "ok");
      } else {
        throw new Error(resData.message || "Lỗi Apps Script");
      }
    } catch (e) {
      console.error(e);
      testSheetsBtn.innerHTML = "❌ Lỗi";
      setStatus("Lỗi kết nối Sheets: " + e.message, "error");
    } finally {
      setTimeout(() => {
        testSheetsBtn.innerHTML = originalText;
        testSheetsBtn.disabled = false;
      }, 3000);
    }
  });
}
async function pushQuanLyKhachHangToSheets(tuyChonGR) {
  const sheetsUrl = document.getElementById("sheets-url")?.value?.trim();
  if (!sheetsUrl) {
    setStatus("Vui lòng nhập Google Apps Script URL trong phần cấu hình.", "error");
    return;
  }

  const from = parseControlDate(fromDateInput.value);
  const to = parseControlDate(toDateInput.value);

  if (!from || !to) {
    setStatus("Vui lòng chọn Từ ngày và Đến ngày của kỳ báo cáo.", "error");
    return;
  }
  if (from > to) {
    setStatus("Từ ngày không được lớn hơn Đến ngày.", "error");
    return;
  }

  setBusy(true);
  setStatus(`Đang truy xuất dữ liệu KH Chi tiết (${tuyChonGR}) từ máy chủ...`);

  const pushKhSheetsBtn = document.getElementById("push-kh-sheets");
  const originalText = pushKhSheetsBtn.innerHTML;
  pushKhSheetsBtn.innerHTML = "⏳ Đang xử lý...";
  pushKhSheetsBtn.disabled = true;

  try {
    const fromStr = toApiDate(from);
    const toStr = toApiDate(to);
    const selectedProv = getSelectedProvince();

    const params = {
      TuyChonGR: tuyChonGR,
      stMaHuyenChapNhan: "",
      stMaTinhChapNhan: tuyChonGR === "BC" ? selectedProv : selectedProv,
      stMaBuuCucChapNhan: tuyChonGR === "BC" ? "ALL" : "NULL",
      stLoaiDichVu: "ALL",
      iFrom: fromStr,
      iTo: toStr
    };

    const requestUrl = buildApiRequestUrl("quanLyKhachHang", params);
    const records = await executeFetchViaContentScript(requestUrl);

    if (!records || records.length === 0) {
      throw new Error("Không có dữ liệu chi tiết nào được tìm thấy.");
    }

    const validRecords = records.filter(row => row.length > 5 && row[0].trim() !== "");

    // Map to array of objects
    const keys = [
      "TT", "MaTinh", "TenTinh", "MaBC", "TenBC", "MaCMR", "MaCRM",
      "TenKHL", "SoHieuBG", "DichVu", "LoaiDichVu", "MaSPDV", "TenSPDV",
      "CuocThucThu", "ThoiGianChapNhan", "KhoiLuong"
    ];

    const dataPayload = validRecords.map(row => {
      let obj = {};
      keys.forEach((k, i) => {
        obj[k] = row[i] ? row[i].trim() : "";
      });
      return obj;
    });

    setStatus(`Đã tải xong ${validRecords.length} dòng! Đang đẩy lên Google Sheets...`);

    const response = await fetch(sheetsUrl, {
      method: "POST",
      headers: {
        "Content-Type": "text/plain;charset=utf-8"
      },
      body: JSON.stringify(dataPayload)
    });

    if (!response.ok) {
      throw new Error(`Lỗi HTTP: ${response.status}`);
    }

    const responseData = await response.json();
    if (responseData.status === "success") {
      pushKhSheetsBtn.innerHTML = "✅ Đã đẩy Sheets";
      setStatus(`Đã đẩy thành công ${validRecords.length} dòng lên Google Sheets!`, "ok");
    } else {
      throw new Error(responseData.message || "Lỗi từ Google Apps Script.");
    }
  } catch (error) {
    console.error(error);
    pushKhSheetsBtn.innerHTML = "❌ Lỗi đẩy Sheets";
    setStatus(error.message || "Có lỗi khi xử lý dữ liệu đẩy lên Sheets.", "error");
  } finally {
    setBusy(false);
    setTimeout(() => {
      pushKhSheetsBtn.innerHTML = originalText;
      pushKhSheetsBtn.disabled = false;
    }, 3000);
  }
}
