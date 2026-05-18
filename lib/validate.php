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

    public static function slug(string $slug): ?string
    {
        $slug = trim($slug);
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) return 'invalid_slug';
        if (strlen($slug) > 120) return 'invalid_slug';
        return null;
    }

    public static function productTitle(string $title): ?string
    {
        $title = trim($title);
        $len = mb_strlen($title);
        if ($len < 2 || $len > 180) return 'invalid_title';
        return null;
    }

    public static function categoryTitle(string $title): ?string
    {
        $title = trim($title);
        $len = mb_strlen($title);
        if ($len < 2 || $len > 120) return 'invalid_title';
        return null;
    }

    public static function priceCents(int $cents): ?string
    {
        if ($cents < 0 || $cents > 99_999_999) return 'invalid_price';
        return null;
    }

    public static function currency(string $code): ?string
    {
        if (!preg_match('/^[A-Z]{3}$/', strtoupper(trim($code)))) return 'invalid_currency';
        return null;
    }

    public static function imageUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') return null; // картинка опциональна
        if (strlen($url) > 512) return 'invalid_image_url';
        // Допускаем абсолютные http/https и относительные пути от корня (/uploads/foo.png)
        if (preg_match('#^/[\w\-./]+$#', $url)) return null;
        if (filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url)) return null;
        return 'invalid_image_url';
    }

    /**
     * Превращает произвольный заголовок в slug. Транслитерация для кириллицы.
     */
    public static function makeSlug(string $title): string
    {
        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh',
            'з'=>'z','и'=>'i','й'=>'i','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
            'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c',
            'ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
            'і'=>'i','ї'=>'i','є'=>'e','ґ'=>'g',
        ];
        $s = mb_strtolower(trim($title), 'UTF-8');
        $s = strtr($s, $map);
        $s = preg_replace('/[^a-z0-9]+/u', '-', $s) ?? '';
        $s = trim($s, '-');
        return $s === '' ? 'item' : substr($s, 0, 120);
    }
}
