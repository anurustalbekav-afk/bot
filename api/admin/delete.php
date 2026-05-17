<?php
/**
 * POST /api/admin/delete.php  { id }
 * Удаляет пользователя. Запрещено удалять самого себя и удалять последнего
 * админа в системе (защита от lock-out).
 */
declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$me = Auth::requireAdminOrFail();

$body = read_json_body();
$id = trim((string)($body['id'] ?? ''));
if ($id === '') {
    json_response(400, ['ok' => false, 'error' => 'invalid_request']);
}

$target = DB::findById($id);
if (!$target) {
    json_response(404, ['ok' => false, 'error' => 'user_not_found']);
}

if ($target['id'] === $me['id']) {
    json_response(409, ['ok' => false, 'error' => 'cannot_delete_self']);
}

if ($target['role'] === DB::ROLE_ADMIN && DB::countAdmins() <= 1) {
    json_response(409, ['ok' => false, 'error' => 'cannot_delete_last_admin']);
}

DB::deleteUser($target['id']);
json_response(200, ['ok' => true]);
