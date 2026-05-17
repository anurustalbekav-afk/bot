<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/layout.php';

$me = Auth::currentUser();
layout_head('meta.title.catalog', 'fear.dev — Каталог', $me);
?>
<main class="shell catalog-page">
  <section class="catalog-head">
    <h1 data-i18n="catalog.title">Каталог</h1>
    <p class="sub" data-i18n="catalog.subtitle">Моды, скрипты и карты для SAMP.</p>
  </section>

  <section class="catalog-toolbar">
    <div class="cat-tabs" id="catTabs">
      <button type="button" class="cat-tab active" data-category="" data-i18n="catalog.all">Все</button>
    </div>
    <div class="cat-filters">
      <input type="search" id="search" data-i18n-placeholder="catalog.search" placeholder="Поиск" />
      <select id="sort">
        <option value="new"        data-i18n="catalog.sort.new">Сначала новые</option>
        <option value="price_asc"  data-i18n="catalog.sort.price_asc">Дешевле</option>
        <option value="price_desc" data-i18n="catalog.sort.price_desc">Дороже</option>
        <option value="title"      data-i18n="catalog.sort.title">По названию</option>
      </select>
    </div>
  </section>

  <div id="status" class="status" role="status" aria-live="polite"></div>

  <section class="grid" id="productsGrid"></section>

  <div class="pager">
    <button id="prevBtn" type="button">←</button>
    <span id="pageInfo">1 / 1</span>
    <button id="nextBtn" type="button">→</button>
  </div>
</main>
<script src="/assets/catalog.js"></script>
<?php layout_foot(); ?>
