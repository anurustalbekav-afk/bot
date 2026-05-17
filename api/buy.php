<?php
/**
 * Customer-initiated purchase from the catalog.
 *
 * POST /api/buy.php  body: { modId: string }
 *
 * Debits the current user's balance by the mod's price (server-side, the
 * client cannot influence the amount), appends a purchase record, and
 * returns the updated balance. The balance check and write are atomic
 * (single flock) — no double-spend on parallel requests.
 *
 * Errors (HTTP status mirrored in body.error):
 *   401 unauthorized
 *   404 mod_not_found
 *   402 insufficient_funds       (also returns balance + required for the UI)
 */
require_once __DIR__ . '/../lib/bootstrap.php';

fd_require_method('POST');

$user = fd_current_user();
if (!$user) fd_json_response(401, ['ok' => false, 'error' => 'unauthorized']);

// Light per-user rate limit so a misbehaving client cannot spam writes.
if (!fd_rate_limit('buy:' . $user['id'], 30, 60)) {
    fd_json_response(429, ['ok' => false, 'error' => 'rate_limited']);
}

$body  = fd_json_body();
$modId = (string) ($body['modId'] ?? '');
$mod   = $modId !== '' ? fd_mod_find($modId) : null;
if (!$mod) fd_json_response(404, ['ok' => false, 'error' => 'mod_not_found']);

$result = fd_user_buy_atomic($user['id'], [
    'modId'    => $mod['id'],
    'title'    => $mod['title'],
    'price'    => (float) $mod['price'],
    'currency' => (string) ($mod['currency'] ?? 'USD'),
    'kind'     => ($mod['type'] ?? 'mod') === 'script' ? 'script' : 'mod',
]);

if (isset($result['error'])) {
    $status = match ($result['error']) {
        'insufficient_funds'     => 402,
        'already_purchased'      => 409,
        'user_not_found'         => 404,
        'purchase_price_invalid' => 400,
        default                  => 400,
    };
    fd_json_response($status, ['ok' => false] + $result);
}

fd_json_response(201, [
    'ok'       => true,
    'purchase' => $result['purchase'],
    'balance'  => $result['balance'],
]);
