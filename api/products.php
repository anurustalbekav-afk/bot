<?php
/**
 * GET /api/products.php?category=slug&search=&sort=&limit=&offset=
 * Публичный список товаров. Возвращает только опубликованные.
 *
 * Если в запросе stale-сессия админа и параметр include_drafts=1,
 * вернёт также черновики — это используется в /admin/products.php.
 */
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$includeDrafts = !empty($_GET['include_drafts']) && Auth::isAdmin(Auth::currentUser());

$filters = [];
if (!$includeDrafts) {
    $filters['status'] = DB::PRODUCT_PUBLISHED;
}
if (!empty($_GET['category'])) {
    $cat = DB::findCategoryBySlug((string)$_GET['category']);
    if ($cat) $filters['category_id'] = (int)$cat['id'];
    // Если категория не найдена — возвращаем пустой результат, не падаем
    else      $filters['category_id'] = -1;
}
if (!empty($_GET['search'])) {
    $filters['search'] = trim((string)$_GET['search']);
}

$sort   = (string)($_GET['sort']   ?? 'new');
$limit  = (int)   ($_GET['limit']  ?? 24);
$offset = (int)   ($_GET['offset'] ?? 0);

$rows  = DB::listProducts($filters, $sort, $limit, $offset);
$total = DB::countProducts($filters);

json_response(200, [
    'ok'       => true,
    'total'    => $total,
    'products' => array_map('public_product', $rows),
]);
