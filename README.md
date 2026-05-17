# fear.dev

Стартовая база для сайта продажи модов SAMP и скриптов.
На текущем этапе реализована система авторизации.

## Стек

- **PHP 8.0+** (нативный, без Composer и фреймворков)
- **MySQL / MariaDB** через **phpMyAdmin**
- Расширения PHP: `pdo`, `pdo_mysql`, `json`, `mbstring`, `openssl`
- Хеш паролей: `password_hash` (bcrypt)
- Сессии: подписанный HMAC-SHA256 токен в `HttpOnly` cookie (без `$_SESSION` —
  не зависит от настроек хостинга)
- Фронт: статические HTML/CSS/JS, без сборки. i18n: RU / UK / EN

## Структура

```
.
├── config.example.php   ← шаблон конфигурации
├── config.php           ← локальный (в .gitignore)
├── index.php            ← редирект на login или dashboard
├── login.php
├── register.php
├── dashboard.php
├── api/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── me.php
├── lib/                 ← подключаемые модули (закрыты .htaccess)
│   ├── bootstrap.php
│   ├── db.php
│   ├── auth.php
│   ├── validate.php
│   └── http.php
├── assets/              ← статика
│   ├── styles.css
│   ├── auth.js
│   ├── i18n.js
│   └── favicon.svg
├── sql/
│   └── schema.sql
├── .htaccess
└── .gitignore
```

## Установка

### 1. Подготовка БД

Открой phpMyAdmin → вкладка **Импорт** → загрузи `sql/schema.sql`.
Создастся БД `fear_dev` и таблица `users`.

> Если ручной импорт пропустишь — таблица создастся сама при первом
> запросе. Главное, чтобы БД `fear_dev` уже существовала.

### 2. Конфиг

```bash
cp config.example.php config.php
```

В `config.php` пропиши:
- `db.user`, `db.password`, `db.host` — данные MySQL (на локальном XAMPP/OpenServer
  обычно `root` с пустым паролем)
- `auth.secret` — длинная случайная строка. Сгенерировать:
  ```bash
  php -r "echo bin2hex(random_bytes(32));"
  ```

### 3. Залей файлы в DocumentRoot

- **XAMPP**: в `htdocs/fear-dev/`
- **OpenServer**: в `domains/fear.dev/`
- **shared-хостинг**: в `public_html/`

Открой `http://localhost/fear-dev/` (или свой домен).

## Маршруты

| Метод | URL                  | Описание                                    |
|-------|----------------------|---------------------------------------------|
| GET   | `/`                  | Редирект на `/login.php` или `/dashboard.php` |
| GET   | `/login.php`         | Страница входа                              |
| GET   | `/register.php`      | Страница регистрации                        |
| GET   | `/dashboard.php`     | Кабинет (требуется сессия)                  |
| POST  | `/api/register.php`  | `{ email, login, password }`                |
| POST  | `/api/login.php`     | `{ identifier, password }` (email или login) |
| GET   | `/api/me.php`        | Текущий пользователь                        |
| POST  | `/api/logout.php`    | Сброс сессии                                |

## Схема БД

```sql
CREATE TABLE users (
  id            CHAR(36)      NOT NULL,
  email         VARCHAR(254)  NOT NULL,
  login         VARCHAR(24)   NOT NULL,
  password_hash VARCHAR(255)  NOT NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_users_email (email),
  UNIQUE KEY uk_users_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Правила валидации

- email: стандартный формат, до 254 символов
- login: 3–24 символа, `[A-Za-z0-9_]`
- password: 8–128 символов

## Безопасность

- Пароли — `password_hash(PASSWORD_DEFAULT)` (bcrypt с автоматической солью)
- Сессионный токен подписан HMAC-SHA256 секретом из `config.php`,
  лежит в `HttpOnly`, `SameSite=Lax` cookie
- Все SQL-запросы параметризованные (PDO prepared statements)
- Уникальные индексы на `email` и `login` ловят гонку при одновременной регистрации
- Простой файловый rate-limit по IP на `/api/login.php` и `/api/register.php`
- `.htaccess` запрещает прямой доступ к `config.php`, `lib/`, `sql/`
- Проверка пароля через `hash_equals` / `password_verify` (constant-time)

## Что дальше (для магазина SAMP-модов)

1. Каталог товаров (`mods`, `scripts`) с категориями, медиа и ценами
2. Привязка покупки к нику SAMP / IP / HWID — через дополнительное поле в профиле
3. Лицензионные ключи: генерация, выдача после оплаты, проверка
4. Платёжный шлюз (CryptoBot, ЮKassa, Stripe)
5. Админка: модерация, выпуск ключей, статистика продаж
