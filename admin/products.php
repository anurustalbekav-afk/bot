<?php
declare(strict_types=1);
require __DIR__ . '/_guard.php';
$me = Auth::currentUser();

$categories = DB::listCategories();

layout_head('meta.title.admin', 'fear.dev — Админка', $me);
?>
<main class="adm-shell">
  <?php admin_sidebar('products'); ?>
  <section class="adm-main">
    <div class="adm-head">
      <h1 data-i18n="admin.products.title">Товары</h1>
      <button type="button" id="newProductBtn" class="btn btn-primary" data-i18n="admin.products.new">Новый товар</button>
    </div>

    <div class="toolbar">
      <input type="search" id="search" data-i18n-placeholder="admin.products.search" placeholder="Поиск" />
      <select id="filterStatus">
        <option value=""           data-i18n="admin.products.status.any">Любой статус</option>
        <option value="published"  data-i18n="admin.products.status.published">Опубликован</option>
        <option value="draft"      data-i18n="admin.products.status.draft">Черновик</option>
      </select>
      <select id="filterCategory">
        <option value="" data-i18n="admin.products.cat.any">Все категории</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <span id="totalCount"></span>
    </div>

    <div id="status" class="status" role="status" aria-live="polite"></div>

    <table class="adm-table">
      <thead>
        <tr>
          <th data-i18n="admin.products.col.title">Название</th>
          <th data-i18n="admin.products.col.category">Категория</th>
          <th data-i18n="admin.products.col.price">Цена</th>
          <th data-i18n="admin.products.col.status">Статус</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="productRows"></tbody>
    </table>

    <div class="pager">
      <button id="prevBtn" type="button">←</button>
      <span id="pageInfo">1</span>
      <button id="nextBtn" type="button">→</button>
    </div>
  </section>
</main>

<!-- Модалка для создания/редактирования -->
<div id="productModal" class="modal" hidden>
  <div class="modal-box">
    <button type="button" class="modal-close" id="modalClose" aria-label="Close">×</button>
    <h2 id="modalTitle" data-i18n="admin.products.new">Новый товар</h2>
    <form id="productForm" class="adm-form">
      <input type="hidden" name="id" />
      <div class="row">
        <label>
          <span data-i18n="admin.products.col.title">Название</span>
          <input type="text" name="title" required maxlength="180" />
        </label>
        <label>
          <span data-i18n="admin.cats.col.slug">Slug</span>
          <input type="text" name="slug" pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="120"
                 placeholder="auto" data-i18n-placeholder="admin.placeholder.slug" />
        </label>
      </div>
      <div class="row">
        <label>
          <span data-i18n="admin.products.col.category">Категория</span>
          <select name="category_id">
            <option value="" data-i18n="admin.products.cat.none">Без категории</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span data-i18n="admin.products.col.price">Цена</span>
          <input type="number" name="price" min="0" step="0.01" value="0" />
        </label>
        <label>
          <span data-i18n="admin.products.col.currency">Валюта</span>
          <select name="currency">
            <option>USD</option><option>EUR</option><option>RUB</option><option>UAH</option>
          </select>
        </label>
      </div>
      <label>
        <span data-i18n="admin.products.col.summary">Короткое описание</span>
        <input type="text" name="summary" maxlength="255" />
      </label>
      <label>
        <span data-i18n="admin.products.col.image">Картинка (URL)</span>
        <input type="text" name="image_url" placeholder="/uploads/foo.jpg или https://..." />
      </label>
      <label>
        <span data-i18n="admin.products.col.description">Описание</span>
        <textarea name="description" rows="6"></textarea>
      </label>
      <label>
        <span data-i18n="admin.products.col.status">Статус</span>
        <select name="status">
          <option value="draft"     data-i18n="admin.products.status.draft">Черновик</option>
          <option value="published" data-i18n="admin.products.status.published">Опубликован</option>
        </select>
      </label>

      <div id="formStatus" class="status" role="status" aria-live="polite"></div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary" data-i18n="admin.btn.save">Сохранить</button>
        <button type="button" class="btn btn-ghost" id="cancelBtn" data-i18n="admin.btn.cancel">Отмена</button>
      </div>
    </form>
  </div>
</div>

<script>
  window.FD_OPEN_NEW = <?= !empty($_GET['new']) ? 'true' : 'false' ?>;
  window.FD_CATEGORIES = <?= json_encode(array_map(static fn ($c) => ['id' => (int)$c['id'], 'slug' => $c['slug'], 'title' => $c['title']], $categories), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
<script src="/assets/admin-products.js"></script>
<?php layout_foot(); ?>
