<?php
// PHP 7.4+ compatible.

if (!defined('FD_LOGIN_RE')) {
    define('FD_LOGIN_RE', '/^[A-Za-z0-9_]{3,24}$/');
}

function fd_validate_email($email) {
    $v = trim((string)$email);
    if ($v === '') return 'email_required';
    if (strlen($v) > 254) return 'email_too_long';
    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) return 'invalid_email';
    return null;
}

function fd_validate_login($login) {
    $v = trim((string)$login);
    if ($v === '') return 'login_required';
    if (!preg_match(FD_LOGIN_RE, $v)) return 'invalid_login';
    return null;
}

function fd_validate_password($password) {
    $len = strlen((string)$password);
    if ($len < 8)   return 'password_too_short';
    if ($len > 128) return 'password_too_long';
    return null;
}
