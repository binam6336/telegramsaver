<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

/** @var array $config */

$failedDir = $config['paths']['failed'];
$maxAge    = 7 * 86400; // 7 روز
$keepMax   = 500;       // حداکثر نگه‌داری 500 فایل

$now = time();
$files = glob($failedDir . '/*.json') ?: [];

// 1. حذف فایل‌های قدیمی
$deleted = 0;
foreach ($files as $f) {
    if ($now - filemtime($f) > $maxAge) {
        @unlink($f);
        $deleted++;
    }
}

// 2. اگه هنوز بیشتر از keepMax داریم، قدیمی‌ترین‌ها رو پاک کن
$files = glob($failedDir . '/*.json') ?: [];
if (count($files) > $keepMax) {
    usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
    $toDelete = array_slice($files, 0, count($files) - $keepMax);
    foreach ($toDelete as $f) {
        @unlink($f);
        $deleted++;
    }
}

Logger::info('پاکسازی فایل‌های ناموفق انجام شد', 'Failed files cleanup done', [
    'deleted' => $deleted,
    'kept'    => count(glob($failedDir . '/*.json') ?: []),
]);