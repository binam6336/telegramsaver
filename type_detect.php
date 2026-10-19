<?php

declare(strict_types=1);

set_time_limit(0);
ignore_user_abort(true);

ob_start();
require __DIR__ . '/bootstrap.php';
if (ob_get_length() > 0) ob_get_clean();

header('Content-Type: application/json; charset=utf-8');
/** @var array $config */

// ============================================================
// ⚙️ تنظیمات
// ============================================================
const AI_URL         = 'https://hermes.ai.unturf.com/v1/chat/completions';
const AI_MODEL       = 'turboderp/Qwen3.8-27B-exl3';
const AI_TIMEOUT     = 90;
const CLASSIFY_BATCH_SIZE  = 25;
const CLASSIFY_PARALLEL    = 5;
const CLASSIFY_TEXT_LIMIT  = 400;
const CLASSIFY_MAX_RUNTIME = 240;
const DEFAULT_LIMIT        = 500;

const TASKAM_URL      = 'https://mahyarsalehee.ir/taskam/api/v1/tasks/create/';
const TASKAM_TOKEN    = 'tk_ebafa53eda5d1d8c4613ef4f3720ba70bd6c8fe5740b95cd';
const TASKAM_PARALLEL = 5;
const TASKAM_TIMEOUT  = 30;

const LOCK_FILE = __DIR__ . '/storage/type_detect.lock';

// ============================================================
// 🚀 پارامترها
// ============================================================
$processAll = isset($_GET['all']) && $_GET['all'] === '1';
$syncMode   = isset($_GET['sync']) && $_GET['sync'] === '1';
$testMode   = isset($_GET['test']) && $_GET['test'] === '1';
$limit      = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : DEFAULT_LIMIT;

// ============================================================
// 🧪 حالت تست AI
// ============================================================
if ($testMode) {
    $body = json_encode([
        'model' => AI_MODEL,
        'messages' => [['role' => 'user', 'content' => 'بگو: تست']],
        'max_tokens' => 10,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init(AI_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);

    echo json_encode([
        'ok'       => ($errno === 0 && $code === 200),
        'http'     => $code,
        'errno'    => $errno,
        'error'    => $err,
        'preview'  => substr((string) $resp, 0, 500),
        'ai_url'   => AI_URL,
        'ai_model' => AI_MODEL,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================================================
// 🔒 قفل
// ============================================================
$lock = @fopen(LOCK_FILE, 'c');
if ($lock === false) {
    echo json_encode(['ok' => false, 'error' => 'lock file failed'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo json_encode([
        'ok'    => false,
        'error' => 'یک پردازش type_detect دیگر در حال اجراست',
    ], JSON_UNESCAPED_UNICODE);
    fclose($lock);
    exit;
}

// ============================================================
// 🚀 پاسخ فوری (در حالت async)
// ============================================================
if (!$syncMode) {
    echo json_encode([
        'ok'          => true,
        'message'     => 'پردازش شروع شد. برای دیباگ از ?sync=1 استفاده کن.',
        'started_at'  => date('Y-m-d H:i:s'),
        'process_all' => $processAll,
        'limit'       => $limit,
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
$result = [
    'ok'         => false,
    'fetched'    => 0,
    'classified' => 0,
    'updated'    => 0,
    'types'      => ['SUPPORT' => 0, 'LEAD' => 0, 'OTHER' => 0],
    'tasks_ok'   => 0,
    'tasks_fail' => 0,
    'errors'     => [],
];

try {
    Database::connect($config['db']);

    $ref  = new ReflectionClass(Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $pdo  = $prop->getValue();

    $startTime = time();

    // ---- ۱) گرفتن پیام‌های منتظر ----
    $sql = 'SELECT `id`, `telegram_id`, `chat_id`, `group_name`, `sender_id`, `sender_name`,
                   `text`, `link`, `date_human`
            FROM `filtered_messages`';
    if (!$processAll) {
        $sql .= ' WHERE `message_type` IS NULL';
    }
    $sql .= ' ORDER BY `id` ASC LIMIT ' . (int) $limit;

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $total = count($rows);

    $result['fetched'] = $total;

    Logger::info('type_detect: پیام‌ها گرفته شد', 'type_detect: fetched', [
        'total'       => $total,
        'process_all' => $processAll,
        'sync_mode'   => $syncMode,
        'limit'       => $limit,
    ]);

    if ($total === 0) {
        Logger::warning('type_detect: هیچ پیامی برای پردازش نیست', 'type_detect: nothing pending', [
            'process_all' => $processAll,
        ]);
        $result['ok'] = true;
        $result['note'] = 'هیچ پیامی با message_type IS NULL وجود ندارد';
    } else {
        // ---- ۲) payload ----
        $payloads = [];
        $byId = [];
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $text = (string) $r['text'];
            if (mb_strlen($text) > CLASSIFY_TEXT_LIMIT) {
                $text = mb_substr($text, 0, CLASSIFY_TEXT_LIMIT);
            }
            $payloads[] = ['i' => $id, 't' => $text];
            $byId[$id] = $r;
        }

        // ---- ۳) AI classify ----
        $batches = array_chunk($payloads, CLASSIFY_BATCH_SIZE);
        $totalBatches = count($batches);

        Logger::info('type_detect: شروع classify', 'type_detect: classify starting', [
            'batches'    => $totalBatches,
            'parallel'   => CLASSIFY_PARALLEL,
            'batch_size' => CLASSIFY_BATCH_SIZE,
            'messages'   => $total,
        ]);

        // 🎯 پردازش batch به batch با ذخیره‌ی فوری
        $updateStmt = $pdo->prepare(
            'UPDATE `filtered_messages` SET `message_type` = ? WHERE `id` = ?'
        );

        $leadTasks = [];
        $counts    = ['SUPPORT' => 0, 'LEAD' => 0, 'OTHER' => 0];
        $classified = 0;

        foreach ($batches as $batchIdx => $batch) {
            if (time() - $startTime >= CLASSIFY_MAX_RUNTIME) {
                Logger::warning('type_detect: timeout', 'type_detect: timeout', [
                    'processed_batches' => $batchIdx,
                    'total_batches'     => $totalBatches,
                ]);
                break;
            }

            $batchResults = sendSingleClassifyBatch($batch);

            Logger::info("type_detect: batch " . ($batchIdx + 1) . "/{$totalBatches}", 'batch done', [
                'batch_idx' => $batchIdx + 1,
                'results'   => count($batchResults),
                'batch_size' => count($batch),
            ]);

            // ---- ذخیره‌ی فوری هر batch ----
            foreach ($batchResults as $id => $type) {
                if (!isset($byId[$id])) continue;
                if (!in_array($type, ['SUPPORT', 'LEAD', 'OTHER'], true)) continue;

                try {
                    $updateStmt->execute([$type, $id]);
                    $counts[$type]++;
                    $classified++;

                    if ($type === 'LEAD') {
                        $leadTasks[] = buildLeadTask($byId[$id]);
                    }
                } catch (Throwable $e) {
                    $result['errors'][] = "UPDATE id=$id: " . $e->getMessage();
                    Logger::error('type_detect: UPDATE failed', 'Update failed', [
                        'id' => $id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $result['classified'] = $classified;
        $result['updated']    = $classified;
        $result['types']      = $counts;

        Logger::info('type_detect: classify کامل شد', 'type_detect: classify done', [
            'classified' => $classified,
            'types'      => $counts,
            'lead_tasks' => count($leadTasks),
        ]);

        // ---- ۴) ارسال تسک‌های LEAD ----
        if (!empty($leadTasks)) {
            Logger::info('type_detect: ارسال تسک‌های LEAD', 'Sending LEAD tasks', [
                'count' => count($leadTasks),
            ]);

            $taskResults = sendTaskamRequests($leadTasks);

            foreach ($taskResults as $idx => $r) {
                if (!empty($r['ok'])) {
                    $result['tasks_ok']++;
                } else {
                    $result['tasks_fail']++;
                    $result['errors'][] = "Task #$idx: code={$r['code']} errno={$r['errno']}";
                    Logger::warning('type_detect: تسک fail', 'Task failed', [
                        'idx' => $idx,
                        'code' => $r['code'] ?? 0,
                        'errno' => $r['errno'] ?? 0,
                    ]);
                }
            }
        }

        $result['ok'] = ($classified > 0);
        $result['duration'] = time() - $startTime;

        Logger::info('type_detect: کامل شد', 'type_detect: completed', $result);
    }
} catch (Throwable $e) {
    $result['ok'] = false;
    $result['errors'][] = $e->getMessage();
    Logger::critical('type_detect: خطای کلی', 'type_detect: fatal error', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
}

// ============================================================
// 📤 خروجی (فقط sync mode)
// ============================================================
if ($syncMode) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

flock($lock, LOCK_UN);
fclose($lock);

// ============================================================
// 📝 توابع
// ============================================================

function buildLeadTask(array $row): array
{
    $phone = extractPhone((string) ($row['text'] ?? ''));

    $lines = [
        'نام چت: ' . ((string) ($row['group_name'] ?? '') !== '' ? $row['group_name'] : '-'),
        'نام فرستنده: ' . ((string) ($row['sender_name'] ?? '') !== '' ? $row['sender_name'] : '-'),
        'شماره: ' . ($phone ?: 'ثبت نشده'),
        'آیدی: ' . ((string) ($row['sender_id'] ?? '') !== '' ? $row['sender_id'] : '-'),
        'پیام: ' . ((string) ($row['text'] ?? '') !== '' ? $row['text'] : '-'),
        'مشاهده: ' . ((string) ($row['link'] ?? '') !== '' ? $row['link'] : 'لینک موجود نیست'),
    ];

    return [
        'title'       => 'پیام جدید - لید',
        'description' => implode("\n", $lines),
        'priority'    => 'medium',
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

function sendTaskamRequests(array $tasks): array
{
    $results = [];
    $total = count($tasks);
    if ($total === 0) return $results;

    $mh = curl_multi_init();
    $active  = [];
    $nextIdx = 0;

    $addHandle = function (array $task) {
        $body = json_encode([
            'title'       => $task['title'],
            'description' => $task['description'],
            'priority'    => $task['priority'] ?? 'medium',
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(TASKAM_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . TASKAM_TOKEN,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => TASKAM_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        return $ch;
    };

    while (count($active) < TASKAM_PARALLEL && $nextIdx < $total) {
        $ch = $addHandle($tasks[$nextIdx]);
        curl_multi_add_handle($mh, $ch);
        $active[(int) $ch] = ['ch' => $ch, 'idx' => $nextIdx];
        $nextIdx++;
    }

    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 1.0);

        while ($info = curl_multi_info_read($mh)) {
            $ch  = $info['handle'];
            $idx = $active[(int) $ch]['idx'] ?? -1;

            $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno    = curl_errno($ch);
            $response = curl_multi_getcontent($ch);

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($active[(int) $ch]);

            $results[$idx] = [
                'ok'       => ($errno === 0 && $code >= 200 && $code < 300),
                'code'     => $code,
                'errno'    => $errno,
                'response' => substr((string) $response, 0, 200),
            ];

            if ($nextIdx < $total) {
                $newCh = $addHandle($tasks[$nextIdx]);
                curl_multi_add_handle($mh, $newCh);
                $active[(int) $newCh] = ['ch' => $newCh, 'idx' => $nextIdx];
                $nextIdx++;
            }
        }

        if (empty($active) && $nextIdx >= $total) break;
    } while ($running > 0 || !empty($active));

    foreach ($active as $item) {
        curl_multi_remove_handle($mh, $item['ch']);
        curl_close($item['ch']);
        $results[$item['idx']] = ['ok' => false, 'code' => 0, 'errno' => -1];
    }
    curl_multi_close($mh);

    return $results;
}

/**
 * ارسال یک batch به AI + parse پاسخ
 * خروجی: [filtered_messages.id => 'SUPPORT'|'LEAD'|'OTHER']
 */
function sendSingleClassifyBatch(array $batch): array
{
    $flat = [];
    if (empty($batch)) return $flat;

    $systemPrompt = buildClassifyPrompt();

    $body = json_encode([
        'model'    => AI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => json_encode($batch, JSON_UNESCAPED_UNICODE)],
        ],
        'temperature' => 0.0,
        'top_p'       => 0.9,
        'stream'      => false,
        'max_tokens'  => 2000,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init(AI_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Connection: keep-alive',
            'Accept-Encoding: gzip, deflate',
        ],
        CURLOPT_ENCODING       => '',
        CURLOPT_TIMEOUT        => AI_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno    = curl_errno($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($errno || $code !== 200) {
        Logger::warning('type_detect: batch خطا', 'Batch failed', [
            'errno' => $errno,
            'code'  => $code,
            'error' => $err,
            'raw'   => substr((string) $response, 0, 200),
        ]);
        return $flat;
    }

    $decoded = json_decode((string) $response, true);
    $aiText  = $decoded['choices'][0]['message']['content'] ?? '';
    $parsed  = extractJsonFromAi($aiText);

    if ($parsed === null) {
        Logger::warning('type_detect: JSON استخراج نشد', 'JSON extraction failed', [
            'raw' => substr($aiText, 0, 300),
        ]);
        return $flat;
    }

    // ---- حالت ۱: {"results": [...]}
    if (isset($parsed['results']) && is_array($parsed['results'])) {
        foreach ($parsed['results'] as $r) {
            $rid  = (int) ($r['i'] ?? 0);
            $type = strtoupper((string) ($r['c'] ?? ''));
            if ($rid > 0 && in_array($type, ['SUPPORT', 'LEAD', 'OTHER'], true)) {
                $flat[$rid] = $type;
            }
        }
        return $flat;
    }

    // ---- حالت ۲ (fallback): {"matches": [...]}
    if (isset($parsed['matches']) && is_array($parsed['matches'])) {
        foreach ($parsed['matches'] as $r) {
            $rid  = (int) ($r['i'] ?? 0);
            $type = strtoupper((string) ($r['c'] ?? $r['type'] ?? ''));
            if ($rid > 0 && in_array($type, ['SUPPORT', 'LEAD', 'OTHER'], true)) {
                $flat[$rid] = $type;
            }
        }
        return $flat;
    }

    // ---- حالت ۳ (fallback): آرایه‌ی مستقیم ----
    if (isset($parsed[0]) && is_array($parsed[0])) {
        foreach ($parsed as $r) {
            $rid  = (int) ($r['i'] ?? 0);
            $type = strtoupper((string) ($r['c'] ?? $r['type'] ?? ''));
            if ($rid > 0 && in_array($type, ['SUPPORT', 'LEAD', 'OTHER'], true)) {
                $flat[$rid] = $type;
            }
        }
        return $flat;
    }

    Logger::warning('type_detect: فرمت JSON نامشناخته', 'Unknown JSON format', [
        'keys' => array_keys($parsed),
        'raw'  => substr($aiText, 0, 300),
    ]);

    return $flat;
}

function buildClassifyPrompt(): string
{
    return <<<EOT
دسته‌بندی پیام‌های فارسی مرتبط با تاپین و لجستیک.

## دسته‌ها:

### SUPPORT
درخواست پشتیبانی، رهگیری مرسوله، گزارش خطا، مشکل افزونه، نصب، تنظیمات، پیگیری خدمات فعلی تاپین.
مثال: «کد رهگیری نمیاد»، «افزونه کار نمیکنه»، «مرسوله من کجاست؟»

### LEAD
پرس‌وجو درباره قیمت، تعرفه، امکانات، معرفی تاپین، یا تمایل به شروع استفاده از خدمات — بدون درخواست پشتیبانی مشخص.
مثال: «تاپین چیه؟»، «تعرفه چقدره؟»، «میخوام پنل بخرم»، «تاپین چطور کار میکنه؟»، «میخوام راه اندازی کنم»

### OTHER
سلام، تشکر، تبلیغات، گفت‌وگوی عمومی، یا فاقد شواهد کافی.
مثال: «سلام»، «ممنون»، «کانفیگ VPN»، «تبلیغات در بله»

## قوانین:
- مشکل یا درخواست رسیدگی → SUPPORT
- هدف شناخت/بررسی استفاده از تاپین → LEAD
- عمومی یا مبهم → OTHER
- صرف وجود کلمه «تاپین» برای SUPPORT کافی نیست
- پیام‌های تبلیغاتی → OTHER

## ورودی
[{"i":id,"t":"متن"}, ...]

## خروجی
فقط JSON معتبر:
{"results":[{"i":id,"c":"SUPPORT|LEAD|OTHER"}]}

اگر هیچ: {"results":[]}
EOT;
}

function extractJsonFromAi(string $text): ?array
{
    if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $m)) {
        $d = json_decode($m[1], true);
        if (is_array($d)) return $d;
    }
    if (preg_match('/\{.*\}/s', $text, $m)) {
        $d = json_decode($m[0], true);
        if (is_array($d)) return $d;
    }
    if (preg_match('/\[.*\]/s', $text, $m)) {
        $d = json_decode($m[0], true);
        if (is_array($d)) return $d;
    }
    $d = json_decode(trim($text), true);
    return is_array($d) ? $d : null;
}
