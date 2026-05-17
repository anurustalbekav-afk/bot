<?php
require_once __DIR__ . '/../lib/bootstrap.php';

fd_require_method('GET');

$user = fd_current_user();
if (!$user) fd_json_response(401, ['ok' => false, 'error' => 'unauthorized']);

fd_json_response(200, ['ok' => true, 'user' => fd_public_user($user)]);
