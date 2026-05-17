<?php
/**
 * GET /api/purchases.php — list the current user's purchases (newest first).
 *
 * Used by the dashboard's "My purchases" tab. The full url of the mod is
 * included so a customer who already paid can re-download it.
 */
require_once __DIR__ . '/../lib/bootstrap.php';

fd_require_method('GET');

$user = fd_current_user();
if (!$user) fd_json_response(401, ['ok' => false, 'error' => 'unauthorized']);

$purchases = $user['purchases'] ?? [];
usort($purchases, fn($a, $b) => strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? '')));

// Resolve the download URL by looking up the mod by id (the title/price are
// frozen at purchase time, but the URL might have been updated since).
$mods = [];
foreach (fd_mods_all() as $m) $mods[(string) $m['id']] = $m;

$shape = array_map(static function (array $p) use ($mods): array {
    $mod = $p['modId'] && isset($mods[$p['modId']]) ? $mods[$p['modId']] : null;
    return [
        'id'        => $p['id']        ?? null,
        'modId'     => $p['modId']     ?? null,
        'title'     => $p['title']     ?? '',
        'kind'      => ($p['kind'] ?? 'mod') === 'script' ? 'script' : 'mod',
        'price'     => (float) ($p['price'] ?? 0),
        'currency'  => $p['currency']  ?? 'USD',
        'createdAt' => $p['createdAt'] ?? null,
        'banner'    => $mod['banner'] ?? '',
        'url'       => $mod['url']    ?? '',
    ];
}, $purchases);

fd_json_response(200, ['ok' => true, 'purchases' => $shape]);
