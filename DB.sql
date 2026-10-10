CREATE TABLE `messages` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `telegram_id`  BIGINT          NULL,
    `chat_id`      BIGINT          NULL,
    `sender_id`    BIGINT          NULL,
    `text`         TEXT            NULL,
    `link`         VARCHAR(512)    NULL,
    `date`         BIGINT          NULL,
    `date_human`   VARCHAR(32)     NULL,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_telegram_id` (`telegram_id`),
    KEY `idx_chat_id`     (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;