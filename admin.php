<?php
require_once __DIR__ . '/lib/bootstrap.php';

$user = fd_current_user();
if (!$user) { header('Location: /index.php'); exit; }
if (!fd_is_admin($user)) { http_response_code(403); echo 'Forbidden'; exit; }
$pub = fd_public_user($user);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title data-i18n-title="meta.title.admin">fear.dev — Админка</title>
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= fd_e(fd_asset('/assets/styles.css')) ?>" />
</head>
<body class="admin-body">
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
      <span class="adm-tag" data-i18n="admin.title">Админ-панель</span>
    </div>
    <nav class="adm-tabs" role="tablist">
      <button type="button" class="adm-tab active" data-tab="users" data-i18n="admin.tab.users">Пользователи</button>
      <button type="button" class="adm-tab"        data-tab="mods"  data-i18n="admin.tab.mods">Моды</button>
    </nav>
    <div class="adm-top-right">
      <div class="lang-switch" role="tablist" aria-label="Language">
        <button type="button" data-lang-btn="ru">RU</button>
        <button type="button" data-lang-btn="uk">UK</button>
        <button type="button" data-lang-btn="en">EN</button>
      </div>
      <span class="adm-me" title="<?= fd_e($pub['email']) ?>"><?= fd_e($pub['login']) ?></span>
      <a class="btn btn-ghost btn-sm" href="/dashboard.php" data-i18n="btn.back">Кабинет</a>
    </div>
  </header>

  <main class="adm-main">
    <!-- USERS TAB -->
    <section class="adm-pane" data-pane="users">
      <div class="adm-toolbar">
        <input class="adm-search" id="userSearch" type="search" data-i18n-placeholder="admin.users.search" placeholder="Поиск по логину, email или IP…" />
        <span class="adm-spacer"></span>
        <span class="adm-count" id="userCount">0</span>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table" id="usersTable">
          <thead>
            <tr>
              <th data-i18n="admin.users.col.login">Логин</th>
              <th data-i18n="admin.users.col.email">Email</th>
              <th data-i18n="admin.users.col.ip">IP</th>
              <th data-i18n="admin.users.col.created">Регистрация</th>
              <th data-i18n="admin.users.col.topups">Пополнения</th>
              <th data-i18n="admin.users.col.purchases">Покупки</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <!-- MODS TAB -->
    <section class="adm-pane" data-pane="mods" hidden>
      <div class="adm-toolbar">
        <input class="adm-search" id="modSearch" type="search" data-i18n-placeholder="admin.mods.search" placeholder="Поиск модов…" />
        <span class="adm-spacer"></span>
        <button class="btn btn-primary btn-sm" id="addModBtn" data-i18n="admin.mods.add">+ Добавить мод</button>
      </div>
      <div class="adm-grid" id="modsGrid"></div>
      <div class="adm-empty" id="modsEmpty" hidden data-i18n="admin.mods.empty">Пока нет ни одного мода.</div>
    </section>
  </main>

  <!-- USER DETAILS MODAL -->
  <div class="modal" id="userModal" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-box modal-lg" role="dialog" aria-modal="true">
      <div class="modal-head">
        <h3 id="userModalTitle" data-i18n="admin.user.details">Детали пользователя</h3>
        <button class="modal-x" data-close aria-label="Close">×</button>
      </div>
      <div class="modal-body" id="userModalBody"></div>
    </div>
  </div>

  <!-- MOD EDIT MODAL -->
  <div class="modal" id="modModal" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-box" role="dialog" aria-modal="true">
      <div class="modal-head">
        <h3 id="modModalTitle" data-i18n="admin.mods.edit">Редактирование мода</h3>
        <button class="modal-x" data-close aria-label="Close">×</button>
      </div>
      <form id="modForm" class="modal-body" novalidate>
        <input type="hidden" name="id" />
        <label class="adm-label" data-i18n="admin.mods.field.title">Название</label>
        <input class="adm-input" name="title" maxlength="120" required />
        <label class="adm-label" data-i18n="admin.mods.field.banner">Ссылка на баннер</label>
        <input class="adm-input" name="banner" type="url" placeholder="https://…" />
        <label class="adm-label" data-i18n="admin.mods.field.url">Ссылка на мод</label>
        <input class="adm-input" name="url" type="url" placeholder="https://…" required />
        <div class="adm-row">
          <div>
            <label class="adm-label" data-i18n="admin.mods.field.price">Цена</label>
            <input class="adm-input" name="price" type="number" step="0.01" min="0" required />
          </div>
          <div>
            <label class="adm-label" data-i18n="admin.mods.field.currency">Валюта</label>
            <input class="adm-input" name="currency" maxlength="3" value="USD" />
          </div>
          <div>
            <label class="adm-label" data-i18n="admin.mods.field.type">Тип</label>
            <select class="adm-input" name="type">
              <option value="mod">mod</option>
              <option value="script">script</option>
            </select>
          </div>
        </div>
        <div class="status" id="modFormStatus" role="status" aria-live="polite"></div>
        <div class="modal-actions">
          <button type="button" class="btn btn-ghost btn-sm" data-close data-i18n="btn.cancel">Отмена</button>
          <button type="submit" class="btn btn-primary btn-sm" data-i18n="btn.save">Сохранить</button>
        </div>
      </form>
    </div>
  </div>

  <script src="<?= fd_e(fd_asset('/assets/i18n.js')) ?>"></script>
  <script src="<?= fd_e(fd_asset('/assets/auth.js')) ?>"></script>
  <script src="<?= fd_e(fd_asset('/assets/admin.js')) ?>"></script>
</body>
</html>
