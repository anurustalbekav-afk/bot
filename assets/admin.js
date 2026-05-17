/**
 * fear.dev — admin panel client.
 *
 * Two tabs:
 *   • Users — table with login/email/IP/joined/topups/purchases. Click "Details"
 *     to open a per-user modal with topup/purchase history and quick-add forms.
 *   • Mods  — card grid. Add/edit (pencil)/delete via a single shared form modal.
 */
(function () {
  const t = (k) => window.FD_I18N.t(k);
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const state = {
    users: [],
    mods: [],
    activeUserId: null,
  };

  // --- helpers -------------------------------------------------------------

  const escape = (s) => String(s == null ? '' : s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

  const formatDate = (iso) => {
    if (!iso) return '—';
    try { return new Date(iso).toLocaleString(window.FD_I18N.getLocale()); }
    catch { return iso; }
  };

  const formatMoney = (amount, currency) => {
    if (amount == null) return '—';
    try {
      return new Intl.NumberFormat(window.FD_I18N.getLocale(), {
        style: 'currency',
        currency: currency || 'USD',
        maximumFractionDigits: 2,
      }).format(Number(amount));
    } catch {
      return `${Number(amount).toFixed(2)} ${currency || ''}`.trim();
    }
  };

  const sumTopups = (u) => (u.topups || []).reduce((acc, t) => acc + Number(t.amount || 0), 0);

  const toast = (kind, key) => {
    const el = document.createElement('div');
    el.className = `toast ${kind}`;
    el.textContent = t(key);
    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 250); }, 2200);
  };

  // --- modals --------------------------------------------------------------

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

  // --- tabs ----------------------------------------------------------------

  $$('.adm-tab').forEach((btn) => {
    btn.addEventListener('click', () => {
      const tab = btn.getAttribute('data-tab');
      $$('.adm-tab').forEach((b) => b.classList.toggle('active', b === btn));
      $$('.adm-pane').forEach((p) => { p.hidden = p.getAttribute('data-pane') !== tab; });
      if (tab === 'mods' && state.mods.length === 0) loadMods();
    });
  });

  // --- users ---------------------------------------------------------------

  async function loadUsers() {
    const r = await FD_AUTH.getJson('/api/admin/users.php');
    if (!r.ok || !r.body || !r.body.ok) {
      toast('error', FD_AUTH.errorKey(r.body && r.body.error));
      return;
    }
    state.users = r.body.users || [];
    renderUsers();
  }

  function renderUsers() {
    const q = ($('#userSearch').value || '').trim().toLowerCase();
    const tbody = $('#usersTable tbody');
    tbody.innerHTML = '';
    let shown = 0;

    for (const u of state.users) {
      const hay = [u.login, u.email, u.ip, u.lastIp].filter(Boolean).join(' ').toLowerCase();
      if (q && !hay.includes(q)) continue;
      shown++;

      const topupsTotal = sumTopups(u);
      const topupCcy = (u.topups[0] && u.topups[0].currency) || 'USD';
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>
          <div class="adm-cell-strong">${escape(u.login)}${u.isAdmin ? ' <span class="adm-pill">admin</span>' : ''}</div>
          <div class="adm-cell-mute">${escape(u.id.slice(0, 8))}…</div>
        </td>
        <td>${escape(u.email)}</td>
        <td>
          <div>${escape(u.lastIp || u.ip || '—')}</div>
          ${u.ip && u.ip !== u.lastIp ? `<div class="adm-cell-mute">${escape(u.ip)}</div>` : ''}
        </td>
        <td>${formatDate(u.createdAt)}</td>
        <td>
          <div class="adm-cell-strong">${formatMoney(topupsTotal, topupCcy)}</div>
          <div class="adm-cell-mute">${u.topups.length}×</div>
        </td>
        <td>
          <div class="adm-cell-strong">${u.purchases.length}</div>
          <div class="adm-cell-mute">${(u.purchases.filter(p => p.kind === 'mod').length)} ${t('admin.user.kind.mod')} / ${(u.purchases.filter(p => p.kind === 'script').length)} ${t('admin.user.kind.script')}</div>
        </td>
        <td><button class="btn btn-ghost btn-sm" data-user-details="${escape(u.id)}">${escape(t('admin.users.action.details'))}</button></td>
      `;
      tbody.appendChild(tr);
    }
    $('#userCount').textContent = String(shown);
  }

  $('#userSearch').addEventListener('input', renderUsers);

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-user-details]');
    if (!btn) return;
    openUserModal(btn.getAttribute('data-user-details'));
  });

  function openUserModal(userId) {
    const u = state.users.find((x) => x.id === userId);
    if (!u) return;
    state.activeUserId = userId;
    $('#userModalTitle').textContent = `${u.login} — ${u.email}`;

    const topups = (u.topups || []).slice().reverse();
    const purchases = (u.purchases || []).slice().reverse();
    const topupCcy = (u.topups[0] && u.topups[0].currency) || 'USD';

    $('#userModalBody').innerHTML = `
      <div class="adm-detail-grid">
        <div class="adm-cell"><div class="k">ID</div><div class="v">${escape(u.id)}</div></div>
        <div class="adm-cell"><div class="k">Email</div><div class="v">${escape(u.email)}</div></div>
        <div class="adm-cell"><div class="k">${escape(t('admin.users.col.login'))}</div><div class="v">${escape(u.login)}</div></div>
        <div class="adm-cell"><div class="k">${escape(t('admin.user.firstIp'))}</div><div class="v">${escape(u.ip || '—')}</div></div>
        <div class="adm-cell"><div class="k">${escape(t('admin.user.lastIp'))}</div><div class="v">${escape(u.lastIp || '—')}</div></div>
        <div class="adm-cell"><div class="k">${escape(t('admin.users.col.created'))}</div><div class="v">${escape(formatDate(u.createdAt))}</div></div>
      </div>

      <div class="adm-section">
        <div class="adm-section-head">
          <h4>${escape(t('admin.user.section.topups'))} <span class="adm-cell-mute">· ${formatMoney(sumTopups(u), topupCcy)}</span></h4>
          <button class="btn btn-ghost btn-sm" id="btnAddTopup">${escape(t('admin.user.add.topup'))}</button>
        </div>
        ${topups.length === 0 ? `<div class="adm-empty-sm">${escape(t('admin.user.empty.topups'))}</div>` :
          `<table class="adm-table">
            <thead><tr><th>${escape(t('admin.users.col.created'))}</th><th>${escape(t('admin.topup.amount'))}</th><th>${escape(t('admin.topup.method'))}</th><th>${escape(t('admin.topup.note'))}</th></tr></thead>
            <tbody>${topups.map(tt => `
              <tr>
                <td>${escape(formatDate(tt.createdAt))}</td>
                <td>${escape(formatMoney(tt.amount, tt.currency))}</td>
                <td>${escape(tt.method || '—')}</td>
                <td>${escape(tt.note || '—')}</td>
              </tr>`).join('')}</tbody>
           </table>`}
      </div>

      <div class="adm-section">
        <div class="adm-section-head">
          <h4>${escape(t('admin.user.section.purchases'))} <span class="adm-cell-mute">· ${purchases.length}</span></h4>
          <button class="btn btn-ghost btn-sm" id="btnAddPurchase">${escape(t('admin.user.add.purchase'))}</button>
        </div>
        ${purchases.length === 0 ? `<div class="adm-empty-sm">${escape(t('admin.user.empty.purchases'))}</div>` :
          `<table class="adm-table">
            <thead><tr><th>${escape(t('admin.users.col.created'))}</th><th>${escape(t('admin.mods.field.title'))}</th><th>${escape(t('admin.mods.field.type'))}</th><th>${escape(t('admin.mods.field.price'))}</th></tr></thead>
            <tbody>${purchases.map(p => `
              <tr>
                <td>${escape(formatDate(p.createdAt))}</td>
                <td>${escape(p.title)}</td>
                <td><span class="adm-pill">${escape(p.kind === 'script' ? t('admin.user.kind.script') : t('admin.user.kind.mod'))}</span></td>
                <td>${escape(formatMoney(p.price, p.currency))}</td>
              </tr>`).join('')}</tbody>
           </table>`}
      </div>
    `;

    $('#btnAddTopup').addEventListener('click', () => promptTopup(u));
    $('#btnAddPurchase').addEventListener('click', () => promptPurchase(u));
    openModal('userModal');
  }

  async function promptTopup(u) {
    const amount = window.prompt(`${t('admin.topup.title')}\n${t('admin.topup.amount')}:`);
    if (amount == null || amount.trim() === '') return;
    const currency = window.prompt(`${t('admin.topup.currency')}:`, 'USD') || 'USD';
    const method = window.prompt(`${t('admin.topup.method')}:`, '') || '';
    const r = await FD_AUTH.postJson('/api/admin/topup.php', {
      userId: u.id,
      amount: Number(amount),
      currency: currency.toUpperCase(),
      method: method || null,
    });
    if (r.ok && r.body && r.body.ok) {
      toast('ok', 'ok.saved');
      await loadUsers();
      openUserModal(u.id);
    } else {
      toast('error', FD_AUTH.errorKey(r.body && r.body.error));
    }
  }

  async function promptPurchase(u) {
    const useMod = state.mods.length > 0 && window.confirm(t('admin.purchase.mod') + '?');
    let payload;
    if (useMod) {
      const list = state.mods.map((m, i) => `${i + 1}. ${m.title} — ${formatMoney(m.price, m.currency)}`).join('\n');
      const idx = Number(window.prompt(`${t('admin.purchase.mod')}\n${list}`));
      const mod = state.mods[idx - 1];
      if (!mod) return;
      payload = { userId: u.id, modId: mod.id };
    } else {
      const title = window.prompt(`${t('admin.purchase.custom')}:`);
      if (!title) return;
      const price = Number(window.prompt(`${t('admin.mods.field.price')}:`, '0')) || 0;
      const currency = (window.prompt(`${t('admin.mods.field.currency')}:`, 'USD') || 'USD').toUpperCase();
      const kind = (window.prompt(`${t('admin.mods.field.type')} (mod / script):`, 'mod') || 'mod').trim();
      payload = { userId: u.id, title, price, currency, kind };
    }
    const r = await FD_AUTH.postJson('/api/admin/purchase.php', payload);
    if (r.ok && r.body && r.body.ok) {
      toast('ok', 'ok.saved');
      await loadUsers();
      openUserModal(u.id);
    } else {
      toast('error', FD_AUTH.errorKey(r.body && r.body.error));
    }
  }

  // --- mods ----------------------------------------------------------------

  async function loadMods() {
    const r = await FD_AUTH.getJson('/api/admin/mods.php');
    if (!r.ok || !r.body || !r.body.ok) {
      toast('error', FD_AUTH.errorKey(r.body && r.body.error));
      return;
    }
    state.mods = r.body.mods || [];
    renderMods();
  }

  function renderMods() {
    const grid = $('#modsGrid');
    const empty = $('#modsEmpty');
    const q = ($('#modSearch').value || '').trim().toLowerCase();
    const filtered = state.mods.filter((m) => !q || (m.title || '').toLowerCase().includes(q));
    grid.innerHTML = '';
    empty.hidden = filtered.length > 0;

    for (const m of filtered) {
      const card = document.createElement('article');
      card.className = 'mod-card';
      const fallbackChar = (m.title || '?').slice(0, 1).toUpperCase();
      card.innerHTML = `
        <div class="mod-banner">
          ${m.banner ? `<img alt="" loading="lazy" src="${escape(m.banner)}" data-fallback="${escape(fallbackChar)}" />`
                     : `<div class="mod-banner-fallback">${escape(fallbackChar)}</div>`}
          <span class="mod-pill">${escape(m.type)}</span>
        </div>
        <div class="mod-body">
          <h4 class="mod-title">${escape(m.title)}</h4>
          <a class="mod-url" href="${escape(m.url)}" target="_blank" rel="noopener noreferrer">${escape(m.url)}</a>
          <div class="mod-row">
            <span class="mod-price">${escape(formatMoney(m.price, m.currency))}</span>
            <div class="mod-actions">
              <button class="icon-btn" title="${escape(t('admin.mods.edit'))}" data-mod-edit="${escape(m.id)}" aria-label="${escape(t('admin.mods.edit'))}">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M14 6l4 4" stroke="currentColor" stroke-width="1.6"/></svg>
              </button>
              <button class="icon-btn icon-btn-danger" title="${escape(t('btn.delete'))}" data-mod-del="${escape(m.id)}" aria-label="${escape(t('btn.delete'))}">
                <svg viewBox="0 0 24 24" fill="none"><path d="M5 7h14M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M7 7l1 13a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2l1-13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
              </button>
            </div>
          </div>
        </div>
      `;
      grid.appendChild(card);
    }
  }

  $('#modSearch').addEventListener('input', renderMods);
  $('#addModBtn').addEventListener('click', () => openModForm(null));

  // Fallback for broken banner URLs (kept out of inline handlers so the strict
  // CSP `script-src 'self'` from the host can stay enabled).
  document.addEventListener('error', (e) => {
    const img = e.target;
    if (!(img instanceof HTMLImageElement) || !img.dataset.fallback) return;
    const div = document.createElement('div');
    div.className = 'mod-banner-fallback';
    div.textContent = img.dataset.fallback;
    img.replaceWith(div);
  }, true);

  document.addEventListener('click', (e) => {
    const editBtn = e.target.closest('[data-mod-edit]');
    if (editBtn) return openModForm(editBtn.getAttribute('data-mod-edit'));
    const delBtn = e.target.closest('[data-mod-del]');
    if (delBtn) return deleteMod(delBtn.getAttribute('data-mod-del'));
  });

  function openModForm(id) {
    const form = $('#modForm');
    form.reset();
    FD_AUTH.clearStatus($('#modFormStatus'));
    const titleEl = $('#modModalTitle');
    if (id) {
      const m = state.mods.find((x) => x.id === id);
      if (!m) return;
      titleEl.textContent = `${t('admin.mods.edit')} — ${m.title}`;
      form.id.value       = m.id;
      form.title.value    = m.title || '';
      form.description.value = m.description || '';
      form.banner.value   = m.banner || '';
      form.url.value      = m.url || '';
      form.price.value    = m.price ?? 0;
      form.currency.value = m.currency || 'USD';
      form.type.value     = m.type === 'script' ? 'script' : 'mod';
    } else {
      titleEl.textContent = t('admin.mods.create');
      form.currency.value = 'USD';
      form.type.value = 'mod';
    }
    openModal('modModal');
    form.title.focus();
  }

  $('#modForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const status = $('#modFormStatus');
    FD_AUTH.clearStatus(status);

    const payload = {
      title:       form.title.value.trim(),
      description: form.description.value.trim(),
      banner:      form.banner.value.trim(),
      url:         form.url.value.trim(),
      price:    Number(form.price.value),
      currency: (form.currency.value || 'USD').toUpperCase(),
      type:     form.type.value,
    };

    const id = form.id.value;
    const r = id
      ? await FD_AUTH.patchJson(`/api/admin/mods.php?id=${encodeURIComponent(id)}`, payload)
      : await FD_AUTH.postJson('/api/admin/mods.php', payload);

    if (r.ok && r.body && r.body.ok) {
      closeModal('modModal');
      toast('ok', 'ok.saved');
      await loadMods();
    } else {
      FD_AUTH.showStatus(status, 'error', FD_AUTH.errorKey(r.body && r.body.error));
    }
  });

  async function deleteMod(id) {
    if (!window.confirm(t('admin.mods.confirm.delete'))) return;
    const r = await FD_AUTH.deleteJson(`/api/admin/mods.php?id=${encodeURIComponent(id)}`);
    if (r.ok && r.body && r.body.ok) {
      toast('ok', 'ok.saved');
      await loadMods();
    } else {
      toast('error', FD_AUTH.errorKey(r.body && r.body.error));
    }
  }

  // --- boot ----------------------------------------------------------------

  document.addEventListener('DOMContentLoaded', async () => {
    window.FD_I18N.mount();
    document.addEventListener('fd:locale', () => { renderUsers(); renderMods(); });
    await Promise.all([loadUsers(), loadMods()]);
  });
})();
