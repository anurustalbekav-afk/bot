<?php
/**
 * GET /api/products/get.php?slug=foo
 *      /api/products/get.php?id=123
 * Карточка товара. Черновики возвращаются только админам.
 */
declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$slug = trim((string)($_GET['slug'] ?? ''));
$id   = (int)($_GET['id'] ?? 0);

$product = $slug !== '' ? DB::findProductBySlug($slug) : ($id > 0 ? DB::findProduct($id) : null);
if (!$product) {
    json_response(404, ['ok' => false, 'error' => 'product_not_found']);
}

if ($product['status'] !== DB::PRODUCT_PUBLISHED && !Auth::isAdmin(Auth::currentUser())) {
    json_response(404, ['ok' => false, 'error' => 'product_not_found']);
}

json_response(200, ['ok' => true, 'product' => public_product($product, true)]);
