<?php
/**
 * Router for the built-in PHP dev server: `php -S 127.0.0.1:8000 router.php`.
 *
 * In production the same job is done by the root .htaccess (Apache/LiteSpeed)
 * or an equivalent nginx config. Keep both in sync.
 */
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// 1. Block direct access to private folders (mirrors the .htaccess rules).
if (preg_match('#^/(lib|data)(/|$)#', $path) || preg_match('#/\.(env|git|htaccess)(/|$)#', $path)) {
    http_response_code(404);
    echo 'Not found';
    return true;
}

// 2. Serve real static files as-is.
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false; // let the dev server handle it
}

// 3. Default index.
if ($path === '/' || $path === '') {
    require __DIR__ . '/index.php';
    return true;
}

// 4. Pretty URLs: /admin -> admin.php, /api/login -> api/login.php
$candidate = __DIR__ . rtrim($path, '/') . '.php';
if (is_file($candidate)) {
    require $candidate;
    return true;
}

// 5. Fall back to 404.
http_response_code(404);
echo 'Not found';
return true;
