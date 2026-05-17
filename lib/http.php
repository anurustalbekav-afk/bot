<?php
declare(strict_types=1);

/**
 * Хелперы для JSON API + простой файловый rate-limit.
 * Намеренно без классов — это голые функции, чтобы не плодить
 * статические синглтоны под каждую мелочь.
 */

function json_response(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(int $maxBytes = 32768): array
{
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false) return [];
    if (strlen($raw) > $maxBytes) {
        json_response(413, ['ok' => false, 'error' => 'payload_too_large']);
    }
    if ($raw === '') return [];
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(400, ['ok' => false, 'error' => 'invalid_json']);
    }
    return $data;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Лёгкий файловый rate limit. Хранит JSON со счётчиком и временем
 * сброса в sys_get_temp_dir(). Достаточно для базовой защиты от
 * брутфорса; для серьёзной нагрузки лучше Redis/APCu.
 */
function rate_limit(string $key, int $limit, int $windowSec): bool
{
    $dir = sys_get_temp_dir() . '/fear_dev_rate';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $file = $dir . '/' . hash('sha256', $key) . '.json';

    $fp = @fopen($file, 'c+');
    if (!$fp) return true; // если ФС не пускает — не блокируем легитимных юзеров
    try {
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $now = time();
        $entry = json_decode($raw ?: '{}', true);
        if (!is_array($entry) || ($entry['reset'] ?? 0) < $now) {
            $entry = ['count' => 0, 'reset' => $now + $windowSec];
        }
        $entry['count']++;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($entry));
        return $entry['count'] <= $limit;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/**
 * Преобразует строку БД в публичный формат (без password_hash).
 * createdAt отдаётся ISO-8601 в UTC, чтобы JS легко его парсил.
 */
function public_user(array $row): array
{
    $created = $row['created_at'] ?? null;
    if ($created) {
        $ts = strtotime($created . ' UTC');
        if ($ts !== false) {
            $created = gmdate('Y-m-d\TH:i:s\Z', $ts);
        }
    }
    return [
        'id'        => $row['id'],
        'email'     => $row['email'],
        'login'     => $row['login'],
        'createdAt' => $created,
    ];
}
