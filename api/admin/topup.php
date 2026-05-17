<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

fd_require_admin();
fd_require_method('POST');

$body = fd_json_body();
$userId = (string) ($body['userId'] ?? '');
if ($userId === '' || !fd_user_find_by_id($userId)) {
    fd_json_response(404, ['ok' => false, 'error' => 'user_not_found']);
}

$check = fd_validate_topup($body);
if ($check['error']) fd_json_response(400, ['ok' => false, 'error' => $check['error']]);

$record = fd_user_add_topup($userId, $check['value']);
fd_json_response(201, ['ok' => true, 'topup' => $record]);
