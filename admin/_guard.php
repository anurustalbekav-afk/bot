<?php
/**
 * Общий «вход» для всех админ-страниц.
 * Подключается первой строкой каждого файла в /admin/.
 */
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

$me = Auth::currentUser();
if (!$me) {
    header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/admin/'));
    exit;
}
if (!Auth::isAdmin($me)) {
    http_response_code(403);
    require_once __DIR__ . '/../lib/layout.php';
    layout_head('meta.title.admin', 'fear.dev — 403', $me);
    echo '<main class="shell-page"><section class="panel notfound">'
       . '<h1>403</h1><p>Доступ только для администраторов.</p>'
       . '<p><a href="/dashboard.php">В кабинет</a></p>'
       . '</section></main>';
    layout_foot();
    exit;
}

require_once __DIR__ . '/../lib/layout.php';

/**
 * Боковое меню админки.
 */
function admin_sidebar(string $active): void
{
    $items = [
        'dashboard'  => ['/admin/',              'admin.nav.overview',   'Обзор'],
        'products'   => ['/admin/products.php',  'admin.nav.products',   'Товары'],
        'categories' => ['/admin/categories.php','admin.nav.categories', 'Категории'],
        'users'      => ['/admin/users.php',     'admin.nav.users',      'Пользователи'],
    ];
    echo '<aside class="adm-side"><nav>';
    foreach ($items as $key => [$href, $i18n, $label]) {
        $cls = $key === $active ? 'active' : '';
        echo "<a class=\"$cls\" href=\"$href\" data-i18n=\"$i18n\">$label</a>";
    }
    echo '<a class="back" href="/" data-i18n="admin.nav.toSite">← На сайт</a>';
    echo '</nav></aside>';
}
