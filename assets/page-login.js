// Wiring for /index.php (login form).
document.addEventListener('DOMContentLoaded', () => {
  window.FD_I18N.mount();
  const form = document.getElementById('loginForm');
  const statusEl = document.getElementById('status');
  const submitBtn = form.querySelector('button[type=submit]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    FD_AUTH.clearStatus(statusEl);
    const identifier = form.identifier.value.trim();
    const password = form.password.value;
    if (!identifier || !password) {
      FD_AUTH.showStatus(statusEl, 'error', 'err.missing_credentials');
      return;
    }
    submitBtn.disabled = true;
    try {
      const r = await FD_AUTH.postJson('/api/login.php', { identifier, password });
      if (r.ok && r.body && r.body.ok) {
        FD_AUTH.showStatus(statusEl, 'ok', 'ok.logged_in');
        setTimeout(() => window.location.replace('/dashboard.php'), 350);
      } else {
        FD_AUTH.showStatus(statusEl, 'error', FD_AUTH.errorKey(r.body && r.body.error));
      }
    } catch {
      FD_AUTH.showStatus(statusEl, 'error', 'err.network');
    } finally {
      submitBtn.disabled = false;
    }
  });
});
