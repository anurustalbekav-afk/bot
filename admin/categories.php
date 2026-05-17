<?php
declare(strict_types=1);
require __DIR__ . '/_guard.php';
$me = Auth::currentUser();

layout_head('meta.title.admin', 'fear.dev — Админка', $me);
?>
<main class="adm-shell">
  <?php admin_sidebar('categories'); ?>
  <section class="adm-main">
    <h1 data-i18n="admin.cats.title">Категории</h1>
    <p class="sub" data-i18n="admin.cats.subtitle">Группы для каталога: моды, скрипты, карты и т.д.</p>

    <div id="status" class="status" role="status" aria-live="polite"></div>

    <table class="adm-table">
      <thead>
        <tr>
          <th data-i18n="admin.cats.col.title">Название</th>
          <th data-i18n="admin.cats.col.slug">Slug</th>
          <th data-i18n="admin.cats.col.position">Порядок</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="catRows"></tbody>
    </table>

    <h2 style="margin-top:32px" data-i18n="admin.cats.add">Добавить категорию</h2>
    <form id="catForm" class="adm-form">
      <div class="row">
        <label>
          <span data-i18n="admin.cats.col.title">Название</span>
          <input type="text" name="title" required maxlength="120" />
        </label>
        <label>
          <span data-i18n="admin.cats.col.slug">Slug</span>
          <input type="text" name="slug" pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="64"
                 placeholder="auto" data-i18n-placeholder="admin.placeholder.slug" />
        </label>
        <label>
          <span data-i18n="admin.cats.col.position">Порядок</span>
          <input type="number" name="position" value="0" />
        </label>
      </div>
      <button type="submit" class="btn btn-primary" data-i18n="admin.btn.create">Создать</button>
    </form>
  </section>
</main>
<script src="/assets/admin-categories.js"></script>
<?php layout_foot(); ?>
