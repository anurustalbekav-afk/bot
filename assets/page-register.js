// Wiring for /register.php.
document.addEventListener('DOMContentLoaded', () => {
  window.FD_I18N.mount();
  const form = document.getElementById('registerForm');
  const statusEl = document.getElementById('status');
  const submitBtn = form.querySelector('button[type=submit]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    FD_AUTH.clearStatus(statusEl);
    const email = form.email.value.trim();
    const login = form.login.value.trim();
    const password = form.password.value;
    const password2 = form.password2.value;

    if (password !== password2) {
      FD_AUTH.showStatus(statusEl, 'error', 'err.password_mismatch');
      return;
    }

    submitBtn.disabled = true;
    try {
      const r = await FD_AUTH.postJson('/api/register.php', { email, login, password });
      if (r.ok && r.body && r.body.ok) {
        FD_AUTH.showStatus(statusEl, 'ok', 'ok.registered');
        setTimeout(() => window.location.replace('/dashboard.php'), 400);
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
