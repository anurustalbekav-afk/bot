// fear.dev — shared auth client helpers
(function () {
  const t = (k) => window.FD_I18N.t(k);

  // API endpoints (PHP backend)
  const ENDPOINTS = {
    register: '/api/register.php',
    login:    '/api/login.php',
    logout:   '/api/logout.php',
    me:       '/api/me.php',
    health:   '/api/health.php',
  };

  async function postJson(url, data) {
    let res, raw;
    try {
      res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data || {}),
      });
      raw = await res.text();
    } catch (e) {
      console.error('[fear.dev] network error', e);
      return { ok: false, status: 0, body: { error: 'network' }, raw: '' };
    }
    let body = null;
    try { body = JSON.parse(raw); } catch {}
    if (!body) {
      console.error('[fear.dev] non-JSON response from', url, '— status', res.status, '— body:', raw);
    }
    return { ok: res.ok, status: res.status, body, raw };
  }

  async function getJson(url) {
    let res, raw;
    try {
      res = await fetch(url, { credentials: 'same-origin' });
      raw = await res.text();
    } catch (e) {
      console.error('[fear.dev] network error', e);
      return { ok: false, status: 0, body: { error: 'network' }, raw: '' };
    }
    let body = null;
    try { body = JSON.parse(raw); } catch {}
    return { ok: res.ok, status: res.status, body, raw };
  }

  function showStatus(el, kind, key, extra) {
    if (!el) return;
    el.classList.remove('error', 'ok');
    el.classList.add('show', kind);
    el.textContent = extra ? `${t(key)} (${extra})` : t(key);
  }
  function clearStatus(el) {
    if (!el) return;
    el.classList.remove('show', 'error', 'ok');
    el.textContent = '';
  }

  // Map a server response to the right error key + extra info.
  function describeError(r) {
    if (!r) return { key: 'err.unknown', extra: '' };
    if (r.status === 0) return { key: 'err.network', extra: '' };
    if (r.body && r.body.error) {
      // Surface the precise server-side reason in the toast.
      return { key: 'err.' + r.body.error, extra: r.body.detail || '' };
    }
    // Server returned non-JSON (HTML 500 page, broken .htaccess, PHP < 7.4 …)
    return { key: 'err.server', extra: 'HTTP ' + r.status };
  }

  window.FD_AUTH = {
    postJson, getJson,
    showStatus, clearStatus,
    describeError,
    ENDPOINTS,
    // legacy alias used by older inline scripts
    errorKey: (e) => 'err.' + (e || 'unknown'),
  };
})();
