<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/layout.php';

$uid = Auth::currentUserId();
$user = $uid ? DB::findById($uid) : null;
if (!$user) {
    header('Location: /login.php');
    exit;
}

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$loginEsc = $h($user['login']);
$emailEsc = $h($user['email']);
$idEsc    = $h($user['id']);
$roleEsc  = $h($user['role'] ?? 'user');
$isAdmin  = Auth::isAdmin($user);
$created  = $user['created_at'] ?? '';
$ts       = $created ? strtotime($created . ' UTC') : false;
$createdIso = $ts !== false ? gmdate('Y-m-d\TH:i:s\Z', $ts) : $h($created);

layout_head('meta.title.dashboard', 'fear.dev — Панель', $user);
?>
<main class="shell">
  <section class="dash" aria-label="Dashboard">
    <h2><span data-i18n="dash.welcome">Добро пожаловать,</span> <span id="userLogin"><?= $loginEsc ?></span></h2>
    <p class="sub" data-i18n="dash.subtitle">Это ваш кабинет fear.dev. Каталог модов и скриптов уже доступен.</p>

    <div class="userbox">
      <div class="cell"><div class="k" data-i18n="dash.email">Email</div><div class="v"><?= $emailEsc ?></div></div>
      <div class="cell"><div class="k" data-i18n="dash.login">Логин</div><div class="v"><?= $loginEsc ?></div></div>
      <div class="cell"><div class="k" data-i18n="dash.id">ID</div><div class="v"><?= $idEsc ?></div></div>
      <div class="cell"><div class="k" data-i18n="dash.role">Роль</div><div class="v"><?= $roleEsc ?><?= $isAdmin ? ' ★' : '' ?></div></div>
      <div class="cell"><div class="k" data-i18n="dash.created">Регистрация</div><div class="v" id="userCreated" data-iso="<?= $createdIso ?>"><?= $createdIso ?></div></div>
    </div>

    <div class="actions">
      <a href="/catalog.php" class="btn btn-primary" data-i18n="btn.toCatalog" style="text-decoration:none">В каталог</a>
      <?php if ($isAdmin): ?>
        <a href="/admin/" class="btn btn-ghost" data-i18n="btn.admin" style="text-decoration:none">Админка</a>
      <?php endif; ?>
      <button id="logoutBtn" class="btn btn-ghost" data-i18n="btn.logout">Выйти</button>
    </div>
  </section>
</main>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('userCreated');
    const iso = el && el.dataset.iso;
    if (iso) {
      try { el.textContent = new Date(iso).toLocaleString(window.FD_I18N.getLocale()); } catch {}
    }
    document.getElementById('logoutBtn').addEventListener('click', async () => {
      await FD_AUTH.postJson('/api/logout.php', {});
      window.location.replace('/login.php');
    });
  });
</script>
<?php layout_foot(); ?>
