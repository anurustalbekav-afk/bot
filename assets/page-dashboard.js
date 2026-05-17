// Wiring for /dashboard.php.
(function () {
  const t = (k) => window.FD_I18N.t(k);
  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const escape = (s) => String(s == null ? '' : s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

  const formatMoney = (amount, currency) => {
    if (amount == null) return '—';
    try {
      return new Intl.NumberFormat(window.FD_I18N.getLocale(), {
        style: 'currency', currency: currency || 'USD', maximumFractionDigits: 2,
      }).format(Number(amount));
    } catch {
      return `${Number(amount).toFixed(2)} ${currency || ''}`.trim();
    }
  };

  const formatDate = (iso) => {
    if (!iso) return '—';
    try { return new Date(iso).toLocaleString(window.FD_I18N.getLocale()); }
    catch { return iso; }
  };

  let purchasesLoaded = false;
  async function loadPurchases() {
    if (purchasesLoaded) return;
    purchasesLoaded = true;

    const list = $('#purchasesList');
    const empty = $('#purchasesEmpty');
    list.innerHTML = '';

    const r = await FD_AUTH.getJson('/api/purchases.php');
    const items = (r.ok && r.body && r.body.ok && r.body.purchases) || [];

    empty.hidden = items.length > 0;

    for (const p of items) {
      const fallbackChar = (p.title || '?').slice(0, 1).toUpperCase();
      const card = document.createElement('article');
      card.className = 'mod-card cat-card';
      card.innerHTML = `
        <div class="mod-banner">
          ${p.banner ? `<img alt="" loading="lazy" src="${escape(p.banner)}" data-fallback="${escape(fallbackChar)}" />`
                     : `<div class="mod-banner-fallback">${escape(fallbackChar)}</div>`}
          <span class="mod-pill">${escape(p.kind === 'script' ? t('catalog.kind.script') : t('catalog.kind.mod'))}</span>
        </div>
        <div class="mod-body">
          <h4 class="mod-title">${escape(p.title)}</h4>
          <div class="mod-cell-mute">${escape(formatDate(p.createdAt))} · ${escape(formatMoney(p.price, p.currency))}</div>
          <div class="mod-row">
            <span class="mod-price">${escape(formatMoney(p.price, p.currency))}</span>
            ${p.url
              ? `<a class="btn btn-primary btn-sm" href="${escape(p.url)}" target="_blank" rel="noopener noreferrer">${escape(t('dash.purchase.download'))}</a>`
              : ''}
          </div>
        </div>
      `;
      list.appendChild(card);
    }
  }

  // Capture-phase handler so broken images get a colored fallback letter.
  document.addEventListener('error', (e) => {
    const img = e.target;
    if (!(img instanceof HTMLImageElement) || !img.dataset.fallback) return;
    const div = document.createElement('div');
    div.className = 'mod-banner-fallback';
    div.textContent = img.dataset.fallback;
    img.replaceWith(div);
  }, true);

  document.addEventListener('DOMContentLoaded', () => {
    window.FD_I18N.mount();

    // Localize the registration date if present.
    const created = $('#userCreated');
    if (created && created.textContent) {
      try { created.textContent = new Date(created.textContent).toLocaleString(window.FD_I18N.getLocale()); } catch {}
    }

    // Tab switching.
    $$('.adm-tab[data-dash-tab]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const tab = btn.getAttribute('data-dash-tab');
        $$('.adm-tab[data-dash-tab]').forEach((b) => b.classList.toggle('active', b === btn));
        $$('.adm-pane[data-dash-pane]').forEach((p) => {
          p.hidden = p.getAttribute('data-dash-pane') !== tab;
        });
        if (tab === 'purchases') loadPurchases();
      });
    });

    // Allow opening purchases tab directly via #purchases or ?tab=purchases.
    if (location.hash === '#purchases' || (new URLSearchParams(location.search)).get('tab') === 'purchases') {
      const btn = $('.adm-tab[data-dash-tab="purchases"]');
      if (btn) btn.click();
    }

    const logoutBtn = $('#logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', async () => {
        await FD_AUTH.postJson('/api/logout.php', {});
        window.location.replace('/index.php');
      });
    }
  });
})();
