// Shared CSRF helper.
// Loads the session CSRF token once. Exposes window.__csrf and a promise
// (window.__csrfPromise) so callers can await it if it isn't ready yet.
window.__csrf = null;
window.__csrfPromise = (function () {
  return fetch('csrf_token.php', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (d) { window.__csrf = (d && d.csrf_token) || null; return window.__csrf; })
    .catch(function () { window.__csrf = null; return null; });
})();

// Returns a payload object merged with the CSRF token, awaiting the token
// load if it hasn't completed yet (avoids the race where the user clicks
// before the async token fetch resolves).
async function withCsrf(payload) {
  payload = payload || {};
  if (!window.__csrf) {
    try { window.__csrf = await window.__csrfPromise; } catch (e) { window.__csrf = null; }
  }
  payload.csrf_token = window.__csrf || '';
  return payload;
}
