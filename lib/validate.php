<?php
declare(strict_types=1);

/**
 * Валидаторы возвращают строковый код ошибки или null, если всё ок.
 * Коды совпадают с серверным API: фронт переводит их через i18n.
 */

final class Validate
{
    public static function email(string $email): ?string
    {
        $email = trim($email);
        if ($email === '' || strlen($email) > 254) return 'invalid_email';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'invalid_email';
        return null;
    }

    public static function login(string $login): ?string
    {
        $login = trim($login);
        if (!preg_match('/^[A-Za-z0-9_]{3,24}$/', $login)) return 'invalid_login';
        return null;
    }

    public static function password(string $password): ?string
    {
        $len = strlen($password);
        if ($len < 8 || $len > 128) return 'invalid_password';
        return null;
    }
}
