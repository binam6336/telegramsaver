<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();

    // ---------- LIST ----------
    if ($action === 'list') {
        $rows = $pdo->query("SELECT id, keyword, created_at FROM filters ORDER BY id DESC")
            ->fetchAll();
        echo json_encode(['status' => 'success', 'items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---------- ADD ----------
    if ($action === 'add') {
        $keyword = trim((string)($_POST['keyword'] ?? ''));

        if ($keyword === '') {
            echo json_encode(['status' => 'error', 'message' => 'کلمه را وارد کنید'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (mb_strlen($keyword) > 190) {
            echo json_encode(['status' => 'error', 'message' => 'حداکثر ۱۹۰ کاراکتر'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO filters (keyword) VALUES (?)");
            $stmt->execute([$keyword]);
            $id = (int)$pdo->lastInsertId();

            echo json_encode([
                'status'  => 'success',
                'item'    => ['id' => $id, 'keyword' => $keyword, 'created_at' => date('Y-m-d H:i:s')],
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                echo json_encode(['status' => 'error', 'message' => 'این کلمه قبلاً ثبت شده است'], JSON_UNESCAPED_UNICODE);
            } else {
                throw $e;
            }
        }
        exit;
    }

    // ---------- DELETE ----------
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'شناسه نامعتبر'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $pdo->prepare("DELETE FROM filters WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'اکشن نامعتبر'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
