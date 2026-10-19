<?php

declare(strict_types=1);

/**
 * سینک نام گروه‌ها و فرستندگان از جدول messages به chats/senders.
 * idempotent — هر چند بار اجرا شود، نتیجه یکسان است.
 *
 * @param PDO $pdo
 * @return array{chats:int, senders:int}
 */
function sync_names(PDO $pdo): array
{
    $chatsStmt = $pdo->prepare(
        "INSERT INTO `chats` (`chat_id`, `group_name`)
         SELECT `chat_id`, MAX(`group_name`)
         FROM `messages`
         WHERE `chat_id` IS NOT NULL
           AND `group_name` IS NOT NULL
           AND `group_name` <> ''
         GROUP BY `chat_id`
         ON DUPLICATE KEY UPDATE `group_name` = VALUES(`group_name`)"
    );
    $chatsStmt->execute();

    $sendersStmt = $pdo->prepare(
        "INSERT INTO `senders` (`sender_id`, `sender_name`)
         SELECT `sender_id`, MAX(`sender_name`)
         FROM `messages`
         WHERE `sender_id` IS NOT NULL
           AND `sender_name` IS NOT NULL
           AND `sender_name` <> ''
         GROUP BY `sender_id`
         ON DUPLICATE KEY UPDATE `sender_name` = VALUES(`sender_name`)"
    );
    $sendersStmt->execute();

    return [
        'chats'   => $chatsStmt->rowCount(),
        'senders' => $sendersStmt->rowCount(),
    ];
}
