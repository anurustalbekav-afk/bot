<?php
declare(strict_types=1);
require __DIR__ . '/_guard.php';
$me = Auth::currentUser();

$totalProducts   = DB::countProducts([]);
$totalPublished  = DB::countProducts(['status' => DB::PRODUCT_PUBLISHED]);
$totalUsers      = DB::countUsers('');
$totalCategories = count(DB::listCategories());

layout_head('meta.title.admin', 'fear.dev — Админка', $me);
?>
<main class="shell-page">
  <div class="section-head">
    <h1 data-i18n="admin.title">Админка</h1>
    <p class="sub" data-i18n="admin.subtitle">Управление каталогом и пользователями.</p>
  </div>

  <div class="adm-layout">
    <?php admin_sidebar('dashboard'); ?>
    <section class="panel adm-main">
      <div class="stats">
        <div class="stat-card">
          <div class="k" data-i18n="admin.stats.products">Товары</div>
          <div class="v"><?= $totalProducts ?></div>
          <div class="hint"><span data-i18n="admin.stats.published">опубликовано</span>: <?= $totalPublished ?></div>
        </div>
        <div class="stat-card">
          <div class="k" data-i18n="admin.stats.categories">Категории</div>
          <div class="v"><?= $totalCategories ?></div>
        </div>
        <div class="stat-card">
          <div class="k" data-i18n="admin.stats.users">Пользователи</div>
          <div class="v"><?= $totalUsers ?></div>
        </div>
      </div>

      <div class="quick-links">
        <a class="btn btn-primary" href="/admin/products.php?new=1" style="text-decoration:none" data-i18n="admin.quick.newProduct">Новый товар</a>
        <a class="btn btn-ghost" href="/admin/categories.php" style="text-decoration:none" data-i18n="admin.quick.manageCats">Категории</a>
        <a class="btn btn-ghost" href="/admin/users.php" style="text-decoration:none" data-i18n="admin.quick.users">Пользователи</a>
      </div>
    </section>
  </div>
</main>
<?php layout_foot(); ?>
