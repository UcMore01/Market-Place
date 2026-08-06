(function() {
    'use strict';

    let authCache = null;
    let authPromise = null;

    async function checkAuth() {
        if (authCache !== null) return authCache;
        if (authPromise) return authPromise;

        authPromise = fetch('auth_status_api.php', { credentials: 'same-origin', cache: 'no-store' })
            .then(res => res.json())
            .then(data => {
                authCache = !!(data && data.logged_in);
                return authCache;
            })
            .catch(() => {
                authCache = false;
                return false;
            });

        return authPromise;
    }

    async function requireAuth() {
        const loggedIn = await checkAuth();
        if (!loggedIn) {
            const redirect = encodeURIComponent(window.location.pathname + window.location.search);
            window.location.href = 'login.html?redirect=' + redirect;
            throw new Error('Not authenticated');
        }
        return true;
    }

    async function requireGuestOrRedirect() {
        const loggedIn = await checkAuth();
        if (!loggedIn) {
            const redirect = encodeURIComponent(window.location.pathname + window.location.search);
            window.location.href = 'register.html?redirect=' + redirect;
            throw new Error('Not authenticated');
        }
        return true;
    }

    window.requireAuth = requireAuth;
    window.requireGuestOrRedirect = requireGuestOrRedirect;
    window.checkAuth = checkAuth;
})();
