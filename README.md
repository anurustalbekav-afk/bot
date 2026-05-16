# fear.dev

Стартовая база для сайта продажи модов SAMP и скриптов.
На текущем этапе реализована система авторизации.

## Стек

- **PHP 8.1+** (используются только встроенные функции, без Composer)
- Хранилище: JSON-файл (`data/users.json`) с file-locking — легко заменить на PDO/SQLite/MySQL
- Хеш паролей: `password_hash` (bcrypt) + `password_verify`
- Сессии: нативные `$_SESSION`, cookie `fd_session` (`HttpOnly`, `SameSite=Lax`)
- Фронт: статические HTML/CSS/JS, без сборки. i18n: RU / UK / EN

## Запуск (локально)

```bash
cp .env.example .env
php -S localhost:8000 -t public router.php
# открой http://localhost:8000
```

## Запуск (production, Apache)

- Корень DocumentRoot — папка `public/`
- Файл `public/.htaccess` уже включает rewrite для `/api/<name>` → `/api/<name>.php`
- Папка `data/` должна быть **выше** DocumentRoot или хотя бы недоступна по HTTP. По умолчанию мы кладём её рядом с `public/`, и доступ к ней Apache не маршрутизирует, потому что DocumentRoot указывает только на `public/`.

## Запуск (production, Nginx + PHP-FPM)

Минимальный фрагмент:

```nginx
server {
    listen 80;
    root /var/www/fear.dev/public;
    index index.html;

    # /api/<name>  ->  /api/<name>.php
    location ~ ^/api/([a-z][a-z0-9_-]+)/?$ {
        try_files /api/$1.php =404;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location / { try_files $uri $uri.html $uri/ =404; }
}
```

## Маршруты

| Метод | URL                  | Описание                                     |
|-------|----------------------|----------------------------------------------|
| GET   | `/`                  | Страница входа                               |
| GET   | `/register.html`     | Страница регистрации                         |
| GET   | `/dashboard.html`    | Кабинет (требуется сессия)                   |
| POST  | `/api/register.php`  | `{ email, login, password }` → создаёт юзера |
| POST  | `/api/login.php`     | `{ identifier, password }` (email или login) |
| GET   | `/api/me.php`        | Текущий пользователь                         |
| POST  | `/api/logout.php`    | Сброс сессии                                 |

## Правила валидации

- email: стандартный формат (`FILTER_VALIDATE_EMAIL`), до 254 символов
- login: 3–24 символа, `[A-Za-z0-9_]`
- password: 8–128 символов

## Безопасность

- Пароли хешируются `password_hash(..., PASSWORD_BCRYPT)`
- Сравнение через `password_verify` (constant-time)
- При несуществующем пользователе всё равно выполняется проверка по dummy-хешу — это сглаживает таймминговый сигнал
- Сессии: `HttpOnly`, `SameSite=Lax`, регенерация ID после login/register
- Простой rate-limit по IP на `/api/login.php` и `/api/register.php`

## Структура

```
bot/
├── public/                  # web root
│   ├── index.html           # вход
│   ├── register.html        # регистрация
│   ├── dashboard.html       # кабинет
│   ├── assets/              # styles.css, i18n.js, auth.js, favicon.svg
│   ├── api/
│   │   ├── register.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── me.php
│   └── .htaccess
├── src/
│   ├── bootstrap.php        # сессии, .env, общие хелперы
│   ├── db.php               # хранилище (JSON + flock)
│   └── validate.php         # валидация полей
├── data/                    # users.json + sessions/ (создаётся автоматически)
├── router.php               # для `php -S`
├── .env.example
└── README.md
```

## Что дальше (для магазина SAMP-модов)

1. Каталог товаров (`mods`, `scripts`) с категориями, медиа и ценами
2. Привязка покупки к нику SAMP / IP / HWID — через дополнительное поле в профиле
3. Лицензионные ключи: генерация, выдача после оплаты, проверка
4. Платёжный шлюз (CryptoBot, ЮKassa, Stripe — что подходит юрисдикции)
5. Админка: модерация, выпуск ключей, статистика продаж
6. Замена JSON-хранилища на MySQL/PostgreSQL через PDO + миграции
