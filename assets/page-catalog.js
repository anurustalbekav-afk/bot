/**
 * fear.dev — public catalog client.
 *
 * Tabs at the top filter by `type`: all / mod / script. Cards mirror the
 * admin grid look but expose only safe fields and a "Buy / Get" CTA that
 * opens the mod URL in a new tab. Recording the actual purchase still goes
 * through the admin endpoint — this client is read-only on purpose.
 */
(function () {
  const t = (k) => window.FD_I18N.t(k);
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const state = { mods: [], filter: 'all' };

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

  async function loadMods() {
    const r = await FD_AUTH.getJson('/api/mods.php');
    state.mods = (r.body && r.body.ok && r.body.mods) || [];
    render();
  }

  function render() {
    const grid = $('#catalogGrid');
    const empty = $('#catalogEmpty');
    const count = $('#catalogCount');
    const q = ($('#catalogSearch').value || '').trim().toLowerCase();

    const filtered = state.mods.filter((m) => {
      if (state.filter !== 'all' && m.type !== state.filter) return false;
      if (q && !(m.title || '').toLowerCase().includes(q)) return false;
      return true;
    });

    grid.innerHTML = '';
    empty.hidden = filtered.length > 0;
    count.textContent = String(filtered.length);

    for (const m of filtered) {
      const fallbackChar = (m.title || '?').slice(0, 1).toUpperCase();
      const card = document.createElement('article');
      card.className = 'mod-card cat-card';
      card.innerHTML = `
        <div class="mod-banner">
          ${m.banner ? `<img alt="" loading="lazy" src="${escape(m.banner)}" data-fallback="${escape(fallbackChar)}" />`
                     : `<div class="mod-banner-fallback">${escape(fallbackChar)}</div>`}
          <span class="mod-pill">${escape(m.type === 'script' ? t('catalog.kind.script') : t('catalog.kind.mod'))}</span>
        </div>
        <div class="mod-body">
          <h4 class="mod-title">${escape(m.title)}</h4>
          <div class="mod-row">
            <span class="mod-price">${escape(formatMoney(m.price, m.currency))}</span>
            <a class="btn btn-primary btn-sm" href="${escape(m.url)}" target="_blank" rel="noopener noreferrer">
              ${escape(t('catalog.btn.buy'))}
            </a>
          </div>
        </div>
      `;
      grid.appendChild(card);
    }
  }

  // Replace broken banners with a colored fallback (capture-phase listener
  // because <img> error events don't bubble).
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

    $$('.cat-tabs .adm-tab').forEach((btn) => {
      btn.addEventListener('click', () => {
        $$('.cat-tabs .adm-tab').forEach((b) => b.classList.toggle('active', b === btn));
        state.filter = btn.getAttribute('data-cat');
        render();
      });
    });

    $('#catalogSearch').addEventListener('input', render);
    document.addEventListener('fd:locale', render);

    loadMods();
  });
})();
