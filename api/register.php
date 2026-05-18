<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$cfg = $GLOBALS['fd_cfg'];
$rl  = $cfg['rate_limit']['register'];
if (!rate_limit('reg:' . client_ip(), $rl['limit'], $rl['window'])) {
    json_response(429, ['ok' => false, 'error' => 'rate_limited']);
}

$body = read_json_body();
$email    = strtolower(trim((string)($body['email']    ?? '')));
$login    = trim((string)($body['login']    ?? ''));
$password = (string)($body['password'] ?? '');

foreach ([Validate::email($email), Validate::login($login), Validate::password($password)] as $err) {
    if ($err) json_response(400, ['ok' => false, 'error' => $err]);
}

if (DB::findByEmail($email)) json_response(409, ['ok' => false, 'error' => 'email_taken']);
if (DB::findByLogin($login)) json_response(409, ['ok' => false, 'error' => 'login_taken']);

$user = [
    'id'            => Auth::uuidV4(),
    'email'         => $email,
    'login'         => $login,
    'password_hash' => Auth::hashPassword($password),
    'created_at'    => gmdate('Y-m-d H:i:s'),
];

try {
    $row = DB::createUser($user);
} catch (PDOException $e) {
    // SQLSTATE 23000 — нарушение уникальности (гонка регистраций)
    if ($e->getCode() === '23000') {
        $msg = $e->getMessage();
        $which = stripos($msg, 'uk_users_login') !== false ? 'login_taken' : 'email_taken';
        json_response(409, ['ok' => false, 'error' => $which]);
    }
    json_response(500, ['ok' => false, 'error' => 'db_error']);
}

Auth::setSessionCookie(Auth::signSession($row['id']));
json_response(201, ['ok' => true, 'user' => public_user($row)]);
