<?php

declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tehran');

$config = require __DIR__ . '/config.php';

require __DIR__ . '/src/Logger.php';
require __DIR__ . '/src/Database.php';
require __DIR__ . '/src/Queue.php';

Logger::init($config['paths']['logs'], $config['log_level']);

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e): void {
    Logger::critical(
        'استثنای مدیریت‌نشده رخ داد',
        'Uncaught exception',
        [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]
    );
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        Logger::critical(
            'خطای کشنده در اجرای برنامه',
            'Fatal error',
            [
                'message' => $err['message'],
                'file'    => $err['file'],
                'line'    => $err['line'],
            ]
        );
    }
});
