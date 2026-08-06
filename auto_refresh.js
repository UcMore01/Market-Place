(function() {
    'use strict';

    const POLL_INTERVAL = 5000;
    const API_URL = 'site_version_api.php';
    let currentVersion = null;
    let pollTimer = null;
    let reloadScheduled = false;

    async function checkVersion() {
        try {
            const res = await fetch(API_URL, { credentials: 'same-origin', cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            const newVersion = data.version || '';

            if (currentVersion === null) {
                currentVersion = newVersion;
                return;
            }

            if (newVersion && newVersion !== currentVersion) {
                currentVersion = newVersion;
                scheduleReload();
            }
        } catch (e) {
            // Silently ignore network errors during polling
        }
    }

    function scheduleReload() {
        if (reloadScheduled) return;
        reloadScheduled = true;

        const banner = document.createElement('div');
        banner.id = 'auto-refresh-banner';
        banner.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:9999;background:#0d6efd;color:#fff;text-align:center;padding:8px;font-size:14px;font-family:system-ui,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,0.15);';
        banner.textContent = 'Updating page...';
        document.body.appendChild(banner);

        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }

    function isUserActive() {
        const tag = document.activeElement?.tagName?.toLowerCase();
        const isInput = tag === 'input' || tag === 'textarea' || tag === 'select';
        const hasModal = document.querySelector('.modal.show') !== null;
        return isInput || hasModal;
    }

    function startPolling() {
        if (pollTimer) return;
        checkVersion();
        pollTimer = setInterval(() => {
            if (!isUserActive()) {
                checkVersion();
            }
        }, POLL_INTERVAL);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startPolling);
    } else {
        startPolling();
    }

    window.addEventListener('beforeunload', stopPolling);
    window.addEventListener('pagehide', stopPolling);
})();
