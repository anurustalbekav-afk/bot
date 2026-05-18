<?php
/**
 * Сессии и пароли.
 *
 * Сессия хранится в HttpOnly cookie как подписанный HMAC-SHA256 токен:
 *     base64url(payload).base64url(sig)
 * payload = JSON {sub, iat, exp}
 *
 * Это позволяет масштабировать без серверного хранилища сессий и
 * не зависит от настроек session.save_path хостинга.
 */

declare(strict_types=1);

final class Auth
{
    private static array $cfg = [];

    public static function configure(array $cfg): void
    {
        self::$cfg = $cfg;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        if ($hash === '') return false;
        return password_verify($password, $hash);
    }

    public static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    public static function signSession(string $userId): string
    {
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + (int)self::$cfg['session_ttl'],
        ];
        $body = self::b64url(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $sig  = self::b64url(hash_hmac('sha256', $body, self::$cfg['secret'], true));
        return $body . '.' . $sig;
    }

    public static function verifySession(?string $token): ?array
    {
        if (!is_string($token) || strpos($token, '.') === false) return null;
        [$body, $sig] = explode('.', $token, 2);
        $expected = self::b64url(hash_hmac('sha256', $body, self::$cfg['secret'], true));
        if (!hash_equals($expected, $sig)) return null;

        $json = self::b64urlDecode($body);
        if ($json === false) return null;
        $payload = json_decode($json, true);
        if (!is_array($payload) || empty($payload['sub']) || empty($payload['exp'])) return null;
        if ($payload['exp'] < time()) return null;
        return $payload;
    }

    public static function setSessionCookie(string $token): void
    {
        setcookie(self::$cfg['cookie_name'], $token, [
            'expires'  => time() + (int)self::$cfg['session_ttl'],
            'path'     => '/',
            'secure'   => (bool)(self::$cfg['cookie_secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function clearSessionCookie(): void
    {
        setcookie(self::$cfg['cookie_name'], '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => (bool)(self::$cfg['cookie_secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function currentUserId(): ?string
    {
        $name  = self::$cfg['cookie_name'];
        $token = $_COOKIE[$name] ?? null;
        $p = self::verifySession($token);
        return $p['sub'] ?? null;
    }

    /**
     * Текущий пользователь из БД (или null). Свежее чтение каждый раз —
     * ленится не стоит, прав в куке мы не храним, чтобы повышение/снятие
     * роли вступало в силу немедленно, без перелогина.
     */
    public static function currentUser(): ?array
    {
        $uid = self::currentUserId();
        if (!$uid) return null;
        return DB::findById($uid);
    }

    public static function isAdmin(?array $user): bool
    {
        return is_array($user) && ($user['role'] ?? '') === DB::ROLE_ADMIN;
    }

    /**
     * Ограждает API-эндпоинты для админов. При неудаче — отвечает JSON и exit.
     * Возвращает текущего админа, чтобы вызывающий код не ходил в БД повторно.
     */
    public static function requireAdminOrFail(): array
    {
        $user = self::currentUser();
        if (!$user) {
            json_response(401, ['ok' => false, 'error' => 'unauthorized']);
        }
        if (!self::isAdmin($user)) {
            json_response(403, ['ok' => false, 'error' => 'forbidden']);
        }
        return $user;
    }

    private static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $s)
    {
        $pad = strlen($s) % 4;
        if ($pad) $s .= str_repeat('=', 4 - $pad);
        return base64_decode(strtr($s, '-_', '+/'), true);
    }
}
