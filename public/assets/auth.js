// fear.dev — shared auth client helpers
(function () {
  const t = (k) => window.FD_I18N.t(k);

  async function jsonRequest(method, url, data) {
    const init = {
      method,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
    };
    if (data !== undefined) {
      init.headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(data || {});
    }
    const res = await fetch(url, init);
    let body = null;
    try { body = await res.json(); } catch {}
    return { ok: res.ok, status: res.status, body };
  }

  const postJson   = (url, data) => jsonRequest('POST',   url, data);
  const getJson    = (url)       => jsonRequest('GET',    url);
  const patchJson  = (url, data) => jsonRequest('PATCH',  url, data);
  const deleteJson = (url)       => jsonRequest('DELETE', url);

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
    return serverError ? 'err.' + serverError : 'err.unknown';
  }

  window.FD_AUTH = { postJson, getJson, patchJson, deleteJson, showStatus, clearStatus, errorKey };
})();
