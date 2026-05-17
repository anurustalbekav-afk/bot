<?php
/**
 * Общий «вход» для всех админ-страниц.
 * Подключается первой строкой каждого файла в /admin/.
 *
 * Возвращает массив текущего админа.
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
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>403</title>'
       . '<body style="font:14px system-ui;padding:32px;color:#eee;background:#0a0a0c">'
       . '<h1>403 Forbidden</h1><p>Доступ только для администраторов.</p>'
       . '<p><a style="color:#9cf" href="/dashboard.php">В кабинет</a></p></body>';
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
