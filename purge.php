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

try {
    Database::connect($config['db']);

    // دسترسی به PDO
    $ref  = new ReflectionClass(Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $pdo  = $prop->getValue();

    // ============================================================
    // 🔎 مرحله ۱: قبل از پاک‌سازی، یک بار فیلترگذاری انجام بده
    // ============================================================
    Logger::info(
        'شروع فیلترگذاری خودکار قبل از پاک‌سازی',
        'Auto-filtering before purge started'
    );

    $filterResult = runFilterBeforePurge($pdo);

    Logger::info(
        'فیلترگذاری قبل از پاک‌سازی انجام شد',
        'Pre-purge filtering completed',
        $filterResult
    );

    // ============================================================
    // 🧨 مرحله ۲: پاک‌سازی دیتابیس
    // ============================================================
    $before = (int) $pdo->query('SELECT COUNT(*) FROM `messages`')->fetchColumn();

    Logger::warning(
        'شروع پاک‌سازی کامل دیتابیس',
        'Full database purge started',
        [
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'rows_before' => $before,
            'filtered'    => $filterResult['total_new'],
        ]
    );

    $pdo->exec('DELETE FROM `messages`');
    $pdo->exec('ALTER TABLE `messages` AUTO_INCREMENT = 1');

    // ---- پاک کردن فایل‌های صف ----
    $queueFiles = 0;
    foreach ([
        $config['paths']['queue'],
        $config['paths']['processing'],
        $config['paths']['failed'],
    ] as $dir) {
        $files = glob($dir . '/*.json') ?: [];
        foreach ($files as $f) {
            @unlink($f);
            $queueFiles++;
        }
    }

    $after = (int) $pdo->query('SELECT COUNT(*) FROM `messages`')->fetchColumn();

    Logger::warning(
        'پاک‌سازی کامل دیتابیس انجام شد',
        'Full database purge completed',
        [
            'rows_before' => $before,
            'rows_after'  => $after,
            'deleted'     => $before - $after,
            'queue_files' => $queueFiles,
            'filtered'    => $filterResult['total_new'],
        ]
    );

    echo json_encode([
        'ok'          => true,
        'filtered'    => $filterResult['total_new'],
        'filtered_details' => $filterResult['details'],
        'deleted'     => $before - $after,
        'before'      => $before,
        'after'       => $after,
        'queue_files' => $queueFiles,
    ], JSON_UNESCAPED_UNICODE);

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

// ============================================================
// 🧩 تابع کمکی: فیلترگذاری قبل از پاک‌سازی
// ============================================================
function runFilterBeforePurge(PDO $pdo): array
{
    $keywords = $pdo->query('SELECT `id`, `keyword` FROM `filters`')->fetchAll(PDO::FETCH_ASSOC);

    if (empty($keywords)) {
        return [
            'keywords'  => 0,
            'total_new' => 0,
            'details'   => [],
        ];
    }

    $insertSql = 'INSERT IGNORE INTO `filtered_messages`
        (`message_id`, `telegram_id`, `chat_id`, `sender_id`, `text`, `link`,
         `date`, `date_human`, `matched_keyword`, `original_created_at`)
        SELECT `id`, `telegram_id`, `chat_id`, `sender_id`, `text`, `link`,
               `date`, `date_human`, ?, `created_at`
        FROM `messages`
        WHERE `text` LIKE ? ESCAPE "\\\\"';

    $stmt = $pdo->prepare($insertSql);

    $totalNew = 0;
    $details  = [];

    foreach ($keywords as $kw) {
        $keyword = (string) $kw['keyword'];
        if ($keyword === '') {
            continue;
        }

        $escaped = str_replace(
            ['\\',   '%',   '_'],
            ['\\\\', '\\%', '\\_'],
            $keyword
        );
        $pattern = '%' . $escaped . '%';

        try {
            $stmt->execute([$keyword, $pattern]);
            $inserted = $stmt->rowCount();
            $totalNew += $inserted;

            $details[] = [
                'keyword' => $keyword,
                'new'     => $inserted,
            ];

            Logger::info(
                'فیلتر یک کلیدواژه انجام شد',
                'Keyword filter done',
                ['keyword' => $keyword, 'new' => $inserted]
            );

        } catch (Throwable $e) {
            Logger::error(
                'خطا در فیلتر یک کلیدواژه',
                'Error filtering a keyword',
                [
                    'keyword' => $keyword,
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ]
            );
        }
    }

    return [
        'keywords'  => count($keywords),
        'total_new' => $totalNew,
        'details'   => $details,
    ];
}