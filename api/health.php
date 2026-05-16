<?php
/**
 * fear.dev — health check.
 *
 * Open https://your-domain/api/health.php in the browser to diagnose
 * a misbehaving deployment. Reports PHP version, required extensions,
 * data directory writability, and whether sessions actually start.
 */

require_once __DIR__ . '/../src/bootstrap.php';

$checks = [];
$ok = true;

$phpOk = PHP_VERSION_ID >= 70400;
$ok = $ok && $phpOk;
$checks[] = ['name' => 'PHP version', 'ok' => $phpOk, 'detail' => PHP_VERSION . ' (need 7.4+)'];

foreach (['json', 'session', 'filter', 'openssl'] as $ext) {
    $loaded = extension_loaded($ext);
    $ok = $ok && $loaded;
    $checks[] = ['name' => "ext: $ext", 'ok' => $loaded, 'detail' => $loaded ? 'loaded' : 'missing'];
}

$randOk = function_exists('random_bytes');
$ok = $ok && $randOk;
$checks[] = ['name' => 'random_bytes()', 'ok' => $randOk, 'detail' => $randOk ? 'available' : 'missing'];

$pwOk = function_exists('password_hash') && defined('PASSWORD_BCRYPT');
$ok = $ok && $pwOk;
$checks[] = ['name' => 'password_hash + bcrypt', 'ok' => $pwOk, 'detail' => $pwOk ? 'ok' : 'missing'];

$dataOk = is_dir(FD_DATA_DIR) && is_writable(FD_DATA_DIR);
$ok = $ok && $dataOk;
$checks[] = ['name' => 'data/ writable', 'ok' => $dataOk, 'detail' => FD_DATA_DIR . ' — ' . ($dataOk ? 'ok' : 'NOT WRITABLE — chmod 755')];

$sessOk = is_dir(FD_SESSIONS_DIR) && is_writable(FD_SESSIONS_DIR);
$ok = $ok && $sessOk;
$checks[] = ['name' => 'data/sessions/ writable', 'ok' => $sessOk, 'detail' => FD_SESSIONS_DIR . ' — ' . ($sessOk ? 'ok' : 'NOT WRITABLE')];

$probe = FD_DATA_DIR . '/.health_probe';
$wrote = @file_put_contents($probe, (string)time());
$probeOk = $wrote !== false;
@unlink($probe);
$ok = $ok && $probeOk;
$checks[] = ['name' => 'data/ probe write', 'ok' => $probeOk, 'detail' => $probeOk ? 'ok' : 'cannot create files in data/'];

$jsonOk = json_encode(['x' => 1]) === '{"x":1}';
$ok = $ok && $jsonOk;
$checks[] = ['name' => 'json_encode', 'ok' => $jsonOk, 'detail' => $jsonOk ? 'ok' : 'broken'];

$sessionStarted = session_status() === PHP_SESSION_ACTIVE;
$ok = $ok && $sessionStarted;
$checks[] = ['name' => 'session started', 'ok' => $sessionStarted, 'detail' => $sessionStarted ? session_id() : 'NOT STARTED'];

fd_json(200, [
    'ok'     => $ok,
    'fearDev'=> 'health',
    'php'    => PHP_VERSION,
    'sapi'   => PHP_SAPI,
    'checks' => $checks,
]);
