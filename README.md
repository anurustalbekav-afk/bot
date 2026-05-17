# fear.dev

Стартовая база для сайта продажи модов SAMP и скриптов. На текущем этапе
реализованы: регистрация/вход с сессиями и админ-панель с двумя вкладками
(пользователи и моды).

## Стек

- **PHP 8.1+** (без внешних зависимостей)
- Хранилище: JSON-файлы в `data/` (`users.json`, `mods.json`) — легко заменить на SQLite/Postgres
- Хеш паролей: `password_hash` (bcrypt)
- Сессии: стандартные PHP-сессии в `HttpOnly` cookie c `SameSite=Lax`
- Фронт: статические HTML+CSS+JS, без сборки. i18n: RU / UK / EN

## Структура

```
bot/
├── lib/                   # ядро: bootstrap, db, auth, validate, admin
└── public/                # docroot
    ├── index.php          # вход
    ├── register.php       # регистрация
    ├── dashboard.php      # личный кабинет
    ├── admin.php          # админ-панель (вкладки: пользователи / моды)
    ├── api/
    │   ├── login.php      register.php  logout.php  me.php
    │   └── admin/
    │       ├── users.php       # GET список пользователей
    │       ├── mods.php        # GET / POST / PATCH / DELETE
    │       ├── topup.php       # POST: добавить пополнение пользователю
    │       └── purchase.php    # POST: записать покупку (мод/скрипт)
    └── assets/            # styles.css, i18n.js, auth.js, admin.js, favicon.svg
```

## Запуск (локально)

```bash
cp .env.example .env
# при желании — задайте ADMIN_LOGINS=ваш_логин

# встроенный сервер PHP, document root = public/
php -S 127.0.0.1:8000 -t public
# открывайте http://127.0.0.1:8000
```

## Маршруты

| Метод       | URL                          | Описание                                            |
|-------------|------------------------------|-----------------------------------------------------|
| GET         | `/index.php`                 | Страница входа                                      |
| GET         | `/register.php`              | Страница регистрации                                |
| GET         | `/dashboard.php`             | Личный кабинет (требуется сессия)                   |
| GET         | `/admin.php`                 | Админ-панель (только для админов)                   |
| POST        | `/api/register.php`          | `{ email, login, password }`                        |
| POST        | `/api/login.php`             | `{ identifier, password }` (email или login)        |
| POST        | `/api/logout.php`            | Сброс сессии                                        |
| GET         | `/api/me.php`                | Текущий пользователь                                |
| GET         | `/api/admin/users.php`       | Список пользователей (admin)                        |
| GET/POST/PATCH/DELETE | `/api/admin/mods.php` | CRUD над модами (admin)                            |
| POST        | `/api/admin/topup.php`       | `{ userId, amount, currency, method?, note? }`      |
| POST        | `/api/admin/purchase.php`    | `{ userId, modId? \| title, price, currency, kind }`|

## Админ-панель

- **Пользователи** — таблица с логином, email, IP (первый и последний), датой
  регистрации, суммой пополнений и количеством покупок. Кнопка «Детали»
  открывает модалку с полным профилем, историей пополнений и покупок и
  кнопками «+ Пополнение» / «+ Покупка».
- **Моды** — карточки модов (баннер + цена + ссылка). Кнопка «+ Добавить мод»,
  карандаш — модалка редактирования (название / баннер / ссылка / цена),
  корзина — удаление с подтверждением.

### Как сделать кого-то админом

1. На время первого деплоя укажите свой логин или email в `.env`:
   ```
   ADMIN_LOGINS=root,admin@fear.dev
   ```
2. После создания первого админа можно убрать `ADMIN_LOGINS` и продолжать
   назначать админов через данные (флаг `isAdmin: true` в `data/users.json`).

## Правила валидации

- email: стандартный формат, до 254 символов
- login: 3–24 символа, `[A-Za-z0-9_]`
- password: 8–128 символов
- mod.title: 1–120 символов
- mod.url / mod.banner: `http(s)://…`
- mod.price: число от 0 до 1 000 000

## Безопасность

- Пароли — `password_hash` (bcrypt) + `password_verify`
- HMAC-сравнение через `hash_equals` (внутри `password_verify`)
- `session_regenerate_id(true)` после логина — защита от session fixation
- Cookie сессии: `HttpOnly`, `SameSite=Lax`, `Secure` под HTTPS
- Простой rate-limit по IP на `/api/login.php` и `/api/register.php`
- Все endpoints возвращают `Cache-Control: no-store`
