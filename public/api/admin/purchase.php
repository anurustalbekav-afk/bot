<?php
require_once __DIR__ . '/../../../lib/bootstrap.php';

fd_require_admin();
fd_require_method('POST');

$body = fd_json_body();
$userId = (string) ($body['userId'] ?? '');
$user = $userId !== '' ? fd_user_find_by_id($userId) : null;
if (!$user) fd_json_response(404, ['ok' => false, 'error' => 'user_not_found']);

// If a modId is supplied we copy snapshot fields (title/price/currency) from
// the catalog so the purchase record stays meaningful even if the mod is later
// edited or deleted.
$modId = isset($body['modId']) ? (string) $body['modId'] : '';
if ($modId !== '') {
    $mod = fd_mod_find($modId);
    if (!$mod) fd_json_response(404, ['ok' => false, 'error' => 'mod_not_found']);
    $body['title']    = $body['title']    ?? $mod['title'];
    $body['price']    = $body['price']    ?? $mod['price'];
    $body['currency'] = $body['currency'] ?? $mod['currency'];
    $body['kind']     = $body['kind']     ?? $mod['type'];
    $body['modId']    = $modId;
}

$check = fd_validate_purchase($body);
if ($check['error']) fd_json_response(400, ['ok' => false, 'error' => $check['error']]);

$record = fd_user_add_purchase($userId, $check['value']);
fd_json_response(201, ['ok' => true, 'purchase' => $record]);
