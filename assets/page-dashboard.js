// Wiring for /dashboard.php.
document.addEventListener('DOMContentLoaded', () => {
  window.FD_I18N.mount();

  const created = document.getElementById('userCreated');
  if (created && created.textContent) {
    try {
      created.textContent = new Date(created.textContent).toLocaleString(window.FD_I18N.getLocale());
    } catch { /* leave the original ISO string */ }
  }

  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      await FD_AUTH.postJson('/api/logout.php', {});
      window.location.replace('/index.php');
    });
  }
});
