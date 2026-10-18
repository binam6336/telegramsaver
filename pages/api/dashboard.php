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
        'unique_groups'  => (int)$pdo->query("SELECT COUNT(DISTINCT group_name) FROM `" . MSG_TABLE . "` WHERE group_name IS NOT NULL AND group_name <> ''")->fetchColumn(),
        'unique_senders' => (int)$pdo->query("SELECT COUNT(DISTINCT sender_name) FROM `" . MSG_TABLE . "` WHERE sender_name IS NOT NULL AND sender_name <> ''")->fetchColumn(),
        'total_clicks'   => (int)$pdo->query("SELECT COALESCE(SUM(click_count), 0) FROM `" . MSG_TABLE . "`")->fetchColumn(),
    ];
    try {
        $data['stats']['filtered'] = (int)$pdo->query("SELECT COUNT(*) FROM `" . FM_TABLE . "`")->fetchColumn();
    } catch (Throwable $e) {
        $data['stats']['filtered'] = 0;
    }

    /* =========================================================
       GROUP MAP — نگاشت chat_id → group_name
    ========================================================= */
    $groupMap = [];
    foreach (
        $pdo->query(
            "SELECT chat_id, MAX(group_name) AS group_name
         FROM `" . MSG_TABLE . "`
         WHERE chat_id IS NOT NULL
           AND group_name IS NOT NULL
           AND group_name <> ''
         GROUP BY chat_id"
        ) as $r
    ) {
        $groupMap[(string)$r['chat_id']] = $r['group_name'];
    }

    $resolveGroup = function ($chatId, $fallbackName = '') use ($groupMap) {
        if (!empty($fallbackName)) return $fallbackName;
        $k = (string)$chatId;
        if (isset($groupMap[$k])) return $groupMap[$k];
        return 'گروه ' . $chatId;
    };

    /* =========================================================
       KEYWORDS → GROUPS
    ========================================================= */
    $keywords = [];
    try {
        $rows = $pdo->query(
            "SELECT fm.matched_keyword AS keyword,
                    fm.chat_id,
                    NULLIF(m.group_name, '') AS group_name,
                    COUNT(*) AS count
             FROM `" . FM_TABLE . "` fm
             LEFT JOIN `" . MSG_TABLE . "` m
                    ON (m.telegram_id = fm.telegram_id AND m.chat_id = fm.chat_id)
                    OR (m.chat_id = fm.chat_id AND m.id = fm.message_id)
             GROUP BY fm.matched_keyword, fm.chat_id, group_name"
        )->fetchAll();

        foreach ($rows as $r) {
            $kw  = $r['keyword'];
            $g   = $resolveGroup($r['chat_id'], $r['group_name'] ?? '');
            if (!isset($keywords[$kw])) {
                $keywords[$kw] = ['keyword' => $kw, 'total' => 0, 'groups' => []];
            }
            if (!isset($keywords[$kw]['groups'][$g])) {
                $keywords[$kw]['groups'][$g] = 0;
            }
            $keywords[$kw]['groups'][$g]  += (int)$r['count'];
            $keywords[$kw]['total']       += (int)$r['count'];
        }

        foreach ($keywords as &$k) {
            arsort($k['groups']);
            $k['groups'] = array_slice(
                array_map(
                    fn($name, $cnt) => ['group_name' => $name, 'count' => $cnt],
                    array_keys($k['groups']),
                    array_values($k['groups'])
                ),
                0,
                5
            );
        }
        unset($k);

        usort($keywords, fn($a, $b) => $b['total'] <=> $a['total']);
        $keywords = array_values($keywords);
    } catch (Throwable $e) {
        $keywords = [];
    }
    $data['keywords'] = $keywords;

    /* =========================================================
       TOP SENDERS — با نگاشت sender_id → sender_name
    ========================================================= */
    $senderMap = [];
    foreach (
        $pdo->query(
            "SELECT sender_id, MAX(sender_name) AS sender_name
         FROM `" . MSG_TABLE . "`
         WHERE sender_id IS NOT NULL
           AND sender_name IS NOT NULL
           AND sender_name <> ''
         GROUP BY sender_id"
        ) as $r
    ) {
        $senderMap[(string)$r['sender_id']] = $r['sender_name'];
    }

    try {
        $rows = $pdo->query(
            "SELECT fm.sender_id,
                    NULLIF(m.sender_name, '') AS sender_name,
                    COUNT(*) AS count,
                    COUNT(DISTINCT fm.matched_keyword) AS keywords_used
             FROM `" . FM_TABLE . "` fm
             LEFT JOIN `" . MSG_TABLE . "` m
                    ON (m.telegram_id = fm.telegram_id AND m.chat_id = fm.chat_id)
                    OR (m.chat_id = fm.chat_id AND m.id = fm.message_id)
             WHERE fm.sender_id IS NOT NULL
             GROUP BY fm.sender_id, sender_name"
        )->fetchAll();

        $bySender = [];
        foreach ($rows as $r) {
            $sid  = (string)$r['sender_id'];
            $name = $r['sender_name'] ?: ($senderMap[$sid] ?? ('کاربر ' . $sid));
            if (!isset($bySender[$sid])) {
                $bySender[$sid] = [
                    'sender_name'   => $name,
                    'count'         => 0,
                    'keywords_used' => 0,
                ];
            }
            $bySender[$sid]['count'] += (int)$r['count'];
            $bySender[$sid]['keywords_used'] = max(
                $bySender[$sid]['keywords_used'],
                (int)$r['keywords_used']
            );
        }

        $senders = array_values($bySender);
        usort($senders, fn($a, $b) => $b['count'] <=> $a['count']);
        $data['top_senders'] = array_slice($senders, 0, 15);
    } catch (Throwable $e) {
        $data['top_senders'] = [];
    }

    /* =========================================================
       IRRELEVANT BY GROUP — از هر دو منبع (جدید + قدیمی)
       ────────────────────────────────────────────────────────
       جدید: messages.is_irrelevant = 1
       قدیمی: irrelevant_messages → filtered_messages
       نتیجه با chat_id یکی می‌شوند و sum می‌شوند (با DISTINCT)
    ========================================================= */
    $irrelevantByGroup = [];
    try {
        // ---- منبع جدید ----
        foreach (
            $pdo->query(
                "SELECT chat_id, group_name, COUNT(*) AS count
             FROM `" . MSG_TABLE . "`
             WHERE is_irrelevant = 1
             GROUP BY chat_id, group_name"
            ) as $r
        ) {
            $g = $resolveGroup($r['chat_id'], $r['group_name'] ?? '');
            $irrelevantByGroup[$g] = ($irrelevantByGroup[$g] ?? 0) + (int)$r['count'];
        }

        // ---- منبع قدیمی (اگر جدول وجود داشت) ----
        foreach (
            $pdo->query(
                "SELECT fm.id AS fm_id, fm.chat_id,
                    NULLIF(m.group_name, '') AS group_name,
                    COUNT(DISTINCT im.id) AS count
             FROM `" . FM_TABLE . "` fm
             INNER JOIN `" . IM_TABLE . "` im
                     ON im.filtered_message_id = fm.id
             LEFT JOIN `" . MSG_TABLE . "` m
                    ON (m.telegram_id = fm.telegram_id AND m.chat_id = fm.chat_id)
                    OR (m.chat_id = fm.chat_id AND m.id = fm.message_id)
             GROUP BY fm.id, fm.chat_id, group_name"
            ) as $r
        ) {
            $g = $resolveGroup($r['chat_id'], $r['group_name'] ?? '');
            $irrelevantByGroup[$g] = ($irrelevantByGroup[$g] ?? 0) + (int)$r['count'];
        }
    } catch (Throwable $e) {
        // نادیده بگیر
    }
    arsort($irrelevantByGroup);
    $data['irrelevant_by_group'] = array_slice(
        array_map(
            fn($name, $cnt) => ['group_name' => $name, 'count' => $cnt],
            array_keys($irrelevantByGroup),
            array_values($irrelevantByGroup)
        ),
        0,
        10
    );

    /* =========================================================
       VIEWED BY GROUP — از هر دو منبع
    ========================================================= */
    $viewedByGroup = [];
    try {
        foreach (
            $pdo->query(
                "SELECT chat_id, group_name, COUNT(*) AS count
             FROM `" . MSG_TABLE . "`
             WHERE is_viewed = 1
             GROUP BY chat_id, group_name"
            ) as $r
        ) {
            $g = $resolveGroup($r['chat_id'], $r['group_name'] ?? '');
            $viewedByGroup[$g] = ($viewedByGroup[$g] ?? 0) + (int)$r['count'];
        }

        foreach (
            $pdo->query(
                "SELECT fm.id AS fm_id, fm.chat_id,
                    NULLIF(m.group_name, '') AS group_name,
                    COUNT(DISTINCT vm.id) AS count
             FROM `" . FM_TABLE . "` fm
             INNER JOIN `" . VM_TABLE . "` vm
                     ON vm.filtered_message_id = fm.id
             LEFT JOIN `" . MSG_TABLE . "` m
                    ON (m.telegram_id = fm.telegram_id AND m.chat_id = fm.chat_id)
                    OR (m.chat_id = fm.chat_id AND m.id = fm.message_id)
             GROUP BY fm.id, fm.chat_id, group_name"
            ) as $r
        ) {
            $g = $resolveGroup($r['chat_id'], $r['group_name'] ?? '');
            $viewedByGroup[$g] = ($viewedByGroup[$g] ?? 0) + (int)$r['count'];
        }
    } catch (Throwable $e) {
        // نادیده بگیر
    }
    arsort($viewedByGroup);
    $data['viewed_by_group'] = array_slice(
        array_map(
            fn($name, $cnt) => ['group_name' => $name, 'count' => $cnt],
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
