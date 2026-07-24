// Content Script injected into web pages to expose license verification helper
console.log('[ELMS] License Extension Content Script Active');

window.addEventListener('message', async (event) => {
    if (event.source !== window) return;
    if (event.data && event.data.type === 'ELMS_CHECK_LICENSE') {
        if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.local) {
            chrome.storage.local.get(['elms_license'], (res) => {
                window.postMessage({ type: 'ELMS_LICENSE_RESPONSE', data: res.elms_license || null }, '*');
            });
        }
    }
});
