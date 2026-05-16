<?php
/**
 * fear.dev — router for PHP's built-in web server.
 *
 * Usage:
 *   php -S localhost:8000 -t public router.php
 *
 * Production should use Apache (see public/.htaccess) or Nginx with a
 * `try_files` rule that maps /api/<name> to /api/<name>.php.
 */

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$publicDir = __DIR__ . '/public';

// 1) Map /api/<name> -> /api/<name>.php (the .htaccess rewrite, but for `php -S`)
if (preg_match('#^/api/([a-z][a-z0-9_-]*)/?$#', $uri, $m)) {
    $script = "$publicDir/api/{$m[1]}.php";
    if (is_file($script)) {
        require $script;
        return true;
    }
}

// 2) Pretty URLs without extension: /login -> /login.html, etc.
if ($uri !== '/' && !str_contains(basename($uri), '.')) {
    $candidate = $publicDir . $uri . '.html';
    if (is_file($candidate)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($candidate);
        return true;
    }
}

// 3) Default: let the built-in server serve the file under /public.
$path = $publicDir . ($uri === '/' ? '/index.html' : $uri);
if (is_file($path)) {
    return false; // serve as-is
}
http_response_code(404);
echo '404 Not Found';
return true;
