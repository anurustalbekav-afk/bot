<?php
/**
 * Минимальный шаблон шапки и подвала. Дизайн совпадает с login/register —
 * тёмный фон, top bar с lang-switch + компактным меню в общей пилюле.
 */
declare(strict_types=1);

function layout_head(string $titleKey, string $titleFallback, ?array $me = null): void
{
    $isAdmin = Auth::isAdmin($me);
    $loginEsc = $me ? htmlspecialchars($me['login'], ENT_QUOTES, 'UTF-8') : '';
    ?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title data-i18n-title="<?= htmlspecialchars($titleKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($titleFallback, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="/assets/styles.css" />
  <link rel="stylesheet" href="/assets/catalog.css" />
</head>
<body>
  <div class="topbar">
    <a class="brand-mark" href="/" aria-label="fear.dev">
      <span class="logo-mini" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="#0a0a0c" stroke-width="2.2" stroke-linecap="round"/>
          <rect x="5" y="10" width="14" height="10" rx="2.4" fill="#0a0a0c"/>
          <circle cx="12" cy="15" r="1.6" fill="#fff"/>
        </svg>
      </span>
      <span data-i18n="brand.name">FEAR.DEV</span>
    </a>

    <nav class="topnav" aria-label="Main">
      <a href="/catalog.php" data-i18n="nav.catalog">Каталог</a>
      <?php if ($me): ?>
        <a href="/dashboard.php" data-i18n="nav.dashboard">Кабинет</a>
        <?php if ($isAdmin): ?>
          <a href="/admin/" data-i18n="nav.admin">Админка</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="/login.php" data-i18n="nav.login">Войти</a>
        <a href="/register.php" data-i18n="nav.register">Регистрация</a>
      <?php endif; ?>
    </nav>

    <div class="topright">
      <?php if ($me): ?><span class="who" title="<?= $loginEsc ?>"><?= $loginEsc ?></span><?php endif; ?>
      <div class="lang-switch" role="tablist" aria-label="Language">
        <button type="button" data-lang-btn="ru">RU</button>
        <button type="button" data-lang-btn="uk">UK</button>
        <button type="button" data-lang-btn="en">EN</button>
      </div>
    </div>
  </div>
<?php }

function layout_foot(): void
{
    ?>
  <div class="page-meta">
    <span data-i18n="meta.system">Система защищена</span>
    <span class="dot" aria-hidden="true"></span>
    <span data-i18n="meta.developer">fear.dev · build 0.3-php</span>
  </div>
  <script src="/assets/i18n.js"></script>
  <script src="/assets/auth.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => window.FD_I18N.mount());</script>
</body>
</html>
<?php }
