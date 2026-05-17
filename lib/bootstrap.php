<?php
declare(strict_types=1);

/**
 * Common bootstrap: loads .env, sets up paths, starts a hardened session.
 * Every entrypoint (page or API) must require_once this file first.
 */

if (!defined('FD_ROOT')) {
    define('FD_ROOT', dirname(__DIR__));
}
if (!defined('FD_DATA_DIR')) {
    define('FD_DATA_DIR', FD_ROOT . '/data');
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

// --- minimal .env loader ----------------------------------------------------

(function (): void {
    $envPath = FD_ROOT . '/.env';
    if (!is_file($envPath)) {
        return;
    }
    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*#/', $line)) continue;
        if (!preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/i', $line, $m)) continue;
        $key = $m[1];
        $val = $m[2];
        if ((str_starts_with($val, '"') && str_ends_with($val, '"'))
            || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
})();

// --- data dir ---------------------------------------------------------------

if (!is_dir(FD_DATA_DIR)) {
    @mkdir(FD_DATA_DIR, 0775, true);
}

// --- session ----------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => (int) (getenv('SESSION_TTL') ?: 60 * 60 * 24 * 7),
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('fd_session');
    session_start();
}

// --- shared modules ---------------------------------------------------------

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/validate.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin.php';
