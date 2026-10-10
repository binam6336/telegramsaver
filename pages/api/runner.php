<?php

declare(strict_types=1);

/* =========================================================
   فقط از داخل سرور قابل فراخوانی باشد
========================================================= */
$internalHeader = $_SERVER['HTTP_X_INTERNAL'] ?? '';
$remoteIp       = $_SERVER['REMOTE_ADDR'] ?? '';

$isLocal = in_array($remoteIp, ['127.0.0.1', '::1'], true);
$hasFlag = ($internalHeader === '1');

if (!$isLocal && !$hasFlag) {
    http_response_code(403);
    exit('Forbidden');
}

/* =========================================================
   تنظیمات
========================================================= */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__, 2);

$map = [
    'worker' => $root . '/worker.php',
    'filter' => $root . '/filter.php',
];

$key = (string)($_GET['file'] ?? '');
if (!isset($map[$key])) {
    http_response_code(400);
    exit("Invalid file key\n");
}

$file = $map[$key];
if (!is_file($file)) {
    http_response_code(404);
    exit("File not found: {$file}\n");
}

/* =========================================================
   اجرای ایمن: چرخش CWD به ریشه پروژه + مدیریت Fatal
========================================================= */
$prevCwd = getcwd();
@chdir($root);

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        echo "\n--- FATAL ---\n"
            . $err['message'] . "\n"
            . $err['file'] . ':' . $err['line'] . "\n";
    }
});

try {
    include $file;
} catch (Throwable $e) {
    echo "\n--- EXCEPTION ---\n"
        . $e->getMessage() . "\n"
        . $e->getFile() . ':' . $e->getLine() . "\n";
    http_response_code(500);
}

if ($prevCwd !== false) @chdir($prevCwd);
