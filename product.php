<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/layout.php';

$me   = Auth::currentUser();
$slug = trim((string)($_GET['slug'] ?? ''));

$product = $slug !== '' ? DB::findProductBySlug($slug) : null;
$canSeeDraft = Auth::isAdmin($me);
$visible = $product && ($product['status'] === DB::PRODUCT_PUBLISHED || $canSeeDraft);

if (!$visible) {
    http_response_code(404);
    layout_head('meta.title.notfound', 'fear.dev — 404', $me);
    ?>
    <main class="shell">
      <section class="card-empty">
        <h1>404</h1>
        <p data-i18n="err.product_not_found">Товар не найден.</p>
        <p><a href="/catalog.php" data-i18n="catalog.toCatalog">В каталог</a></p>
      </section>
    </main>
    <?php
    layout_foot();
    exit;
}

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$priceMajor = number_format($product['price_cents'] / 100, 2, '.', ' ');

layout_head('meta.title.product', $product['title'], $me);
?>
<main class="shell product-page">
  <nav class="crumbs">
    <a href="/catalog.php" data-i18n="catalog.title">Каталог</a>
    <?php if ($product['category_slug']): ?>
      <span>›</span>
      <a href="/catalog.php?category=<?= $h($product['category_slug']) ?>"><?= $h($product['category_title']) ?></a>
    <?php endif; ?>
    <span>›</span>
    <span><?= $h($product['title']) ?></span>
  </nav>

  <article class="product">
    <div class="product-media">
      <?php if ($product['image_url']): ?>
        <img src="<?= $h($product['image_url']) ?>" alt="<?= $h($product['title']) ?>" />
      <?php else: ?>
        <div class="media-stub" aria-hidden="true"></div>
      <?php endif; ?>
    </div>

    <div class="product-info">
      <?php if ($product['status'] !== DB::PRODUCT_PUBLISHED): ?>
        <div class="badge draft" data-i18n="catalog.draft">Черновик</div>
      <?php endif; ?>
      <h1><?= $h($product['title']) ?></h1>
      <?php if (!empty($product['summary'])): ?>
        <p class="summary"><?= $h($product['summary']) ?></p>
      <?php endif; ?>

      <div class="price-row">
        <div class="price"><?= $priceMajor ?> <span class="cur"><?= $h($product['currency']) ?></span></div>
        <button type="button" class="btn btn-primary" disabled
                title="Скоро" data-i18n="btn.buy">Купить</button>
      </div>

      <?php if (!empty($product['description'])): ?>
        <div class="description"><?= nl2br($h((string)$product['description'])) ?></div>
      <?php endif; ?>
    </div>
  </article>
</main>
<?php layout_foot(); ?>
