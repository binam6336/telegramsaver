<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

/** @var array $config */

$startTime = time();

// ---- قفل ضد اجرای همزمان ----
$lock = @fopen($config['paths']['lock'], 'c');
if ($lock === false) {
    Logger::critical('فایل قفل ورکر باز نشد', 'Cannot open worker lock file');
    exit(1);
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    Logger::info('ورکر قبلی همچنان در حال اجراست، خروج', 'Previous worker still running, exiting');
    fclose($lock);
    exit(0);
}

Logger::info('ورکر شروع به کار کرد', 'Worker started');

try {
    Database::connect($config['db']);
} catch (Throwable $e) {
    Logger::critical('اتصال به دیتابیس برقرار نشد', 'Database connection failed', [
        'message' => $e->getMessage(),
    ]);
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(1);
}

$queue = new Queue(
    $config['paths']['queue'],
    $config['paths']['processing'],
    $config['paths']['failed']
);

$maxRuntime     = (int) $config['worker']['max_runtime'];
$batchSize      = (int) $config['worker']['batch_size'];
$maxFilesPerRun = (int) $config['worker']['max_files_run'];

$files          = $queue->listPending($maxFilesPerRun);
$processedFiles = 0;
$processedRows  = 0;
$failedFiles    = 0;

foreach ($files as $path) {
    if (time() - $startTime >= $maxRuntime) {
        Logger::info('زمان اجرای ورکر به پایان رسید', 'Worker runtime limit reached', [
            'processed' => $processedFiles,
            'remaining' => count($files) - $processedFiles - $failedFiles,
        ]);
        break;
    }

    $claimed = $queue->claim($path);
    if ($claimed === null) {
        Logger::warning('فایل صف قابل claim نبود', 'Queue file could not be claimed', [
            'file' => $path,
        ]);
        continue;
    }

    $raw = @file_get_contents($claimed);
    if ($raw === false) {
        Logger::error('خواندن فایل صف ناموفق بود', 'Failed to read queue file', ['file' => $claimed]);
        $queue->fail($claimed);
        $failedFiles++;
        continue;
    }

    $rows = json_decode($raw, true);
    if (!is_array($rows)) {
        Logger::error('محتوای فایل صف JSON نامعتبر است', 'Invalid JSON in queue file', [
            'file'  => $claimed,
            'error' => json_last_error_msg(),
        ]);
        $queue->fail($claimed);
        $failedFiles++;
        continue;
    }

    $success = true;
    foreach (array_chunk($rows, $batchSize) as $chunk) {
        try {
            Database::insertMessages($chunk);
            $processedRows += count($chunk);
        } catch (Throwable $e) {
            Logger::error('درج در دیتابیس شکست خورد', 'Database insert failed', [
                'file'    => $claimed,
                'message' => $e->getMessage(),
                'chunk'   => count($chunk),
            ]);
            $success = false;
            break;
        }
    }

    if ($success) {
        $queue->done($claimed);
        $processedFiles++;
    } else {
        $queue->fail($claimed);
        $failedFiles++;
    }
}

Logger::info('ورکر پایان یافت', 'Worker finished', [
    'files_ok'   => $processedFiles,
    'rows_ok'    => $processedRows,
    'files_fail' => $failedFiles,
    'duration'   => time() - $startTime,
]);

flock($lock, LOCK_UN);
fclose($lock);
