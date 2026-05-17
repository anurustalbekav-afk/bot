<?php
/**
 * Минимальный шаблонизатор «руками»: общая шапка и подвал.
 * Не используем include-наследование, чтобы остаться в плоской
 * процедурной модели страниц.
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
  <header class="navbar">
    <a class="brand-link" href="/" data-i18n="brand.name">FEAR.DEV</a>
    <nav class="navmenu">
      <a href="/catalog.php" data-i18n="nav.catalog">Каталог</a>
      <?php if ($me): ?>
        <a href="/dashboard.php" data-i18n="nav.dashboard">Кабинет</a>
        <?php if ($isAdmin): ?>
          <a href="/admin/" class="adm-link" data-i18n="nav.admin">Админка</a>
        <?php endif; ?>
        <span class="who"><?= $loginEsc ?></span>
      <?php else: ?>
        <a href="/login.php" data-i18n="nav.login">Войти</a>
        <a href="/register.php" data-i18n="nav.register">Регистрация</a>
      <?php endif; ?>
    </nav>
    <div class="lang-switch" role="tablist" aria-label="Language">
      <button type="button" data-lang-btn="ru">RU</button>
      <button type="button" data-lang-btn="uk">UK</button>
      <button type="button" data-lang-btn="en">EN</button>
    </div>
  </header>
<?php }

function layout_foot(): void
{
    ?>
  <footer class="page-foot">
    <span data-i18n="meta.system">Система защищена</span>
    <span class="dot" aria-hidden="true"></span>
    <span data-i18n="meta.developer">fear.dev · build 0.3-php</span>
  </footer>
  <script src="/assets/i18n.js"></script>
  <script src="/assets/auth.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => window.FD_I18N.mount());</script>
</body>
</html>
<?php }
