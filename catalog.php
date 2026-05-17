<?php
require_once __DIR__ . '/lib/bootstrap.php';

// Catalog is public — anyone can browse, even without an account.
$user = fd_current_user();
$pub  = $user ? fd_public_user($user) : null;
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#0a0a0c" />
  <title data-i18n-title="meta.title.catalog">fear.dev — Каталог</title>
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= fd_e(fd_asset('/assets/styles.css')) ?>" />
</head>
<body class="catalog-body">
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
      <span class="adm-tag" data-i18n="catalog.title">Каталог</span>
    </div>
    <nav class="adm-tabs cat-tabs" role="tablist">
      <button type="button" class="adm-tab active" data-cat="all"    data-i18n="catalog.tab.all">Все</button>
      <button type="button" class="adm-tab"        data-cat="mod"    data-i18n="catalog.tab.mods">Моды</button>
      <button type="button" class="adm-tab"        data-cat="script" data-i18n="catalog.tab.scripts">Скрипты</button>
    </nav>
    <div class="adm-top-right">
      <div class="lang-switch" role="tablist" aria-label="Language">
        <button type="button" data-lang-btn="ru">RU</button>
        <button type="button" data-lang-btn="uk">UK</button>
        <button type="button" data-lang-btn="en">EN</button>
      </div>
      <?php if ($pub): ?>
        <span class="adm-balance" id="catBalance" title="<?= fd_e($pub['email']) ?>"></span>
        <a class="btn btn-ghost btn-sm" href="/dashboard.php" data-i18n="btn.back">Кабинет</a>
        <?php if ($pub['isAdmin']): ?>
          <a class="btn btn-ghost btn-sm" href="/admin.php" data-i18n="btn.admin">Админка</a>
        <?php endif; ?>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="/index.php" data-i18n="btn.login">Войти</a>
        <a class="btn btn-primary btn-sm" href="/register.php" data-i18n="btn.register">Создать аккаунт</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="adm-main">
    <div class="adm-toolbar">
      <input class="adm-search" id="catalogSearch" type="search" data-i18n-placeholder="catalog.search" placeholder="Поиск по названию…" />
      <span class="adm-spacer"></span>
      <span class="adm-count" id="catalogCount">0</span>
    </div>

    <div class="adm-grid" id="catalogGrid"></div>
    <div class="adm-empty" id="catalogEmpty" hidden data-i18n="catalog.empty">В этой категории пока пусто.</div>
  </main>

  <!-- DETAILS MODAL -->
  <div class="modal" id="detailsModal" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-box modal-details" role="dialog" aria-modal="true">
      <button class="modal-x modal-x-float" data-close aria-label="Close">×</button>
      <div class="details-banner mod-banner" id="detailsBanner"></div>
      <div class="details-body">
        <h3 class="details-title" id="detailsTitle"></h3>
        <p class="details-description" id="detailsDescription" hidden></p>
        <div class="details-foot">
          <span class="details-price" id="detailsPrice"></span>
          <button id="detailsBuyBtn" class="btn btn-primary" data-i18n="catalog.btn.buy">Купить</button>
        </div>
      </div>
    </div>
  </div>

  <!-- PAYMENT METHODS MODAL (shown on insufficient funds) -->
  <div class="modal" id="paymentModal" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-box modal-pay" role="dialog" aria-modal="true">
      <button class="modal-x modal-x-float" data-close aria-label="Close">×</button>
      <div class="pay-head">
        <h3 class="pay-title">
          <span data-i18n="pay.title">К оплате:</span>
          <span id="payAmount"></span>
        </h3>
        <p class="pay-hint" id="payHint" data-i18n="pay.subtitle">Выберите удобный для вас метод оплаты и завершите покупку</p>
      </div>

      <div class="pay-section">
        <div class="pay-section-title" data-i18n="pay.methods">Доступные методы оплаты</div>
        <div class="pay-methods">
          <button type="button" class="pay-method" data-pay-method="balance">
            <span class="pay-method-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="6" width="18" height="13" rx="2.4" stroke="currentColor" stroke-width="1.6"/>
                <path d="M3 10h18" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span data-i18n="pay.method.balance">Баланс аккаунта</span>
          </button>
          <button type="button" class="pay-method" data-pay-method="card">
            <span class="pay-method-ico pay-method-mir" aria-hidden="true">МИР</span>
            <span data-i18n="pay.method.card">Банковские карты (РФ)</span>
          </button>
          <button type="button" class="pay-method" data-pay-method="sbp">
            <span class="pay-method-ico pay-method-sbp" aria-hidden="true">СБП</span>
            <span data-i18n="pay.method.sbp">СБП</span>
          </button>
          <button type="button" class="pay-method" data-pay-method="yoomoney">
            <span class="pay-method-ico pay-method-yoo" aria-hidden="true">Ю</span>
            <span data-i18n="pay.method.yoomoney">ЮMoney</span>
          </button>
        </div>
      </div>

      <div class="pay-foot">
        <span class="pay-error" data-i18n="pay.insufficient">Недостаточно средств</span>
        <button type="button" class="btn btn-ghost btn-sm" data-close data-i18n="btn.cancel">Отмена</button>
      </div>
    </div>
  </div>

  <script src="<?= fd_e(fd_asset('/assets/i18n.js')) ?>"></script>
  <script src="<?= fd_e(fd_asset('/assets/auth.js')) ?>"></script>
  <script src="<?= fd_e(fd_asset('/assets/page-catalog.js')) ?>"></script>
</body>
</html>
