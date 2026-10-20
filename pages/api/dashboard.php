<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

const MSG_TABLE     = 'messages';
const FM_TABLE      = 'filtered_messages';
const IM_TABLE      = 'irrelevant_messages';
const VM_TABLE      = 'viewed_messages';
const BLOCKED_TABLE = 'blocked_chats';

$action = $_GET['action'] ?? 'full';

try {
    $pdo = db();

    if ($action === 'trend') {
        handle_trend($pdo);
    } else {
        handle_full($pdo);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/* =========================================================
   TREND
========================================================= */
function handle_trend(PDO $pdo): void
{
    $range = $_GET['range'] ?? 'week';
    json_ok(['range' => $range, 'trend' => build_trend($pdo, $range)]);
}

function build_trend(PDO $pdo, string $range): array
{
    $ranges = [
        '2days' => ['days' => 2,   'group' => 'day'],
        'week'  => ['days' => 7,   'group' => 'day'],
        'month' => ['days' => 30,  'group' => 'day'],
        'year'  => ['days' => 365, 'group' => 'month'],
    ];
    $cfg  = $ranges[$range] ?? $ranges['week'];
    $days = $cfg['days'];

    $notBlocked = "NOT EXISTS (SELECT 1 FROM `" . BLOCKED_TABLE . "` b WHERE b.chat_id = fm.chat_id)";

    if ($cfg['group'] === 'month') {
        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(date)), '%Y-%m') AS bucket,
                    COUNT(*) AS count
             FROM `" . FM_TABLE . "` fm
             WHERE date >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 365 DAY))
               AND {$notBlocked}
             GROUP BY bucket
             ORDER BY bucket ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $byBucket = [];
        foreach ($rows as $r) $byBucket[$r['bucket']] = (int)$r['count'];

        $result = [];
        for ($i = 11; $i >= 0; $i--) {
            $d = date('Y-m', strtotime("-{$i} months"));
            $result[] = ['bucket' => $d, 'count' => $byBucket[$d] ?? 0];
        }
        return $result;
    }

    $stmt = $pdo->prepare(
        "SELECT DATE(FROM_UNIXTIME(date)) AS bucket,
                COUNT(*) AS count
         FROM `" . FM_TABLE . "` fm
         WHERE date >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL :days DAY))
           AND {$notBlocked}
         GROUP BY bucket
         ORDER BY bucket ASC"
    );
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $byBucket = [];
    foreach ($rows as $r) $byBucket[$r['bucket']] = (int)$r['count'];

    $result = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $result[] = ['bucket' => $d, 'count' => $byBucket[$d] ?? 0];
    }
    return $result;
}

/* =========================================================
   FULL
========================================================= */
function handle_full(PDO $pdo): void
{
    $data = [];

    /* ⭐ همه کوئری‌های messages با alias m — و زیرکوئری هم m.chat_id */
    $nbMsg = "NOT EXISTS (SELECT 1 FROM `" . BLOCKED_TABLE . "` b WHERE b.chat_id = m.chat_id)";
    $nbFm  = "NOT EXISTS (SELECT 1 FROM `" . BLOCKED_TABLE . "` b WHERE b.chat_id = fm.chat_id)";

    /* ═══ STATS ═══ */
    $data['stats'] = [
        'total_messages' => (int)$pdo->query(
            "SELECT COUNT(*) FROM `" . MSG_TABLE . "` m WHERE {$nbMsg}"
        )->fetchColumn(),
        'viewed'         => (int)$pdo->query(
            "SELECT COUNT(*) FROM `" . MSG_TABLE . "` m WHERE m.is_viewed = 1 AND {$nbMsg}"
        )->fetchColumn(),
        'irrelevant'     => (int)$pdo->query(
            "SELECT COUNT(*) FROM `" . MSG_TABLE . "` m WHERE m.is_irrelevant = 1 AND {$nbMsg}"
        )->fetchColumn(),
        'unique_groups'  => (int)$pdo->query(
            "SELECT COUNT(*) FROM `chats`
             WHERE NOT EXISTS (SELECT 1 FROM `" . BLOCKED_TABLE . "` b WHERE b.chat_id = chats.chat_id)"
        )->fetchColumn(),
        'unique_senders' => (int)$pdo->query(
            "SELECT COUNT(DISTINCT m.sender_id) FROM `" . MSG_TABLE . "` m
             WHERE m.sender_id IS NOT NULL AND {$nbMsg}"
        )->fetchColumn(),
        'total_clicks'   => (int)$pdo->query(
            "SELECT COALESCE(SUM(m.click_count), 0) FROM `" . MSG_TABLE . "` m WHERE {$nbMsg}"
        )->fetchColumn(),
    ];
    try {
        $data['stats']['filtered'] = (int)$pdo->query(
            "SELECT COUNT(*) FROM `" . FM_TABLE . "` fm WHERE {$nbFm}"
        )->fetchColumn();
    } catch (Throwable $e) {
        $data['stats']['filtered'] = 0;
    }

    /* ═══ KEYWORDS → GROUPS ═══ */
    $keywords = [];
    try {
        $rows = $pdo->query(
            "SELECT fm.matched_keyword AS keyword,
                    COALESCE(c.group_name, CONCAT('گروه ', fm.chat_id)) AS group_name,
                    COUNT(*) AS count
             FROM `" . FM_TABLE . "` fm
             LEFT JOIN `chats` c ON c.chat_id = fm.chat_id
             WHERE {$nbFm}
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

    /* ═══ TOP SENDERS ═══ */
    try {
        $data['top_senders'] = $pdo->query(
            "SELECT COALESCE(s.sender_name, CONCAT('کاربر ', fm.sender_id)) AS sender_name,
                    fm.sender_id,
                    COUNT(*) AS count,
                    COUNT(DISTINCT fm.matched_keyword) AS keywords_used
             FROM `" . FM_TABLE . "` fm
             LEFT JOIN `senders` s ON s.sender_id = fm.sender_id
             WHERE fm.sender_id IS NOT NULL
               AND {$nbFm}
             GROUP BY sender_name, fm.sender_id
             ORDER BY count DESC
             LIMIT 15"
        )->fetchAll();
    } catch (Throwable $e) {
        $data['top_senders'] = [];
    }

    /* ═══ IRRELEVANT BY GROUP ═══ */
    $irrelByGroup = [];
    try {
        foreach (
            $pdo->query(
                "SELECT COALESCE(c.group_name, CONCAT('گروه ', t.chat_id)) AS group_name,
                    COUNT(*) AS count
             FROM `" . MSG_TABLE . "` t
             LEFT JOIN `chats` c ON c.chat_id = t.chat_id
             WHERE t.is_irrelevant = 1
               AND NOT EXISTS (SELECT 1 FROM `" . BLOCKED_TABLE . "` b WHERE b.chat_id = t.chat_id)
             GROUP BY group_name"
            ) as $r
        ) {
            $g = $r['group_name'];
            $irrelByGroup[$g] = ($irrelByGroup[$g] ?? 0) + (int)$r['count'];
        }
        foreach (
            $pdo->query(
                "SELECT COALESCE(c.group_name, CONCAT('گروه ', fm.chat_id)) AS group_name
             FROM `" . FM_TABLE . "` fm
             INNER JOIN `" . IM_TABLE . "` im ON im.filtered_message_id = fm.id
             LEFT JOIN `chats` c ON c.chat_id = fm.chat_id
             WHERE {$nbFm}"
            ) as $r
        ) {
            $g = $r['group_name'];
            $irrelByGroup[$g] = ($irrelByGroup[$g] ?? 0) + 1;
        }
    } catch (Throwable $e) {
    }
    arsort($irrelByGroup);
    $data['irrelevant_by_group'] = array_slice(
        array_map(
            fn($n, $c) => ['group_name' => $n, 'count' => $c],
            array_keys($irrelByGroup),
            array_values($irrelByGroup)
        ),
        0,
        10
    );

    /* ═══ VIEWED BY GROUP ═══ */
    $viewedByGroup = [];
    try {
        foreach (
            $pdo->query(
                "SELECT COALESCE(c.group_name, CONCAT('گروه ', t.chat_id)) AS group_name,
                    COUNT(*) AS count
             FROM `" . MSG_TABLE . "` t
             LEFT JOIN `chats` c ON c.chat_id = t.chat_id
             WHERE t.is_viewed = 1
               AND NOT EXISTS (SELECT 1 FROM `" . BLOCKED_TABLE . "` b WHERE b.chat_id = t.chat_id)
             GROUP BY group_name"
            ) as $r
        ) {
            $g = $r['group_name'];
            $viewedByGroup[$g] = ($viewedByGroup[$g] ?? 0) + (int)$r['count'];
        }
        foreach (
            $pdo->query(
                "SELECT COALESCE(c.group_name, CONCAT('گروه ', fm.chat_id)) AS group_name
             FROM `" . FM_TABLE . "` fm
             INNER JOIN `" . VM_TABLE . "` vm ON vm.filtered_message_id = fm.id
             LEFT JOIN `chats` c ON c.chat_id = fm.chat_id
             WHERE {$nbFm}"
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

    /* ═══ DAILY ═══ */
    $daily = $pdo->query(
        "SELECT DATE(t.date_human) AS day, COUNT(*) AS count
         FROM `" . MSG_TABLE . "` t
         WHERE t.date_human IS NOT NULL
           AND NOT EXISTS (SELECT 1 FROM `" . BLOCKED_TABLE . "` b WHERE b.chat_id = t.chat_id)
         GROUP BY DATE(t.date_human)
         ORDER BY day DESC
         LIMIT 14"
    )->fetchAll();
    $data['daily_activity'] = array_reverse($daily);

    $data['trend'] = build_trend($pdo, 'month');

    json_ok($data);
}

/* =========================================================
   HELPERS
========================================================= */
function json_ok(array $data): void
{
    echo json_encode(['status' => 'success'] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}
