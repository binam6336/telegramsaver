<?php

declare(strict_types=1);

/* =========================================================
   نمایش خطاها را خاموش نگه می‌داریم تا JSON خراب نشود،
   ولی لاگ می‌کنیم.
========================================================= */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

/* =========================================================
   حتی در صورت Fatal Error، پاسخ JSON برگردان
========================================================= */
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'status'  => 'error',
            'message' => 'Fatal: ' . $err['message'],
            'file'    => $err['file'],
            'line'    => $err['line'],
        ], JSON_UNESCAPED_UNICODE);
    }
});

header('Content-Type: application/json; charset=utf-8');

/* =========================================================
   مسیر ریشه پروژه  (pages/api → pages → root)
========================================================= */
$root = dirname(__DIR__, 2);

$jobs = [
    ['name' => 'worker', 'file' => $root . '/worker.php'],
    ['name' => 'filter', 'file' => $root . '/filter.php'],
];

/* =========================================================
   HELPERS
========================================================= */
function json_out(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

function safe_substr(string $s, int $len): string
{
    if (function_exists('mb_substr')) return mb_substr($s, -$len);
    return substr($s, -$len);
}

/**
 * بررسی می‌کند که exec واقعاً قابل استفاده است یا نه.
 * (function_exists تنها کافی نیست — ممکن است در disable_functions باشد)
 */
function exec_available(): bool
{
    if (!function_exists('exec')) return false;

    $disabled = (string)ini_get('disable_functions');
    if ($disabled !== '') {
        $list = array_map('trim', explode(',', $disabled));
        if (in_array('exec', $list, true)) return false;
    }
    return true;
}

function resolve_php_binary(): string
{
    if (defined('PHP_BINARY') && PHP_BINARY !== '' && @is_file(PHP_BINARY)) {
        return PHP_BINARY;
    }
    $candidates = [
        '/usr/bin/php',
        '/usr/local/bin/php',
        '/opt/php/bin/php',
        '/opt/cpanel/ea-php81/root/usr/bin/php',
    ];
    foreach ($candidates as $c) {
        if (@is_executable($c)) return $c;
    }
    return 'php'; // امید به PATH
}

/* =========================================================
   اجرای زنجیره
========================================================= */
$results   = [];
$overallOk = true;
$method    = exec_available() ? 'exec' : 'include';
$phpBin    = $method === 'exec' ? resolve_php_binary() : null;

foreach ($jobs as $job) {

    if (!is_file($job['file'])) {
        $results[] = [
            'name'    => $job['name'],
            'status'  => 'error',
            'message' => 'فایل یافت نشد: ' . $job['file'],
        ];
        $overallOk = false;
        break;
    }

    $exitCode = 0;
    $output   = '';

    if ($method === 'exec') {
        /* ---------- مسیر subprocess (توصیه‌شده) ---------- */
        $cmd    = escapeshellarg($phpBin) . ' ' . escapeshellarg($job['file']) . ' 2>&1';
        $lines  = [];
        @exec($cmd, $lines, $exitCode);
        $output = implode("\n", $lines);
    } else {
        /* ---------- مسیر include (fallback) ---------- */
        $prevCwd = @getcwd();
        @chdir($root);

        ob_start();
        try {
            // scope ایزوله تا متغیرها با اسکریپت اصلی تداخل نکنند
            (static function (string $__file) {
                include $__file;
            })($job['file']);
        } catch (Throwable $e) {
            $exitCode = 1;
            echo 'FATAL: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
        }
        $output = (string)ob_get_clean();

        if ($prevCwd !== false) @chdir($prevCwd);
    }

    $results[] = [
        'name'   => $job['name'],
        'status' => $exitCode === 0 ? 'ok' : 'error',
        'code'   => $exitCode,
        'output' => safe_substr($output, 2000),
    ];

    if ($exitCode !== 0) {
        $overallOk = false;
        break; // اگر worker خطا داد، filter اجرا نشود
    }
}

json_out([
    'status'  => $overallOk ? 'success' : 'error',
    'method'  => $method,
    'php'     => $phpBin,
    'root'    => $root,
    'results' => $results,
]);
