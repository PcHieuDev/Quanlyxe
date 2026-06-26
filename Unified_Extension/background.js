const DKCL_ORIGIN = "https://dkcl.vnpost.vn/";
const SIDEBAR_PATH = "popup.html";

chrome.runtime.onInstalled.addListener(async () => {
  await enableActionClickSidebar();
  await openSidebarForExistingDkclTabs();
});

chrome.runtime.onStartup.addListener(async () => {
  await enableActionClickSidebar();
  await openSidebarForExistingDkclTabs();
});

chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  const nextUrl = changeInfo.url || tab.url || "";
  if (isDkclPage(nextUrl) && (changeInfo.status === "complete" || changeInfo.url)) {
    openSidebar(tabId, tab.windowId);
  }
});

chrome.tabs.onActivated.addListener(async ({ tabId }) => {
  try {
    const tab = await chrome.tabs.get(tabId);
    if (isDkclPage(tab.url)) {
      openSidebar(tab.id, tab.windowId);
    }
  } catch (error) {
    console.warn("Không đọc được tab hiện tại:", error);
  }
});

async function enableActionClickSidebar() {
  await chrome.sidePanel.setPanelBehavior({ openPanelOnActionClick: true });
}

async function openSidebarForExistingDkclTabs() {
  const tabs = await chrome.tabs.query({ url: `${DKCL_ORIGIN}*` });
  tabs.forEach((tab) => openSidebar(tab.id, tab.windowId));
}

function isDkclPage(url = "") {
  return url.startsWith(DKCL_ORIGIN);
}

async function openSidebar(tabId, windowId) {
  if (!tabId || !windowId) return;

  try {
    await chrome.sidePanel.setOptions({ tabId, path: SIDEBAR_PATH, enabled: true });
    await chrome.sidePanel.open({ windowId });
  } catch (error) {
    console.warn("Không thể tự mở DKCL sidebar:", error);
  }
}

// Lắng nghe message từ content script để gọi API (Vượt rào CORS của trình duyệt)
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'sync_to_google_sheet') {
    const googleSheetUrl = 'https://script.google.com/macros/s/AKfycbwoBZZFON_3u0V8b9Pak8_vvK8lE99haAOD1X533PfTyf_TN5jTywNDD38ANtAY8L_L/exec';
    
    fetch(googleSheetUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'text/plain;charset=utf-8' },
      body: JSON.stringify({ month: request.month, year: request.year, data: request.data })
    })
    .then(res => res.json())
    .then(data => sendResponse(data))
    .catch(err => {
      console.error("Fetch error:", err);
      sendResponse({ success: false, error: err.toString() });
    });

    return true; // Phải return true để báo hiệu sẽ gọi sendResponse bất đồng bộ (sau khi fetch xong)
  }
});
