# fear.dev

Стартовая база для сайта продажи модов SAMP и скриптов.
На текущем этапе реализована система авторизации.

## Стек

- **Node.js 18+**
- **MySQL / MariaDB** (через phpMyAdmin) — хранилище пользователей
- Драйвер: [`mysql2`](https://www.npmjs.com/package/mysql2) (единственная зависимость)
- Хеш паролей: `crypto.scrypt` (без внешних либ)
- Сессии: подписанный HMAC-SHA256 токен в `HttpOnly` cookie
- Фронт: статические HTML/CSS/JS, без сборки. i18n: RU / UK / EN

## Подготовка БД через phpMyAdmin

1. Открой phpMyAdmin (например, `http://localhost/phpmyadmin`).
2. Перейди на вкладку **Импорт** (или **SQL**) и загрузи / вставь содержимое
   файла `sql/schema.sql`. Это создаст БД `fear_dev` и таблицу `users`.
3. (Опционально) Создай отдельного пользователя MySQL с правами на эту БД
   через вкладку **Учётные записи пользователей**.

> Если не хочешь импортировать вручную — таблица создастся сама при первом
> запуске сервера (`db.init()`). Базу `fear_dev` всё равно нужно создать
> заранее (вкладка **Базы данных** → имя `fear_dev`, сравнение `utf8mb4_unicode_ci`).

## Установка и запуск

```bash
cp .env.example .env
# отредактируй DB_*, AUTH_SECRET
npm install
node server.js
# открой http://localhost:3000
```

## Переменные окружения

| Переменная           | Назначение                                       |
|----------------------|--------------------------------------------------|
| `PORT`               | Порт HTTP-сервера (по умолчанию 3000)            |
| `AUTH_SECRET`        | Секрет для подписи сессионных токенов            |
| `SESSION_TTL`        | Время жизни сессии, секунд (по умолчанию 7 дней) |
| `DB_HOST`            | Хост MySQL (часто `127.0.0.1`)                   |
| `DB_PORT`            | Порт MySQL (`3306`)                              |
| `DB_USER`            | Пользователь MySQL                               |
| `DB_PASSWORD`        | Пароль MySQL                                     |
| `DB_NAME`            | Имя БД (`fear_dev`)                              |
| `DB_CONNECTION_LIMIT`| Размер пула соединений (по умолчанию 10)         |

## Маршруты

| Метод | URL              | Описание                                      |
|-------|------------------|-----------------------------------------------|
| GET   | `/`              | Страница входа                                |
| GET   | `/register.html` | Страница регистрации                          |
| GET   | `/dashboard.html`| Кабинет (требуется сессия)                    |
| POST  | `/api/register`  | `{ email, login, password }` → создаёт юзера  |
| POST  | `/api/login`     | `{ identifier, password }` (email или login)  |
| GET   | `/api/me`        | Текущий пользователь                          |
| POST  | `/api/logout`    | Сброс сессии                                  |

## Схема БД

```sql
CREATE TABLE users (
  id            CHAR(36)      NOT NULL,
  email         VARCHAR(254)  NOT NULL,
  login         VARCHAR(24)   NOT NULL,
  password_hash VARCHAR(255)  NOT NULL,
  created_at    DATETIME(3)   NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY uk_users_email (email),
  UNIQUE KEY uk_users_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Миграция со старого JSON-хранилища

Если у тебя уже был файл `data/users.json` от предыдущей версии:

```bash
npm run db:migrate-json
```

Скрипт перенесёт пользователей в MySQL, пропустив тех, у кого email/login
уже существует в БД. Хеши паролей переносятся как есть — пользователи
смогут логиниться без сброса пароля.

## Правила валидации

- email: стандартный формат, до 254 символов
- login: 3–24 символа, `[A-Za-z0-9_]`
- password: 8–128 символов

## Безопасность

- Пароли хешируются `scrypt` с уникальной солью (`scrypt$N$r$p$salt$hash`)
- Сессионный токен подписан HMAC-SHA256 ключом `AUTH_SECRET` и хранится в `HttpOnly` cookie c `SameSite=Lax`
- Простой rate-limit по IP на `/api/login` и `/api/register`
- Сравнение хешей через `crypto.timingSafeEqual`
- Дубликаты email/login отлавливаются уникальными индексами в БД
- Все запросы — параметризованные (защита от SQL-инъекций)

## Что дальше (для магазина SAMP-модов)

1. Каталог товаров (`mods`, `scripts`) с категориями, медиа и ценами
2. Привязка покупки к нику SAMP / IP / HWID — через дополнительное поле в профиле
3. Лицензионные ключи: генерация, выдача после оплаты, проверка
4. Платёжный шлюз (CryptoBot, ЮKassa, Stripe — что подходит юрисдикции)
5. Админка: модерация, выпуск ключей, статистика продаж
