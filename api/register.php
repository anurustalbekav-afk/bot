<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

fd_require_method('POST');

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!fd_rate_limit("reg:$ip", 10, 60)) {
    fd_json(429, ['ok' => false, 'error' => 'rate_limited']);
}

$body = fd_read_json_body();

$email    = strtolower(trim((string)($body['email']    ?? '')));
$login    = trim((string)($body['login']    ?? ''));
$password = (string)($body['password'] ?? '');

foreach ([fd_validate_email($email), fd_validate_login($login), fd_validate_password($password)] as $err) {
    if ($err !== null) fd_json(400, ['ok' => false, 'error' => $err]);
}

if (fd_db_find_by_email($email)) fd_json(409, ['ok' => false, 'error' => 'email_taken']);
if (fd_db_find_by_login($login)) fd_json(409, ['ok' => false, 'error' => 'login_taken']);

$user = [
    'id'           => fd_uuid_v4(),
    'email'        => $email,
    'login'        => $login,
    'passwordHash' => password_hash($password, PASSWORD_BCRYPT),
    'createdAt'    => gmdate('c'),
];

try {
    fd_db_create_user($user);
} catch (RuntimeException $e) {
    // Race: another request just took this email/login under our feet.
    $code = $e->getMessage();
    fd_json(409, ['ok' => false, 'error' => in_array($code, ['email_taken', 'login_taken'], true) ? $code : 'unknown']);
}

// Sign the user in immediately
session_regenerate_id(true);
$_SESSION['uid'] = $user['id'];

fd_json(201, ['ok' => true, 'user' => fd_public_user($user)]);
