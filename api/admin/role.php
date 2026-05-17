<?php
/**
 * POST /api/admin/role.php  { id, role }
 * Меняет роль пользователя. Защита от lock-out: админ не может снять
 * админку с самого себя через веб (только CLI scripts/grant-admin.php).
 */
declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$me = Auth::requireAdminOrFail();

$body = read_json_body();
$id   = trim((string)($body['id']   ?? ''));
$role = trim((string)($body['role'] ?? ''));

if ($id === '' || !in_array($role, DB::ROLES, true)) {
    json_response(400, ['ok' => false, 'error' => 'invalid_request']);
}

$target = DB::findById($id);
if (!$target) {
    json_response(404, ['ok' => false, 'error' => 'user_not_found']);
}

// Self-demote запрещён, чтобы случайно не остаться без админа в системе.
if ($target['id'] === $me['id'] && $role !== DB::ROLE_ADMIN) {
    json_response(409, ['ok' => false, 'error' => 'cannot_demote_self']);
}

DB::setRole($target['id'], $role);
$updated = DB::findById($target['id']);
json_response(200, ['ok' => true, 'user' => public_user($updated)]);
