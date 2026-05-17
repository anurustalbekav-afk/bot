<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

Auth::clearSessionCookie();
json_response(200, ['ok' => true]);
