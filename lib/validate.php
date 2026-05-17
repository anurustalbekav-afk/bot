<?php
declare(strict_types=1);

/**
 * Input validation helpers. Each function returns null on success or an
 * error code string suitable for client-side i18n lookup.
 */

const FD_LOGIN_RE = '/^[A-Za-z0-9_]{3,24}$/';

function fd_validate_email(mixed $email): ?string
{
    if (!is_string($email)) return 'invalid_email';
    $v = trim($email);
    if ($v === '') return 'email_required';
    if (mb_strlen($v) > 254) return 'email_too_long';
    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) return 'invalid_email';
    return null;
}

function fd_validate_login(mixed $login): ?string
{
    if (!is_string($login)) return 'invalid_login';
    $v = trim($login);
    if ($v === '') return 'login_required';
    if (!preg_match(FD_LOGIN_RE, $v)) return 'invalid_login';
    return null;
}

function fd_validate_password(mixed $password): ?string
{
    if (!is_string($password)) return 'invalid_password';
    if (strlen($password) < 8) return 'password_too_short';
    if (strlen($password) > 128) return 'password_too_long';
    return null;
}

function fd_validate_url(mixed $value, bool $allowEmpty = false): ?string
{
    if (!is_string($value)) return 'invalid_url';
    $v = trim($value);
    if ($v === '') return $allowEmpty ? null : 'url_required';
    if (mb_strlen($v) > 2048) return 'url_too_long';
    if (!preg_match('#^https?://[^\s]+$#i', $v)) return 'invalid_url';
    return null;
}

/**
 * Validate a mod payload. When $partial is true, missing fields are skipped
 * (use this for PATCH-style updates).
 *
 * @return array{value: array<string,mixed>, error: ?string}
 */
function fd_validate_mod(array $input, bool $partial = false): array
{
    $out = [];
    $err = null;

    if (array_key_exists('title', $input) || !$partial) {
        $t = trim((string) ($input['title'] ?? ''));
        if ($t === '')              $err ??= 'mod_title_required';
        elseif (mb_strlen($t) > 120) $err ??= 'mod_title_too_long';
        else                         $out['title'] = $t;
    }
    if (array_key_exists('description', $input) || !$partial) {
        $d = trim((string) ($input['description'] ?? ''));
        if (mb_strlen($d) > 4000) $err ??= 'mod_description_too_long';
        else                       $out['description'] = $d;
    }
    if (array_key_exists('banner', $input) || !$partial) {
        $e = fd_validate_url($input['banner'] ?? '', allowEmpty: true);
        if ($e) $err ??= 'mod_banner_' . $e;
        else    $out['banner'] = trim((string) ($input['banner'] ?? ''));
    }
    if (array_key_exists('url', $input) || !$partial) {
        $e = fd_validate_url($input['url'] ?? '', allowEmpty: false);
        if ($e) $err ??= 'mod_url_' . $e;
        else    $out['url'] = trim((string) $input['url']);
    }
    if (array_key_exists('price', $input) || !$partial) {
        $n = filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($n === false || $n < 0)       $err ??= 'mod_price_invalid';
        elseif ($n > 1_000_000)           $err ??= 'mod_price_too_large';
        else                              $out['price'] = round((float) $n, 2);
    }
    if (array_key_exists('currency', $input)) {
        $c = strtoupper(trim((string) $input['currency']));
        if (!preg_match('/^[A-Z]{3}$/', $c)) $err ??= 'mod_currency_invalid';
        else                                  $out['currency'] = $c;
    }
    if (array_key_exists('type', $input)) {
        $out['type'] = ($input['type'] ?? null) === 'script' ? 'script' : 'mod';
    }

    return ['value' => $out, 'error' => $err];
}

/**
 * @return array{value: ?array<string,mixed>, error: ?string}
 */
function fd_validate_topup(array $input): array
{
    $err = null;
    $amount = filter_var($input['amount'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($amount === false || $amount <= 0)  $err ??= 'topup_amount_invalid';
    elseif ($amount > 1_000_000)            $err ??= 'topup_amount_too_large';

    $currency = strtoupper(trim((string) ($input['currency'] ?? 'USD')));
    if (!preg_match('/^[A-Z]{3}$/', $currency)) $err ??= 'topup_currency_invalid';

    $method = isset($input['method']) ? mb_substr((string) $input['method'], 0, 64) : null;
    $note   = isset($input['note'])   ? mb_substr((string) $input['note'],   0, 280) : null;

    if ($err) return ['value' => null, 'error' => $err];
    return [
        'value' => [
            'amount'   => round((float) $amount, 2),
            'currency' => $currency,
            'method'   => $method,
            'note'     => $note,
        ],
        'error' => null,
    ];
}

/**
 * @return array{value: ?array<string,mixed>, error: ?string}
 */
function fd_validate_purchase(array $input): array
{
    $err = null;
    $kind = ($input['kind'] ?? null) === 'script' ? 'script' : 'mod';
    $modId = isset($input['modId']) && is_string($input['modId']) && $input['modId'] !== ''
        ? $input['modId'] : null;
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '' && !$modId) $err ??= 'purchase_title_required';
    if (mb_strlen($title) > 120)  $err ??= 'purchase_title_too_long';

    $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($price === false || $price < 0) $err ??= 'purchase_price_invalid';

    $currency = strtoupper(trim((string) ($input['currency'] ?? 'USD')));
    if (!preg_match('/^[A-Z]{3}$/', $currency)) $err ??= 'purchase_currency_invalid';

    if ($err) return ['value' => null, 'error' => $err];
    return [
        'value' => [
            'kind'     => $kind,
            'modId'    => $modId,
            'title'    => $title,
            'price'    => round((float) $price, 2),
            'currency' => $currency,
        ],
        'error' => null,
    ];
}
