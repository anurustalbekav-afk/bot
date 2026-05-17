/**
 * fear.dev — public catalog client.
 *
 * Tabs at the top filter by `type`: all / mod / script. Clicking a card or
 * its "View" button opens a details modal (banner / title / description /
 * price / Buy). The Buy button charges the user's balance via /api/buy.php.
 * If the server returns 402 insufficient_funds, we open a payment modal
 * that mirrors the screenshot the customer sent.
 */
(function () {
  const t = (k) => window.FD_I18N.t(k);
  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const state = {
    mods: [],
    filter: 'all',
    me: null,             // { id, login, isAdmin, balance, ... } or null for guests
    ownedModIds: new Set(), // mods the current user has already bought
    activeMod: null,
  };

  // --- helpers -------------------------------------------------------------

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

  const toast = (kind, key) => {
    const el = document.createElement('div');
    el.className = `toast ${kind}`;
    el.textContent = t(key);
    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 250); }, 2500);
  };

  // --- modal plumbing ------------------------------------------------------

  function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.hidden = false;
    document.body.classList.add('no-scroll');
  }
  function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.hidden = true;
    if (!$$('.modal:not([hidden])').length) document.body.classList.remove('no-scroll');
  }
  document.addEventListener('click', (e) => {
    const close = e.target.closest('[data-close]');
    if (!close) return;
    const modal = close.closest('.modal');
    if (modal) closeModal(modal.id);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const open = $$('.modal:not([hidden])').pop();
    if (open) closeModal(open.id);
  });

  // --- data ----------------------------------------------------------------

  async function loadMods() {
    const r = await FD_AUTH.getJson('/api/mods.php');
    state.mods = (r.body && r.body.ok && r.body.mods) || [];
    render();
  }

  async function loadMe() {
    const r = await FD_AUTH.getJson('/api/me.php');
    state.me = (r.ok && r.body && r.body.ok) ? r.body.user : null;
    updateBalanceBadge();
    if (state.me) await loadOwned();
  }

  async function loadOwned() {
    const r = await FD_AUTH.getJson('/api/purchases.php');
    state.ownedModIds = new Set(
      ((r.body && r.body.ok && r.body.purchases) || [])
        .map((p) => p.modId)
        .filter(Boolean),
    );
  }

  function updateBalanceBadge() {
    const el = $('#catBalance');
    if (!el) return;
    if (!state.me) { el.hidden = true; return; }
    el.hidden = false;
    el.textContent = formatMoney(state.me.balance, state.me.currency || 'USD');
  }

  // --- rendering -----------------------------------------------------------

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
      const owned = state.ownedModIds.has(m.id);
      const card = document.createElement('article');
      card.className = 'mod-card cat-card' + (owned ? ' is-owned' : '');
      card.dataset.modId = m.id;
      card.innerHTML = `
        <div class="mod-banner">
          ${m.banner ? `<img alt="" loading="lazy" src="${escape(m.banner)}" data-fallback="${escape(fallbackChar)}" />`
                     : `<div class="mod-banner-fallback">${escape(fallbackChar)}</div>`}
          <span class="mod-pill">${escape(m.type === 'script' ? t('catalog.kind.script') : t('catalog.kind.mod'))}</span>
          ${owned ? `<span class="mod-pill mod-pill-owned">${escape(t('catalog.owned'))}</span>` : ''}
        </div>
        <div class="mod-body">
          <h4 class="mod-title">${escape(m.title)}</h4>
          <div class="mod-row">
            <span class="mod-price">${escape(formatMoney(m.price, m.currency))}</span>
            <button type="button" class="btn btn-primary btn-sm" data-mod-view="${escape(m.id)}">
              ${escape(t(owned ? 'catalog.btn.open' : 'catalog.btn.view'))}
            </button>
          </div>
        </div>
      `;
      grid.appendChild(card);
    }
  }

  // --- broken-banner fallback (capture phase: error doesn't bubble) --------

  document.addEventListener('error', (e) => {
    const img = e.target;
    if (!(img instanceof HTMLImageElement) || !img.dataset.fallback) return;
    const div = document.createElement('div');
    div.className = 'mod-banner-fallback';
    div.textContent = img.dataset.fallback;
    img.replaceWith(div);
  }, true);

  // --- details modal -------------------------------------------------------

  function openDetails(modId) {
    const m = state.mods.find((x) => x.id === modId);
    if (!m) return;
    state.activeMod = m;

    const fallbackChar = (m.title || '?').slice(0, 1).toUpperCase();
    $('#detailsBanner').innerHTML = m.banner
      ? `<img alt="" src="${escape(m.banner)}" data-fallback="${escape(fallbackChar)}" />`
      : `<div class="mod-banner-fallback">${escape(fallbackChar)}</div>`;
    $('#detailsBanner').insertAdjacentHTML('beforeend',
      `<span class="mod-pill">${escape(m.type === 'script' ? t('catalog.kind.script') : t('catalog.kind.mod'))}</span>`);

    $('#detailsTitle').textContent = m.title || '';
    $('#detailsPrice').textContent = formatMoney(m.price, m.currency);

    const desc = (m.description || '').trim();
    const descEl = $('#detailsDescription');
    if (desc) {
      descEl.hidden = false;
      // Show description as plain text with line breaks preserved.
      descEl.textContent = desc;
    } else {
      descEl.hidden = true;
      descEl.textContent = '';
    }

    // Swap the "Buy" CTA for "Download" when this mod is already owned.
    const buyBtn = $('#detailsBuyBtn');
    buyBtn.disabled = false;
    buyBtn.dataset.modId = m.id;
    if (state.ownedModIds.has(m.id)) {
      buyBtn.textContent = t('dash.purchase.download');
      buyBtn.dataset.action = 'download';
    } else {
      buyBtn.textContent = t('catalog.btn.buy');
      buyBtn.dataset.action = 'buy';
    }

    openModal('detailsModal');
  }

  document.addEventListener('click', (e) => {
    const view = e.target.closest('[data-mod-view]');
    if (view) {
      e.preventDefault();
      openDetails(view.getAttribute('data-mod-view'));
      return;
    }
    // Click anywhere on the card body (excluding buttons) opens details too.
    const card = e.target.closest('.cat-card');
    if (card && !e.target.closest('button, a')) {
      openDetails(card.dataset.modId);
    }
  });

  // --- buy flow ------------------------------------------------------------

  async function buy(mod) {
    const buyBtn = $('#detailsBuyBtn');

    // Guest -> redirect to login with return-to.
    if (!state.me) {
      window.location.href = '/index.php';
      return;
    }

    buyBtn.disabled = true;
    try {
      const r = await FD_AUTH.postJson('/api/buy.php', { modId: mod.id });
      if (r.status === 402) {
        // Insufficient funds: show the payment-methods modal in our theme.
        openPaymentModal({
          required: r.body && r.body.required != null ? r.body.required : mod.price,
          balance:  r.body && r.body.balance  != null ? r.body.balance  : (state.me?.balance ?? 0),
          currency: mod.currency || 'USD',
        });
        return;
      }
      if (r.ok && r.body && r.body.ok) {
        // Update balance + owned set locally, close modal, toast.
        state.me.balance = r.body.balance;
        if (mod.id) state.ownedModIds.add(mod.id);
        updateBalanceBadge();
        closeModal('detailsModal');
        toast('ok', 'catalog.toast.purchased');
        render();
        return;
      }
      if (r.status === 409 && r.body && r.body.error === 'already_purchased') {
        // Server says we already own it — sync local set and switch to download.
        if (mod.id) state.ownedModIds.add(mod.id);
        render();
        // Reopen details so the button label switches to "Download".
        openDetails(mod.id);
        return;
      }
      toast('error', FD_AUTH.errorKey(r.body && r.body.error));
    } catch {
      toast('error', 'err.network');
    } finally {
      buyBtn.disabled = false;
    }
  }

  $('#detailsBuyBtn').addEventListener('click', (e) => {
    e.preventDefault();
    const m = state.activeMod;
    if (!m) return;
    // "Download" mode: just open the mod URL in a new tab.
    if (e.currentTarget.dataset.action === 'download') {
      if (m.url) window.open(m.url, '_blank', 'noopener,noreferrer');
      return;
    }
    buy(m);
  });

  // --- payment / insufficient-funds modal ---------------------------------

  function openPaymentModal({ required, balance, currency }) {
    $('#payAmount').textContent = formatMoney(required, currency);
    $('#payHint').textContent =
      t('pay.subtitle')
        .replace('{balance}', formatMoney(balance, currency))
        .replace('{required}', formatMoney(required, currency));
    openModal('paymentModal');
  }

  // Method buttons are disabled placeholders for now — the real payment
  // gateway will be wired into them later. They visually mirror the user's
  // reference screenshot (account balance, RU bank cards, SBP, YooMoney).
  $$('#paymentModal [data-pay-method]').forEach((btn) => {
    btn.addEventListener('click', () => {
      toast('error', 'pay.method.unavailable');
    });
  });

  // --- search / tabs / boot -----------------------------------------------

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
    document.addEventListener('fd:locale', () => {
      render();
      // Re-render the open details modal so labels refresh.
      if (!$('#detailsModal').hidden && state.activeMod) openDetails(state.activeMod.id);
    });

    Promise.all([loadMods(), loadMe()]).catch(() => {});
  });
})();
