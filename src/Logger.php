<?php

declare(strict_types=1);

final class Logger
{
    private const LEVELS = [
        'DEBUG'    => 0,
        'INFO'     => 1,
        'WARNING'  => 2,
        'ERROR'    => 3,
        'CRITICAL' => 4,
    ];

    private static string $logDir    = __DIR__ . '/../logs';
    private static string $minLevel  = 'INFO';
    private static string $channel   = 'app';

    public static function init(string $dir, string $minLevel = 'INFO', string $channel = 'app'): void
    {
        self::$logDir   = rtrim($dir, '/');
        self::$minLevel = strtoupper($minLevel);
        self::$channel  = $channel;

        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0775, true);
        }
    }

    public static function debug(string $fa, string $en, array $ctx = []): void
    {
        self::write('DEBUG',    $fa, $en, $ctx);
    }
    public static function info(string $fa, string $en, array $ctx = []): void
    {
        self::write('INFO',     $fa, $en, $ctx);
    }
    public static function warning(string $fa, string $en, array $ctx = []): void
    {
        self::write('WARNING',  $fa, $en, $ctx);
    }
    public static function error(string $fa, string $en, array $ctx = []): void
    {
        self::write('ERROR',    $fa, $en, $ctx);
    }
    public static function critical(string $fa, string $en, array $ctx = []): void
    {
        self::write('CRITICAL', $fa, $en, $ctx);
    }

    private static function write(string $level, string $fa, string $en, array $ctx): void
    {
        if ((self::LEVELS[$level] ?? 0) < (self::LEVELS[self::$minLevel] ?? 1)) {
            return;
        }

        $file = self::$logDir . '/' . date('Y-m-d') . '.log';
        $time = date('Y-m-d H:i:s');
        $pid  = getmypid();

        $ctxStr = '';
        if (!empty($ctx)) {
            $ctxStr = ' | ' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $line = sprintf(
            "[%s] [%s] [pid:%d] [fa: %s] [en: %s]%s%s",
            $time,
            $level,
            $pid,
            $fa,
            $en,
            $ctxStr,
            PHP_EOL
        );

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
