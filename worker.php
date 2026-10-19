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

/* ═══════════════════════════════════════════════════════════
   سینک نام گروه‌ها و فرستندگان به جداول chats/senders
   ═══════════════════════════════════════════════════════════
   فقط اگر پیام جدیدی درج شد اجرا می‌شود (بهینه)
   خطا در سینک باعث شکست worker نمی‌شود
   ═══════════════════════════════════════════════════════════ */
if ($processedRows > 0) {
    try {
        require_once __DIR__ . '/sync_names.php';

        // ---- استخراج PDO از کلاس Database ----
        $__syncPdo = null;

        // ۱) روش‌های رایج
        foreach (['getPdo', 'getInstance', 'getConnection', 'connection', 'pdo'] as $__m) {
            if (method_exists('Database', $__m)) {
                try {
                    $__candidate = Database::$__m();
                    if ($__candidate instanceof PDO) {
                        $__syncPdo = $__candidate;
                        break;
                    }
                } catch (Throwable $e) {
                    // این متد جواب نداد، بعدی
                }
            }
        }

        // ۲) اگر پیدا نشد، از Reflection روی propertyهای static
        if ($__syncPdo === null && class_exists('Database')) {
            try {
                $__ref = new ReflectionClass('Database');
                foreach (['pdo', 'connection', 'db', 'instance', 'conn'] as $__prop) {
                    if ($__ref->hasProperty($__prop)) {
                        $__p = $__ref->getProperty($__prop);
                        $__p->setAccessible(true);
                        $__val = $__p->isStatic()
                            ? $__p->getValue()
                            : null;
                        if ($__val instanceof PDO) {
                            $__syncPdo = $__val;
                            break;
                        }
                    }
                }
            } catch (Throwable $e) {
                // نادیده بگیر
            }
        }

        // ---- اجرای سینک ----
        if ($__syncPdo instanceof PDO) {
            $__syncResult = sync_names($__syncPdo);
            Logger::info('سینک نام‌ها انجام شد', 'sync_names done', [
                'chats'   => $__syncResult['chats']   ?? 0,
                'senders' => $__syncResult['senders'] ?? 0,
            ]);
        } else {
            Logger::warning(
                'PDO برای سینک نام‌ها پیدا نشد — متد getPdo() را به کلاس Database اضافه کنید',
                'PDO not accessible for sync_names — add getPdo() method to Database class'
            );
        }
    } catch (Throwable $e) {
        Logger::error('سینک نام‌ها شکست خورد', 'sync_names failed', [
            'message' => $e->getMessage(),
        ]);
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
