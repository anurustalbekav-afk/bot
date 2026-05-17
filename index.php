<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

// Если уже авторизован — сразу в кабинет
if (Auth::currentUserId() && DB::findById(Auth::currentUserId() ?? '')) {
    header('Location: /dashboard.php');
    exit;
}
header('Location: /login.php');
