<?php
/**
 * Слой доступа к данным. Тонкая обёртка над PDO.
 *
 * Все методы возвращают ассоциативные массивы или null. Поле
 * `password_hash` отдаётся как есть; никакого автоматического
 * преобразования — слой validation/auth знает, что с ним делать.
 *
 * Роли: 'user' (по умолчанию) и 'admin'. Поле role в БД, чтобы
 * выдавать админку прямо из phpMyAdmin без правки кода.
 */

declare(strict_types=1);

final class DB
{
    public const ROLE_USER  = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLES      = [self::ROLE_USER, self::ROLE_ADMIN];

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
     * Идемпотентно создаёт таблицу users и догоняет схему до актуальной.
     * Запускается на каждом запросе из bootstrap.php — операции дёшевые
     * (только проверки information_schema), но позволяют не думать про
     * ручные миграции после деплоя.
     */
    public static function init(): void
    {
        $pdo = self::pdo();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id            CHAR(36)      NOT NULL,
                email         VARCHAR(254)  NOT NULL,
                login         VARCHAR(24)   NOT NULL,
                password_hash VARCHAR(255)  NOT NULL,
                role          VARCHAR(16)   NOT NULL DEFAULT 'user',
                created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_users_email (email),
                UNIQUE KEY uk_users_login (login),
                KEY idx_users_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Если таблица существовала раньше (без role) — добавляем колонку.
        if (!self::columnExists('users', 'role')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(16) NOT NULL DEFAULT 'user' AFTER password_hash");
        }
        if (!self::indexExists('users', 'idx_users_role')) {
            $pdo->exec("ALTER TABLE users ADD KEY idx_users_role (role)");
        }
    }

    private static function columnExists(string $table, string $column): bool
    {
        $stmt = self::pdo()->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
             LIMIT 1'
        );
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }

    private static function indexExists(string $table, string $index): bool
    {
        $stmt = self::pdo()->prepare(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
             LIMIT 1'
        );
        $stmt->execute([$table, $index]);
        return (bool)$stmt->fetchColumn();
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
            'INSERT INTO users (id, email, login, password_hash, role, created_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $u['id'],
            strtolower($u['email']),
            $u['login'],
            $u['password_hash'],
            $u['role'] ?? self::ROLE_USER,
            $u['created_at'] ?? gmdate('Y-m-d H:i:s'),
        ]);
        return self::findById($u['id']) ?? $u;
    }

    /**
     * Список пользователей для админки. С простой пагинацией и поиском
     * по login/email. LIMIT/OFFSET подставляются как int — не через bind,
     * потому что MySQL не любит bound LIMIT в emulate=false режиме.
     */
    public static function listUsers(string $search = '', int $limit = 50, int $offset = 0): array
    {
        $limit  = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE login LIKE ? OR email LIKE ?';
            $like = '%' . $search . '%';
            $params = [$like, $like];
        }

        $sql = "SELECT id, email, login, role, created_at
                FROM users
                $where
                ORDER BY created_at DESC
                LIMIT $limit OFFSET $offset";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countUsers(string $search = ''): int
    {
        if ($search === '') {
            return (int) self::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        }
        $stmt = self::pdo()->prepare(
            'SELECT COUNT(*) FROM users WHERE login LIKE ? OR email LIKE ?'
        );
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like]);
        return (int) $stmt->fetchColumn();
    }

    public static function setRole(string $id, string $role): bool
    {
        if (!in_array($role, self::ROLES, true)) {
            throw new InvalidArgumentException('invalid_role');
        }
        $stmt = self::pdo()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteUser(string $id): bool
    {
        $stmt = self::pdo()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function countAdmins(): int
    {
        return (int) self::pdo()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }
}
