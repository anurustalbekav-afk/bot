<?php
/**
 * GET /api/admin/users.php?search=&limit=50&offset=0
 * Возвращает список пользователей для админки.
 */
declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

Auth::requireAdminOrFail();

$search = trim((string)($_GET['search'] ?? ''));
$limit  = (int)($_GET['limit']  ?? 50);
$offset = (int)($_GET['offset'] ?? 0);

$rows = DB::listUsers($search, $limit, $offset);
$total = DB::countUsers($search);

$users = array_map('public_user', $rows);

json_response(200, [
    'ok'    => true,
    'total' => $total,
    'users' => $users,
]);
