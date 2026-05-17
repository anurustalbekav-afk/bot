<?php
/**
 * Public catalog endpoint.
 *
 * GET /api/mods.php          -> all mods + scripts
 * GET /api/mods.php?type=mod -> only mods (or ?type=script)
 *
 * No authentication required. Only fields safe for public consumption are
 * exposed (no admin metadata, no internal IDs leaked beyond the public id).
 */
require_once __DIR__ . '/../lib/bootstrap.php';

fd_require_method('GET');

$type = isset($_GET['type']) ? (string) $_GET['type'] : '';
$mods = fd_mods_all();

if ($type === 'mod' || $type === 'script') {
    $mods = array_values(array_filter($mods, static fn($m) => ($m['type'] ?? 'mod') === $type));
}

// Newest first.
usort($mods, fn($a, $b) => strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? '')));

$shape = array_map(static function (array $m): array {
    return [
        'id'          => $m['id']       ?? null,
        'title'       => $m['title']    ?? '',
        'description' => $m['description'] ?? '',
        'banner'      => $m['banner']   ?? '',
        'url'         => $m['url']      ?? '',
        'price'       => (float) ($m['price'] ?? 0),
        'currency'    => $m['currency'] ?? 'USD',
        'type'        => ($m['type'] ?? null) === 'script' ? 'script' : 'mod',
    ];
}, $mods);

fd_json_response(200, ['ok' => true, 'mods' => $shape]);
