// fear.dev — админ-страница пользователей
(function () {
  const PAGE_SIZE = 25;
  const $ = (id) => document.getElementById(id);
  const t = (k) => window.FD_I18N && window.FD_I18N.t ? window.FD_I18N.t(k) : k;
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));

  let state = { offset: 0, search: '', total: 0, debounce: null };

  function setStatus(kind, key) {
    const el = $('status');
    if (!key) { el.textContent = ''; el.className = 'status'; return; }
    el.textContent = t(key);
    el.className = 'status show ' + (kind === 'error' ? 'error' : 'ok');
  }

  async function load() {
    setStatus(null, null);
    const q = new URLSearchParams({
      search: state.search,
      limit:  String(PAGE_SIZE),
      offset: String(state.offset),
    });
    try {
      const res = await fetch('/api/admin/users.php?' + q.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' },
      });
      const body = await res.json().catch(() => null);
      if (!res.ok || !body || !body.ok) {
        setStatus('error', body && body.error ? ('err.' + body.error) : 'err.unknown');
        return;
      }
      state.total = body.total;
      render(body.users);
    } catch {
      setStatus('error', 'err.network');
    }
  }

  function render(users) {
    const tbody = $('userRows');
    if (!users.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="empty">' + esc(t('admin.empty')) + '</td></tr>';
    } else {
      tbody.innerHTML = users.map(rowHtml).join('');
      bindRow(tbody);
    }

    $('totalCount').textContent = (t('admin.total') || 'Всего:') + ' ' + state.total;
    const page  = Math.floor(state.offset / PAGE_SIZE) + 1;
    const pages = Math.max(1, Math.ceil(state.total / PAGE_SIZE));
    $('pageInfo').textContent = page + ' / ' + pages;
    $('prevBtn').disabled = state.offset <= 0;
    $('nextBtn').disabled = state.offset + PAGE_SIZE >= state.total;
  }

  function rowHtml(u) {
    const isMe = window.FD_ME && window.FD_ME.id === u.id;
    const created = u.createdAt ? new Date(u.createdAt).toLocaleDateString(window.FD_I18N.getLocale()) : '';
    const roleClass = u.role === 'admin' ? 'role-admin' : 'role-user';
    return `
      <tr data-id="${esc(u.id)}" data-role="${esc(u.role)}">
        <td>
          <strong>${esc(u.login)}</strong>
          ${isMe ? '<span class="muted">' + esc(t('admin.you') || 'вы') + '</span>' : ''}
        </td>
        <td>${esc(u.email)}</td>
        <td>
          <span class="pill ${roleClass}">${esc(u.role)}</span>
          <select data-action="role" ${isMe ? 'disabled title="' + esc(t('err.cannot_demote_self')) + '"' : ''} style="margin-left:6px">
            <option value="user"${u.role === 'user' ? ' selected' : ''}>user</option>
            <option value="admin"${u.role === 'admin' ? ' selected' : ''}>admin</option>
          </select>
        </td>
        <td>${esc(created)}</td>
        <td>
          <button class="icon-btn danger" data-action="delete" ${isMe ? 'disabled' : ''} title="${esc(t('admin.delete') || 'Удалить')}">🗑</button>
        </td>
      </tr>`;
  }

  function bindRow(tbody) {
    tbody.querySelectorAll('select[data-action="role"]').forEach((sel) => {
      sel.addEventListener('change', async (e) => {
        const tr = e.target.closest('tr');
        const id = tr.dataset.id;
        const oldRole = tr.dataset.role;
        const newRole = e.target.value;
        if (oldRole === newRole) return;
        sel.disabled = true;
        const r = await FD_AUTH.postJson('/api/admin/role.php', { id, role: newRole });
        sel.disabled = false;
        if (r.ok && r.body && r.body.ok) {
          setStatus('ok', 'ok.role_updated');
          await load();
        } else {
          e.target.value = oldRole;
          const code = r.body && r.body.error;
          setStatus('error', code ? ('err.' + code) : 'err.unknown');
        }
      });
    });

    tbody.querySelectorAll('button[data-action="delete"]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        const tr = e.target.closest('tr');
        const id = tr.dataset.id;
        const login = tr.querySelector('strong').textContent;
        if (!confirm((t('admin.confirm_delete') || 'Удалить пользователя?') + '\n\n' + login)) return;
        btn.disabled = true;
        const r = await FD_AUTH.postJson('/api/admin/delete.php', { id });
        if (r.ok && r.body && r.body.ok) {
          setStatus('ok', 'ok.user_deleted');
          await load();
        } else {
          btn.disabled = false;
          const code = r.body && r.body.error;
          setStatus('error', code ? ('err.' + code) : 'err.unknown');
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    $('search').addEventListener('input', (e) => {
      clearTimeout(state.debounce);
      state.debounce = setTimeout(() => {
        state.search = e.target.value.trim();
        state.offset = 0;
        load();
      }, 250);
    });
    $('prevBtn').addEventListener('click', () => {
      state.offset = Math.max(0, state.offset - PAGE_SIZE);
      load();
    });
    $('nextBtn').addEventListener('click', () => {
      state.offset += PAGE_SIZE;
      load();
    });
    load();
  });
})();
