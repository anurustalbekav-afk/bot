<?php
/**
 * GET /api/categories.php — публичный список категорий.
 */
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$cats = DB::listCategories();
$out  = array_map(static fn ($c) => [
    'id'       => (int)$c['id'],
    'slug'     => $c['slug'],
    'title'    => $c['title'],
    'position' => (int)$c['position'],
], $cats);

json_response(200, ['ok' => true, 'categories' => $out]);
