<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$cfg = $GLOBALS['fd_cfg'];
$rl  = $cfg['rate_limit']['login'];
if (!rate_limit('login:' . client_ip(), $rl['limit'], $rl['window'])) {
    json_response(429, ['ok' => false, 'error' => 'rate_limited']);
}

$body = read_json_body();
// identifier — это email или login. Допускаем оба алиаса для совместимости.
$identifier = trim((string)($body['identifier'] ?? $body['email'] ?? $body['login'] ?? ''));
$password   = (string)($body['password'] ?? '');

if ($identifier === '' || $password === '') {
    json_response(400, ['ok' => false, 'error' => 'missing_credentials']);
}

$user = DB::findByEmailOrLogin($identifier);

// Чуть выравниваем время ответа: считаем хеш даже когда юзера нет,
// чтобы не давать удобного оракула по таймингу.
$ok = $user
    ? Auth::verifyPassword($password, $user['password_hash'])
    : (password_verify($password, '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidinvalid') && false);

if (!$user || !$ok) {
    json_response(401, ['ok' => false, 'error' => 'invalid_credentials']);
}

Auth::setSessionCookie(Auth::signSession($user['id']));
json_response(200, ['ok' => true, 'user' => public_user($user)]);
