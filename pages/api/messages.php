<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

const MSG_TABLE     = 'messages';
const BLOCKED_TABLE = 'blocked_chats';

try {
    $pdo = db();

    $cols = $pdo->query("SHOW COLUMNS FROM `" . MSG_TABLE . "`")
        ->fetchAll(PDO::FETCH_COLUMN);

    $pick = function (array $cands) use ($cols): ?string {
        foreach ($cands as $c) if (in_array($c, $cols, true)) return $c;
        return null;
    };

    $COL_ID      = $pick(['id', 'message_id']);
    $COL_TEXT    = $pick(['text', 'message', 'content', 'body', 'caption']);
    $COL_SENDER  = $pick(['sender_name', 'sender', 'from_name', 'username', 'first_name']);
    $COL_GROUP   = $pick(['group_name', 'chat_title', 'group', 'chat_name']);
    $COL_CHATID  = $pick(['chat_id']);
    $COL_DATE    = $pick(['date_human', 'created_at', 'date', 'sent_at', 'inserted_at']);
    $COL_LINK    = $pick(['link', 'url', 'message_link', 'source_url', 'original_url', 'tg_link']);

    $q       = trim((string)($_GET['q'] ?? ''));
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 15;
    $offset  = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    /* ⭐ NOT EXISTS — NULL-safe و سریع‌تر */
    if ($COL_CHATID) {
        $where[] = "NOT EXISTS (
            SELECT 1 FROM `" . BLOCKED_TABLE . "` b
            WHERE b.chat_id = `$COL_CHATID`
        )";
    }

    if ($q !== '') {
        $searchable = array_values(array_filter([$COL_TEXT, $COL_SENDER, $COL_GROUP]));
        if ($searchable) {
            $parts = [];
            foreach ($searchable as $c) $parts[] = "`$c` LIKE :q";
            $where[] = '(' . implode(' OR ', $parts) . ')';
            $params[':q'] = "%{$q}%";
        }
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `" . MSG_TABLE . "` {$whereSql}");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $orderBy = $COL_ID ? "`$COL_ID` DESC" : '1';
    $stmt = $pdo->prepare(
        "SELECT * FROM `" . MSG_TABLE . "` {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $items = array_map(function (array $r) use ($COL_ID, $COL_TEXT, $COL_SENDER, $COL_GROUP, $COL_CHATID, $COL_DATE, $COL_LINK) {

        $text = $COL_TEXT ? (string)($r[$COL_TEXT] ?? '') : '';
        $link = null;

        if ($COL_LINK && !empty($r[$COL_LINK]) && preg_match('~^https?://~i', (string)$r[$COL_LINK])) {
            $link = (string)$r[$COL_LINK];
        } elseif ($text && preg_match('~https?://\S+~i', $text, $m)) {
            $link = rtrim($m[0], '.,!؟?،');
        }

        return [
            'id'          => $COL_ID     ? ($r[$COL_ID]     ?? null) : null,
            'sender_name' => $COL_SENDER ? ($r[$COL_SENDER] ?? '')   : '',
            'group_name'  => $COL_GROUP  ? ($r[$COL_GROUP]  ?? '')   : '',
            'chat_id'     => $COL_CHATID ? ($r[$COL_CHATID] ?? null) : null,
            'text'        => $text,
            'date'        => $COL_DATE   ? ($r[$COL_DATE]   ?? '')   : '',
            'link'        => $link,
        ];
    }, $rows);

    echo json_encode([
        'status'   => 'success',
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int)ceil($total / $perPage),
        'items'    => $items,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
