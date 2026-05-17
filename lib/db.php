<?php
declare(strict_types=1);

/**
 * JSON-file storage. Two files, both atomically written under a flock.
 *
 *  data/users.json — { users: [ { id, email, login, passwordHash, createdAt,
 *                                 isAdmin, ip, lastIp, topups: [], purchases: [] } ] }
 *  data/mods.json  — { mods:  [ { id, title, banner, url, price, currency,
 *                                 type, createdAt, updatedAt } ] }
 *
 * Swap this layer for SQLite/Postgres later — call sites only depend on the
 * function signatures.
 */

const FD_USERS_FILE = FD_DATA_DIR . '/users.json';
const FD_MODS_FILE  = FD_DATA_DIR . '/mods.json';

function fd_db_with_lock(string $file, callable $fn): mixed
{
    if (!is_dir(FD_DATA_DIR)) @mkdir(FD_DATA_DIR, 0775, true);
    $fp = fopen($file, 'c+');
    if (!$fp) throw new RuntimeException('cannot_open_store');
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $state = ($raw !== '' && $raw !== false) ? json_decode($raw, true) : null;

    try {
        [$result, $newState] = $fn(is_array($state) ? $state : null);
        if ($newState !== null) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($newState, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            fflush($fp);
        }
        return $result;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

// --- users ------------------------------------------------------------------

function fd_users_normalize(array $state): array
{
    if (!isset($state['users']) || !is_array($state['users'])) $state['users'] = [];
    foreach ($state['users'] as &$u) {
        $u['topups']    = isset($u['topups'])    && is_array($u['topups'])    ? $u['topups']    : [];
        $u['purchases'] = isset($u['purchases']) && is_array($u['purchases']) ? $u['purchases'] : [];
        $u['isAdmin']   = !empty($u['isAdmin']);
        $u['ip']        = $u['ip']     ?? null;
        $u['lastIp']    = $u['lastIp'] ?? null;
    }
    return $state;
}

function fd_users_all(): array
{
    return fd_db_with_lock(FD_USERS_FILE, function ($state) {
        $state = fd_users_normalize($state ?? ['users' => []]);
        return [$state['users'], null];
    });
}

function fd_user_find_by_id(string $id): ?array
{
    foreach (fd_users_all() as $u) {
        if (($u['id'] ?? null) === $id) return $u;
    }
    return null;
}

function fd_user_find_by_email(string $email): ?array
{
    $needle = strtolower(trim($email));
    foreach (fd_users_all() as $u) {
        if (strtolower((string) ($u['email'] ?? '')) === $needle) return $u;
    }
    return null;
}

function fd_user_find_by_login(string $login): ?array
{
    $needle = strtolower(trim($login));
    foreach (fd_users_all() as $u) {
        if (strtolower((string) ($u['login'] ?? '')) === $needle) return $u;
    }
    return null;
}

function fd_user_find_by_email_or_login(string $identifier): ?array
{
    return fd_user_find_by_email($identifier) ?? fd_user_find_by_login($identifier);
}

function fd_user_create(array $user): array
{
    return fd_db_with_lock(FD_USERS_FILE, function ($state) use ($user) {
        $state = fd_users_normalize($state ?? ['users' => []]);
        $full = array_merge([
            'isAdmin'   => false,
            'ip'        => null,
            'lastIp'    => null,
            'topups'    => [],
            'purchases' => [],
        ], $user);
        $state['users'][] = $full;
        return [$full, $state];
    });
}

function fd_user_update(string $id, array $patch): ?array
{
    return fd_db_with_lock(FD_USERS_FILE, function ($state) use ($id, $patch) {
        $state = fd_users_normalize($state ?? ['users' => []]);
        foreach ($state['users'] as $i => $u) {
            if (($u['id'] ?? null) !== $id) continue;
            $state['users'][$i] = array_merge($u, $patch);
            return [$state['users'][$i], $state];
        }
        return [null, null];
    });
}

function fd_user_set_ip(string $id, string $ip): ?array
{
    return fd_db_with_lock(FD_USERS_FILE, function ($state) use ($id, $ip) {
        $state = fd_users_normalize($state ?? ['users' => []]);
        foreach ($state['users'] as $i => $u) {
            if (($u['id'] ?? null) !== $id) continue;
            if (empty($u['ip'])) $u['ip'] = $ip;
            $u['lastIp'] = $ip;
            $state['users'][$i] = $u;
            return [$u, $state];
        }
        return [null, null];
    });
}

function fd_user_add_topup(string $userId, array $entry): ?array
{
    return fd_db_with_lock(FD_USERS_FILE, function ($state) use ($userId, $entry) {
        $state = fd_users_normalize($state ?? ['users' => []]);
        foreach ($state['users'] as $i => $u) {
            if (($u['id'] ?? null) !== $userId) continue;
            $record = [
                'id'        => fd_uuid(),
                'amount'    => round((float) ($entry['amount'] ?? 0), 2),
                'currency'  => strtoupper((string) ($entry['currency'] ?? 'USD')),
                'method'    => $entry['method'] ?? null,
                'note'      => $entry['note']   ?? null,
                'createdAt' => fd_now_iso(),
            ];
            $state['users'][$i]['topups'][] = $record;
            return [$record, $state];
        }
        return [null, null];
    });
}

function fd_user_add_purchase(string $userId, array $entry): ?array
{
    return fd_db_with_lock(FD_USERS_FILE, function ($state) use ($userId, $entry) {
        $state = fd_users_normalize($state ?? ['users' => []]);
        foreach ($state['users'] as $i => $u) {
            if (($u['id'] ?? null) !== $userId) continue;
            $record = [
                'id'        => fd_uuid(),
                'kind'      => ($entry['kind'] ?? 'mod') === 'script' ? 'script' : 'mod',
                'modId'     => $entry['modId'] ?? null,
                'title'     => (string) ($entry['title'] ?? ''),
                'price'     => round((float) ($entry['price'] ?? 0), 2),
                'currency'  => strtoupper((string) ($entry['currency'] ?? 'USD')),
                'createdAt' => fd_now_iso(),
            ];
            $state['users'][$i]['purchases'][] = $record;
            return [$record, $state];
        }
        return [null, null];
    });
}

/**
 * Atomically debit the user's balance and append a purchase record.
 *
 * Returns ['error' => 'insufficient_funds' | 'user_not_found', ...] on failure
 * or ['ok' => true, 'purchase' => ..., 'balance' => float] on success.
 *
 * The balance check and write happen under the same flock so two parallel
 * requests cannot double-spend.
 */
function fd_user_buy_atomic(string $userId, array $entry): array
{
    return fd_db_with_lock(FD_USERS_FILE, function ($state) use ($userId, $entry) {
        $state = fd_users_normalize($state ?? ['users' => []]);
        foreach ($state['users'] as $i => $u) {
            if (($u['id'] ?? null) !== $userId) continue;

            // Compute current balance from durable history.
            $balance = 0.0;
            foreach ($u['topups']    as $t) $balance += (float) ($t['amount'] ?? 0);
            foreach ($u['purchases'] as $p) $balance -= (float) ($p['price']  ?? 0);

            $price = round((float) ($entry['price'] ?? 0), 2);
            if ($price < 0) {
                return [['error' => 'purchase_price_invalid'], null];
            }
            if (round($balance, 2) + 0.0001 < $price) {
                return [
                    [
                        'error'    => 'insufficient_funds',
                        'balance'  => round($balance, 2),
                        'required' => $price,
                    ],
                    null,
                ];
            }

            // Idempotency-by-mod is intentionally NOT enforced here: a user
            // may buy the same mod twice (e.g. for a friend) — that's a
            // product decision and easy to add later if we change our mind.

            $record = [
                'id'        => fd_uuid(),
                'kind'      => ($entry['kind'] ?? 'mod') === 'script' ? 'script' : 'mod',
                'modId'     => $entry['modId'] ?? null,
                'title'     => (string) ($entry['title'] ?? ''),
                'price'     => $price,
                'currency'  => strtoupper((string) ($entry['currency'] ?? 'USD')),
                'createdAt' => fd_now_iso(),
            ];
            $state['users'][$i]['purchases'][] = $record;

            return [
                ['ok' => true, 'purchase' => $record, 'balance' => round($balance - $price, 2)],
                $state,
            ];
        }
        return [['error' => 'user_not_found'], null];
    });
}

// --- mods -------------------------------------------------------------------

function fd_mods_all(): array
{
    return fd_db_with_lock(FD_MODS_FILE, function ($state) {
        if (!is_array($state) || !isset($state['mods']) || !is_array($state['mods'])) {
            $state = ['mods' => []];
        }
        return [$state['mods'], null];
    });
}

function fd_mod_find(string $id): ?array
{
    foreach (fd_mods_all() as $m) {
        if (($m['id'] ?? null) === $id) return $m;
    }
    return null;
}

function fd_mod_create(array $patch): array
{
    return fd_db_with_lock(FD_MODS_FILE, function ($state) use ($patch) {
        if (!is_array($state) || !isset($state['mods']) || !is_array($state['mods'])) {
            $state = ['mods' => []];
        }
        $now = fd_now_iso();
        $mod = [
            'id'          => fd_uuid(),
            'title'       => (string) ($patch['title']    ?? ''),
            'description' => (string) ($patch['description'] ?? ''),
            'banner'      => (string) ($patch['banner']   ?? ''),
            'url'         => (string) ($patch['url']      ?? ''),
            'price'       => round((float) ($patch['price'] ?? 0), 2),
            'currency'    => strtoupper((string) ($patch['currency'] ?? 'USD')),
            'type'        => ($patch['type'] ?? null) === 'script' ? 'script' : 'mod',
            'createdAt'   => $now,
            'updatedAt'   => $now,
        ];
        $state['mods'][] = $mod;
        return [$mod, $state];
    });
}

function fd_mod_update(string $id, array $patch): ?array
{
    return fd_db_with_lock(FD_MODS_FILE, function ($state) use ($id, $patch) {
        if (!is_array($state) || !isset($state['mods']) || !is_array($state['mods'])) {
            $state = ['mods' => []];
        }
        foreach ($state['mods'] as $i => $m) {
            if (($m['id'] ?? null) !== $id) continue;
            $allowed = ['title', 'description', 'banner', 'url', 'price', 'currency', 'type'];
            foreach ($allowed as $k) {
                if (!array_key_exists($k, $patch)) continue;
                if ($k === 'price')         $m[$k] = round((float) $patch[$k], 2);
                elseif ($k === 'currency')  $m[$k] = strtoupper((string) $patch[$k]);
                elseif ($k === 'type')      $m[$k] = $patch[$k] === 'script' ? 'script' : 'mod';
                else                        $m[$k] = (string) $patch[$k];
            }
            $m['updatedAt'] = fd_now_iso();
            $state['mods'][$i] = $m;
            return [$m, $state];
        }
        return [null, null];
    });
}

function fd_mod_delete(string $id): bool
{
    return fd_db_with_lock(FD_MODS_FILE, function ($state) use ($id) {
        if (!is_array($state) || !isset($state['mods']) || !is_array($state['mods'])) {
            return [false, null];
        }
        $before = count($state['mods']);
        $state['mods'] = array_values(array_filter(
            $state['mods'],
            static fn($m) => ($m['id'] ?? null) !== $id,
        ));
        $changed = count($state['mods']) < $before;
        return [$changed, $changed ? $state : null];
    });
}
