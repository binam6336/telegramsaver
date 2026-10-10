<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// ریشه پروژه = دو سطح بالاتر از pages/api/
$root   = dirname(__DIR__, 2);
$worker = $root . '/worker.php';
$filter = $root . '/filter.php';

$jobs = [
    ['name' => 'worker', 'file' => $worker],
    ['name' => 'filter', 'file' => $filter],
];

$results = [];
$ok      = true;

foreach ($jobs as $job) {

    if (!is_file($job['file'])) {
        $results[] = [
            'name'    => $job['name'],
            'status'  => 'error',
            'message' => 'فایل یافت نشد: ' . $job['file'],
        ];
        $ok = false;
        break; // ادامه نده
    }

    $exitCode = 0;
    $output   = '';

    if (function_exists('exec')) {
        // اجرای امن در subprocess
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($job['file']) . ' 2>&1';
        $lines = [];
        @exec($cmd, $lines, $exitCode);
        $output = implode("\n", $lines);
    } else {
        // fallback: include داخل همین پروسه با output buffering
        ob_start();
        try {
            include $job['file'];
        } catch (Throwable $e) {
            $exitCode = 1;
            echo 'ERROR: ' . $e->getMessage();
        }
        $output = ob_get_clean();
    }

    $results[] = [
        'name'   => $job['name'],
        'status' => $exitCode === 0 ? 'ok' : 'error',
        'code'   => $exitCode,
        'output' => mb_substr((string)$output, -1500), // آخرین بخش لاگ
    ];

    // اگر worker خطا داد، filter اجرا نمی‌شود
    if ($exitCode !== 0) {
        $ok = false;
        break;
    }
}

echo json_encode([
    'status'  => $ok ? 'success' : 'error',
    'results' => $results,
], JSON_UNESCAPED_UNICODE);