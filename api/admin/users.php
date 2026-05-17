<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

fd_require_admin();
fd_require_method('GET');

$users = fd_users_all();

// Sort: newest first, redact password hashes.
usort($users, fn($a, $b) => strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? '')));
$shape = array_map(static function (array $u): array {
    return [
        'id'        => $u['id']        ?? null,
        'email'     => $u['email']     ?? null,
        'login'     => $u['login']     ?? null,
        'ip'        => $u['ip']        ?? null,
        'lastIp'    => $u['lastIp']    ?? null,
        'createdAt' => $u['createdAt'] ?? null,
        'isAdmin'   => fd_is_admin($u),
        'topups'    => array_values($u['topups']    ?? []),
        'purchases' => array_values($u['purchases'] ?? []),
    ];
}, $users);

fd_json_response(200, ['ok' => true, 'users' => $shape]);
