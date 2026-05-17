<?php
/**
 * POST /api/admin/categories.php
 * Body: { action: 'create' | 'update' | 'delete', ... }
 */
declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

Auth::requireAdminOrFail();

$body   = read_json_body();
$action = (string)($body['action'] ?? '');

switch ($action) {
    case 'create': {
        $title = trim((string)($body['title'] ?? ''));
        if ($e = Validate::categoryTitle($title)) json_response(400, ['ok' => false, 'error' => $e]);
        $slug = trim((string)($body['slug'] ?? ''));
        if ($slug === '') $slug = Validate::makeSlug($title);
        if ($e = Validate::slug($slug))           json_response(400, ['ok' => false, 'error' => $e]);
        if (DB::findCategoryBySlug($slug))        json_response(409, ['ok' => false, 'error' => 'slug_taken']);
        $position = (int)($body['position'] ?? 0);
        $cat = DB::createCategory($slug, $title, $position);
        json_response(201, ['ok' => true, 'category' => $cat]);
    }

    case 'update': {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) json_response(400, ['ok' => false, 'error' => 'invalid_request']);
        $existing = DB::findCategory($id);
        if (!$existing) json_response(404, ['ok' => false, 'error' => 'category_not_found']);

        $title = trim((string)($body['title'] ?? $existing['title']));
        if ($e = Validate::categoryTitle($title)) json_response(400, ['ok' => false, 'error' => $e]);

        $slug = trim((string)($body['slug'] ?? $existing['slug']));
        if ($e = Validate::slug($slug))           json_response(400, ['ok' => false, 'error' => $e]);
        $bySlug = DB::findCategoryBySlug($slug);
        if ($bySlug && (int)$bySlug['id'] !== $id) json_response(409, ['ok' => false, 'error' => 'slug_taken']);

        $position = (int)($body['position'] ?? $existing['position']);
        DB::updateCategory($id, $slug, $title, $position);
        json_response(200, ['ok' => true, 'category' => DB::findCategory($id)]);
    }

    case 'delete': {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0)             json_response(400, ['ok' => false, 'error' => 'invalid_request']);
        if (!DB::findCategory($id)) json_response(404, ['ok' => false, 'error' => 'category_not_found']);
        DB::deleteCategory($id);
        // Связанные товары не удаляются — у них category_id станет NULL.
        json_response(200, ['ok' => true]);
    }

    default:
        json_response(400, ['ok' => false, 'error' => 'invalid_action']);
}
