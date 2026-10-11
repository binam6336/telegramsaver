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

    public static function insertMessages(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $placeholders = [];
        $values       = [];

        foreach ($rows as $r) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $values[] = $r['telegram_id'];
            $values[] = $r['chat_id'];
            $values[] = $r['group_name'];
            $values[] = $r['sender_id'];
            $values[] = $r['sender_name'];
            $values[] = $r['text'];
            $values[] = $r['link'];
            $values[] = $r['date'];
            $values[] = $r['date_human'];
            $values[] = $r['created_at'];
        }

        $sql = 'INSERT IGNORE INTO `messages`
                (`telegram_id`, `chat_id`, `group_name`, `sender_id`, `sender_name`,
                 `text`, `link`, `date`, `date_human`, `created_at`)
                VALUES ' . implode(',', $placeholders);

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($values);

        return $stmt->rowCount();
    }
}
