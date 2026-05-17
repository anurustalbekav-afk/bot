<?php
declare(strict_types=1);
require __DIR__ . '/_guard.php';
$me = Auth::currentUser();

layout_head('meta.title.admin', 'fear.dev — Админка', $me);
?>
<main class="shell-page">
  <div class="section-head">
    <h1 data-i18n="admin.users.title">Пользователи</h1>
    <p class="sub" data-i18n="admin.subtitle">Поиск, смена ролей и удаление.</p>
  </div>

  <div class="adm-layout">
    <?php admin_sidebar('users'); ?>
    <section class="panel adm-main">
      <div class="adm-toolbar">
        <input type="search" id="search" data-i18n-placeholder="admin.search" placeholder="Поиск по логину или email" />
        <span class="total" id="totalCount"></span>
      </div>

      <div id="status" class="status" role="status" aria-live="polite"></div>

      <table class="adm-table">
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
    </section>
  </div>
</main>
<script>
  window.FD_ME = { id: <?= json_encode($me['id'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, login: <?= json_encode($me['login'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?> };
</script>
<script src="/assets/admin.js"></script>
<?php layout_foot(); ?>
