<?php
// Guard: deny direct HTTP access to /data/ when .htaccess is unavailable (nginx).
http_response_code(404);
echo 'Not found';
exit;
