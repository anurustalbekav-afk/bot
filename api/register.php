<?php
require_once __DIR__ . '/../lib/bootstrap.php';

fd_require_method('POST');

$ip = fd_client_ip();
if (!fd_rate_limit("reg:$ip", 10, 60)) {
    fd_json_response(429, ['ok' => false, 'error' => 'rate_limited']);
}

$body = fd_json_body();
$email    = strtolower(trim((string) ($body['email']    ?? '')));
$login    = trim((string) ($body['login']    ?? ''));
$password = (string) ($body['password'] ?? '');

foreach ([
    fd_validate_email($email),
    fd_validate_login($login),
    fd_validate_password($password),
] as $err) {
    if ($err) fd_json_response(400, ['ok' => false, 'error' => $err]);
}

if (fd_user_find_by_email($email)) fd_json_response(409, ['ok' => false, 'error' => 'email_taken']);
if (fd_user_find_by_login($login)) fd_json_response(409, ['ok' => false, 'error' => 'login_taken']);

$user = fd_user_create([
    'id'           => fd_uuid(),
    'email'        => $email,
    'login'        => $login,
    'passwordHash' => fd_hash_password($password),
    'createdAt'    => fd_now_iso(),
    'ip'           => $ip,
    'lastIp'       => $ip,
]);

fd_login($user['id']);
fd_json_response(201, ['ok' => true, 'user' => fd_public_user($user)]);
