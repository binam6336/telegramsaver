<?php
declare(strict_types=1);

// ⚡ اولین خط: هر output بعدی رو بافر کن
ob_start();

require __DIR__ . '/bootstrap.php';

// 🧹 هر چیزی که تا اینجا چاپ شده (BOM، فاصله، خط اضافه) رو پاک کن
if (ob_get_length() > 0) {
    $junk = ob_get_clean();
    Logger::warning(
        'خروجی ناخواسته قبل از هدرها پاک شد',
        'Unwanted output before headers was cleaned',
        ['junk_hex' => bin2hex(substr($junk, 0, 20))]
    );
}

header('Content-Type: application/json; charset=utf-8');

/** @var array $config */

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        Logger::warning('متد درخواست نامعتبر', 'Invalid request method', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'فقط POST مجاز است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        Logger::warning('بدنه درخواست خالی است', 'Empty request body', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'بدنه درخواست خالی است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (strlen($raw) > $config['api']['max_payload_size']) {
        Logger::warning('حجم درخواست بیش از حد مجاز', 'Payload too large', [
            'size' => strlen($raw),
            'max'  => $config['api']['max_payload_size'],
        ]);
        http_response_code(413);
        echo json_encode(['ok' => false, 'message' => 'حجم درخواست بیش از حد مجاز است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        Logger::warning('JSON نامعتبر', 'Invalid JSON', ['error' => json_last_error_msg()]);
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'JSON نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($data['id']) && !is_array($data['id'])) {
        $data = [$data];
    }

    if (empty($data)) {
        echo json_encode(['ok' => true, 'queued' => 0], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $now        = date('Y-m-d H:i:s');
    $normalized = [];
    $skipped    = 0;

    foreach ($data as $i => $row) {
        if (!is_array($row) || !isset($row['id'])) {
            $skipped++;
            Logger::warning('رکورد نامعتبر در ورودی', 'Invalid row in payload', [
                'index' => $i,
                'row'   => $row,
            ]);
            continue;
        }

        $normalized[] = [
            'telegram_id' => (int) $row['id'],
            'chat_id'     => isset($row['chat_id'])    ? (int)    $row['chat_id']    : null,
            'sender_id'   => isset($row['sender_id'])  ? (int)    $row['sender_id']  : null,
            'text'        => isset($row['text'])       ? (string) $row['text']       : null,
            'link'        => isset($row['link'])       ? (string) $row['link']       : null,
            'date'        => isset($row['date'])       ? (int)    $row['date']       : null,
            'date_human'  => isset($row['date_human']) ? (string) $row['date_human'] : null,
            'created_at'  => $now,
        ];
    }

    if (empty($normalized)) {
        Logger::warning('هیچ رکورد معتبری برای صف وجود ندارد', 'No valid rows to queue', [
            'skipped' => $skipped,
        ]);
        echo json_encode(['ok' => true, 'queued' => 0, 'skipped' => $skipped], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $queue = new Queue(
        $config['paths']['queue'],
        $config['paths']['processing'],
        $config['paths']['failed']
    );

    $file = $queue->push($normalized);

    Logger::info('درخواست با موفقیت در صف ذخیره شد', 'Request queued successfully', [
        'count'   => count($normalized),
        'skipped' => $skipped,
        'file'    => $file,
    ]);

    echo json_encode([
        'ok'      => true,
        'queued'  => count($normalized),
        'skipped' => $skipped,
        'file'    => $file,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    Logger::error('خطا در پردازش API', 'API processing error', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);

    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'خطای داخلی سرور'], JSON_UNESCAPED_UNICODE);
}