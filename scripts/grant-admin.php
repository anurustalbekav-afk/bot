<?php
/**
 * CLI: выдать или снять админку.
 *
 *   php scripts/grant-admin.php <login_or_email>            — выдать admin
 *   php scripts/grant-admin.php <login_or_email> --revoke   — снять до user
 *   php scripts/grant-admin.php --list                      — показать всех админов
 *
 * Используется как первый шаг настройки: с веба назначить
 * первого админа невозможно (некому нажать кнопку).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Этот скрипт можно запускать только из консоли.\n");
    exit(2);
}

require __DIR__ . '/../lib/bootstrap.php';

$args = array_slice($argv, 1);

if (in_array('--list', $args, true)) {
    $rows = DB::pdo()
        ->query("SELECT login, email, role, created_at FROM users WHERE role = 'admin' ORDER BY created_at")
        ->fetchAll();
    if (!$rows) {
        echo "Админов нет. Выдай первого: php scripts/grant-admin.php <login>\n";
        exit(0);
    }
    foreach ($rows as $r) {
        printf("%-20s %-32s %s\n", $r['login'], $r['email'], $r['created_at']);
    }
    exit(0);
}

if (count($args) < 1) {
    fwrite(STDERR, "Использование:\n");
    fwrite(STDERR, "  php scripts/grant-admin.php <login_or_email>\n");
    fwrite(STDERR, "  php scripts/grant-admin.php <login_or_email> --revoke\n");
    fwrite(STDERR, "  php scripts/grant-admin.php --list\n");
    exit(2);
}

$identifier = $args[0];
$revoke     = in_array('--revoke', $args, true);
$role       = $revoke ? DB::ROLE_USER : DB::ROLE_ADMIN;

$user = DB::findByEmailOrLogin($identifier);
if (!$user) {
    fwrite(STDERR, "Пользователь не найден: $identifier\n");
    exit(1);
}

if ($revoke && $user['role'] === DB::ROLE_ADMIN && DB::countAdmins() <= 1) {
    fwrite(STDERR, "Нельзя снять админку: это последний админ в системе.\n");
    exit(1);
}

if ($user['role'] === $role) {
    echo "Без изменений: у пользователя {$user['login']} уже роль $role.\n";
    exit(0);
}

DB::setRole($user['id'], $role);
echo ($revoke ? "Снята админка с" : "Выдана админка") . ": {$user['login']} ({$user['email']}) → $role\n";
