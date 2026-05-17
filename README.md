# fear.dev

Стартовая база для сайта продажи модов SAMP и скриптов: регистрация/вход
с сессиями + админ-панель (вкладки «Пользователи» и «Моды»). Под shared-хостинг.

## Стек

- PHP 8.1+, без внешних зависимостей и без сборки фронта
- Apache/LiteSpeed (cPanel/ISPmanager) с `mod_rewrite` и `mod_headers`
- Хранилище — JSON-файлы в `data/` (`users.json`, `mods.json`) под `flock`-ом
- Пароли — `password_hash` (bcrypt); сессии — стандартные PHP, `HttpOnly`, `SameSite=Lax`
- Фронт — `vanilla` HTML/CSS/JS; i18n: RU / UK / EN

## Структура (после деплоя на хостинг)

```
public_html/                     <- docroot хостинга
├── .htaccess                    # роутинг, безопасность, кеш, gzip
├── index.php                    # вход
├── register.php                 # регистрация
├── dashboard.php                # личный кабинет
├── admin.php                    # админ-панель (только для админов)
├── api/                         # JSON-эндпоинты
│   ├── login.php  register.php  logout.php  me.php
│   └── admin/users.php  mods.php  topup.php  purchase.php
├── assets/                      # css / js / svg
├── lib/                         # код бэкенда (закрыт через .htaccess + 404)
│   └── bootstrap.php  db.php  auth.php  admin.php  validate.php  helpers.php
└── data/                        # JSON-хранилище (создаётся при первом запуске)
    ├── .htaccess                # авто-создаётся, запрещает HTTP-доступ
    ├── users.json  mods.json  ratelimit.json
```

## Деплой на shared-хостинг (cPanel / ISPmanager)

1. **PHP 8.1+.** В cPanel → MultiPHP Manager выберите 8.1 или новее
   (поддерживаются 8.2/8.3/8.4 — проверены).
2. **Загрузка файлов.** Положите содержимое репозитория в `public_html/`
   (или в подкаталог типа `public_html/shop/` — будет работать).
   Через FTP/SFTP/Файловый менеджер. Ничего собирать/устанавливать не нужно.
3. **Права.** Папке `data/` нужно дать право записи (обычно `755`/`775`).
   Файлы внутри будут созданы автоматически на первом запросе.
   Если хостинг возвращает `Internal Server Error` при попытке создать
   файл — поправьте владельца на пользователя PHP-FPM.
4. **Скрытые файлы.** Убедитесь, что `.htaccess` и `.env` загружены
   (в большинстве FTP-клиентов нужно включить «Показывать скрытые файлы»).
5. **Первый админ.** Скопируйте `.env.example` → `.env` и впишите свой логин:
   ```
   ADMIN_LOGINS=ваш_логин
   ```
   После регистрации в `/register.php` вы получите доступ к `/admin.php`.
6. **HTTPS.** Включите Let's Encrypt в панели хостинга — куки
   автоматически станут `Secure` (определяется по `$_SERVER['HTTPS']`).

> Для nginx (без Apache) нужен аналог `.htaccess`: запрет `/lib`, `/data` и
> dotfiles, плюс правило rewrite `try_files $uri $uri.php =404;`.
> Готовый сниппет — в комментариях к корневому `.htaccess`.

## Что в админ-панели

- **Пользователи** — таблица с логином, email, IP (первый и последний),
  датой регистрации, суммой пополнений и количеством покупок (mod / script).
  Кнопка «Детали» открывает модалку с историей и кнопками «+ Пополнение» /
  «+ Покупка».
- **Моды** — карточки с баннером, ценой и ссылкой. «+ Добавить мод»,
  иконка-карандаш — модалка редактирования (название, баннер, ссылка, цена,
  валюта, тип `mod`/`script`), корзина — удаление.

## Локальная разработка

Под Apache можно сразу класть файлы в `htdocs`/`public_html` локального XAMPP.
Без Apache используйте встроенный сервер PHP с роутером:

```bash
cp .env.example .env
php -S 127.0.0.1:8000 router.php
# открыть http://127.0.0.1:8000
```

`router.php` повторяет правила корневого `.htaccess`: блокирует доступ к
`/lib`, `/data`, dotfiles и поддерживает «pretty URLs» (`/admin` → `admin.php`).

## Маршруты

| Метод       | URL                          | Описание                                            |
|-------------|------------------------------|-----------------------------------------------------|
| GET         | `/` или `/index.php`         | Страница входа                                      |
| GET         | `/register.php`              | Страница регистрации                                |
| GET         | `/dashboard.php`             | Личный кабинет (нужна сессия)                       |
| GET         | `/admin.php`                 | Админ-панель (только админы)                        |
| POST        | `/api/register.php`          | `{ email, login, password }`                        |
| POST        | `/api/login.php`             | `{ identifier, password }`                          |
| POST        | `/api/logout.php`            | Сброс сессии                                        |
| GET         | `/api/me.php`                | Текущий пользователь                                |
| GET         | `/api/admin/users.php`       | Список пользователей (admin)                        |
| GET/POST/PATCH/DELETE | `/api/admin/mods.php` | CRUD над модами (admin)                            |
| POST        | `/api/admin/topup.php`       | `{ userId, amount, currency, method?, note? }`      |
| POST        | `/api/admin/purchase.php`    | `{ userId, modId? \| title, price, currency, kind }`|

## Безопасность

- bcrypt-хеши паролей; `session_regenerate_id(true)` после логина
- Куки сессии: `HttpOnly`, `SameSite=Lax`, `Secure` под HTTPS
- `.htaccess`: блокирует `/lib`, `/data`, `.env`, `.git`, `*.json`, отключает
  листинг каталогов, добавляет CSP, X-Content-Type-Options, X-Frame-Options,
  Referrer-Policy, Permissions-Policy
- Rate-limit на `/api/login.php` и `/api/register.php` (per-IP, файл-бакет)
- API всегда отдаёт `Cache-Control: no-store`

## Что хочется сделать дальше

- замена JSON-хранилища на SQLite/MySQL (на хостингах обычно есть из коробки)
- категории/теги для модов, привязка покупки к ник-нейму SAMP
- генерация и проверка лицензионных ключей
- интеграция оплат (CryptoBot/ЮKassa/Stripe — что подходит юрисдикции)
