// Background Service Worker for Chrome Extension
let API_URL = 'https://enterprise-license-panel-production.up.railway.app';

chrome.storage.local.get(['api_base_url'], (res) => {
    if (res.api_base_url) API_URL = res.api_base_url;
});


chrome.runtime.onInstalled.addListener(() => {
    console.log('[ELMS] Enterprise License Extension installed.');
    // Set periodic status re-validation alarm (every 60 mins)
    chrome.alarms.create('elms_license_heartbeat', { periodInMinutes: 60 });
});

chrome.alarms.onAlarm.addListener(async (alarm) => {
    if (alarm.name === 'elms_license_heartbeat') {
        verifyActiveLicense();
    }
});

async function verifyActiveLicense() {
    chrome.storage.local.get(['elms_license', 'device_fingerprint'], async (data) => {
        const license = data.elms_license;
        const fingerprint = data.device_fingerprint;

        if (!license || !license.license_key) return;

        try {
            const res = await fetch(`${API_URL}/api/v1/license/check`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    license_key: license.license_key,
                    product_id: 1
                })
            });
            const result = await res.json();
            if (!result.success || !result.data.valid) {
                console.warn('[ELMS] License check failed/invalid. Invalidating cached session.');
                chrome.storage.local.remove(['elms_license']);
            } else {
                console.log('[ELMS] License verified successfully in background.');
            }
        } catch (err) {
            console.error('[ELMS] Background license check error:', err);
        }
    });
}
