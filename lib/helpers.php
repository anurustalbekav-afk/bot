<?php
declare(strict_types=1);

/**
 * Tiny request/response utilities shared by API endpoints.
 */

function fd_client_ip(): string
{
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
        $val = $_SERVER[$key] ?? '';
        if ($val === '') continue;
        // X-Forwarded-For may be a comma-separated chain; take the first.
        $first = trim(explode(',', $val)[0]);
        if ($first !== '') return $first;
    }
    return 'unknown';
}

function fd_json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    try {
        $parsed = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        fd_json_response(400, ['ok' => false, 'error' => 'invalid_json']);
    }
    return is_array($parsed) ? $parsed : [];
}

function fd_json_response(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fd_require_method(string ...$allowed): void
{
    $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($m, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        fd_json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
    }
}

/**
 * Naive in-process rate limit. Persists per-IP buckets in a JSON file so it
 * survives between requests. It's intentionally simple: replace with Redis if
 * you ever need real concurrency guarantees.
 */
function fd_rate_limit(string $key, int $limit, int $windowSec): bool
{
    $file = FD_DATA_DIR . '/ratelimit.json';
    $now = time();

    $fp = @fopen($file, 'c+');
    if (!$fp) return true; // fail-open; do not block legitimate users
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp) ?: '';
    $state = json_decode($raw, true);
    if (!is_array($state)) $state = [];

    $entry = $state[$key] ?? ['count' => 0, 'reset' => $now + $windowSec];
    if ($now > ($entry['reset'] ?? 0)) {
        $entry = ['count' => 0, 'reset' => $now + $windowSec];
    }
    $entry['count'] = (int) $entry['count'] + 1;
    $state[$key] = $entry;

    // Light GC to keep the file small.
    if (count($state) > 1000) {
        foreach ($state as $k => $v) {
            if (($v['reset'] ?? 0) < $now) unset($state[$k]);
        }
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($state));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $entry['count'] <= $limit;
}

function fd_uuid(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function fd_now_iso(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}

function fd_e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return a path with a `?v=<mtime>` cache-buster appended. Use it for every
 * <link rel="stylesheet"> and <script src> to make sure browsers don't keep
 * showing a stale UI after we deploy new assets.
 */
function fd_asset(string $relPath): string
{
    $abs = FD_ROOT . $relPath;
    $v = @filemtime($abs);
    return $v ? $relPath . '?v=' . $v : $relPath;
}
