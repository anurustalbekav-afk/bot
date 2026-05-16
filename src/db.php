<?php
/**
 * Tiny JSON-file user store with file locking.
 * Compatible with PHP 7.4+.
 *
 * When the project grows, swap this module for PDO/SQLite without touching
 * the rest of the app — only these function signatures need to remain.
 */

function fd_db_read_all() {
    if (!is_file(FD_USERS_FILE)) {
        @file_put_contents(FD_USERS_FILE, json_encode(['users' => []], JSON_PRETTY_PRINT));
        return ['users' => []];
    }
    $raw = @file_get_contents(FD_USERS_FILE);
    if ($raw === false || $raw === '') return ['users' => []];
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['users']) || !is_array($data['users'])) {
        return ['users' => []];
    }
    return $data;
}

/**
 * Atomically mutate the users file under an exclusive lock.
 * @param callable $mutator  function(array $state): array{state: array, value: mixed}
 * @return mixed
 */
function fd_db_with_lock(callable $mutator) {
    $fp = @fopen(FD_USERS_FILE, 'c+');
    if (!$fp) {
        throw new RuntimeException('cannot open users file at ' . FD_USERS_FILE);
    }
    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('cannot lock users file');
        }
        rewind($fp);
        $raw = stream_get_contents($fp);
        if ($raw === false) $raw = '';
        if ($raw === '') {
            $state = ['users' => []];
        } else {
            $decoded = json_decode($raw, true);
            $state = is_array($decoded) ? $decoded : ['users' => []];
        }
        if (!isset($state['users']) || !is_array($state['users'])) $state['users'] = [];

        $result = $mutator($state);
        $newState = is_array($result) && isset($result['state']) ? $result['state'] : $state;
        $value    = is_array($result) && array_key_exists('value', $result) ? $result['value'] : null;

        $encoded = json_encode($newState, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $encoded);
        fflush($fp);
        return $value;
    } finally {
        @flock($fp, LOCK_UN);
        @fclose($fp);
    }
}

function fd_db_find_by_email($email) {
    $email = strtolower(trim((string)$email));
    foreach (fd_db_read_all()['users'] as $u) {
        if (isset($u['email']) && $u['email'] === $email) return $u;
    }
    return null;
}

function fd_db_find_by_login($login) {
    $login = strtolower(trim((string)$login));
    foreach (fd_db_read_all()['users'] as $u) {
        if (isset($u['login']) && strtolower($u['login']) === $login) return $u;
    }
    return null;
}

function fd_db_find_by_email_or_login($identifier) {
    $byEmail = fd_db_find_by_email($identifier);
    if ($byEmail) return $byEmail;
    return fd_db_find_by_login($identifier);
}

function fd_db_find_by_id($id) {
    foreach (fd_db_read_all()['users'] as $u) {
        if (isset($u['id']) && $u['id'] === $id) return $u;
    }
    return null;
}

function fd_db_create_user(array $user) {
    return fd_db_with_lock(function ($state) use ($user) {
        // Re-check uniqueness inside the lock to prevent races between concurrent registrations.
        foreach ($state['users'] as $u) {
            if (isset($u['email']) && $u['email'] === $user['email']) {
                throw new RuntimeException('email_taken');
            }
            if (isset($u['login']) && strtolower($u['login']) === strtolower($user['login'])) {
                throw new RuntimeException('login_taken');
            }
        }
        $state['users'][] = $user;
        return ['state' => $state, 'value' => $user];
    });
}

function fd_uuid_v4() {
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40); // version 4
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80); // variant
    $hex = bin2hex($b);
    return sprintf('%s-%s-%s-%s-%s',
        substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4),
        substr($hex, 16, 4), substr($hex, 20, 12));
}
