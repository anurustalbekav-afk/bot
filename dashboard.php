<?php
require_once __DIR__ . '/lib/bootstrap.php';

$user = fd_current_user();
if (!$user) {
    header('Location: /index.php');
    exit;
}
$pub = fd_public_user($user);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#0a0a0c" />
  <title data-i18n-title="meta.title.dashboard">fear.dev — Панель</title>
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= fd_e(fd_asset('/assets/styles.css')) ?>" />
</head>
<body class="dash-body">
  <header class="adm-top">
    <div class="adm-top-left">
      <span class="adm-logo" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="#0a0a0c" stroke-width="2.2" stroke-linecap="round"/>
          <rect x="5" y="10" width="14" height="10" rx="2.4" fill="#0a0a0c"/>
          <circle cx="12" cy="15" r="1.6" fill="#fff"/>
        </svg>
      </span>
      <strong>FEAR.DEV</strong>
      <span class="adm-tag" data-i18n="meta.title.dashboard">Панель</span>
    </div>
    <nav class="adm-tabs" role="tablist">
      <button type="button" class="adm-tab active" data-dash-tab="profile"   data-i18n="dash.tab.profile">Профиль</button>
      <button type="button" class="adm-tab"        data-dash-tab="purchases" data-i18n="dash.tab.purchases">Мои покупки</button>
    </nav>
    <div class="adm-top-right">
      <div class="lang-switch" role="tablist" aria-label="Language">
        <button type="button" data-lang-btn="ru">RU</button>
        <button type="button" data-lang-btn="uk">UK</button>
        <button type="button" data-lang-btn="en">EN</button>
      </div>
      <span class="adm-balance" id="dashBalance"
            title="<?= fd_e(number_format(fd_user_balance($user), 2, '.', '')) ?>">
        <?= fd_e(number_format(fd_user_balance($user), 2, '.', '')) ?>$
      </span>
      <a class="btn btn-ghost btn-sm" href="/catalog.php" data-i18n="btn.catalog">Каталог</a>
      <?php if ($pub['isAdmin']): ?>
        <a class="btn btn-ghost btn-sm" href="/admin.php" data-i18n="btn.admin">Админка</a>
      <?php endif; ?>
      <button id="logoutBtn" class="btn btn-ghost btn-sm" data-i18n="btn.logout">Выйти</button>
    </div>
  </header>

  <main class="adm-main">
    <!-- PROFILE -->
    <section class="adm-pane" data-dash-pane="profile">
      <h2 class="dash-h2"><span data-i18n="dash.welcome">Добро пожаловать,</span> <span><?= fd_e($pub['login']) ?></span></h2>

      <div class="userbox">
        <div class="cell"><div class="k" data-i18n="dash.balance">Баланс</div>
          <div class="v"><?= fd_e(number_format(fd_user_balance($user), 2, '.', '')) ?> $</div></div>
        <div class="cell"><div class="k" data-i18n="dash.email">Email</div><div class="v"><?= fd_e($pub['email']) ?></div></div>
        <div class="cell"><div class="k" data-i18n="dash.login">Логин</div><div class="v"><?= fd_e($pub['login']) ?></div></div>
        <div class="cell"><div class="k" data-i18n="dash.id">ID</div><div class="v" style="font-size:12px"><?= fd_e($pub['id']) ?></div></div>
        <div class="cell"><div class="k" data-i18n="dash.created">Регистрация</div><div class="v" id="userCreated"><?= fd_e($pub['createdAt']) ?></div></div>
      </div>
    </section>

    <!-- PURCHASES -->
    <section class="adm-pane" data-dash-pane="purchases" hidden>
      <div id="purchasesList" class="adm-grid"></div>
      <div class="adm-empty" id="purchasesEmpty" hidden data-i18n="dash.purchases.empty">Покупок пока нет.</div>
    </section>
  </main>

  <script src="<?= fd_e(fd_asset('/assets/i18n.js')) ?>"></script>
  <script src="<?= fd_e(fd_asset('/assets/auth.js')) ?>"></script>
  <script src="<?= fd_e(fd_asset('/assets/page-dashboard.js')) ?>"></script>
</body>
</html>
