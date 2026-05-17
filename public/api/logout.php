<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

fd_require_method('POST');
fd_logout();
fd_json_response(200, ['ok' => true]);
