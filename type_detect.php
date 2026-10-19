<?php

declare(strict_types=1);

ignore_user_abort(true);
set_time_limit(180);

ob_start();
require __DIR__ . '/bootstrap.php';
if (ob_get_length() > 0) ob_get_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

/** @var array $config */

// ============================================================
// ⚙️ تنظیمات
// ============================================================
const AI_URL         = 'https://hermes.ai.unturf.com/v1/chat/completions';
const AI_MODEL       = 'turboderp/Qwen3.8-27B-exl3';
const AI_TIMEOUT     = 90;
const AI_TEXT_LIMIT  = 500;

const TASKAM_URL      = 'https://mahyarsalehee.ir/taskam/api/v1/tasks/create/';
const TASKAM_TOKEN    = 'tk_ebafa53eda5d1d8c4613ef4f3720ba70bd6c8fe5740b95cd';
const TASKAM_TIMEOUT  = 30;

const LOCK_FILE       = __DIR__ . '/storage/type_detect.lock';
const STALE_LOCK_SEC  = 300;   // 5 دقیقه
const DEFAULT_LIMIT   = 1;     // پیش‌فرض ۱ پیام در هر اجرا (کرون هر ۲ دقیقه)
const SLEEP_BETWEEN   = 120;   // فقط وقتی limit > 1 باشه

// ============================================================
// 🎯 پارامترها
// ============================================================
$p = [
    'status'       => isset($_GET['status']),
    'force_unlock' => isset($_GET['force_unlock']),
    'test'         => isset($_GET['test']),
    'sync'         => isset($_GET['sync']),
    'limit'        => isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : DEFAULT_LIMIT,
];

// ============================================================
// 📊 status (چک وضعیت بدون اجرا)
// ============================================================
if ($p['status']) {
    echo json_encode(getStatus($config), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================================================
// 🔓 force unlock (آزادسازی lock گیرکرده)
// ============================================================
if ($p['force_unlock']) {
    $hadLock = file_exists(LOCK_FILE);
    if ($hadLock) @unlink(LOCK_FILE);
    echo json_encode([
        'ok'      => true,
        'message' => $hadLock ? '✅ lock پاک شد' : 'فایلی برای پاک کردن نبود',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// 🧪 تست AI
// ============================================================
if ($p['test']) {
    echo json_encode(testAI(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================================================
// 🕐 Stale lock check
// ============================================================
if (file_exists(LOCK_FILE)) {
    $age = time() - filemtime(LOCK_FILE);
    if ($age > STALE_LOCK_SEC) {
        Logger::warning(
            'lock قدیمی پاک شد',
            'Stale lock removed',
            ['age' => $age, 'threshold' => STALE_LOCK_SEC]
        );
        @unlink(LOCK_FILE);
    }
}

// ============================================================
// 🔒 Acquire lock
// ============================================================
$lock = @fopen(LOCK_FILE, 'c');
if (!$lock) {
    echo json_encode(['ok' => false, 'error' => 'cannot open lock file'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    $lockInfo = null;
    if (file_exists(LOCK_FILE)) {
        $raw = @file_get_contents(LOCK_FILE);
        $lockInfo = json_decode((string) $raw, true);
    }
    echo json_encode([
        'ok'           => false,
        'error'        => 'پردازش دیگر در حال اجراست',
        'lock_age_sec' => file_exists(LOCK_FILE) ? time() - filemtime(LOCK_FILE) : 0,
        'lock_info'    => $lockInfo,
        'hint'         => 'برای آزادسازی: type_detect.php?force_unlock=1',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

ftruncate($lock, 0);
fwrite($lock, json_encode([
    'pid'        => getmypid(),
    'started_at' => date('Y-m-d H:i:s'),
    'limit'      => $p['limit'],
]));
fflush($lock);

// ============================================================
// 🚀 پاسخ فوری (async)
// ============================================================
if (!$p['sync']) {
    echo json_encode([
        'ok'         => true,
        'message'    => 'پردازش شروع شد. برای دیباگ از ?sync=1 استفاده کن.',
        'started_at' => date('Y-m-d H:i:s'),
        'limit'      => $p['limit'],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) ob_end_flush();
        flush();
    }
}

// ============================================================
// 🎯 اجرا
// ============================================================
$result = processMessages($config, $p['limit']);

if ($p['sync']) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

flock($lock, LOCK_UN);
fclose($lock);
@unlink(LOCK_FILE);

// ============================================================
// 🧩 توابع اصلی
// ============================================================

function processMessages(array $config, int $limit): array
{
    $stats = [
        'ok'              => true,
        'fetched'         => 0,
        'classified'      => 0,
        'type_updated'    => 0,
        'type_verify_fail' => 0,
        'tasks_created'   => 0,
        'tasks_failed'    => 0,
        'no_task'         => 0,
        'errors'          => [],
        'processed'       => [],
    ];

    try {
        Database::connect($config['db']);
        $ref  = new ReflectionClass(Database::class);
        $prop = $ref->getProperty('pdo');
        $prop->setAccessible(true);
        $pdo  = $prop->getValue();
    } catch (Throwable $e) {
        $stats['ok'] = false;
        $stats['errors'][] = 'DB: ' . $e->getMessage();
        return $stats;
    }

    // ---- SELECT: پیام‌های جدید که تو viewed/irrelevant نیستن ----
    $sql = "
        SELECT fm.id, fm.text, fm.group_name, fm.sender_name, fm.sender_id,
               fm.link, fm.message_type, fm.task_status
        FROM filtered_messages fm
        WHERE (
            fm.message_type IS NULL
            OR (
                fm.message_type IN ('LEAD','SUPPORT')
                AND (fm.task_status IS NULL OR fm.task_status = 'failed')
            )
        )
        AND fm.id NOT IN (SELECT filtered_message_id FROM irrelevant_messages)
        AND fm.id NOT IN (SELECT filtered_message_id FROM viewed_messages)
        ORDER BY fm.id ASC
        LIMIT " . (int) $limit;

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $stats['fetched'] = count($rows);

    Logger::info('type_detect: fetched', 'Fetched', [
        'count' => count($rows),
        'limit' => $limit,
    ]);

    if (empty($rows)) {
        $stats['note'] = 'هیچ پیام جدیدی برای پردازش نیست';
        return $stats;
    }

    foreach ($rows as $idx => $row) {
        // فاصله بین پیام‌ها (فقط اگه limit > 1)
        if ($idx > 0 && $limit > 1) {
            sleep(SLEEP_BETWEEN);
        }

        $id   = (int) $row['id'];
        $type = $row['message_type'];
        $tsk  = $row['task_status'];

        $step = ['id' => $id, 'started_as' => $type, 'final_type' => null, 'task' => 'none'];

        // ============================================================
        // مرحله ۱: classify (اگه NULL یا failed)
        // ============================================================
        if ($type === null || $tsk === 'failed') {
            $classifiedType = classifyOne($row);

            if ($classifiedType === null) {
                $stats['errors'][] = "id=$id: classify failed";
                $step['task'] = 'classify_failed';
                $stats['processed'][] = $step;
                continue;
            }

            // ذخیره
            $saved = updateTypeSafely($pdo, $id, $classifiedType);

            if (!$saved) {
                $stats['type_verify_fail']++;
                $stats['errors'][] = "id=$id: UPDATE failed for type=$classifiedType";
                $step['task'] = 'update_failed';
                $stats['processed'][] = $step;
                continue;
            }

            $stats['classified']++;
            $stats['type_updated']++;
            $type = $classifiedType;
            $step['final_type'] = $classifiedType;
        } else {
            $step['final_type'] = $type;
        }

        // ============================================================
        // مرحله ۲: تسک (برای LEAD و SUPPORT)
        // ============================================================
        if (in_array($type, ['LEAD', 'SUPPORT'], true)) {

            // اگه قبلاً sent شده، skip (جلوگیری از تسک تکراری)
            if ($tsk === 'sent') {
                $step['task'] = 'already_sent';
                $stats['processed'][] = $step;
                continue;
            }

            // علامت pending قبل از ارسال
            try {
                $pdo->prepare('UPDATE filtered_messages SET task_status = ? WHERE id = ?')
                    ->execute(['pending', $id]);
            } catch (Throwable $e) {
                $stats['errors'][] = "id=$id: mark pending failed";
                $step['task'] = 'pending_mark_failed';
                $stats['processed'][] = $step;
                continue;
            }

            // ارسال
            $taskResult = sendTask($row, $type);

            if ($taskResult['ok']) {
                try {
                    $pdo->prepare('UPDATE filtered_messages SET task_status = ?, task_sent_at = NOW(), task_error = NULL WHERE id = ?')
                        ->execute(['sent', $id]);
                    $stats['tasks_created']++;
                    $step['task'] = 'sent';
                } catch (Throwable $e) {
                    $stats['errors'][] = "id=$id: mark sent failed";
                    $step['task'] = 'sent_but_mark_failed';
                }
            } else {
                $errMsg = substr((string) ($taskResult['error'] ?? 'unknown'), 0, 250);
                try {
                    $pdo->prepare('UPDATE filtered_messages SET task_status = ?, task_error = ? WHERE id = ?')
                        ->execute(['failed', $errMsg, $id]);
                } catch (Throwable $e) {
                    // ignore
                }
                $stats['tasks_failed']++;
                $stats['errors'][] = "id=$id: task failed: $errMsg";
                $step['task'] = 'failed';
            }
        } else {
            $stats['no_task']++;
            $step['task'] = 'none_other_type';
        }

        $stats['processed'][] = $step;
    }

    Logger::info('type_detect: complete', 'Complete', $stats);
    return $stats;
}

// ============================================================
// 🎯 classify یکی
// ============================================================
function classifyOne(array $row): ?string
{
    $text = (string) $row['text'];
    if (mb_strlen($text) > AI_TEXT_LIMIT) {
        $text = mb_substr($text, 0, AI_TEXT_LIMIT);
    }

    $userPrompt = buildClassifyPrompt() . "\n\n---\n" . $text;

    $body = json_encode([
        'model' => AI_MODEL,
        'messages' => [['role' => 'user', 'content' => $userPrompt]],
        'temperature' => 0.0,
        'top_p' => 0.9,
        'stream' => false,
        'max_tokens' => 20,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init(AI_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => AI_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno    = curl_errno($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($errno || $code !== 200) {
        Logger::warning('classify AI failed', 'Classify AI failed', [
            'id' => $row['id'],
            'errno' => $errno,
            'code' => $code,
            'error' => $err,
        ]);
        return null;
    }

    $decoded = json_decode((string) $response, true);
    $aiText  = (string) ($decoded['choices'][0]['message']['content'] ?? '');

    $type = extractType($aiText);

    Logger::info('classify result', 'Classify result', [
        'id' => $row['id'],
        'type' => $type,
        'ai_raw' => substr($aiText, 0, 100),
    ]);

    return $type;
}

function extractType(string $aiText): ?string
{
    $up = strtoupper($aiText);

    // اولویت: LEAD / SUPPORT / OTHER به هر ترتیبی که اومدن
    if (preg_match('/\bLEAD\b/', $up))     return 'LEAD';
    if (preg_match('/\bSUPPORT\b/', $up))  return 'SUPPORT';
    if (preg_match('/\bOTHER\b/', $up))    return 'OTHER';

    return null;
}

function buildClassifyPrompt(): string
{
    return <<<EOT
دسته‌بندی یک پیام فارسی درباره تاپین و لجستیک.

## SUPPORT (پشتیبانی)
درخواست پشتیبانی، رهگیری مرسوله، گزارش خطا، مشکل افزونه، نصب، تنظیمات، پیگیری خدمات فعلی.
مثال: «کد رهگیری نمیاد»، «افزونه کار نمیکنه»، «مرسوله من کجاست؟»

## LEAD (لید فروش)
پرس‌وجو درباره قیمت، تعرفه، امکانات، معرفی تاپین، یا تمایل به شروع استفاده از خدمات.
مثال: «تاپین چیه؟»، «تعرفه چقدره؟»، «میخوام پنل بخرم»، «تاپین چطور کار میکنه؟»، «میخوام راه اندازی کنم»

## OTHER (سایر)
سلام، تشکر، تبلیغات، گفت‌وگوی عمومی، فاقد شواهد.
مثال: «سلام»، «ممنون»، «کانفیگ VPN»، «تبلیغات در بله»

## قانون طلایی
- مشکل/رسیدگی → SUPPORT
- شناخت/شروع استفاده از تاپین → LEAD
- عمومی/مبهم/تبلیغ → OTHER
- صرف وجود «تاپین» برای SUPPORT کافی نیست

## خروجی
فقط یکی از این سه کلمه و هیچ چیز دیگر:
SUPPORT
یا LEAD
یا OTHER
EOT;
}

// ============================================================
// 🛡 UPDATE مطمئن برای message_type
// ============================================================
function updateTypeSafely(PDO $pdo, int $id, string $type): bool
{
    try {
        // ۱) UPDATE
        $upd = $pdo->prepare('UPDATE filtered_messages SET message_type = ? WHERE id = ?');
        $upd->execute([$type, $id]);

        // ۲) چک واقعی در DB (صرف نظر از rowCount)
        $check = $pdo->prepare('SELECT message_type FROM filtered_messages WHERE id = ?');
        $check->execute([$id]);
        $current = $check->fetchColumn();

        if ($current === $type) {
            return true;
        }

        // ۳) تلاش مجدد با transaction
        Logger::warning('type verify failed, retry with tx', 'Retry with tx', [
            'id' => $id,
            'expected' => $type,
            'current' => $current,
        ]);

        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE filtered_messages SET message_type = ? WHERE id = ?')->execute([$type, $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            try {
                $pdo->rollBack();
            } catch (Throwable $e2) {
            }
            Logger::error('retry also failed', 'Retry failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        // چک نهایی
        $check->execute([$id]);
        return $check->fetchColumn() === $type;
    } catch (Throwable $e) {
        Logger::error('updateTypeSafely error', 'Update error', [
            'id' => $id,
            'type' => $type,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}

// ============================================================
// 📤 ارسال تسک
// ============================================================
function sendTask(array $row, string $type): array
{
    $typeLabel = $type === 'LEAD' ? 'لید' : 'پشتیبانی';
    $title = "پیام جدید - {$typeLabel}";

    $phone = extractPhone((string) $row['text']);

    $lines = [
        'نام چت: ' . (trim((string) ($row['group_name'] ?? '')) !== '' ? $row['group_name'] : '-'),
        'نام فرستنده: ' . (trim((string) ($row['sender_name'] ?? '')) !== '' ? $row['sender_name'] : '-'),
        'شماره: ' . ($phone ?: 'ثبت نشده'),
        'آیدی: ' . (trim((string) ($row['sender_id'] ?? '')) !== '' ? $row['sender_id'] : '-'),
        'پیام: ' . (trim((string) ($row['text'] ?? '')) !== '' ? $row['text'] : '-'),
        'مشاهده: ' . (trim((string) ($row['link'] ?? '')) !== '' ? $row['link'] : 'لینک موجود نیست'),
    ];

    $payload = json_encode([
        'title'       => $title,
        'description' => implode("\n", $lines),
        'priority'    => 'medium',
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init(TASKAM_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . TASKAM_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => TASKAM_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $ok = ($errno === 0 && $code >= 200 && $code < 300);

    Logger::info('task send', 'Task send', [
        'id' => $row['id'],
        'type' => $type,
        'ok' => $ok,
        'code' => $code,
        'errno' => $errno,
    ]);

    return [
        'ok'    => $ok,
        'code'  => $code,
        'errno' => $errno,
        'error' => $ok ? null : "HTTP $code errno=$errno " . substr((string) $resp, 0, 150),
    ];
}

function extractPhone(string $text): ?string
{
    $map = [
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9',
        '٠' => '0',
        '١' => '1',
        '٢' => '2',
        '٣' => '3',
        '٤' => '4',
        '٥' => '5',
        '٦' => '6',
        '٧' => '7',
        '٨' => '8',
        '٩' => '9',
    ];
    $norm = strtr($text, $map);
    if (preg_match('/(?:\+98|0098|0)9[0-9]{9}/', $norm, $m)) return $m[0];
    if (preg_match('/(?:0[0-9]{2})[- ]?[0-9]{7,8}/', $norm, $m)) return $m[0];
    return null;
}

// ============================================================
// 📊 status
// ============================================================
function getStatus(array $config): array
{
    $info = [
        'lock' => [
            'exists'   => file_exists(LOCK_FILE),
            'age_sec'  => file_exists(LOCK_FILE) ? time() - filemtime(LOCK_FILE) : 0,
            'content'  => file_exists(LOCK_FILE) ? json_decode((string) @file_get_contents(LOCK_FILE), true) : null,
            'stale'    => file_exists(LOCK_FILE) && (time() - filemtime(LOCK_FILE) > STALE_LOCK_SEC),
        ],
    ];

    try {
        Database::connect($config['db']);
        $ref  = new ReflectionClass(Database::class);
        $prop = $ref->getProperty('pdo');
        $prop->setAccessible(true);
        $pdo  = $prop->getValue();

        $info['stats'] = [
            'total'            => (int) $pdo->query('SELECT COUNT(*) FROM filtered_messages')->fetchColumn(),
            'pending_classify' => (int) $pdo->query('SELECT COUNT(*) FROM filtered_messages WHERE message_type IS NULL')->fetchColumn(),
            'pending_task'     => (int) $pdo->query("SELECT COUNT(*) FROM filtered_messages WHERE message_type IN ('LEAD','SUPPORT') AND (task_status IS NULL OR task_status = 'failed')")->fetchColumn(),
            'tasks_sent'       => (int) $pdo->query("SELECT COUNT(*) FROM filtered_messages WHERE task_status = 'sent'")->fetchColumn(),
            'tasks_failed'     => (int) $pdo->query("SELECT COUNT(*) FROM filtered_messages WHERE task_status = 'failed'")->fetchColumn(),
            'by_type'          => $pdo->query("SELECT IFNULL(message_type,'NULL') as t, COUNT(*) as c FROM filtered_messages GROUP BY message_type")->fetchAll(PDO::FETCH_KEY_PAIR),
            'by_task_status'   => $pdo->query("SELECT IFNULL(task_status,'NULL') as s, COUNT(*) as c FROM filtered_messages GROUP BY task_status")->fetchAll(PDO::FETCH_KEY_PAIR),
        ];
    } catch (Throwable $e) {
        $info['db_error'] = $e->getMessage();
    }

    return $info;
}

// ============================================================
// 🧪 تست AI
// ============================================================
function testAI(): array
{
    $ch = curl_init(AI_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model' => AI_MODEL,
            'messages' => [['role' => 'user', 'content' => 'بگو: تست']],
            'max_tokens' => 10,
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);

    return [
        'ok'    => ($errno === 0 && $code === 200),
        'http'  => $code,
        'errno' => $errno,
        'error' => $err,
        'body'  => substr((string) $resp, 0, 300),
    ];
}
