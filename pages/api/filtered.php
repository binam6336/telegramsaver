<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

const FM_TABLE     = 'filtered_messages';
const MSG_TABLE    = 'messages';
const VIEWED_TABLE = 'viewed_messages';
const IRREL_TABLE  = 'irrelevant_messages';

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
    $keyword = trim((string)($_GET['keyword'] ?? ''));
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 15;
    $offset  = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    // ---- فیلتر بر اساس تب ----
    if ($tab === 'viewed') {
        $where[] = 'vm.id IS NOT NULL';
    } elseif ($tab === 'irrelevant') {
        $where[] = 'im.id IS NOT NULL';
    } else {
        $where[] = 'vm.id IS NULL AND im.id IS NULL';
    }

    if ($q !== '') {
        $where[] = '(fm.text LIKE :q OR fm.matched_keyword LIKE :q OR m.sender_name LIKE :q OR m.group_name LIKE :q)';
        $params[':q'] = "%{$q}%";
    }
    if ($keyword !== '') {
        $where[] = 'fm.matched_keyword = :kw';
        $params[':kw'] = $keyword;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    /* ⭐ JOIN با messages برای گرفتن group_name و sender_name */
    $baseFrom = "FROM `" . FM_TABLE . "` fm
                 LEFT JOIN `" . MSG_TABLE . "` m ON m.id = fm.message_id
                 LEFT JOIN `" . VIEWED_TABLE . "` vm ON vm.filtered_message_id = fm.id
                 LEFT JOIN `" . IRREL_TABLE  . "` im ON im.filtered_message_id = fm.id";

    // ---- Count ----
    $stmt = $pdo->prepare("SELECT COUNT(*) {$baseFrom} {$whereSql}");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    // ---- Data ----
    $stmt = $pdo->prepare(
        "SELECT fm.id, fm.message_id, fm.sender_id, fm.text, fm.link,
                fm.date_human, fm.matched_keyword, fm.click_count,
                m.sender_name AS sender_name,
                m.group_name  AS group_name,
                vm.id AS viewed_id, vm.viewed_at,
                im.id AS irrelevant_id, im.marked_at
         {$baseFrom}
         {$whereSql}
         ORDER BY fm.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $items = array_map(static function (array $r): array {
        return [
            'id'              => (int)$r['id'],
            'message_id'      => $r['message_id'],
            'sender_id'       => $r['sender_id'],
            'sender_name'     => $r['sender_name'] ?? '',
            'group_name'      => $r['group_name']  ?? '',
            'text'            => $r['text'],
            'link'            => $r['link'],
            'date_human'      => $r['date_human'],
            'matched_keyword' => $r['matched_keyword'],
            'click_count'     => (int)$r['click_count'],
            'is_viewed'       => $r['viewed_id'] !== null,
            'viewed_at'       => $r['viewed_at'],
            'is_irrelevant'   => $r['irrelevant_id'] !== null,
            'irrelevant_at'   => $r['marked_at'],
        ];
    }, $rows);

    // ---- لیست کلمات کلیدی ----
    $keywords = $pdo->query(
        "SELECT DISTINCT matched_keyword FROM `" . FM_TABLE . "` ORDER BY matched_keyword"
    )->fetchAll(PDO::FETCH_COLUMN);

    json_ok([
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int)ceil($total / $perPage),
        'items'    => $items,
        'counts'   => get_counts($pdo),
        'keywords' => $keywords,
    ]);
}

/* =========================================================
   INCREMENT CLICK
========================================================= */
function handle_increment_click(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_error('شناسه نامعتبر');

    $pdo->prepare("UPDATE `" . FM_TABLE . "` SET click_count = click_count + 1 WHERE id = ?")
        ->execute([$id]);

    $stmt = $pdo->prepare("SELECT click_count FROM `" . FM_TABLE . "` WHERE id = ?");
    $stmt->execute([$id]);
    $count = (int)$stmt->fetchColumn();

    json_ok(['click_count' => $count]);
}

/* =========================================================
   TOGGLE VIEWED
========================================================= */
function handle_toggle_viewed(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_error('شناسه نامعتبر');

    $stmt = $pdo->prepare("SELECT id FROM `" . VIEWED_TABLE . "` WHERE filtered_message_id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $pdo->prepare("DELETE FROM `" . VIEWED_TABLE . "` WHERE filtered_message_id = ?")->execute([$id]);
        json_ok(['is_viewed' => false, 'counts' => get_counts($pdo)]);
    } else {
        $pdo->prepare("INSERT INTO `" . VIEWED_TABLE . "` (filtered_message_id) VALUES (?)")->execute([$id]);
        json_ok(['is_viewed' => true, 'counts' => get_counts($pdo)]);
    }
}

/* =========================================================
   TOGGLE IRRELEVANT
========================================================= */
function handle_toggle_irrelevant(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_error('شناسه نامعتبر');

    $stmt = $pdo->prepare("SELECT id FROM `" . IRREL_TABLE . "` WHERE filtered_message_id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $pdo->prepare("DELETE FROM `" . IRREL_TABLE . "` WHERE filtered_message_id = ?")->execute([$id]);
        json_ok(['is_irrelevant' => false, 'counts' => get_counts($pdo)]);
    } else {
        $pdo->prepare("INSERT INTO `" . IRREL_TABLE . "` (filtered_message_id) VALUES (?)")->execute([$id]);
        json_ok(['is_irrelevant' => true, 'counts' => get_counts($pdo)]);
    }
}

/* =========================================================
   HELPERS
========================================================= */
function get_counts(PDO $pdo): array
{
    $all = (int)$pdo->query(
        "SELECT COUNT(*) FROM `" . FM_TABLE . "` fm
         LEFT JOIN `" . VIEWED_TABLE . "` vm ON vm.filtered_message_id = fm.id
         LEFT JOIN `" . IRREL_TABLE  . "` im ON im.filtered_message_id = fm.id
         WHERE vm.id IS NULL AND im.id IS NULL"
    )->fetchColumn();

    return [
        'all'        => $all,
        'viewed'     => (int)$pdo->query("SELECT COUNT(*) FROM `" . VIEWED_TABLE . "`")->fetchColumn(),
        'irrelevant' => (int)$pdo->query("SELECT COUNT(*) FROM `" . IRREL_TABLE  . "`")->fetchColumn(),
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
