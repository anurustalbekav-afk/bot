<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

fd_require_method('POST');

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!fd_rate_limit("login:$ip", 20, 60)) {
    fd_json(429, ['ok' => false, 'error' => 'rate_limited']);
}

$body = fd_read_json_body();

$identifier = trim((string)($body['identifier'] ?? $body['email'] ?? $body['login'] ?? ''));
$password   = (string)($body['password'] ?? '');

if ($identifier === '' || $password === '') {
    fd_json(400, ['ok' => false, 'error' => 'missing_credentials']);
}

$user = fd_db_find_by_email_or_login($identifier);

// Always run a hash compare even when user is missing, to keep timing similar.
$dummyHash = '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidinvalid';
$ok = $user
    ? password_verify($password, (string)$user['passwordHash'])
    : (password_verify($password, $dummyHash) || false);

if (!$user || !$ok) {
    fd_json(401, ['ok' => false, 'error' => 'invalid_credentials']);
}

session_regenerate_id(true);
$_SESSION['uid'] = $user['id'];

fd_json(200, ['ok' => true, 'user' => fd_public_user($user)]);
