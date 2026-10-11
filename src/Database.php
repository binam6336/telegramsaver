<?php

declare(strict_types=1);

final class Database
{
    /** @var PDO|null */
    private static $pdo = null;

    public static function connect(array $cfg): void
    {
        if (self::$pdo !== null) return;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['charset']
        );

        self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /**
     * درج انبوه با چک دستی تکراری (بدون اتکا به UNIQUE)
     */
    public static function insertMessages(array $rows): int
    {
        if (empty($rows)) return 0;

        // ---- ۱) گرفتن همه telegram_id های ورودی ----
        $telegramIds = [];
        foreach ($rows as $r) {
            $tid = (int) ($r['telegram_id'] ?? 0);
            if ($tid > 0) $telegramIds[] = $tid;
        }

        if (empty($telegramIds)) return 0;

        // ---- ۲) پیدا کردن telegram_id هایی که از قبل تو دیتابیس هستن ----
        $placeholders = implode(',', array_fill(0, count($telegramIds), '?'));
        $checkStmt = self::$pdo->prepare(
            "SELECT `telegram_id` FROM `messages` WHERE `telegram_id` IN ($placeholders)"
        );
        $checkStmt->execute($telegramIds);
        $existingIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN);

        // تبدیل به set برای جستجوی سریع
        $existingSet = array_flip(array_map('intval', $existingIds));

        // ---- ۳) فقط ردیف‌های جدید رو نگه دار ----
        $newRows = [];
        foreach ($rows as $r) {
            $tid = (int) ($r['telegram_id'] ?? 0);
            if ($tid > 0 && !isset($existingSet[$tid])) {
                $newRows[] = $r;
            }
        }

        if (empty($newRows)) {
            return 0;
        }

        // ---- ۴) درج فقط ردیف‌های جدید ----
        $placeholders = [];
        $values       = [];

        foreach ($newRows as $r) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $values[] = (int) ($r['telegram_id'] ?? 0);
            $values[] = (int) ($r['chat_id']     ?? 0);
            $values[] = $r['group_name']  ?? null;
            $values[] = isset($r['sender_id']) ? (int) $r['sender_id'] : null;
            $values[] = $r['sender_name'] ?? null;
            $values[] = $r['text']        ?? null;
            $values[] = $r['link']        ?? null;
            $values[] = isset($r['date']) ? (int) $r['date'] : null;
            $values[] = $r['date_human']  ?? null;
            $values[] = $r['created_at']  ?? date('Y-m-d H:i:s');
        }

        $sql = 'INSERT INTO `messages`
                (`telegram_id`, `chat_id`, `group_name`, `sender_id`, `sender_name`,
                 `text`, `link`, `date`, `date_human`, `created_at`)
                VALUES ' . implode(',', $placeholders);

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($values);

        return $stmt->rowCount();
    }
}
