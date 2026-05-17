<?php
declare(strict_types=1);

/**
 * Auth helpers: password hashing + session-based current-user lookup.
 * Sessions are managed by PHP itself (see bootstrap.php).
 */

function fd_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function fd_verify_password(string $password, string $hash): bool
{
    return $hash !== '' && password_verify($password, $hash);
}

function fd_login(string $userId): void
{
    // Mitigate session fixation.
    session_regenerate_id(true);
    $_SESSION['uid']      = $userId;
    $_SESSION['loginAt']  = time();
}

function fd_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }
    session_destroy();
}

function fd_current_user(): ?array
{
    $uid = $_SESSION['uid'] ?? null;
    if (!is_string($uid) || $uid === '') return null;
    return fd_user_find_by_id($uid);
}

function fd_public_user(array $u): array
{
    return [
        'id'        => $u['id']        ?? null,
        'email'     => $u['email']     ?? null,
        'login'     => $u['login']     ?? null,
        'createdAt' => $u['createdAt'] ?? null,
        'isAdmin'   => fd_is_admin($u),
    ];
}
