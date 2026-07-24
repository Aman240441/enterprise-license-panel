# Chrome Extension Integration Deliverable

This directory contains the ready-to-load **Chrome Extension Manifest V3** client for the Enterprise License Management System.

---

## 🛠️ How to Load in Chrome

1. Open Google Chrome.
2. Navigate to `chrome://extensions/`.
3. Enable **Developer mode** (toggle in the top-right corner).
4. Click **Load unpacked**.
5. Select this folder: `c:\Users\Akaria Innovations\OneDrive\Desktop\aDMIN PANEL\chrome_extension`.

---

## ⚡ Features Included

- **License Activation**: Input key (`GB-UZ57-77Y5-MKX8-B6YT` or generated key), activate against `/api/v1/license/activate`.
- **Local Caching**: Stores session token, key, and device fingerprint in `chrome.storage.local`.
- **Periodic Background Heartbeat**: Background service worker periodically validates license status via `/api/v1/license/check`.
- **Upload Authorization**: Sends request to `/api/v1/upload-authorize` passing `x-license-key`, `x-session-id`, `x-device-id` headers for Supabase / storage authorization.
