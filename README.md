# fear.dev

Сайт-магазин для модов SAMP, скриптов и карт. На текущем этапе:
авторизация, роли, каталог с категориями и админ-панель управления товарами.

## Стек

- **PHP 8.0+** (нативный, без Composer и фреймворков)
- **MySQL / MariaDB** через **phpMyAdmin**
- Расширения PHP: `pdo`, `pdo_mysql`, `json`, `mbstring`, `openssl`
- Хеш паролей: `password_hash` (bcrypt)
- Сессии: подписанный HMAC-SHA256 токен в `HttpOnly` cookie
- Фронт: статические HTML/CSS/JS, без сборки. i18n: RU / UK / EN

## Структура

```
.
├── config.example.php        ← шаблон конфигурации
├── config.php                ← локальный (в .gitignore)
├── index.php                 ← редирект на /catalog.php
├── catalog.php               ← публичный каталог
├── product.php?slug=...      ← карточка товара
├── login.php / register.php
├── dashboard.php
├── admin.php                 ← редирект-совместимость → /admin/
├── admin/
│   ├── _guard.php            ← общий контроль доступа админ-страниц
│   ├── index.php             ← обзор и быстрые ссылки
│   ├── products.php          ← CRUD товаров
│   ├── categories.php        ← CRUD категорий
│   └── users.php             ← управление пользователями и ролями
├── api/
│   ├── login.php / register.php / logout.php / me.php
│   ├── categories.php        ← публичный список категорий
│   ├── products.php          ← публичный список товаров
│   ├── products/get.php      ← карточка товара
│   └── admin/
│       ├── users.php / role.php / delete.php
│       ├── categories.php    ← create | update | delete
│       └── products.php      ← create | update | delete | publish | unpublish
├── lib/                      ← подключаемые модули (закрыты .htaccess)
│   ├── bootstrap.php
│   ├── db.php
│   ├── auth.php
│   ├── validate.php
│   ├── http.php
│   └── layout.php
├── assets/
│   ├── styles.css            ← основные стили (формы, кнопки)
│   ├── catalog.css           ← каталог + админка
│   ├── catalog.js            ← клиентский каталог
│   ├── admin.js              ← клиент админки (юзеры)
│   ├── admin-products.js
│   ├── admin-categories.js
│   ├── auth.js
│   ├── i18n.js
│   └── favicon.svg
├── scripts/
│   └── grant-admin.php       ← CLI: выдать первого админа
├── sql/
│   └── schema.sql
└── .htaccess
```

## Установка

### 1. Подготовка БД (phpMyAdmin)

phpMyAdmin → **Импорт** → загрузи `sql/schema.sql`.
Создаст БД `fear_dev`, таблицы `users`, `categories` (с тремя стартовыми
категориями: моды/скрипты/карты) и `products`.

> Если ручной импорт пропустишь — таблицы создадутся сами при первом
> запросе. БД `fear_dev` всё равно нужно создать заранее.

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

Открой `http://localhost/fear-dev/` (или свой домен) — попадёшь на каталог.

### 4. Выдай первого админа

```bash
php scripts/grant-admin.php твой_логин
```

Или через phpMyAdmin → SQL:
```sql
UPDATE users SET role = 'admin' WHERE login = 'твой_логин';
```

После этого в шапке появится ссылка «Админка», в `/admin/` — товары,
категории, пользователи.

## Маршруты

### Публичные страницы
| URL                       | Что делает                                  |
|---------------------------|---------------------------------------------|
| `/`                       | Редирект на `/catalog.php`                  |
| `/catalog.php`            | Каталог (фильтр по категории, поиск, сорт.) |
| `/catalog.php?category=mods` | Каталог в одной категории                |
| `/product.php?slug=...`   | Карточка товара                             |
| `/login.php`              | Вход                                        |
| `/register.php`           | Регистрация                                 |
| `/dashboard.php`          | Кабинет (требуется сессия)                  |

### Админка
| URL                         | Описание                                  |
|-----------------------------|-------------------------------------------|
| `/admin/`                   | Дашборд со статистикой                    |
| `/admin/products.php`       | Список + модалка создания/редактирования  |
| `/admin/products.php?new=1` | Сразу открывает модалку нового товара     |
| `/admin/categories.php`     | Inline-редактирование категорий           |
| `/admin/users.php`          | Поиск, смена ролей, удаление              |

### API
| Метод | URL                          | Описание                             |
|-------|------------------------------|--------------------------------------|
| GET   | `/api/categories.php`        | Все категории                        |
| GET   | `/api/products.php`          | Список товаров (`?category=&search=&sort=`) |
| GET   | `/api/products/get.php?slug=`| Карточка товара                      |
| POST  | `/api/admin/categories.php`  | `{action: create\|update\|delete}`   |
| POST  | `/api/admin/products.php`    | `{action: create\|update\|delete\|publish\|unpublish}` |
| GET   | `/api/admin/users.php`       | Список пользователей                 |
| POST  | `/api/admin/role.php`        | `{id, role}`                         |
| POST  | `/api/admin/delete.php`      | `{id}`                               |

## Поля товара

| Поле | Тип | Описание |
|---|---|---|
| `slug` | строка | уникальный, латиница/цифры/дефисы. Если пустой — генерится из title. |
| `category_id` | int / null | категория (nullable, чтобы не терять товары при удалении категории) |
| `title` | строка | до 180 символов |
| `summary` | строка | до 255 символов, идёт в листинг |
| `description` | текст | полное описание в карточке |
| `price_cents` | int | в копейках/центах, чтобы не возиться с float |
| `currency` | строка(3) | USD, EUR, UAH, RUB и т.д. |
| `image_url` | строка | `/uploads/...` или `https://...` |
| `status` | `draft` / `published` | в каталоге показываются только `published` |

## Безопасность

- Пароли — `password_hash(PASSWORD_DEFAULT)` (bcrypt с автоматической солью)
- HMAC-SHA256 сессионный токен в `HttpOnly`, `SameSite=Lax` cookie
- Все SQL-запросы параметризованные (PDO prepared statements)
- Уникальные индексы на `email`, `login`, `slug` ловят гонки
- Файловый rate-limit по IP на `/api/login.php` и `/api/register.php`
- `.htaccess` запрещает прямой доступ к `config.php`, `lib/`, `sql/`, `scripts/`, `admin/_guard.php`
- Все админ-действия защищены `Auth::requireAdminOrFail()`
- Защита от lock-out: нельзя снять админку с себя через веб, удалить себя или последнего админа

## Что дальше

1. Загрузка картинок товара (сейчас только URL)
2. Привязка покупки к нику SAMP / IP / HWID — поле в профиле + поле в `orders`
3. Лицензионные ключи: генерация, выдача после оплаты, проверка
4. Платёжный шлюз (CryptoBot, ЮKassa, Stripe)
5. Корзина и заказы
