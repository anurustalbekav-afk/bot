<?php
require_once __DIR__ . '/lib/bootstrap.php';

// Already logged in? Bounce to dashboard.
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
  <title data-i18n-title="meta.title.login">fear.dev — Вход</title>
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
    <section class="card" aria-label="Sign in">
      <div class="brand">
        <div class="logo" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="#0a0a0c" stroke-width="2.2" stroke-linecap="round"/>
            <rect x="5" y="10" width="14" height="10" rx="2.4" fill="#0a0a0c"/>
            <circle cx="12" cy="15" r="1.6" fill="#fff"/>
          </svg>
        </div>
        <h1 data-i18n="brand.name">FEAR.DEV</h1>
        <p data-i18n="login.subtitle">Доступ только для зарегистрированных разработчиков и клиентов.</p>
      </div>

      <form id="loginForm" autocomplete="on" novalidate>
        <div class="field">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/>
              <path d="M4 20c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </span>
          <input type="text" name="identifier" id="identifier" data-i18n-placeholder="placeholder.identifier" autocomplete="username" required />
        </div>

        <div class="field">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
              <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </span>
          <input type="password" name="password" id="password" data-i18n-placeholder="placeholder.password" autocomplete="current-password" required />
        </div>

        <div id="status" class="status" role="status" aria-live="polite"></div>

        <button type="submit" class="btn btn-primary">
          <span data-i18n="btn.login">Войти</span>
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </form>

      <div class="alt-link">
        <span data-i18n="alt.toRegister">Нет аккаунта?</span>
        <a href="/register.php" data-i18n="alt.toRegister.link">Регистрация</a>
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
  <script src="/assets/page-login.js"></script>
</body>
</html>
