<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

const BLOCKED_TABLE = 'blocked_chats';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();
    switch ($action) {
        case 'list':
            handle_list($pdo);
            break;
        case 'add':
            handle_add($pdo);
            break;
        case 'delete':
            handle_delete($pdo);
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
    $rows = $pdo->query(
        "SELECT id, chat_id, group_name, created_at
         FROM `" . BLOCKED_TABLE . "`
         ORDER BY id DESC"
    )->fetchAll();

    $items = array_map(static fn(array $r) => [
        'id'         => (int)$r['id'],
        'chat_id'    => (string)$r['chat_id'],
        'group_name' => (string)($r['group_name'] ?? ''),
        'created_at' => $r['created_at'],
    ], $rows);

    json_ok(['items' => $items]);
}

/* =========================================================
   ADD
========================================================= */
function handle_add(PDO $pdo): void
{
    $chatId    = trim((string)($_POST['chat_id'] ?? ''));
    $groupName = trim((string)($_POST['group_name'] ?? ''));

    if ($chatId === '')          json_error('شناسه چت را وارد کنید');
    if (!preg_match('/^-?\d+$/', $chatId))
        json_error('شناسه چت باید عددی باشد (می‌تواند منفی باشد)');
    if (strlen(ltrim($chatId, '-')) > 20)
        json_error('طول شناسه چت بیش از حد مجاز است');
    if ($groupName !== '' && mb_strlen($groupName) > 190)
        json_error('نام گروه حداکثر ۱۹۰ کاراکتر');

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO `" . BLOCKED_TABLE . "` (chat_id, group_name) VALUES (?, ?)"
        );
        $stmt->execute([$chatId, $groupName !== '' ? $groupName : null]);
        $id = (int)$pdo->lastInsertId();

        json_ok([
            'item' => [
                'id'         => $id,
                'chat_id'    => $chatId,
                'group_name' => $groupName,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') json_error('این چت قبلاً مسدود شده است');
        throw $e;
    }
}

/* =========================================================
   DELETE
========================================================= */
function handle_delete(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_error('شناسه نامعتبر');

    $pdo->prepare("DELETE FROM `" . BLOCKED_TABLE . "` WHERE id = ?")->execute([$id]);
    json_ok([]);
}

/* =========================================================
   HELPERS
========================================================= */
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
