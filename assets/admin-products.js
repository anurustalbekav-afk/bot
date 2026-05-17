// fear.dev — админ-товары
(function () {
  const PAGE_SIZE = 25;
  const $ = (id) => document.getElementById(id);
  const t = (k) => window.FD_I18N && window.FD_I18N.t ? window.FD_I18N.t(k) : k;
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));

  const state = {
    search:    '',
    status:    '',
    category:  '',
    offset:    0,
    total:     0,
    debounce:  null,
  };

  function setStatus(elId, kind, key) {
    const el = $(elId);
    if (!el) return;
    if (!key) { el.textContent = ''; el.className = 'status'; return; }
    el.textContent = t(key);
    el.className = 'status ' + (kind === 'error' ? 'error' : 'ok');
  }

  function fmtPrice(cents, currency) { return (cents/100).toFixed(2) + ' ' + currency; }

  async function load() {
    setStatus('status', null, null);
    const q = new URLSearchParams({
      include_drafts: '1',
      limit:  String(PAGE_SIZE),
      offset: String(state.offset),
    });
    if (state.search)   q.set('search',   state.search);
    if (state.category) q.set('category', state.category);

    try {
      const res = await fetch('/api/products.php?' + q.toString(), { credentials: 'same-origin' });
      const body = await res.json();
      if (!body.ok) { setStatus('status', 'error', 'err.unknown'); return; }
      // Фильтр по статусу — на клиенте, чтобы не плодить ещё один параметр API.
      let products = body.products;
      if (state.status) products = products.filter((p) => p.status === state.status);
      state.total = state.status ? products.length : body.total;
      render(products);
    } catch {
      setStatus('status', 'error', 'err.network');
    }
  }

  function render(products) {
    const tbody = $('productRows');
    if (!products.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="empty">—</td></tr>';
    } else {
      tbody.innerHTML = products.map(rowHtml).join('');
      bind(tbody);
    }
    $('totalCount').textContent = (t('admin.total') || 'Всего:') + ' ' + state.total;
    const page  = Math.floor(state.offset / PAGE_SIZE) + 1;
    const pages = Math.max(1, Math.ceil(state.total / PAGE_SIZE));
    $('pageInfo').textContent = page + ' / ' + pages;
    $('prevBtn').disabled = state.offset <= 0;
    $('nextBtn').disabled = state.offset + PAGE_SIZE >= state.total;
  }

  function rowHtml(p) {
    const statusBadge = p.status === 'published'
      ? '<span class="role-badge role-admin">' + esc(t('admin.products.status.published') || 'published') + '</span>'
      : '<span class="role-badge role-user">'  + esc(t('admin.products.status.draft')     || 'draft')     + '</span>';
    return `
      <tr data-id="${p.id}">
        <td>
          <a href="/product.php?slug=${encodeURIComponent(p.slug)}" target="_blank">
            <strong>${esc(p.title)}</strong>
          </a>
          <div class="muted">${esc(p.slug)}</div>
        </td>
        <td>${esc(p.category_title || '—')}</td>
        <td>${fmtPrice(p.price_cents, p.currency)}</td>
        <td>${statusBadge}</td>
        <td>
          <button class="btn btn-ghost" data-action="edit">✎</button>
          <button class="btn btn-ghost" data-action="toggle">${p.status === 'published' ? '⏸' : '▶'}</button>
          <button class="danger" data-action="delete">🗑</button>
        </td>
      </tr>`;
  }

  function bind(tbody) {
    tbody.querySelectorAll('button[data-action]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        const tr = e.target.closest('tr');
        const id = +tr.dataset.id;
        const action = btn.dataset.action;

        if (action === 'edit') {
          openModalForEdit(id);
          return;
        }
        if (action === 'toggle') {
          const isPublished = tr.querySelector('.role-admin') !== null;
          const r = await FD_AUTH.postJson('/api/admin/products.php', {
            action: isPublished ? 'unpublish' : 'publish', id,
          });
          if (r.ok && r.body && r.body.ok) { setStatus('status', 'ok', 'ok.saved'); load(); }
          else setStatus('status', 'error', (r.body && r.body.error) ? ('err.' + r.body.error) : 'err.unknown');
          return;
        }
        if (action === 'delete') {
          const title = tr.querySelector('strong').textContent;
          if (!confirm((t('admin.products.confirm_delete') || 'Удалить товар?') + '\n\n' + title)) return;
          const r = await FD_AUTH.postJson('/api/admin/products.php', { action: 'delete', id });
          if (r.ok && r.body && r.body.ok) { setStatus('status', 'ok', 'ok.deleted'); load(); }
          else setStatus('status', 'error', (r.body && r.body.error) ? ('err.' + r.body.error) : 'err.unknown');
        }
      });
    });
  }

  // ============ модалка ============
  function openModalForCreate() {
    $('modalTitle').textContent = t('admin.products.new') || 'Новый товар';
    const form = $('productForm');
    form.reset();
    form.id.value = '';
    form.status.value = 'draft';
    form.currency.value = 'USD';
    setStatus('formStatus', null, null);
    $('productModal').hidden = false;
    setTimeout(() => form.title.focus(), 50);
  }
  async function openModalForEdit(id) {
    setStatus('formStatus', null, null);
    const res = await fetch('/api/products/get.php?id=' + id, { credentials: 'same-origin' });
    const body = await res.json();
    if (!body.ok) { setStatus('status', 'error', (body.error ? 'err.' + body.error : 'err.unknown')); return; }
    const p = body.product;
    $('modalTitle').textContent = (t('admin.products.edit') || 'Редактирование') + ': ' + p.title;
    const form = $('productForm');
    form.id.value          = p.id;
    form.title.value       = p.title || '';
    form.slug.value        = p.slug || '';
    form.category_id.value = p.category_id ? String(p.category_id) : '';
    form.price.value       = (p.price_cents / 100).toFixed(2);
    form.currency.value    = p.currency || 'USD';
    form.summary.value     = p.summary || '';
    form.image_url.value   = p.image_url || '';
    form.description.value = p.description || '';
    form.status.value      = p.status || 'draft';
    $('productModal').hidden = false;
    setTimeout(() => form.title.focus(), 50);
  }
  function closeModal() {
    $('productModal').hidden = true;
  }

  async function submitForm(e) {
    e.preventDefault();
    setStatus('formStatus', null, null);
    const form = $('productForm');
    const id   = form.id.value ? +form.id.value : null;
    const payload = {
      action: id ? 'update' : 'create',
      title:  form.title.value.trim(),
      slug:   form.slug.value.trim() || undefined,
      category_id: form.category_id.value ? +form.category_id.value : null,
      price:    +form.price.value || 0,
      currency: form.currency.value || 'USD',
      summary:  form.summary.value.trim(),
      image_url: form.image_url.value.trim(),
      description: form.description.value,
      status:   form.status.value,
    };
    if (id) payload.id = id;

    const submit = form.querySelector('button[type=submit]');
    submit.disabled = true;
    const r = await FD_AUTH.postJson('/api/admin/products.php', payload);
    submit.disabled = false;
    if (r.ok && r.body && r.body.ok) {
      setStatus('formStatus', 'ok', id ? 'ok.saved' : 'ok.created');
      closeModal();
      load();
    } else {
      setStatus('formStatus', 'error', (r.body && r.body.error) ? ('err.' + r.body.error) : 'err.unknown');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    $('newProductBtn').addEventListener('click', openModalForCreate);
    $('modalClose').addEventListener('click', closeModal);
    $('cancelBtn').addEventListener('click', closeModal);
    $('productModal').addEventListener('click', (e) => {
      if (e.target.id === 'productModal') closeModal();
    });
    $('productForm').addEventListener('submit', submitForm);

    $('search').addEventListener('input', (e) => {
      clearTimeout(state.debounce);
      state.debounce = setTimeout(() => { state.search = e.target.value.trim(); state.offset = 0; load(); }, 250);
    });
    $('filterStatus').addEventListener('change',   (e) => { state.status   = e.target.value; state.offset = 0; load(); });
    $('filterCategory').addEventListener('change', (e) => { state.category = e.target.value; state.offset = 0; load(); });
    $('prevBtn').addEventListener('click', () => { state.offset = Math.max(0, state.offset - PAGE_SIZE); load(); });
    $('nextBtn').addEventListener('click', () => { state.offset += PAGE_SIZE; load(); });

    if (window.FD_OPEN_NEW) openModalForCreate();
    load();
  });
})();
