/* Login / register / dashboard scripts */
(function () {
  const flash = document.getElementById('flash');
  function showFlash(msg, ok = false) {
    if (!flash) return;
    flash.textContent = msg;
    flash.classList.toggle('ok', !!ok);
    flash.classList.add('show');
  }
  function hideFlash() { flash && flash.classList.remove('show'); }

  function clearErrors(form) {
    form.querySelectorAll('.err').forEach(el => (el.textContent = ''));
  }
  function showErrors(form, fields) {
    Object.entries(fields || {}).forEach(([name, msg]) => {
      const el = form.querySelector(`[data-err="${name}"]`);
      if (el) el.textContent = msg;
    });
  }

  async function postJSON(url, body) {
    const r = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body),
    });
    let data = {};
    try { data = await r.json(); } catch {}
    return { ok: r.ok, status: r.status, data };
  }

  // ---------------- Register ----------------
  const regForm = document.getElementById('registerForm');
  if (regForm) {
    regForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      hideFlash();
      clearErrors(regForm);
      const fd = new FormData(regForm);
      const payload = {
        login: (fd.get('login') || '').toString().trim(),
        email: (fd.get('email') || '').toString().trim(),
        password: (fd.get('password') || '').toString(),
      };
      const btn = regForm.querySelector('button[type=submit]');
      btn.disabled = true;
      const { ok, data } = await postJSON('/api/register', payload);
      btn.disabled = false;
      if (!ok) {
        if (data.fields) showErrors(regForm, data.fields);
        else showFlash(data.message || 'Не удалось создать аккаунт.');
        return;
      }
      showFlash('Аккаунт создан. Перенаправляем…', true);
      setTimeout(() => (location.href = '/dashboard'), 600);
    });
  }

  // ---------------- Login ----------------
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      hideFlash();
      clearErrors(loginForm);
      const fd = new FormData(loginForm);
      const payload = {
        identifier: (fd.get('identifier') || '').toString().trim(),
        password: (fd.get('password') || '').toString(),
      };
      const btn = loginForm.querySelector('button[type=submit]');
      btn.disabled = true;
      const { ok, data } = await postJSON('/api/login', payload);
      btn.disabled = false;
      if (!ok) {
        if (data.fields) showErrors(loginForm, data.fields);
        else showFlash(data.message || 'Ошибка входа.');
        return;
      }
      showFlash('Успешный вход. Перенаправляем…', true);
      setTimeout(() => (location.href = '/dashboard'), 400);
    });
  }

  // ---------------- Dashboard ----------------
  const dashTopbar = document.getElementById('topbar');
  const pLogin = document.getElementById('pLogin');
  if (dashTopbar && pLogin) {
    (async () => {
      try {
        const r = await fetch('/api/me', { credentials: 'same-origin' });
        if (!r.ok) throw new Error('unauthorized');
        const { user } = await r.json();
        dashTopbar.innerHTML = `
          <div class="user-card">
            <div class="user-avatar">☺</div>
            <div class="user-meta"><b>${user.login}</b><small>ID: ${user.id}</small></div>
          </div>
          <button class="btn-ghost" id="logoutBtnTop">ВЫЙТИ</button>
        `;
        pLogin.textContent = user.login;
        document.getElementById('pEmail').textContent = user.email;
        document.getElementById('pId').textContent = user.id;
        document.getElementById('pCreated').textContent = new Date(user.created_at).toLocaleString('ru-RU');

        const doLogout = async () => {
          await fetch('/api/logout', { method: 'POST', credentials: 'same-origin' });
          location.href = '/';
        };
        document.getElementById('logoutBtnTop')?.addEventListener('click', doLogout);
        document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); doLogout(); });
        document.getElementById('logoutBtn2')?.addEventListener('click', doLogout);
      } catch {
        location.href = '/login';
      }
    })();
  }
})();
