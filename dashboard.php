<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';

$uid = Auth::currentUserId();
$user = $uid ? DB::findById($uid) : null;
if (!$user) {
    header('Location: /login.php');
    exit;
}

// Готовим безопасные значения для разметки.
$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$loginEsc = $h($user['login']);
$emailEsc = $h($user['email']);
$idEsc    = $h($user['id']);
$created  = $user['created_at'] ?? '';
$ts       = $created ? strtotime($created . ' UTC') : false;
$createdIso = $ts !== false ? gmdate('Y-m-d\TH:i:s\Z', $ts) : $h($created);
?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title data-i18n-title="meta.title.dashboard">fear.dev — Панель</title>
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
    <section class="dash" aria-label="Dashboard">
      <h2><span data-i18n="dash.welcome">Добро пожаловать,</span> <span id="userLogin"><?= $loginEsc ?></span></h2>
      <p class="sub" data-i18n="dash.subtitle">Это ваш кабинет fear.dev. Скоро здесь появится каталог модов и скриптов.</p>

      <div class="userbox">
        <div class="cell"><div class="k" data-i18n="dash.email">Email</div><div class="v"><?= $emailEsc ?></div></div>
        <div class="cell"><div class="k" data-i18n="dash.login">Логин</div><div class="v"><?= $loginEsc ?></div></div>
        <div class="cell"><div class="k" data-i18n="dash.id">ID</div><div class="v"><?= $idEsc ?></div></div>
        <div class="cell"><div class="k" data-i18n="dash.created">Регистрация</div><div class="v" id="userCreated" data-iso="<?= $createdIso ?>"><?= $createdIso ?></div></div>
      </div>

      <div class="actions">
        <button id="logoutBtn" class="btn btn-ghost" data-i18n="btn.logout">Выйти</button>
      </div>

      <div class="meta" style="margin-top:24px">
        <span data-i18n="meta.system">Система защищена</span>
        <span class="dot" aria-hidden="true"></span>
        <span data-i18n="meta.developer">fear.dev · build 0.2-php</span>
      </div>
    </section>
  </main>

  <script src="/assets/i18n.js"></script>
  <script src="/assets/auth.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      window.FD_I18N.mount();

      const el = document.getElementById('userCreated');
      const iso = el && el.dataset.iso;
      if (iso) {
        try { el.textContent = new Date(iso).toLocaleString(window.FD_I18N.getLocale()); }
        catch {}
      }

      document.getElementById('logoutBtn').addEventListener('click', async () => {
        await FD_AUTH.postJson('/api/logout.php', {});
        window.location.replace('/login.php');
      });
    });
  </script>
</body>
</html>
