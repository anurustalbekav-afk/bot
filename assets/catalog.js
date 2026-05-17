// fear.dev — публичный каталог
(function () {
  const PAGE_SIZE = 24;
  const $ = (id) => document.getElementById(id);
  const t = (k) => window.FD_I18N && window.FD_I18N.t ? window.FD_I18N.t(k) : k;
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));

  const state = {
    category:   '',
    search:     '',
    sort:       'new',
    offset:     0,
    total:      0,
    debounce:   null,
    categories: [],
  };

  function setStatus(kind, key) {
    const el = $('status');
    if (!key) { el.textContent = ''; el.className = 'status'; return; }
    el.textContent = t(key);
    el.className = 'status show ' + (kind === 'error' ? 'error' : 'ok');
  }

  function fmtPrice(cents, currency) {
    const v = (cents / 100).toFixed(2);
    return v + ' <span class="cur">' + esc(currency) + '</span>';
  }

  async function loadCategories() {
    try {
      const res = await fetch('/api/categories.php', { credentials: 'same-origin' });
      const body = await res.json();
      if (body.ok) {
        state.categories = body.categories;
        renderTabs();
      }
    } catch {}
  }

  function renderTabs() {
    const tabs = $('catTabs');
    const allBtn = tabs.querySelector('[data-category=""]');
    tabs.innerHTML = '';
    tabs.appendChild(allBtn);
    for (const c of state.categories) {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'cat-tab';
      b.dataset.category = c.slug;
      b.textContent = c.title;
      tabs.appendChild(b);
    }
    tabs.querySelectorAll('.cat-tab').forEach((btn) => {
      btn.classList.toggle('active', (btn.dataset.category || '') === state.category);
      btn.addEventListener('click', () => {
        state.category = btn.dataset.category || '';
        state.offset = 0;
        loadProducts();
        tabs.querySelectorAll('.cat-tab').forEach((b) => b.classList.toggle('active', b === btn));
        const url = new URL(window.location.href);
        if (state.category) url.searchParams.set('category', state.category);
        else                url.searchParams.delete('category');
        window.history.replaceState({}, '', url);
      });
    });
  }

  async function loadProducts() {
    setStatus(null, null);
    const q = new URLSearchParams({
      sort:   state.sort,
      limit:  String(PAGE_SIZE),
      offset: String(state.offset),
    });
    if (state.category) q.set('category', state.category);
    if (state.search)   q.set('search',   state.search);

    try {
      const res = await fetch('/api/products.php?' + q.toString(), { credentials: 'same-origin' });
      const body = await res.json().catch(() => null);
      if (!res.ok || !body || !body.ok) {
        setStatus('error', 'err.unknown');
        return;
      }
      state.total = body.total;
      render(body.products);
    } catch {
      setStatus('error', 'err.network');
    }
  }

  function render(products) {
    const grid = $('productsGrid');
    if (!products.length) {
      grid.innerHTML = '<div class="empty">' + esc(t('catalog.empty')) + '</div>';
    } else {
      grid.innerHTML = products.map(card).join('');
    }
    const page  = Math.floor(state.offset / PAGE_SIZE) + 1;
    const pages = Math.max(1, Math.ceil(state.total / PAGE_SIZE));
    $('pageInfo').textContent = page + ' / ' + pages;
    $('prevBtn').disabled = state.offset <= 0;
    $('nextBtn').disabled = state.offset + PAGE_SIZE >= state.total;
  }

  function card(p) {
    const href = '/product.php?slug=' + encodeURIComponent(p.slug);
    const img  = p.image_url
      ? '<img loading="lazy" src="' + esc(p.image_url) + '" alt="' + esc(p.title) + '" />'
      : '<div class="media-stub" aria-hidden="true"></div>';
    const cat = p.category_title
      ? '<span class="cat-pill">' + esc(p.category_title) + '</span>'
      : '';
    return `
      <a class="product-card" href="${href}">
        <div class="media">${img}</div>
        <div class="body">
          ${cat}
          <h3>${esc(p.title)}</h3>
          ${p.summary ? '<p>' + esc(p.summary) + '</p>' : ''}
          <div class="price">${fmtPrice(p.price_cents, p.currency)}</div>
        </div>
      </a>`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const url = new URL(window.location.href);
    state.category = url.searchParams.get('category') || '';
    state.search   = url.searchParams.get('search')   || '';
    state.sort     = url.searchParams.get('sort')     || 'new';
    if ($('search')) $('search').value = state.search;
    if ($('sort'))   $('sort').value   = state.sort;

    $('search').addEventListener('input', (e) => {
      clearTimeout(state.debounce);
      state.debounce = setTimeout(() => {
        state.search = e.target.value.trim();
        state.offset = 0;
        loadProducts();
      }, 250);
    });
    $('sort').addEventListener('change', (e) => {
      state.sort = e.target.value;
      state.offset = 0;
      loadProducts();
    });
    $('prevBtn').addEventListener('click', () => {
      state.offset = Math.max(0, state.offset - PAGE_SIZE);
      loadProducts();
    });
    $('nextBtn').addEventListener('click', () => {
      state.offset += PAGE_SIZE;
      loadProducts();
    });

    loadCategories().then(() => loadProducts());
  });
})();
