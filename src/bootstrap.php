<?php
declare(strict_types=1);

/**
 * fear.dev — common bootstrap for every PHP entry point.
 *
 * Responsibilities:
 *   - load .env (no external deps)
 *   - configure paths and ensure the data dir exists
 *   - configure and start the session (HttpOnly, SameSite=Lax)
 *   - expose helpers used by the JSON API endpoints
 */

// --- paths ------------------------------------------------------------------

define('FD_ROOT',     dirname(__DIR__));
define('FD_DATA_DIR', FD_ROOT . '/data');
define('FD_USERS_FILE', FD_DATA_DIR . '/users.json');
define('FD_SESSIONS_DIR', FD_DATA_DIR . '/sessions');

if (!is_dir(FD_DATA_DIR))     { @mkdir(FD_DATA_DIR, 0775, true); }
if (!is_dir(FD_SESSIONS_DIR)) { @mkdir(FD_SESSIONS_DIR, 0775, true); }

// --- .env loader (very small, no deps) --------------------------------------

(function (): void {
    $envFile = FD_ROOT . '/.env';
    if (!is_file($envFile)) return;
    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)$/i', $line, $m)) continue;
        $k = $m[1];
        $v = trim($m[2]);
        if ((str_starts_with($v, '"') && str_ends_with($v, '"'))
            || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        if (getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
})();

// --- session ---------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    $ttl = (int)(getenv('SESSION_TTL') ?: 60 * 60 * 24 * 7); // 7 days
    @session_save_path(FD_SESSIONS_DIR);
    session_name('fd_session');
    session_set_cookie_params([
        'lifetime' => $ttl,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', (string)$ttl);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate.php';

// --- helpers shared by API endpoints ---------------------------------------

function fd_json(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fd_read_json_body(int $maxBytes = 32768): array {
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false || $raw === '') return [];
    if (strlen($raw) > $maxBytes) fd_json(413, ['ok' => false, 'error' => 'payload_too_large']);
    $data = json_decode($raw, true);
    if (!is_array($data)) fd_json(400, ['ok' => false, 'error' => 'invalid_json']);
    return $data;
}

function fd_require_method(string $method): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        fd_json(405, ['ok' => false, 'error' => 'method_not_allowed']);
    }
}

function fd_public_user(array $u): array {
    return [
        'id'        => $u['id'],
        'email'     => $u['email'],
        'login'     => $u['login'],
        'createdAt' => $u['createdAt'],
    ];
}

function fd_current_user(): ?array {
    $uid = $_SESSION['uid'] ?? null;
    if (!is_string($uid) || $uid === '') return null;
    return fd_db_find_by_id($uid);
}

/**
 * Naive per-IP rate limiter, persisted in the session file. Good enough to
 * blunt brute-force attempts without pulling in Redis.
 */
function fd_rate_limit(string $bucket, int $limit, int $windowSec): bool {
    $key = '_rl_' . $bucket;
    $now = time();
    $entry = $_SESSION[$key] ?? ['count' => 0, 'reset' => $now + $windowSec];
    if ($now > $entry['reset']) {
        $entry = ['count' => 0, 'reset' => $now + $windowSec];
    }
    $entry['count']++;
    $_SESSION[$key] = $entry;
    return $entry['count'] <= $limit;
}
