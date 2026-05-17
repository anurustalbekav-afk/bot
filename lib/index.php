<?php
// Guard: deny direct HTTP access to /lib/ when .htaccess is unavailable (nginx).
http_response_code(404);
echo 'Not found';
exit;
