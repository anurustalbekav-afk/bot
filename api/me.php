<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$uid = Auth::currentUserId();
if (!$uid) {
    json_response(401, ['ok' => false, 'error' => 'unauthorized']);
}

$user = DB::findById($uid);
if (!$user) {
    Auth::clearSessionCookie();
    json_response(401, ['ok' => false, 'error' => 'unauthorized']);
}

json_response(200, ['ok' => true, 'user' => public_user($user)]);
