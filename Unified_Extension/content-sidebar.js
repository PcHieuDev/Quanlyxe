const DKCL_SIDEBAR_ID = "dkcl-report-extension-sidebar";
const DKCL_TOGGLE_ID = "dkcl-report-extension-toggle";
const DKCL_PIN_STORAGE_KEY = "dkclSidebarPinned";

function mountDkclSidebar() {
  if (document.getElementById(DKCL_SIDEBAR_ID)) return;

  const sidebar = document.createElement("aside");
  sidebar.id = DKCL_SIDEBAR_ID;
  sidebar.style.cssText = [
    "position: fixed",
    "bottom: 0",
    "left: 0",
    "width: 100vw",
    "height: 380px",
    "z-index: 2147483000",
    "background: #09346d",
    "box-shadow: 0 -16px 48px rgba(2, 6, 23, 0.28)",
    "border-top: 1px solid rgba(255,255,255,0.18)",
    "transition: transform 220ms ease, opacity 220ms ease",
    "overflow: hidden",
    `transform: ${localStorage.getItem(DKCL_PIN_STORAGE_KEY) === "true" ? "translateY(0)" : "translateY(100%)"}`,
    "opacity: 0.98"
  ].join(";");

  const iframe = document.createElement("iframe");
  iframe.title = "Báo cáo BĐ Nghệ An";
  iframe.src = chrome.runtime.getURL("popup.html?embedded=1");
  iframe.allow = "clipboard-write";
  iframe.style.cssText = [
    "width: 100%",
    "height: 100%",
    "border: 0",
    "display: block",
    "background: transparent"
  ].join(";");

  const toggle = document.createElement("button");
  toggle.id = DKCL_TOGGLE_ID;
  toggle.type = "button";
  toggle.textContent = "DKCL";
  toggle.title = "Ẩn/hiện DKCL Sidebar";
  toggle.style.cssText = [
    "position: fixed",
    "bottom: 12px",
    "right: 12px",
    "z-index: 2147483001",
    "height: 42px",
    "padding: 0 16px",
    "border: 0",
    "border-radius: 999px",
    "background: #fccf20",
    "color: #09346d",
    "font: 700 13px Tahoma, sans-serif",
    "letter-spacing: .02em",
    "box-shadow: 0 6px 16px rgba(0,0,0,.22)",
    "cursor: pointer",
    "transition: bottom 220ms ease, transform 220ms ease"
  ].join(";");

  let collapsed = localStorage.getItem(DKCL_PIN_STORAGE_KEY) !== "true";
  let pinned = localStorage.getItem(DKCL_PIN_STORAGE_KEY) === "true";
  toggle.style.bottom = collapsed ? "12px" : "392px";
  toggle.textContent = pinned ? "Đã ghim" : collapsed ? "DKCL Báo cáo" : "Ẩn";

  toggle.addEventListener("click", () => {
    if (pinned) return;
    collapsed = !collapsed;
    sidebar.style.transform = collapsed ? "translateY(100%)" : "translateY(0)";
    toggle.style.bottom = collapsed ? "12px" : "392px";
    toggle.textContent = collapsed ? "DKCL Báo cáo" : "Ẩn";
  });

  window.addEventListener("message", (event) => {
    if (event.source !== iframe.contentWindow || event.data?.source !== "dkcl-report-popup") return;

    if (event.data?.type === "pin-state") {
      pinned = Boolean(event.data.pinned);
      localStorage.setItem(DKCL_PIN_STORAGE_KEY, String(pinned));
      collapsed = false;
      sidebar.style.transform = "translateY(0)";
      toggle.style.bottom = "392px";
      toggle.textContent = pinned ? "Bỏ ghim" : "Ẩn";
      toggle.title = pinned ? "Giao diện DKCL đang được ghim" : "Ẩn/hiện DKCL Sidebar";
    }

    if (event.data?.type === "fetch-summary") {
      (async () => {
        try {
          const req = event.data.request;
          const sumResp = await fetch(req.summaryUrl, { headers: { "X-Requested-With": "XMLHttpRequest" } });
          const sumJson = await sumResp.json();
          const dom = new DOMParser().parseFromString(`<table><tbody>${sumJson.data}</tbody></table>`, "text/html");
          const rows = [...dom.querySelectorAll("tr")].filter(tr => tr.children.length > 2 && !tr.classList.contains("tr_tong"));
          const records = rows.map(tr => [...tr.children].map(td => td.textContent.trim()));
          event.source.postMessage({
            source: "dkcl-content-script", type: "fetch-summary-response", id: event.data.id, result: { data: records }
          }, "*");
        } catch(e) {
          event.source.postMessage({
            source: "dkcl-content-script", type: "fetch-summary-response", id: event.data.id, result: { error: e.message }
          }, "*");
        }
      })();
    }

    if (event.data?.type === "fetch-detail") {
      (async () => {
        try {
          const req = event.data.request;
          const sumResp = await fetch(req.summaryUrl, { headers: { "X-Requested-With": "XMLHttpRequest" } });
          const sumJson = await sumResp.json();
          const dom = new DOMParser().parseFromString(`<table><tbody>${sumJson.data}</tbody></table>`, "text/html");
          const tongquanRow = dom.querySelector("tr.tongquan_params");
          if (!tongquanRow) throw new Error("Không tìm thấy params chi tiết.");
          
          const detailUrl = tongquanRow.getAttribute("data-url");
          let detailParams = tongquanRow.getAttribute("data-params");
          
          // Lấy name_store từ tr
          const nameStore = tongquanRow.getAttribute("data-store");
          
          // Lấy tổng số thực tế để tránh lỗi treo máy chủ khi truyền số quá to
          // Tìm giá trị lớn nhất trong các cột data-detail (Sản lượng thường lớn hơn số KH)
          let exactTotal = "2000"; // fallback
          const detailCells = [...dom.querySelectorAll("td[data-detail]")];
          if (detailCells.length > 0) {
            const maxVal = Math.max(...detailCells.map(td => Number(td.textContent.replace(/,/g, '').trim()) || 0));
            if (maxVal > 0) exactTotal = String(maxVal);
          }

          // Xóa bỏ định dạng N'...' của SQL Server vì Laravel GET API không chấp nhận
          if (detailParams) {
            detailParams = detailParams.replace(/N'([^']*)'/g, '$1');
          }
          
          // Hệ thống bắt buộc phải có name_store, iDetailReport (1 = Khách hàng phát sinh), iTotal, iPage, iPageSize
          const additionalParams = `&name_store=${nameStore}&iDetailReport=1&iTotal=${exactTotal}&iPage=1&iPageSize=${exactTotal}`;
          const detailFullUrl = detailUrl + (detailUrl.includes("?") ? "&" : "?") + detailParams + additionalParams;
          
          const detailResp = await fetch(detailFullUrl, {
            method: "GET",
            headers: {
              "X-Requested-With": "XMLHttpRequest"
            }
          });
          
          const textResponse = await detailResp.text();
          let detailJson;
          try {
            detailJson = JSON.parse(textResponse);
          } catch (e) {
            throw new Error(`Phản hồi không phải JSON. Phản hồi: ${textResponse.substring(0, 150)}`);
          }
          
          if (!detailJson || !detailJson.data) {
             throw new Error(`Dữ liệu JSON không có 'data'. Nội dung: ${textResponse.substring(0, 150)}`);
          }
          
          const detailDom = new DOMParser().parseFromString(`<table><tbody>${detailJson.data}</tbody></table>`, "text/html");
          const rows = [...detailDom.querySelectorAll("tr")];
          const records = rows.map(tr => [...tr.querySelectorAll("td")].map(td => td.textContent.trim()));
          
          event.source.postMessage({
            source: "dkcl-content-script", type: "fetch-detail-response", id: event.data.id, result: { data: records }
          }, "*");
        } catch (e) {
          event.source.postMessage({
            source: "dkcl-content-script", type: "fetch-detail-response", id: event.data.id, result: { error: e.message }
          }, "*");
        }
      })();
    }
  });

  sidebar.appendChild(iframe);
  document.documentElement.appendChild(sidebar);
  document.documentElement.appendChild(toggle);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", mountDkclSidebar, { once: true });
} else {
  mountDkclSidebar();
}

// DKCL có thể đổi route sau đăng nhập không reload full page, nên kiểm tra lại vài lần.
let retryCount = 0;
const retryTimer = setInterval(() => {
  mountDkclSidebar();
  retryCount += 1;
  if (retryCount >= 10) clearInterval(retryTimer);
}, 1000);
