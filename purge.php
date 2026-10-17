<?php

declare(strict_types=1);

ob_start();
require __DIR__ . '/bootstrap.php';
if (ob_get_length() > 0) ob_get_clean();

header('Content-Type: application/json; charset=utf-8');
/** @var array $config */

try {
    Database::connect($config['db']);

    $ref  = new ReflectionClass(Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $pdo  = $prop->getValue();

    // شمارش قبل
    $before = (int) $pdo->query('SELECT COUNT(*) FROM `messages`')->fetchColumn();

    Logger::warning(
        'شروع پاک‌سازی جدول messages',
        'Messages table purge started',
        ['ip' => $_SERVER['REMOTE_ADDR'] ?? null, 'rows_before' => $before]
    );

    // پاک‌سازی messages
    $pdo->exec('DELETE FROM `messages`');
    $pdo->exec('ALTER TABLE `messages` AUTO_INCREMENT = 1');

    // پاک کردن فایل‌های صف و state (چون messages ریست شده)
    $queueFiles = 0;
    foreach (
        [
            $config['paths']['queue'],
            $config['paths']['processing'],
            $config['paths']['failed'],
        ] as $dir
    ) {
        $files = glob($dir . '/*.json') ?: [];
        foreach ($files as $f) {
            @unlink($f);
            $queueFiles++;
        }
    }

    $stateFile = __DIR__ . '/storage/filter_state.json';
    $stateDeleted = false;
    if (file_exists($stateFile) && @unlink($stateFile)) {
        $stateDeleted = true;
    }

    $after = (int) $pdo->query('SELECT COUNT(*) FROM `messages`')->fetchColumn();

    Logger::warning(
        'پاک‌سازی جدول messages انجام شد',
        'Messages table purge completed',
        [
            'rows_before'   => $before,
            'rows_after'    => $after,
            'deleted'       => $before - $after,
            'queue_files'   => $queueFiles,
            'state_deleted' => $stateDeleted,
        ]
    );

    echo json_encode([
        'ok'            => true,
        'table'         => 'messages',
        'before'        => $before,
        'after'         => $after,
        'deleted'       => $before - $after,
        'queue_files'   => $queueFiles,
        'state_deleted' => $stateDeleted,
        'note'          => 'filtered_messages, filters, blocked_chats دست‌نخورده ماندند',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    Logger::critical('خطا در پاک‌سازی', 'Purge failed', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);

    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'خطای داخلی سرور'], JSON_UNESCAPED_UNICODE);
}
