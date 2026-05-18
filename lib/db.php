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

    public const PRODUCT_DRAFT     = 'draft';
    public const PRODUCT_PUBLISHED = 'published';
    public const PRODUCT_STATUSES  = [self::PRODUCT_DRAFT, self::PRODUCT_PUBLISHED];

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
     * Идемпотентно создаёт таблицы и догоняет схему до актуальной.
     * Запускается на каждом запросе из bootstrap.php — операции дёшевые
     * (только проверки information_schema), но позволяют не думать про
     * ручные миграции после деплоя.
     */
    public static function init(): void
    {
        $pdo = self::pdo();

        // --- users ----------------------------------------------------------
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
        if (!self::columnExists('users', 'role')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(16) NOT NULL DEFAULT 'user' AFTER password_hash");
        }
        if (!self::indexExists('users', 'idx_users_role')) {
            $pdo->exec("ALTER TABLE users ADD KEY idx_users_role (role)");
        }

        // --- categories -----------------------------------------------------
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS categories (
                id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                slug        VARCHAR(64)   NOT NULL,
                title       VARCHAR(120)  NOT NULL,
                position    INT           NOT NULL DEFAULT 0,
                created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_categories_slug (slug),
                KEY idx_categories_position (position)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Сидим стартовыми категориями только при пустой таблице, чтобы
        // не мешать существующим данным.
        $count = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (slug, title, position) VALUES (?, ?, ?)'
            );
            foreach ([
                ['mods',    'Моды',    10],
                ['scripts', 'Скрипты', 20],
                ['maps',    'Карты',   30],
            ] as $c) {
                $stmt->execute($c);
            }
        }

        // --- products -------------------------------------------------------
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS products (
                id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                slug         VARCHAR(120)  NOT NULL,
                category_id  INT UNSIGNED  NULL,
                title        VARCHAR(180)  NOT NULL,
                summary      VARCHAR(255)  NOT NULL DEFAULT '',
                description  TEXT          NULL,
                price_cents  INT UNSIGNED  NOT NULL DEFAULT 0,
                currency     CHAR(3)       NOT NULL DEFAULT 'USD',
                image_url    VARCHAR(512)  NOT NULL DEFAULT '',
                status       VARCHAR(16)   NOT NULL DEFAULT 'draft',
                created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_products_slug (slug),
                KEY idx_products_category (category_id),
                KEY idx_products_status (status),
                CONSTRAINT fk_products_category
                  FOREIGN KEY (category_id) REFERENCES categories (id)
                  ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
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

    // ===================== users =========================================

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
                FROM users $where
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

    // ===================== categories ====================================

    public static function listCategories(): array
    {
        return self::pdo()
            ->query('SELECT * FROM categories ORDER BY position, title')
            ->fetchAll();
    }

    public static function findCategory(int $id): ?array
    {
        $stmt = self::pdo()->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findCategoryBySlug(string $slug): ?array
    {
        $stmt = self::pdo()->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function createCategory(string $slug, string $title, int $position = 0): array
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO categories (slug, title, position) VALUES (?, ?, ?)'
        );
        $stmt->execute([$slug, $title, $position]);
        $id = (int) self::pdo()->lastInsertId();
        return self::findCategory($id) ?? ['id' => $id, 'slug' => $slug, 'title' => $title, 'position' => $position];
    }

    public static function updateCategory(int $id, string $slug, string $title, int $position): bool
    {
        $stmt = self::pdo()->prepare(
            'UPDATE categories SET slug = ?, title = ?, position = ? WHERE id = ?'
        );
        $stmt->execute([$slug, $title, $position, $id]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteCategory(int $id): bool
    {
        // FK ON DELETE SET NULL обнулит products.category_id — сами товары уцелеют.
        $stmt = self::pdo()->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ===================== products ======================================

    /**
     * Список товаров с join'ом категории.
     * $filters: ['status' => 'published', 'category_id' => 1, 'search' => 'foo']
     */
    public static function listProducts(array $filters = [], string $sort = 'new', int $limit = 24, int $offset = 0): array
    {
        $limit  = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'p.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE ? OR p.summary LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $orderBy = match ($sort) {
            'price_asc'  => 'p.price_cents ASC',
            'price_desc' => 'p.price_cents DESC',
            'title'      => 'p.title ASC',
            default      => 'p.created_at DESC',
        };

        $sql = 'SELECT p.*, c.slug AS category_slug, c.title AS category_title
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . " ORDER BY $orderBy LIMIT $limit OFFSET $offset";

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countProducts(array $filters = []): int
    {
        $where = [];
        $params = [];
        if (!empty($filters['status']))      { $where[] = 'status = ?';      $params[] = $filters['status']; }
        if (!empty($filters['category_id'])) { $where[] = 'category_id = ?'; $params[] = (int)$filters['category_id']; }
        if (!empty($filters['search']))      {
            $where[] = '(title LIKE ? OR summary LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like;
        }
        $sql = 'SELECT COUNT(*) FROM products' . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function findProduct(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT p.*, c.slug AS category_slug, c.title AS category_title
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findProductBySlug(string $slug): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT p.*, c.slug AS category_slug, c.title AS category_title
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? LIMIT 1'
        );
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function createProduct(array $p): array
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO products
                (slug, category_id, title, summary, description, price_cents, currency, image_url, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $p['slug'],
            $p['category_id'] ?? null,
            $p['title'],
            $p['summary']     ?? '',
            $p['description'] ?? null,
            (int)($p['price_cents'] ?? 0),
            strtoupper((string)($p['currency'] ?? 'USD')),
            $p['image_url']   ?? '',
            $p['status']      ?? self::PRODUCT_DRAFT,
        ]);
        $id = (int) self::pdo()->lastInsertId();
        return self::findProduct($id) ?? array_merge($p, ['id' => $id]);
    }

    public static function updateProduct(int $id, array $p): bool
    {
        $stmt = self::pdo()->prepare(
            'UPDATE products SET
                slug = ?, category_id = ?, title = ?, summary = ?, description = ?,
                price_cents = ?, currency = ?, image_url = ?, status = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $p['slug'],
            $p['category_id'] ?? null,
            $p['title'],
            $p['summary']     ?? '',
            $p['description'] ?? null,
            (int)($p['price_cents'] ?? 0),
            strtoupper((string)($p['currency'] ?? 'USD')),
            $p['image_url']   ?? '',
            $p['status']      ?? self::PRODUCT_DRAFT,
            $id,
        ]);
        return $stmt->rowCount() >= 0; // UPDATE без изменений тоже считаем успешным
    }

    public static function deleteProduct(int $id): bool
    {
        $stmt = self::pdo()->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
