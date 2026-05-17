<?php
declare(strict_types=1);

/**
 * Admin authorization.
 *
 * A user is considered an admin when EITHER:
 *   - their stored record has `isAdmin: true`, OR
 *   - their email or login appears in the ADMIN_LOGINS env var
 *     (comma-separated). This bootstrap path lets the very first admin be
 *     seeded without manual JSON editing.
 */

function fd_admin_env_set(): array
{
    static $cached = null;
    if ($cached !== null) return $cached;
    $raw = (string) (getenv('ADMIN_LOGINS') ?: '');
    $set = [];
    foreach (explode(',', $raw) as $part) {
        $p = strtolower(trim($part));
        if ($p !== '') $set[$p] = true;
    }
    return $cached = $set;
}

function fd_is_admin(?array $user): bool
{
    if (!$user) return false;
    if (!empty($user['isAdmin'])) return true;
    $set = fd_admin_env_set();
    if (!$set) return false;
    $email = strtolower((string) ($user['email'] ?? ''));
    $login = strtolower((string) ($user['login'] ?? ''));
    return ($email !== '' && isset($set[$email])) || ($login !== '' && isset($set[$login]));
}

/**
 * Guard for admin-only API endpoints. Sends a 401/403 JSON and exits when the
 * caller is not authorized.
 */
function fd_require_admin(): array
{
    $user = fd_current_user();
    if (!$user)               fd_json_response(401, ['ok' => false, 'error' => 'unauthorized']);
    if (!fd_is_admin($user))  fd_json_response(403, ['ok' => false, 'error' => 'forbidden']);
    return $user;
}
