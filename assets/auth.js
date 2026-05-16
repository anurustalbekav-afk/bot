// fear.dev — shared auth client helpers
(function () {
  const t = (k) => window.FD_I18N.t(k);

  async function postJson(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data || {}),
    });
    let body = null;
    try { body = await res.json(); } catch {}
    return { ok: res.ok, status: res.status, body };
  }

  async function getJson(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    let body = null;
    try { body = await res.json(); } catch {}
    return { ok: res.ok, status: res.status, body };
  }

  function showStatus(el, kind, key) {
    if (!el) return;
    el.classList.remove('error', 'ok');
    el.classList.add('show', kind);
    el.textContent = t(key);
  }
  function clearStatus(el) {
    if (!el) return;
    el.classList.remove('show', 'error', 'ok');
    el.textContent = '';
  }

  function errorKey(serverError) {
    if (!serverError) return 'err.unknown';
    return 'err.' + serverError;
  }

  // API endpoints (PHP backend)
  const ENDPOINTS = {
    register: '/api/register.php',
    login:    '/api/login.php',
    logout:   '/api/logout.php',
    me:       '/api/me.php',
  };

  window.FD_AUTH = { postJson, getJson, showStatus, clearStatus, errorKey, ENDPOINTS };
})();
