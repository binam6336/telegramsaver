<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

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
   مسیرها
========================================================= */
$root = dirname(__DIR__, 2); // pages/api → pages → root

$jobs = [
    ['name' => 'worker', 'file' => $root . '/worker.php'],
    ['name' => 'filter', 'file' => $root . '/filter.php'],
];

/* =========================================================
   HELPERS
========================================================= */
function json_out(array $d): void
{
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

function safe_substr(string $s, int $n): string
{
    return function_exists('mb_substr') ? mb_substr($s, -$n) : substr($s, -$n);
}

/** آیا تابع واقعاً قابل فراخوانی است؟ (function_exists کافی نیست) */
function fn_enabled(string $name): bool
{
    if (!function_exists($name)) return false;
    $disabled = (string)ini_get('disable_functions');
    if ($disabled === '') return true;
    $list = array_map('trim', explode(',', $disabled));
    return !in_array($name, $list, true);
}

function php_binary(): string
{
    if (defined('PHP_BINARY') && PHP_BINARY !== '' && @is_file(PHP_BINARY)) return PHP_BINARY;
    $cands = [
        '/usr/bin/php',
        '/usr/local/bin/php',
        '/opt/php/bin/php',
        '/opt/cpanel/ea-php81/root/usr/bin/php',
        '/opt/cpanel/ea-php82/root/usr/bin/php',
        '/opt/cpanel/ea-php83/root/usr/bin/php',
    ];
    foreach ($cands as $c) if (@is_executable($c)) return $c;
    return 'php';
}

/* =========================================================
   RUNNERS
========================================================= */
function run_via_exec(string $phpBin, string $root, string $file): array
{
    $cmd = 'cd ' . escapeshellarg($root) . ' && '
        . escapeshellarg($phpBin) . ' ' . escapeshellarg($file) . ' 2>&1';
    $lines = [];
    $code = 0;
    @exec($cmd, $lines, $code);
    return ['code' => (int)$code, 'output' => implode("\n", $lines)];
}

function run_via_shell_exec(string $phpBin, string $root, string $file): array
{
    $cmd = 'cd ' . escapeshellarg($root) . ' && '
        . escapeshellarg($phpBin) . ' ' . escapeshellarg($file) . ' 2>&1';
    $out = @shell_exec($cmd);
    return ['code' => ($out === null || $out === false) ? 1 : 0, 'output' => (string)$out];
}

function run_via_proc_open(string $phpBin, string $root, string $file): array
{
    $cmd  = escapeshellarg($phpBin) . ' ' . escapeshellarg($file);
    $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $desc, $pipes, $root);
    if (!is_resource($proc)) return ['code' => 1, 'output' => 'proc_open failed'];
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    return ['code' => (int)$code, 'output' => (string)$out . (string)$err];
}

function run_via_popen(string $phpBin, string $root, string $file): array
{
    $cmd = 'cd ' . escapeshellarg($root) . ' && '
        . escapeshellarg($phpBin) . ' ' . escapeshellarg($file) . ' 2>&1';
    $h = @popen($cmd, 'r');
    if (!is_resource($h)) return ['code' => 1, 'output' => 'popen failed'];
    $out = '';
    while (!feof($h)) $out .= fread($h, 4096);
    $code = pclose($h);
    return ['code' => (int)$code, 'output' => $out];
}

/**
 * HTTP fallback — درخواست به runner.php روی همان سرور.
 * این روش در هاست‌هایی که همه توابع اجرای فرمان را بسته‌اند کار می‌کند.
 */
function run_via_http(string $jobName): array
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $self   = $_SERVER['SCRIPT_NAME'] ?? '';      // /.../pages/api/refresh.php
    $dir    = rtrim(dirname($self), '/\\');        // /.../pages/api
    $url    = $scheme . '://' . $host . $dir . '/runner.php?file=' . urlencode($jobName);

    // ---- تلاش با file_get_contents ----
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 120,
                'ignore_errors' => true,
                'header'        => "X-Internal: 1\r\n",
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $out = @file_get_contents($url, false, $ctx);
        if ($out !== false) {
            return ['code' => 0, 'output' => (string)$out];
        }
    }

    // ---- تلاش با cURL ----
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => ['X-Internal: 1'],
        ]);
        $out  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($out !== false) {
            return ['code' => $code >= 200 && $code < 300 ? 0 : 1, 'output' => (string)$out];
        }
        return ['code' => 1, 'output' => 'cURL error: ' . $err];
    }

    return ['code' => 1, 'output' => 'HTTP fallback unavailable (no fopen/curl)'];
}

/* =========================================================
   انتخاب روش اجرا
========================================================= */
$phpBin = php_binary();
$method = null;
foreach (['exec', 'shell_exec', 'proc_open', 'popen'] as $fn) {
    if (fn_enabled($fn)) {
        $method = $fn;
        break;
    }
}
if ($method === null) $method = 'http';

/* =========================================================
   اجرا — هر job در یک پروسه جداگانه
========================================================= */
$results   = [];
$overallOk = true;

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

    switch ($method) {
        case 'exec':
            $r = run_via_exec($phpBin, $root, $job['file']);
            break;
        case 'shell_exec':
            $r = run_via_shell_exec($phpBin, $root, $job['file']);
            break;
        case 'proc_open':
            $r = run_via_proc_open($phpBin, $root, $job['file']);
            break;
        case 'popen':
            $r = run_via_popen($phpBin, $root, $job['file']);
            break;
        case 'http':
            $r = run_via_http($job['name']);
            break;
        default:
            $r = ['code' => 1, 'output' => 'no runner'];
    }

    $results[] = [
        'name'   => $job['name'],
        'status' => $r['code'] === 0 ? 'ok' : 'error',
        'code'   => (int)$r['code'],
        'output' => safe_substr((string)$r['output'], 2000),
    ];

    if ($r['code'] !== 0) {
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
