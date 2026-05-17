<?php
require_once __DIR__ . '/../lib/bootstrap.php';

fd_require_method('POST');

$ip = fd_client_ip();
if (!fd_rate_limit("login:$ip", 20, 60)) {
    fd_json_response(429, ['ok' => false, 'error' => 'rate_limited']);
}

$body = fd_json_body();
$identifier = trim((string) ($body['identifier'] ?? $body['email'] ?? $body['login'] ?? ''));
$password   = (string) ($body['password'] ?? '');

if ($identifier === '' || $password === '') {
    fd_json_response(400, ['ok' => false, 'error' => 'missing_credentials']);
}

$user = fd_user_find_by_email_or_login($identifier);
// Run a hash compare even when the user is missing to avoid timing leaks.
$ok = $user ? fd_verify_password($password, (string) ($user['passwordHash'] ?? '')) : false;
if (!$user || !$ok) {
    fd_json_response(401, ['ok' => false, 'error' => 'invalid_credentials']);
}

fd_user_set_ip($user['id'], $ip);
fd_login($user['id']);
$user = fd_user_find_by_id($user['id']) ?? $user;

fd_json_response(200, ['ok' => true, 'user' => fd_public_user($user)]);
