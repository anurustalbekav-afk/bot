<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

fd_require_method('GET');

$user = fd_current_user();
if (!$user) fd_json(401, ['ok' => false, 'error' => 'unauthorized']);

fd_json(200, ['ok' => true, 'user' => fd_public_user($user)]);
