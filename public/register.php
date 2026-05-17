<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if (fd_current_user()) {
    header('Location: /dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title data-i18n-title="meta.title.register">fear.dev — Регистрация</title>
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
  <div class="topbar">
    <div class="lang-switch" role="tablist" aria-label="Language">
      <button type="button" data-lang-btn="ru">RU</button>
      <button type="button" data-lang-btn="uk">UK</button>
      <button type="button" data-lang-btn="en">EN</button>
    </div>
  </div>

  <main class="shell">
    <section class="card" aria-label="Create account">
      <div class="brand">
        <div class="logo" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="#0a0a0c" stroke-width="2.2" stroke-linecap="round"/>
            <rect x="5" y="10" width="14" height="10" rx="2.4" fill="#0a0a0c"/>
            <circle cx="12" cy="15" r="1.6" fill="#fff"/>
          </svg>
        </div>
        <h1 data-i18n="brand.name">FEAR.DEV</h1>
        <p data-i18n="register.subtitle">Создайте аккаунт fear.dev, чтобы покупать моды SAMP и скрипты.</p>
      </div>

      <form id="registerForm" autocomplete="on" novalidate>
        <div class="field">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="5" width="18" height="14" rx="2.4" stroke="currentColor" stroke-width="1.6"/>
              <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            </svg>
          </span>
          <input type="email" name="email" id="email" data-i18n-placeholder="placeholder.email" autocomplete="email" required />
        </div>

        <div class="field">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/>
              <path d="M4 20c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </span>
          <input type="text" name="login" id="login" data-i18n-placeholder="placeholder.login" autocomplete="username" required minlength="3" maxlength="24" pattern="[A-Za-z0-9_]+" />
        </div>

        <div class="field">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
              <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </span>
          <input type="password" name="password" id="password" data-i18n-placeholder="placeholder.password" autocomplete="new-password" required minlength="8" />
        </div>

        <div class="field">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
              <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              <path d="M9 15l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <input type="password" name="password2" id="password2" data-i18n-placeholder="placeholder.password_confirm" autocomplete="new-password" required minlength="8" />
        </div>

        <div id="status" class="status" role="status" aria-live="polite"></div>

        <button type="submit" class="btn btn-primary">
          <span data-i18n="btn.register">Создать аккаунт</span>
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </form>

      <div class="alt-link">
        <span data-i18n="alt.toLogin">Уже есть аккаунт?</span>
        <a href="/index.php" data-i18n="alt.toLogin.link">Войти</a>
      </div>

      <div class="meta">
        <span data-i18n="meta.system">Система защищена</span>
        <span class="dot" aria-hidden="true"></span>
        <span data-i18n="meta.developer">fear.dev · build 0.2</span>
      </div>
    </section>
  </main>

  <script src="/assets/i18n.js"></script>
  <script src="/assets/auth.js"></script>
  <script>
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
  </script>
</body>
</html>
