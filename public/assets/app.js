/* Landing page logic: load servers, news, current user, render topbar */
(async function () {
  const fmtDate = (ts) => {
    const d = new Date(ts);
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2, '0');
    const mi = String(d.getMinutes()).padStart(2, '0');
    return { date: `${dd}.${mm}.${yyyy}`, time: `${hh}:${mi}` };
  };

  const tagLabel = { important: 'ВАЖНО', update: 'ОБНОВЛЕНИЕ', event: 'СОБЫТИЕ' };

  async function getJSON(url) {
    const r = await fetch(url, { credentials: 'same-origin' });
    if (!r.ok) throw new Error(`${url}: ${r.status}`);
    return r.json();
  }

  function pingClass(ms) {
    if (ms <= 70) return 'good';
    if (ms <= 90) return 'mid';
    return 'bad';
  }

  function renderServers(data) {
    const list = document.getElementById('serversList');
    if (!data.servers?.length) { list.textContent = 'Серверов пока нет.'; return; }
    list.innerHTML = data.servers
      .map((s, i) => `
        <div class="server-row ${i === 0 ? 'primary' : ''}">
          <div class="server-num">${String(s.id).padStart(2, '0')}</div>
          <div class="server-info">
            <div class="server-name">${s.name}</div>
            <div class="server-online">Онлайн: ${s.online} / ${s.capacity}</div>
          </div>
          <div class="server-ping">
            <span class="bars ${pingClass(s.ping)}"><i></i><i></i><i></i><i></i></span>
            ${s.ping} ms
          </div>
        </div>
      `).join('');
    const total = document.getElementById('totalOnline');
    if (total) total.textContent = data.totalOnline.toLocaleString('ru-RU');
  }

  function renderNews(data) {
    const list = document.getElementById('newsList');
    if (!data.news?.length) { list.textContent = 'Новостей пока нет.'; return; }
    list.innerHTML = data.news.slice(0, 3).map(n => {
      const { date, time } = fmtDate(n.published_at);
      const tag = (n.tag || '').toLowerCase();
      return `
        <div class="news-item">
          <div class="news-item-head">
            <span class="tag ${tag}">${tagLabel[tag] || n.tag}</span>
            <span class="news-date">${date}<br/>${time}</span>
          </div>
          <div class="news-title">${n.title}</div>
          <div class="news-body">${n.body}</div>
        </div>
      `;
    }).join('');
  }

  async function renderTopbar() {
    const bar = document.getElementById('topbar');
    const navAuthAction = document.getElementById('navAuthAction');
    try {
      const { user } = await getJSON('/api/me');
      bar.innerHTML = `
        <div class="user-card">
          <div class="user-avatar">☺</div>
          <div class="user-meta"><b>${user.login}</b><small>ID: ${user.id}</small></div>
        </div>
        <button class="btn-ghost" id="logoutBtn">ВЫЙТИ</button>
      `;
      document.getElementById('logoutBtn').addEventListener('click', async () => {
        await fetch('/api/logout', { method: 'POST', credentials: 'same-origin' });
        location.reload();
      });
      if (navAuthAction) {
        navAuthAction.innerHTML = `<span class="ic">⇤</span>ВЫХОД`;
        navAuthAction.addEventListener('click', async (e) => {
          e.preventDefault();
          await fetch('/api/logout', { method: 'POST', credentials: 'same-origin' });
          location.reload();
        });
      }
    } catch {
      bar.innerHTML = `
        <a href="/login" class="btn-ghost">ВОЙТИ</a>
        <a href="/register" class="btn-ghost" style="background:transparent;color:#fff;border:1px solid var(--line);">РЕГИСТРАЦИЯ</a>
      `;
      if (navAuthAction) navAuthAction.href = '/login';
    }
  }

  try {
    const [s, n] = await Promise.all([getJSON('/api/servers'), getJSON('/api/news')]);
    renderServers(s);
    renderNews(n);
  } catch (e) {
    console.error(e);
  }
  renderTopbar();
})();
