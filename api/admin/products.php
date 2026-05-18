<?php
/**
 * POST /api/admin/products.php
 * Body: { action: 'create' | 'update' | 'delete' | 'publish' | 'unpublish', ... }
 *
 * Цена принимается в виде "9.99" (price), либо целочисленный price_cents.
 */
declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

Auth::requireAdminOrFail();

$body   = read_json_body();
$action = (string)($body['action'] ?? '');

/**
 * Собирает и валидирует поля товара из запроса. На create все поля
 * из тела; на update — недостающие подтягиваются из существующего товара.
 */
function build_product_payload(array $body, ?array $existing): array
{
    $title = trim((string)($body['title'] ?? $existing['title'] ?? ''));
    if ($e = Validate::productTitle($title)) json_response(400, ['ok' => false, 'error' => $e]);

    $slug = trim((string)($body['slug'] ?? ''));
    if ($slug === '' && $existing)  $slug = (string)$existing['slug'];
    if ($slug === '')               $slug = Validate::makeSlug($title);
    if ($e = Validate::slug($slug)) json_response(400, ['ok' => false, 'error' => $e]);

    $cents = isset($body['price_cents'])
        ? (int)$body['price_cents']
        : (isset($body['price']) ? (int) round(((float)$body['price']) * 100) : (int)($existing['price_cents'] ?? 0));
    if ($e = Validate::priceCents($cents)) json_response(400, ['ok' => false, 'error' => $e]);

    $currency = strtoupper(trim((string)($body['currency'] ?? $existing['currency'] ?? 'USD')));
    if ($e = Validate::currency($currency)) json_response(400, ['ok' => false, 'error' => $e]);

    $image = trim((string)($body['image_url'] ?? $existing['image_url'] ?? ''));
    if ($e = Validate::imageUrl($image)) json_response(400, ['ok' => false, 'error' => $e]);

    $categoryId = isset($body['category_id'])
        ? ((int)$body['category_id'] ?: null)
        : ($existing['category_id'] ?? null);
    if ($categoryId !== null && !DB::findCategory((int)$categoryId)) {
        json_response(400, ['ok' => false, 'error' => 'category_not_found']);
    }

    $status = (string)($body['status'] ?? $existing['status'] ?? DB::PRODUCT_DRAFT);
    if (!in_array($status, DB::PRODUCT_STATUSES, true)) {
        json_response(400, ['ok' => false, 'error' => 'invalid_status']);
    }

    $summary = trim((string)($body['summary'] ?? $existing['summary'] ?? ''));
    if (mb_strlen($summary) > 255) json_response(400, ['ok' => false, 'error' => 'summary_too_long']);

    $description = (string)($body['description'] ?? $existing['description'] ?? '');
    if (strlen($description) > 65535) json_response(400, ['ok' => false, 'error' => 'description_too_long']);

    return [
        'slug'        => $slug,
        'category_id' => $categoryId,
        'title'       => $title,
        'summary'     => $summary,
        'description' => $description !== '' ? $description : null,
        'price_cents' => $cents,
        'currency'    => $currency,
        'image_url'   => $image,
        'status'      => $status,
    ];
}

switch ($action) {
    case 'create': {
        $p = build_product_payload($body, null);
        if (DB::findProductBySlug($p['slug'])) {
            json_response(409, ['ok' => false, 'error' => 'slug_taken']);
        }
        $row = DB::createProduct($p);
        json_response(201, ['ok' => true, 'product' => public_product($row, true)]);
    }

    case 'update': {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) json_response(400, ['ok' => false, 'error' => 'invalid_request']);
        $existing = DB::findProduct($id);
        if (!$existing) json_response(404, ['ok' => false, 'error' => 'product_not_found']);

        $p = build_product_payload($body, $existing);
        $bySlug = DB::findProductBySlug($p['slug']);
        if ($bySlug && (int)$bySlug['id'] !== $id) {
            json_response(409, ['ok' => false, 'error' => 'slug_taken']);
        }
        DB::updateProduct($id, $p);
        json_response(200, ['ok' => true, 'product' => public_product(DB::findProduct($id), true)]);
    }

    case 'publish':
    case 'unpublish': {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0)              json_response(400, ['ok' => false, 'error' => 'invalid_request']);
        $existing = DB::findProduct($id);
        if (!$existing)            json_response(404, ['ok' => false, 'error' => 'product_not_found']);
        $existing['status'] = $action === 'publish' ? DB::PRODUCT_PUBLISHED : DB::PRODUCT_DRAFT;
        DB::updateProduct($id, $existing);
        json_response(200, ['ok' => true, 'product' => public_product(DB::findProduct($id), true)]);
    }

    case 'delete': {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0)              json_response(400, ['ok' => false, 'error' => 'invalid_request']);
        if (!DB::findProduct($id)) json_response(404, ['ok' => false, 'error' => 'product_not_found']);
        DB::deleteProduct($id);
        json_response(200, ['ok' => true]);
    }

    default:
        json_response(400, ['ok' => false, 'error' => 'invalid_action']);
}
