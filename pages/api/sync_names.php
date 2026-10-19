<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

try {
    $root = dirname(__DIR__, 2);
    require_once $root . '/sync_names.php';

    $result = sync_names(db());

    $pdo = db();

    echo json_encode([
        'status' => 'success',
        'result' => $result,
        'totals' => [
            'chats'   => (int)$pdo->query("SELECT COUNT(*) FROM `chats`")->fetchColumn(),
            'senders' => (int)$pdo->query("SELECT COUNT(*) FROM `senders`")->fetchColumn(),
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
