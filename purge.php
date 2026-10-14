<?php

declare(strict_types=1);

ob_start();

require __DIR__ . '/bootstrap.php';

// 🧹 پاک کردن هر خروجی اضافه
if (ob_get_length() > 0) {
    ob_get_clean();
}

header('Content-Type: application/json; charset=utf-8');

/** @var array $config */

// ============================================================
// 🧨 فایل پاک‌سازی همه چیز
// - messages
// - filtered_messages
// - irrelevant_messages
// - viewed_messages
// - فایل‌های صف (queue, processing, failed)
// - فایل state فیلتر
// - reset AUTO_INCREMENT همه جداول
// - فیلترها (filters) دست‌نخورده می‌مونن
// - blocked_chats دست‌نخورده می‌مونن
// ============================================================

try {
    Database::connect($config['db']);

    // دسترسی به PDO
    $ref  = new ReflectionClass(Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $pdo  = $prop->getValue();

    // ============================================================
    // 🔢 شمارش قبل از پاک‌سازی
    // ============================================================
    $tables = [
        'messages',
        'filtered_messages',
        'irrelevant_messages',
        'viewed_messages',
    ];

    $before = [];
    foreach ($tables as $t) {
        try {
            $before[$t] = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        } catch (Throwable $e) {
            $before[$t] = -1; // جدول وجود نداره
        }
    }

    Logger::warning(
        'شروع پاک‌سازی کامل دیتابیس',
        'Full database purge started',
        [
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
            'before' => $before,
        ]
    );

    // ============================================================
    // 🧨 مرحله ۱: پاک کردن جداول وابسته اول (به ترتیب صحیح)
    // ============================================================
    // ترتیب مهمه چون ممکنه FK داشته باشن — ولی اینجا FK نداریم
    // با این حال، ترتیب منطقی رو رعایت می‌کنیم
    // ============================================================

    $deleted = [];

    // 1) irrelevant_messages
    try {
        $pdo->exec('DELETE FROM `irrelevant_messages`');
        $pdo->exec('ALTER TABLE `irrelevant_messages` AUTO_INCREMENT = 1');
        $deleted['irrelevant_messages'] = $before['irrelevant_messages'];
    } catch (Throwable $e) {
        Logger::error('خطا در پاک‌سازی irrelevant_messages', 'Failed to purge irrelevant_messages', [
            'message' => $e->getMessage(),
        ]);
    }

    // 2) viewed_messages
    try {
        $pdo->exec('DELETE FROM `viewed_messages`');
        $pdo->exec('ALTER TABLE `viewed_messages` AUTO_INCREMENT = 1');
        $deleted['viewed_messages'] = $before['viewed_messages'];
    } catch (Throwable $e) {
        Logger::error('خطا در پاک‌سازی viewed_messages', 'Failed to purge viewed_messages', [
            'message' => $e->getMessage(),
        ]);
    }

    // 3) filtered_messages
    try {
        $pdo->exec('DELETE FROM `filtered_messages`');
        $pdo->exec('ALTER TABLE `filtered_messages` AUTO_INCREMENT = 1');
        $deleted['filtered_messages'] = $before['filtered_messages'];
    } catch (Throwable $e) {
        Logger::error('خطا در پاک‌سازی filtered_messages', 'Failed to purge filtered_messages', [
            'message' => $e->getMessage(),
        ]);
    }

    // 4) messages
    try {
        $pdo->exec('DELETE FROM `messages`');
        $pdo->exec('ALTER TABLE `messages` AUTO_INCREMENT = 1');
        $deleted['messages'] = $before['messages'];
    } catch (Throwable $e) {
        Logger::error('خطا در پاک‌سازی messages', 'Failed to purge messages', [
            'message' => $e->getMessage(),
        ]);
    }

    // ============================================================
    // 🗑 مرحله ۲: پاک کردن فایل‌های صف
    // ============================================================
    $queueFiles = 0;
    foreach (
        [
            $config['paths']['queue'],
            $config['paths']['processing'],
            $config['paths']['failed'],
        ] as $dir
    ) {
        if (!is_dir($dir)) continue;

        $files = glob($dir . '/*.json') ?: [];
        foreach ($files as $f) {
            if (@unlink($f)) $queueFiles++;
        }
    }

    Logger::info('فایل‌های صف پاک شدند', 'Queue files purged', [
        'files_deleted' => $queueFiles,
    ]);

    // ============================================================
    // 🗂 مرحله ۳: پاک کردن فایل state فیلتر
    // ============================================================
    $stateFile = __DIR__ . '/storage/filter_state.json';
    $stateDeleted = false;
    if (file_exists($stateFile)) {
        if (@unlink($stateFile)) {
            $stateDeleted = true;
        }
    }

    Logger::info('فایل state فیلتر پاک شد', 'Filter state file purged', [
        'deleted' => $stateDeleted,
    ]);

    // ============================================================
    // 🔢 شمارش بعد از پاک‌سازی
    // ============================================================
    $after = [];
    foreach ($tables as $t) {
        try {
            $after[$t] = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        } catch (Throwable $e) {
            $after[$t] = -1;
        }
    }

    // ============================================================
    // 📊 لاگ نهایی
    // ============================================================
    Logger::warning(
        'پاک‌سازی کامل دیتابیس انجام شد',
        'Full database purge completed',
        [
            'deleted'       => $deleted,
            'after'         => $after,
            'queue_files'   => $queueFiles,
            'state_deleted' => $stateDeleted,
        ]
    );

    // ============================================================
    // 📤 پاسخ JSON
    // ============================================================
    echo json_encode([
        'ok'            => true,
        'deleted'       => $deleted,
        'after'         => $after,
        'queue_files'   => $queueFiles,
        'state_deleted' => $stateDeleted,
        'message'       => 'همه چیز پاک شد (به‌جز filters و blocked_chats)',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    Logger::critical(
        'خطا در پاک‌سازی دیتابیس',
        'Purge failed',
        [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]
    );

    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'خطای داخلی سرور'], JSON_UNESCAPED_UNICODE);
}
