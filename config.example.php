<?php
/**
 * fear.dev — конфигурация.
 *
 * Скопируй этот файл в config.php и пропиши свои значения.
 * config.php не должен попадать в git (см. .gitignore).
 */

return [
    // --- База данных (MySQL / MariaDB через phpMyAdmin) -----------------
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'fear_dev',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // --- Аутентификация --------------------------------------------------
    'auth' => [
        // Длинная случайная строка. ОБЯЗАТЕЛЬНО смени в проде.
        // Сгенерировать: php -r "echo bin2hex(random_bytes(32));"
        'secret'      => 'change-me-to-a-long-random-string',
        // Время жизни сессии в секундах (по умолчанию 7 дней)
        'session_ttl' => 60 * 60 * 24 * 7,
        // Имя cookie сессии
        'cookie_name' => 'fd_session',
        // true только когда сайт на HTTPS
        'cookie_secure' => false,
    ],

    // --- Rate limit ------------------------------------------------------
    'rate_limit' => [
        // /api/register: N запросов в окне
        'register' => ['limit' => 10, 'window' => 60],
        // /api/login
        'login'    => ['limit' => 20, 'window' => 60],
    ],
];
