<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

const MSG_TABLE = 'messages';
const FM_TABLE  = 'filtered_messages';
const IM_TABLE  = 'irrelevant_messages';
const VM_TABLE  = 'viewed_messages';

try {
    $pdo = db();

    $data = [];

    /* =========================================================
       STATS
    ========================================================= */
    $data['stats'] = [
        'total_messages' => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "`")->fetchColumn(),
        'viewed'         => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "` WHERE is_viewed = 1")->fetchColumn(),
        'irrelevant'     => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "` WHERE is_irrelevant = 1")->fetchColumn(),
        'unique_groups'  => (int)$pdo->query("SELECT COUNT(*) FROM `chats`")->fetchColumn(),
        'unique_senders' => (int)$pdo->query("SELECT COUNT(*) FROM `senders`")->fetchColumn(),
        'total_clicks'   => (int)$pdo->query("SELECT COALESCE(SUM(click_count), 0) FROM `" . MSG_TABLE . "`")->fetchColumn(),
    ];
    try {
        $data['stats']['filtered'] = (int)$pdo->query("SELECT COUNT(*) FROM `" . FM_TABLE . "`")->fetchColumn();
    } catch (Throwable $e) {
        $data['stats']['filtered'] = 0;
    }

    /* =========================================================
       KEYWORDS → GROUPS
       ────────────────────────────────────────────────────────
       JOIN با chats (نه با messages) — پس حذف پیام تأثیری ندارد
    ========================================================= */
    $keywords = [];
    try {
        $rows = $pdo->query(
            "SELECT fm.matched_keyword AS keyword,
                    COALESCE(
                        c.group_name,
                        CONCAT('گروه ', fm.chat_id)
                    ) AS group_name,
                    COUNT(*) AS count
             FROM `" . FM_TABLE . "` fm
             LEFT JOIN `chats` c ON c.chat_id = fm.chat_id
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
       TOP SENDERS — JOIN با senders
    ========================================================= */
    try {
        $data['top_senders'] = $pdo->query(
            "SELECT COALESCE(
                        s.sender_name,
                        CONCAT('کاربر ', fm.sender_id)
                    ) AS sender_name,
                    fm.sender_id,
                    COUNT(*) AS count,
                    COUNT(DISTINCT fm.matched_keyword) AS keywords_used
             FROM `" . FM_TABLE . "` fm
             LEFT JOIN `senders` s ON s.sender_id = fm.sender_id
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
       جدید: messages.is_irrelevant = 1 → JOIN chats
       قدیمی: irrelevant_messages → filtered_messages → chats
    ========================================================= */
    $irrelevantByGroup = [];
    try {
        // منبع جدید
        foreach (
            $pdo->query(
                "SELECT COALESCE(c.group_name, CONCAT('گروه ', t.chat_id)) AS group_name,
                    COUNT(*) AS count
             FROM `" . MSG_TABLE . "` t
             LEFT JOIN `chats` c ON c.chat_id = t.chat_id
             WHERE t.is_irrelevant = 1
             GROUP BY group_name"
            ) as $r
        ) {
            $g = $r['group_name'];
            $irrelevantByGroup[$g] = ($irrelevantByGroup[$g] ?? 0) + (int)$r['count'];
        }

        // منبع قدیمی
        foreach (
            $pdo->query(
                "SELECT fm.id AS fm_id,
                    COALESCE(c.group_name, CONCAT('گروه ', fm.chat_id)) AS group_name
             FROM `" . FM_TABLE . "` fm
             INNER JOIN `" . IM_TABLE . "` im ON im.filtered_message_id = fm.id
             LEFT JOIN `chats` c ON c.chat_id = fm.chat_id"
            ) as $r
        ) {
            $g = $r['group_name'];
            $irrelevantByGroup[$g] = ($irrelevantByGroup[$g] ?? 0) + 1;
        }
    } catch (Throwable $e) {
    }
    arsort($irrelevantByGroup);
    $data['irrelevant_by_group'] = array_slice(
        array_map(
            fn($n, $c) => ['group_name' => $n, 'count' => $c],
            array_keys($irrelevantByGroup),
            array_values($irrelevantByGroup)
        ),
        0,
        10
    );

    /* =========================================================
       VIEWED BY GROUP
    ========================================================= */
    $viewedByGroup = [];
    try {
        foreach (
            $pdo->query(
                "SELECT COALESCE(c.group_name, CONCAT('گروه ', t.chat_id)) AS group_name,
                    COUNT(*) AS count
             FROM `" . MSG_TABLE . "` t
             LEFT JOIN `chats` c ON c.chat_id = t.chat_id
             WHERE t.is_viewed = 1
             GROUP BY group_name"
            ) as $r
        ) {
            $g = $r['group_name'];
            $viewedByGroup[$g] = ($viewedByGroup[$g] ?? 0) + (int)$r['count'];
        }

        foreach (
            $pdo->query(
                "SELECT fm.id AS fm_id,
                    COALESCE(c.group_name, CONCAT('گروه ', fm.chat_id)) AS group_name
             FROM `" . FM_TABLE . "` fm
             INNER JOIN `" . VM_TABLE . "` vm ON vm.filtered_message_id = fm.id
             LEFT JOIN `chats` c ON c.chat_id = fm.chat_id"
            ) as $r
        ) {
            $g = $r['group_name'];
            $viewedByGroup[$g] = ($viewedByGroup[$g] ?? 0) + 1;
        }
    } catch (Throwable $e) {
    }
    arsort($viewedByGroup);
    $data['viewed_by_group'] = array_slice(
        array_map(
            fn($n, $c) => ['group_name' => $n, 'count' => $c],
            array_keys($viewedByGroup),
            array_values($viewedByGroup)
        ),
        0,
        10
    );

    /* =========================================================
       DAILY ACTIVITY
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
