// fear.dev — админ-категории
(function () {
  const $ = (id) => document.getElementById(id);
  const t = (k) => window.FD_I18N && window.FD_I18N.t ? window.FD_I18N.t(k) : k;
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));

  function setStatus(kind, key) {
    const el = $('status');
    if (!key) { el.textContent = ''; el.className = 'status'; return; }
    el.textContent = t(key);
    el.className = 'status ' + (kind === 'error' ? 'error' : 'ok');
  }

  async function load() {
    try {
      const res = await fetch('/api/categories.php', { credentials: 'same-origin' });
      const body = await res.json();
      if (!body.ok) { setStatus('error', 'err.unknown'); return; }
      render(body.categories);
    } catch {
      setStatus('error', 'err.network');
    }
  }

  function render(cats) {
    const tbody = $('catRows');
    if (!cats.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="empty">—</td></tr>';
      return;
    }
    tbody.innerHTML = cats.map((c) => `
      <tr data-id="${c.id}">
        <td><input data-field="title" value="${esc(c.title)}" /></td>
        <td><input data-field="slug"  value="${esc(c.slug)}" /></td>
        <td><input data-field="position" type="number" value="${c.position}" style="width:80px" /></td>
        <td>
          <button class="btn btn-ghost" data-action="save">💾</button>
          <button class="danger" data-action="delete">🗑</button>
        </td>
      </tr>
    `).join('');
    bind(tbody);
  }

  function bind(tbody) {
    tbody.querySelectorAll('button[data-action="save"]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        const tr = e.target.closest('tr');
        const id = +tr.dataset.id;
        const get = (f) => tr.querySelector(`input[data-field="${f}"]`).value.trim();
        const r = await FD_AUTH.postJson('/api/admin/categories.php', {
          action: 'update',
          id, title: get('title'), slug: get('slug'), position: +get('position') || 0,
        });
        if (r.ok && r.body && r.body.ok) setStatus('ok', 'ok.saved');
        else setStatus('error', (r.body && r.body.error) ? ('err.' + r.body.error) : 'err.unknown');
      });
    });
    tbody.querySelectorAll('button[data-action="delete"]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        const tr = e.target.closest('tr');
        const id = +tr.dataset.id;
        if (!confirm(t('admin.cats.confirm_delete') || 'Удалить категорию?')) return;
        const r = await FD_AUTH.postJson('/api/admin/categories.php', { action: 'delete', id });
        if (r.ok && r.body && r.body.ok) { setStatus('ok', 'ok.deleted'); load(); }
        else setStatus('error', (r.body && r.body.error) ? ('err.' + r.body.error) : 'err.unknown');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    $('catForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const payload = {
        action: 'create',
        title: String(fd.get('title') || '').trim(),
        slug:  String(fd.get('slug')  || '').trim(),
        position: +(fd.get('position') || 0),
      };
      if (!payload.slug) delete payload.slug;
      const r = await FD_AUTH.postJson('/api/admin/categories.php', payload);
      if (r.ok && r.body && r.body.ok) {
        setStatus('ok', 'ok.created');
        e.target.reset();
        load();
      } else {
        setStatus('error', (r.body && r.body.error) ? ('err.' + r.body.error) : 'err.unknown');
      }
    });
    load();
  });
})();
