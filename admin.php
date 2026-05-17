<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';

$me = Auth::currentUser();
if (!$me) {
    header('Location: /login.php');
    exit;
}
if (!Auth::isAdmin($me)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>403</title>'
       . '<body style="font:14px system-ui;padding:32px;color:#eee;background:#0a0a0c">'
       . '<h1>403 Forbidden</h1><p>Доступ только для администраторов.</p>'
       . '<p><a style="color:#9cf" href="/dashboard.php">В кабинет</a></p></body>';
    exit;
}

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$myIdEsc    = $h($me['id']);
$myLoginEsc = $h($me['login']);
?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title data-i18n-title="meta.title.admin">fear.dev — Админка</title>
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="/assets/styles.css" />
  <style>
    .admin { width: min(960px, 96vw); margin: 24px auto; }
    .admin .toolbar { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin: 12px 0 16px; }
    .admin .toolbar input[type=search] { flex:1; min-width: 200px; padding: 10px 12px; border-radius: 10px; border: 1px solid #2a2a30; background:#0d0d10; color:#eaeaea; }
    .admin table { width:100%; border-collapse: collapse; background:#0d0d10; border-radius: 12px; overflow:hidden; border: 1px solid #1d1d22; }
    .admin th, .admin td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #1a1a1f; font-size: 13px; vertical-align: middle; }
    .admin th { background:#13131a; color:#aab; font-weight: 600; }
    .admin tr:last-child td { border-bottom: 0; }
    .admin .role-badge { display:inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
    .admin .role-admin { background: #2b1f3a; color: #d6b6ff; }
    .admin .role-user  { background: #1a2230; color: #9bb6d6; }
    .admin select { background:#0d0d10; color:#eaeaea; border:1px solid #2a2a30; border-radius: 8px; padding: 6px 8px; }
    .admin .danger { background:#3a1f24; color:#ffb3bd; border:1px solid #5a2a32; padding: 6px 10px; border-radius: 8px; cursor:pointer; }
    .admin .danger:disabled { opacity: .4; cursor: not-allowed; }
    .admin .empty { padding: 24px; text-align:center; color:#888; }
    .admin .pager { display:flex; gap:8px; align-items:center; justify-content:flex-end; margin-top: 12px; color:#aab; font-size: 13px; }
    .admin .pager button { background:#13131a; color:#ddd; border:1px solid #2a2a30; padding: 6px 10px; border-radius: 8px; cursor:pointer; }
    .admin .pager button:disabled { opacity: .4; cursor: not-allowed; }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="lang-switch" role="tablist" aria-label="Language">
      <button type="button" data-lang-btn="ru">RU</button>
      <button type="button" data-lang-btn="uk">UK</button>
      <button type="button" data-lang-btn="en">EN</button>
    </div>
  </div>

  <main class="shell admin">
    <section class="dash" aria-label="Admin">
      <h2><span data-i18n="admin.title">Админка</span></h2>
      <p class="sub"><span data-i18n="admin.subtitle">Управление пользователями и ролями.</span>
         <span> · </span>
         <a href="/dashboard.php" data-i18n="admin.toDashboard">В кабинет</a>
      </p>

      <div class="toolbar">
        <input type="search" id="search" data-i18n-placeholder="admin.search" placeholder="Поиск по логину или email" />
        <span id="totalCount" style="color:#888;font-size:13px"></span>
      </div>

      <div id="status" class="status" role="status" aria-live="polite"></div>

      <table>
        <thead>
          <tr>
            <th data-i18n="admin.col.login">Логин</th>
            <th data-i18n="admin.col.email">Email</th>
            <th data-i18n="admin.col.role">Роль</th>
            <th data-i18n="admin.col.created">Регистрация</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="userRows"></tbody>
      </table>
      <div class="pager">
        <button id="prevBtn" type="button">←</button>
        <span id="pageInfo">1</span>
        <button id="nextBtn" type="button">→</button>
      </div>

      <div class="meta" style="margin-top:24px">
        <span data-i18n="meta.system">Система защищена</span>
        <span class="dot" aria-hidden="true"></span>
        <span data-i18n="meta.developer">fear.dev · build 0.2-php</span>
      </div>
    </section>
  </main>

  <script>
    window.FD_ME = { id: <?= json_encode($myIdEsc, JSON_UNESCAPED_UNICODE) ?>, login: <?= json_encode($myLoginEsc, JSON_UNESCAPED_UNICODE) ?> };
  </script>
  <script src="/assets/i18n.js"></script>
  <script src="/assets/auth.js"></script>
  <script src="/assets/admin.js"></script>
</body>
</html>
