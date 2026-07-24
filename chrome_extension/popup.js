document.addEventListener('DOMContentLoaded', async () => {
    let API_BASE_URL = await getStoredValue('api_base_url') || 'http://localhost:8000';

    const licenseInput = document.getElementById('license-key-input');

    const btnActivate   = document.getElementById('btn-activate');
    const statusBox     = document.getElementById('status-box');
    const uploadSection = document.getElementById('upload-section');
    const fileInput     = document.getElementById('file-input');
    const btnUpload     = document.getElementById('btn-upload');

    // Generate or retrieve persistent browser device fingerprint
    let deviceFingerprint = await getStoredValue('device_fingerprint');
    if (!deviceFingerprint) {
        deviceFingerprint = 'ext_dev_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        await setStoredValue('device_fingerprint', deviceFingerprint);
    }

    // Load active license from storage if exists
    const storedLicense = await getStoredValue('elms_license');
    if (storedLicense && storedLicense.activated) {
        renderActiveStatus(storedLicense);
        uploadSection.style.display = 'block';
    }

    // Activate License Click Handler
    btnActivate.addEventListener('click', async () => {
        const licenseKey = licenseInput.value.trim();
        if (!licenseKey) {
            showError("Please enter a valid license key.");
            return;
        }

        btnActivate.disabled = true;
        btnActivate.innerText = "Activating...";

        try {
            const response = await fetch(`${API_BASE_URL}/api/v1/license/activate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    license_key: licenseKey,
                    product_id: 1,
                    device_fingerprint: deviceFingerprint,
                    browser: 'Chrome Extension',
                    os: navigator.platform
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const licData = data.data;
                licData.license_key = licenseKey;
                licData.activated_at = new Date().toISOString();

                await setStoredValue('elms_license', licData);
                renderActiveStatus(licData);
                uploadSection.style.display = 'block';
            } else {
                showError(data.message || "Activation failed.");
            }
        } catch (err) {
            showError("Connection error: Unable to reach ELMS backend.");
        } finally {
            btnActivate.disabled = false;
            btnActivate.innerText = "Activate License";
        }
    });

    // Upload Authorization Handler
    btnUpload.addEventListener('click', async () => {
        const storedLicense = await getStoredValue('elms_license');
        if (!storedLicense || !storedLicense.session_token) {
            showError("Active license session missing. Please activate license.");
            return;
        }

        const file = fileInput.files[0];
        const fileName = file ? file.name : "demo_extension_upload.txt";
        const fileSize = file ? file.size : 1024;

        btnUpload.disabled = true;
        btnUpload.innerText = "Authorizing...";

        try {
            const response = await fetch(`${API_BASE_URL}/api/v1/upload-authorize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'x-license-key': storedLicense.license_key,
                    'x-session-id': storedLicense.session_token,
                    'x-device-id': deviceFingerprint
                },
                body: JSON.stringify({
                    license_key: storedLicense.license_key,
                    session_token: storedLicense.session_token,
                    device_fingerprint: deviceFingerprint,
                    file_name: fileName,
                    file_size: fileSize,
                    mime_type: file ? file.type : 'text/plain'
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showSuccess(`Upload Authorized!<br>UUID: ${data.data.upload_uuid}<br>Driver: ${data.data.storage_driver}`);
            } else {
                showError(data.message || "Upload authorization denied.");
            }
        } catch (err) {
            showError("Upload connection error.");
        } finally {
            btnUpload.disabled = false;
            btnUpload.innerText = "Authorize & Send Upload";
        }
    });

    function renderActiveStatus(data) {
        statusBox.className = "status-box active";
        statusBox.innerHTML = `
            <div class="info-row"><strong>Status:</strong> <span>Active ✅</span></div>
            <div class="info-row"><strong>Key:</strong> <span>${data.license_key}</span></div>
            <div class="info-row"><strong>Plan:</strong> <span>${data.plan_name || 'Standard'}</span></div>
            <div class="info-row"><strong>Devices:</strong> <span>${data.current_devices ?? 1} / ${data.allowed_devices ?? 1}</span></div>
            <div class="info-row"><strong>Uploads:</strong> <span>${data.upload_permission ? 'Allowed' : 'Disabled'}</span></div>
        `;
    }

    function showError(msg) {
        statusBox.className = "status-box error";
        statusBox.innerHTML = `⚠️ ${msg}`;
    }

    function showSuccess(msg) {
        statusBox.className = "status-box active";
        statusBox.innerHTML = `✅ ${msg}`;
    }

    function getStoredValue(key) {
        return new Promise((resolve) => {
            if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.local) {
                chrome.storage.local.get([key], (res) => resolve(res[key]));
            } else {
                const item = localStorage.getItem(key);
                resolve(item ? JSON.parse(item) : null);
            }
        });
    }

    function setStoredValue(key, val) {
        return new Promise((resolve) => {
            if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.local) {
                chrome.storage.local.set({ [key]: val }, () => resolve());
            } else {
                localStorage.setItem(key, JSON.stringify(val));
                resolve();
            }
        });
    }
});
