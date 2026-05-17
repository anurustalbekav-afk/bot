<?php
/**
 * CRUD for mods. Method dispatch:
 *   GET    /api/admin/mods.php           — list
 *   POST   /api/admin/mods.php           — create  (body: title, banner, url, price, currency?, type?)
 *   PATCH  /api/admin/mods.php?id=...    — update  (any subset of the above)
 *   DELETE /api/admin/mods.php?id=...    — delete
 */

require_once __DIR__ . '/../../lib/bootstrap.php';

fd_require_admin();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$id = isset($_GET['id']) ? (string) $_GET['id'] : '';

switch ($method) {
    case 'GET': {
        $mods = fd_mods_all();
        usort($mods, fn($a, $b) => strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? '')));
        fd_json_response(200, ['ok' => true, 'mods' => $mods]);
    }

    case 'POST': {
        $body = fd_json_body();
        $check = fd_validate_mod($body, partial: false);
        if ($check['error']) fd_json_response(400, ['ok' => false, 'error' => $check['error']]);
        $mod = fd_mod_create($check['value']);
        fd_json_response(201, ['ok' => true, 'mod' => $mod]);
    }

    case 'PATCH':
    case 'PUT': {
        if ($id === '' || !fd_mod_find($id)) {
            fd_json_response(404, ['ok' => false, 'error' => 'mod_not_found']);
        }
        $body = fd_json_body();
        $check = fd_validate_mod($body, partial: true);
        if ($check['error']) fd_json_response(400, ['ok' => false, 'error' => $check['error']]);
        if (!$check['value']) fd_json_response(400, ['ok' => false, 'error' => 'no_fields']);
        $mod = fd_mod_update($id, $check['value']);
        if (!$mod) fd_json_response(404, ['ok' => false, 'error' => 'mod_not_found']);
        fd_json_response(200, ['ok' => true, 'mod' => $mod]);
    }

    case 'DELETE': {
        if ($id === '') fd_json_response(400, ['ok' => false, 'error' => 'mod_id_required']);
        $ok = fd_mod_delete($id);
        if (!$ok) fd_json_response(404, ['ok' => false, 'error' => 'mod_not_found']);
        fd_json_response(200, ['ok' => true]);
    }

    default: {
        header('Allow: GET, POST, PATCH, PUT, DELETE');
        fd_json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
    }
}
