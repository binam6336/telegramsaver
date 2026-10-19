<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

const MSG_TABLE = 'messages';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();
    switch ($action) {
        case 'list':
            handle_list($pdo);
            break;
        case 'increment_click':
            handle_increment_click($pdo);
            break;
        case 'toggle_viewed':
            handle_toggle_viewed($pdo);
            break;
        case 'toggle_irrelevant':
            handle_toggle_irrelevant($pdo);
            break;
        default:
            json_error('اکشن نامعتبر');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/* =========================================================
   LIST
========================================================= */
function handle_list(PDO $pdo): void
{
    $tab     = $_GET['tab'] ?? 'all';
    $q       = trim((string)($_GET['q'] ?? ''));
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 15;
    $offset  = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    if ($tab === 'viewed') {
        $where[] = 'm.is_viewed = 1';
    } elseif ($tab === 'irrelevant') {
        $where[] = 'm.is_irrelevant = 1';
    } else {
        $where[] = 'm.is_viewed = 0 AND m.is_irrelevant = 0';
    }

    if ($q !== '') {
        $where[] = '(
            m.text LIKE :q1
            OR COALESCE(s.sender_name, m.sender_name) LIKE :q2
            OR COALESCE(c.group_name,  m.group_name)  LIKE :q3
        )';
        $params[':q1'] = "%{$q}%";
        $params[':q2'] = "%{$q}%";
        $params[':q3'] = "%{$q}%";
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    /* JOIN با chats/senders */
    $baseFrom = "FROM `" . MSG_TABLE . "` m
                 LEFT JOIN `chats`   c ON c.chat_id   = m.chat_id
                 LEFT JOIN `senders` s ON s.sender_id = m.sender_id";

    $stmt = $pdo->prepare("SELECT COUNT(*) {$baseFrom} {$whereSql}");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT m.id, m.chat_id, m.sender_id, m.text, m.link, m.date_human,
                m.click_count, m.is_viewed, m.is_irrelevant,
                COALESCE(NULLIF(s.sender_name, ''), m.sender_name, CONCAT('کاربر ', m.sender_id)) AS sender_name,
                COALESCE(NULLIF(c.group_name, ''),  m.group_name,  CONCAT('گروه ',  m.chat_id))   AS group_name
         {$baseFrom}
         {$whereSql}
         ORDER BY m.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $items = array_map(static function (array $r): array {
        return [
            'id'            => (int)$r['id'],
            'chat_id'       => $r['chat_id'],
            'sender_name'   => (string)($r['sender_name'] ?? ''),
            'group_name'    => (string)($r['group_name']  ?? ''),
            'text'          => (string)($r['text'] ?? ''),
            'link'          => $r['link'] ?: null,
            'date_human'    => (string)($r['date_human'] ?? ''),
            'click_count'   => (int)$r['click_count'],
            'is_viewed'     => (int)$r['is_viewed'] === 1,
            'is_irrelevant' => (int)$r['is_irrelevant'] === 1,
        ];
    }, $rows);

    json_ok([
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int)ceil($total / $perPage),
        'items'    => $items,
        'counts'   => get_counts($pdo),
    ]);
}

/* =========================================================
   INCREMENT CLICK
========================================================= */
function handle_increment_click(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_error('شناسه نامعتبر');

    $pdo->prepare("UPDATE `" . MSG_TABLE . "` SET click_count = click_count + 1 WHERE id = ?")
        ->execute([$id]);

    $stmt = $pdo->prepare("SELECT click_count FROM `" . MSG_TABLE . "` WHERE id = ?");
    $stmt->execute([$id]);

    json_ok(['click_count' => (int)$stmt->fetchColumn()]);
}

/* =========================================================
   TOGGLE VIEWED
========================================================= */
function handle_toggle_viewed(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_error('شناسه نامعتبر');

    $pdo->prepare("UPDATE `" . MSG_TABLE . "` SET is_viewed = IF(is_viewed = 1, 0, 1) WHERE id = ?")
        ->execute([$id]);

    $stmt = $pdo->prepare("SELECT is_viewed FROM `" . MSG_TABLE . "` WHERE id = ?");
    $stmt->execute([$id]);

    json_ok(['is_viewed' => (int)$stmt->fetchColumn() === 1, 'counts' => get_counts($pdo)]);
}

/* =========================================================
   TOGGLE IRRELEVANT
========================================================= */
function handle_toggle_irrelevant(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_error('شناسه نامعتبر');

    $pdo->prepare("UPDATE `" . MSG_TABLE . "` SET is_irrelevant = IF(is_irrelevant = 1, 0, 1) WHERE id = ?")
        ->execute([$id]);

    $stmt = $pdo->prepare("SELECT is_irrelevant FROM `" . MSG_TABLE . "` WHERE id = ?");
    $stmt->execute([$id]);

    json_ok(['is_irrelevant' => (int)$stmt->fetchColumn() === 1, 'counts' => get_counts($pdo)]);
}

/* =========================================================
   HELPERS
========================================================= */
function get_counts(PDO $pdo): array
{
    return [
        'all'        => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "` WHERE is_viewed = 0 AND is_irrelevant = 0")->fetchColumn(),
        'viewed'     => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "` WHERE is_viewed = 1")->fetchColumn(),
        'irrelevant' => (int)$pdo->query("SELECT COUNT(*) FROM `" . MSG_TABLE . "` WHERE is_irrelevant = 1")->fetchColumn(),
    ];
}

function json_ok(array $data): void
{
    echo json_encode(['status' => 'success'] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
