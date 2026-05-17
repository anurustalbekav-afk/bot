<?php
/**
 * Слой доступа к данным. Тонкая обёртка над PDO.
 *
 * Все методы возвращают ассоциативные массивы или null. Поле
 * `password_hash` отдаётся как есть; никакого автоматического
 * преобразования — слой validation/auth знает, что с ним делать.
 */

declare(strict_types=1);

final class DB
{
    private static ?PDO $pdo = null;
    private static array $cfg = [];

    public static function configure(array $cfg): void
    {
        self::$cfg = $cfg;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $c = self::$cfg;
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'], (int)$c['port'], $c['name'], $c['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Унифицируем кодировку явно (на старых MariaDB charset в DSN
        // иногда игнорируется).
        $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        self::$pdo = $pdo;
        return $pdo;
    }

    /**
     * Идемпотентно создаёт таблицу users, если её нет.
     * Удобно для первого запуска без ручного импорта schema.sql.
     */
    public static function init(): void
    {
        self::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id            CHAR(36)      NOT NULL,
                email         VARCHAR(254)  NOT NULL,
                login         VARCHAR(24)   NOT NULL,
                password_hash VARCHAR(255)  NOT NULL,
                created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_users_email (email),
                UNIQUE KEY uk_users_login (login)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public static function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') return null;
        $stmt = self::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByLogin(string $login): ?array
    {
        $login = trim($login);
        if ($login === '') return null;
        $stmt = self::pdo()->prepare('SELECT * FROM users WHERE LOWER(login) = LOWER(?) LIMIT 1');
        $stmt->execute([$login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmailOrLogin(string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') return null;
        $stmt = self::pdo()->prepare(
            'SELECT * FROM users WHERE email = LOWER(?) OR LOWER(login) = LOWER(?) LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(string $id): ?array
    {
        if ($id === '') return null;
        $stmt = self::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Создаёт пользователя. Бросает PDOException с SQLSTATE 23000
     * при нарушении уникальности — вызывающий код это ловит.
     */
    public static function createUser(array $u): array
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO users (id, email, login, password_hash, created_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $u['id'],
            strtolower($u['email']),
            $u['login'],
            $u['password_hash'],
            $u['created_at'] ?? gmdate('Y-m-d H:i:s'),
        ]);
        return self::findById($u['id']) ?? $u;
    }
}
