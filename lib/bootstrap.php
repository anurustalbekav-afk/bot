<?php
/**
 * Единая точка инициализации. Все api/* и страницы подключают её первой.
 */
declare(strict_types=1);

$configFile = __DIR__ . '/../config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "config.php not found. Copy config.example.php to config.php and fill in DB credentials.\n";
    exit;
}
$cfg = require $configFile;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validate.php';
require_once __DIR__ . '/http.php';

DB::configure($cfg['db']);
Auth::configure($cfg['auth']);

// Глобально доступная конфигурация для api/*
$GLOBALS['fd_cfg'] = $cfg;

// Гарантируем существование таблицы users.
// Если БД ещё не создана — отдаём дружелюбную ошибку.
try {
    DB::init();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'    => false,
        'error' => 'db_unavailable',
        'hint'  => 'Проверь параметры db.* в config.php и что БД создана в phpMyAdmin.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
