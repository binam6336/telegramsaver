<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

const MSG_TABLE = 'messages';
const FM_TABLE  = 'filtered_messages';

try {
    $pdo = db();

    $data = [];

    /* =========================================================
       STATS — آمار کلی
    ========================================================= */
    $data['stats'] = [
        'total_messages' => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "`")->fetchColumn(),
        'viewed'         => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "` WHERE is_viewed = 1")->fetchColumn(),
        'irrelevant'     => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "` WHERE is_irrelevant = 1")->fetchColumn(),
        'unique_groups'  => (int)$pdo->query("SELECT COUNT(DISTINCT group_name) FROM `" . MSG_TABLE . "` WHERE group_name IS NOT NULL AND group_name <> ''")->fetchColumn(),
        'unique_senders' => (int)$pdo->query("SELECT COUNT(DISTINCT sender_name) FROM `" . MSG_TABLE . "` WHERE sender_name IS NOT NULL AND sender_name <> ''")->fetchColumn(),
        'total_clicks'   => (int)$pdo->query("SELECT COALESCE(SUM(click_count), 0) FROM `" . MSG_TABLE . "`")->fetchColumn(),
    ];

    /* filtered count — graceful اگر جدول وجود نداشت */
    try {
        $data['stats']['filtered'] = (int)$pdo->query("SELECT COUNT(*) FROM `" . FM_TABLE . "`")->fetchColumn();
    } catch (Throwable $e) {
        $data['stats']['filtered'] = 0;
    }

    /* =========================================================
       KEYWORDS → GROUPS
       ────────────────────────────────────────────────────────
       اسم گروه با سه لایه fallback:
       ۱) m.group_name خود پیام متناظر
       ۲) از پیام دیگری با همان chat_id
       ۳) "گروه {chat_id}"
    ========================================================= */
    $keywords = [];
    try {
        $rows = $pdo->query(
            "SELECT fm.matched_keyword AS keyword,
                    COALESCE(
                        NULLIF(m.group_name, ''),
                        (SELECT m2.group_name
                         FROM `" . MSG_TABLE . "` m2
                         WHERE m2.chat_id = fm.chat_id
                           AND m2.group_name IS NOT NULL
                           AND m2.group_name <> ''
                         LIMIT 1),
                        CONCAT('گروه ', fm.chat_id)
                    ) AS group_name,
                    COUNT(*) AS count
             FROM `" . FM_TABLE . "` fm
             LEFT JOIN `" . MSG_TABLE . "` m
                    ON m.telegram_id = fm.telegram_id
                   AND m.chat_id     = fm.chat_id
             GROUP BY fm.matched_keyword, group_name
             ORDER BY fm.matched_keyword ASC, count DESC"
        )->fetchAll();

        foreach ($rows as $r) {
            $kw = $r['keyword'];
            if (!isset($keywords[$kw])) {
                $keywords[$kw] = ['keyword' => $kw, 'total' => 0, 'groups' => []];
            }
            $keywords[$kw]['total'] += (int)$r['count'];
            $keywords[$kw]['groups'][] = [
                'group_name' => $r['group_name'],
                'count'      => (int)$r['count'],
            ];
        }

        foreach ($keywords as &$k) {
            usort($k['groups'], fn($a, $b) => $b['count'] <=> $a['count']);
            $k['groups'] = array_slice($k['groups'], 0, 5);
        }
        unset($k);

        usort($keywords, fn($a, $b) => $b['total'] <=> $a['total']);
        $keywords = array_values($keywords);
    } catch (Throwable $e) {
        $keywords = [];
    }
    $data['keywords'] = $keywords;

    /* =========================================================
       TOP SENDERS — هر شخص چقدر از کلمات کلیدی استفاده کرده
       ────────────────────────────────────────────────────────
       اسم فرستنده با سه لایه fallback:
       ۱) m.sender_name خود پیام متناظر
       ۲) از هر پیام دیگری با همان sender_id
       ۳) "کاربر {sender_id}"
    ========================================================= */
    try {
        $data['top_senders'] = $pdo->query(
            "SELECT COALESCE(
                        NULLIF(m.sender_name, ''),
                        (SELECT m2.sender_name
                         FROM `" . MSG_TABLE . "` m2
                         WHERE m2.sender_id = fm.sender_id
                           AND m2.sender_name IS NOT NULL
                           AND m2.sender_name <> ''
                         LIMIT 1),
                        CONCAT('کاربر ', fm.sender_id)
                    ) AS sender_name,
                    fm.sender_id,
                    COUNT(*) AS count,
                    COUNT(DISTINCT fm.matched_keyword) AS keywords_used
             FROM `" . FM_TABLE . "` fm
             LEFT JOIN `" . MSG_TABLE . "` m
                    ON m.telegram_id = fm.telegram_id
                   AND m.chat_id     = fm.chat_id
             WHERE fm.sender_id IS NOT NULL
             GROUP BY sender_name, fm.sender_id
             ORDER BY count DESC
             LIMIT 15"
        )->fetchAll();
    } catch (Throwable $e) {
        $data['top_senders'] = [];
    }

    /* =========================================================
       IRRELEVANT BY GROUP
       ────────────────────────────────────────────────────────
       اسم گروه با دو لایه fallback:
       ۱) group_name خود پیام
       ۲) از پیام دیگری با همان chat_id
       ۳) "گروه {chat_id}"
    ========================================================= */
    $data['irrelevant_by_group'] = $pdo->query(
        "SELECT COALESCE(
                    NULLIF(group_name, ''),
                    (SELECT m2.group_name
                     FROM `" . MSG_TABLE . "` m2
                     WHERE m2.chat_id = t.chat_id
                       AND m2.group_name IS NOT NULL
                       AND m2.group_name <> ''
                     LIMIT 1),
                    CONCAT('گروه ', chat_id)
                ) AS group_name,
                COUNT(*) AS count
         FROM `" . MSG_TABLE . "` t
         WHERE is_irrelevant = 1
         GROUP BY group_name, chat_id
         ORDER BY count DESC
         LIMIT 10"
    )->fetchAll();

    /* =========================================================
       VIEWED BY GROUP
    ========================================================= */
    $data['viewed_by_group'] = $pdo->query(
        "SELECT COALESCE(
                    NULLIF(group_name, ''),
                    (SELECT m2.group_name
                     FROM `" . MSG_TABLE . "` m2
                     WHERE m2.chat_id = t.chat_id
                       AND m2.group_name IS NOT NULL
                       AND m2.group_name <> ''
                     LIMIT 1),
                    CONCAT('گروه ', chat_id)
                ) AS group_name,
                COUNT(*) AS count
         FROM `" . MSG_TABLE . "` t
         WHERE is_viewed = 1
         GROUP BY group_name, chat_id
         ORDER BY count DESC
         LIMIT 10"
    )->fetchAll();

    /* =========================================================
       DAILY ACTIVITY — ۱۴ روز اخیر
    ========================================================= */
    $daily = $pdo->query(
        "SELECT DATE(date_human) AS day, COUNT(*) AS count
         FROM `" . MSG_TABLE . "`
         WHERE date_human IS NOT NULL
         GROUP BY DATE(date_human)
         ORDER BY day DESC
         LIMIT 14"
    )->fetchAll();
    $data['daily_activity'] = array_reverse($daily);

    echo json_encode(['status' => 'success'] + $data, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
