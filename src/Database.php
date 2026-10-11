<?php

declare(strict_types=1);

final class Database
{
    /** @var PDO|null */
    private static $pdo = null;

    public static function connect(array $cfg): void
    {
        if (self::$pdo !== null) {
            return;
        }

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
     * درج انبوه پیام‌ها با محافظت در برابر تکراری
     *
     * - UNIQUE روی (chat_id, telegram_id) باید توی دیتابیس وجود داشته باشه
     * - اگه پیام تکراری بیاد، فیلدهای متنی و metadata به‌روز می‌شن
     * - chat_id و telegram_id هرگز NULL نیستن
     */
    public static function insertMessages(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $placeholders = [];
        $values       = [];

        foreach ($rows as $r) {
            // 🔒 اجبار به عدد (نه NULL) برای chat_id و telegram_id
            //    چون در MySQL، UNIQUE KEY روی NULL کار نمی‌کنه
            $telegramId = isset($r['telegram_id']) ? (int) $r['telegram_id'] : 0;
            $chatId     = isset($r['chat_id'])     ? (int) $r['chat_id']     : 0;

            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $values[] = $telegramId;
            $values[] = $chatId;
            $values[] = $r['group_name']  ?? null;
            $values[] = isset($r['sender_id']) ? (int) $r['sender_id'] : null;
            $values[] = $r['sender_name'] ?? null;
            $values[] = $r['text']        ?? null;
            $values[] = $r['link']        ?? null;
            $values[] = isset($r['date']) ? (int) $r['date'] : null;
            $values[] = $r['date_human']  ?? null;
            $values[] = $r['created_at']  ?? date('Y-m-d H:i:s');
        }

        // استفاده از ON DUPLICATE KEY UPDATE به جای IGNORE
        // → اگه پیام تکراری اومد، اطلاعات به‌روز می‌شن (نه اینکه نادیده گرفته بشه)
        // → created_at دست نمی‌خوره (زمان اولین مشاهده حفظ می‌شه)
        $sql = 'INSERT INTO `messages`
                (`telegram_id`, `chat_id`, `group_name`, `sender_id`, `sender_name`,
                 `text`, `link`, `date`, `date_human`, `created_at`)
                VALUES ' . implode(',', $placeholders) . '
                ON DUPLICATE KEY UPDATE
                    `group_name`  = VALUES(`group_name`),
                    `sender_name` = VALUES(`sender_name`),
                    `text`        = VALUES(`text`),
                    `link`        = VALUES(`link`),
                    `date`        = VALUES(`date`),
                    `date_human`  = VALUES(`date_human`)';

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($values);

        return $stmt->rowCount();
    }
}
