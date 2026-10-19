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
$processAll   = isset($_GET['all']) && $_GET['all'] === '1';
$syncMode     = isset($_GET['sync']) && $_GET['sync'] === '1';
$testMode     = isset($_GET['test']) && $_GET['test'] === '1';
$debugMode    = isset($_GET['debug']) && $_GET['debug'] === '1';
$resetTasks   = isset($_GET['reset_tasks']) && $_GET['reset_tasks'] === '1';
$limit        = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : DEFAULT_LIMIT;

// ============================================================
// 🧪 test AI
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
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);

    echo json_encode([
        'ok'    => ($errno === 0 && $code === 200),
        'http'  => $code,
        'errno' => $errno,
        'body'  => substr((string) $resp, 0, 500),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================================================
// 🔒 Lock
// ============================================================
$lock = @fopen(LOCK_FILE, 'c');
if ($lock === false) {
    echo json_encode(['ok' => false, 'error' => 'lock failed'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo json_encode(['ok' => false, 'error' => 'پردازش دیگر در حال اجراست'], JSON_UNESCAPED_UNICODE);
    fclose($lock);
    exit;
}

// ============================================================
// 🔌 اتصال DB
// ============================================================
try {
    Database::connect($config['db']);

    $ref  = new ReflectionClass(Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $pdo  = $prop->getValue();

    // ---- reset tasks ----
    if ($resetTasks) {
        $pdo->exec("UPDATE `filtered_messages` SET `task_status` = NULL, `task_sent_at` = NULL, `task_error` = NULL WHERE `task_status` IN ('failed', 'sent')");
        Logger::info('task_status reset شد', 'Task status reset');
    }

    // ---- بررسی ستون‌ها ----
    $cols = [];
    foreach ($pdo->query('SHOW COLUMNS FROM `filtered_messages`')->fetchAll(PDO::FETCH_ASSOC) as $c) {
        $cols[$c['Field']] = $c['Type'];
    }

    if (!isset($cols['message_type'])) {
        throw new RuntimeException('ستون message_type در دیتابیس نیست');
    }
    if (!isset($cols['task_status'])) {
        throw new RuntimeException('ستون task_status در دیتابیس نیست — SQL رو اجرا کن');
    }
} catch (Throwable $e) {
    if (!$syncMode && !$debugMode) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    flock($lock, LOCK_UN);
    fclose($lock);
    exit;
}

// ============================================================
// 🚀 async: پاسخ فوری
// ============================================================
if (!$syncMode && !$debugMode) {
    echo json_encode([
        'ok'      => true,
        'message' => 'پردازش شروع شد. برای دیباگ از ?sync=1 استفاده کن.',
        'limit'   => $limit,
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
    'update_fail' => 0,
    'types'      => ['SUPPORT' => 0, 'LEAD' => 0, 'OTHER' => 0],
    'tasks_ok'   => 0,
    'tasks_fail' => 0,
    'tasks_skip' => 0,
    'errors'     => [],
    'steps'      => [],
];

$startTime = time();

try {
    // ---- ۱) SELECT پیام‌های منتظر ----
    $sql = 'SELECT `id`, `text`, `group_name`, `sender_name`, `sender_id`, `link`,
                   `message_type`, `task_status`
            FROM `filtered_messages`';

    if (!$processAll) {
        // فقط ردیف‌های classify نشده
        $sql .= ' WHERE `message_type` IS NULL';
    }

    $sql .= ' ORDER BY `id` ASC LIMIT ' . (int) $limit;

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $total = count($rows);
    $result['fetched'] = $total;

    $result['steps'][] = "Fetched: $total rows";

    Logger::info('type_detect: fetched', 'type_detect: fetched', [
        'total'       => $total,
        'process_all' => $processAll,
        'limit'       => $limit,
    ]);

    if ($total === 0) {
        $result['ok'] = true;
        $result['note'] = 'هیچ پیامی برای classify نیست';
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

        $result['steps'][] = "Batches: $totalBatches";

        $counts        = ['SUPPORT' => 0, 'LEAD' => 0, 'OTHER' => 0];
        $classified    = 0;
        $updatedCount  = 0;
        $updateFails   = 0;
        $leadTasks     = [];   // ['id' => fm.id, 'task' => [...]]

        foreach ($batches as $batchIdx => $batch) {
            if (time() - $startTime >= CLASSIFY_MAX_RUNTIME) {
                $result['steps'][] = "Timeout at batch " . ($batchIdx + 1);
                break;
            }

            $batchResults = sendSingleClassifyBatch($batch);

            $result['steps'][] = "Batch " . ($batchIdx + 1) . "/$totalBatches: " . count($batchResults) . " classified";

            Logger::info("type_detect: batch " . ($batchIdx + 1) . "/$totalBatches", 'batch done', [
                'results' => count($batchResults),
            ]);

            // ---- ذخیره‌ی فوری هر batch ----
            foreach ($batchResults as $id => $type) {
                if (!isset($byId[$id])) continue;
                if (!in_array($type, ['SUPPORT', 'LEAD', 'OTHER'], true)) continue;

                $classified++;
                $counts[$type]++;

                // 🔒 اتصال زنده؟ چک کن
                if (!isPdoAlive($pdo)) {
                    reconnectDb($config['db'], $pdo);
                }

                // ---- UPDATE با rowCount ----
                try {
                    $updStmt = $pdo->prepare('UPDATE `filtered_messages` SET `message_type` = ? WHERE `id` = ? AND `message_type` IS NULL');
                    $updStmt->execute([$type, $id]);
                    $affected = $updStmt->rowCount();

                    if ($affected === 0) {
                        // یا قبلاً ست شده، یا id غلط
                        $check = $pdo->prepare('SELECT `message_type` FROM `filtered_messages` WHERE `id` = ?');
                        $check->execute([$id]);
                        $current = $check->fetchColumn();

                        if ($current === $type) {
                            // قبلاً توسط process دیگه‌ای ست شده — OK
                            $updatedCount++;
                        } else {
                            $updateFails++;
                            $result['errors'][] = "UPDATE id=$id type=$type: rowCount=0, current=" . var_export($current, true);
                            Logger::warning('type_detect: UPDATE rowCount=0', 'Update 0', [
                                'id' => $id,
                                'type' => $type,
                                'current' => $current,
                            ]);
                            continue;
                        }
                    } else {
                        $updatedCount++;
                    }
                } catch (Throwable $e) {
                    $updateFails++;
                    $result['errors'][] = "UPDATE id=$id: " . $e->getMessage();
                    Logger::error('type_detect: UPDATE failed', 'Update failed', [
                        'id' => $id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }

                // ---- ساخت تسک برای LEAD (فقط اگه task_status NULL یا failed) ----
                if ($type === 'LEAD') {
                    $currentTaskStatus = $byId[$id]['task_status'] ?? null;

                    if ($currentTaskStatus === null || $currentTaskStatus === 'failed' || $currentTaskStatus === 'pending') {
                        $leadTasks[] = [
                            'id'   => $id,
                            'task' => buildLeadTask($byId[$id]),
                        ];
                    } else {
                        $result['tasks_skip']++;
                    }
                }
            }
        }

        $result['classified']  = $classified;
        $result['updated']     = $updatedCount;
        $result['update_fail'] = $updateFails;
        $result['types']       = $counts;

        $result['steps'][] = "Classified: $classified, Updated: $updatedCount, Failed: $updateFails";

        // ---- ۴) ارسال تسک‌ها ----
        if (!empty($leadTasks)) {
            // پشتیبانی از task_status = 'pending' برای جلوگیری از تسک تکراری
            $markStmt = $pdo->prepare('UPDATE `filtered_messages` SET `task_status` = ? WHERE `id` = ?');

            // علامت pending قبل از ارسال
            foreach ($leadTasks as $item) {
                try {
                    $markStmt->execute(['pending', $item['id']]);
                } catch (Throwable $e) {
                    Logger::warning('mark pending failed', 'Mark failed', ['id' => $item['id']]);
                }
            }

            // ارسال به Taskam
            $taskPayloads = array_map(fn($item) => $item['task'], $leadTasks);
            $taskResults = sendTaskamRequests($taskPayloads);

            // ذخیره‌ی نتیجه
            foreach ($taskResults as $idx => $r) {
                $fmId = $leadTasks[$idx]['id'] ?? 0;
                if ($fmId <= 0) continue;

                if (!empty($r['ok'])) {
                    $result['tasks_ok']++;
                    try {
                        $markStmt->execute(['sent', $fmId]);
                        $pdo->prepare('UPDATE `filtered_messages` SET `task_sent_at` = NOW() WHERE `id` = ?')
                            ->execute([$fmId]);
                    } catch (Throwable $e) {
                        Logger::warning('mark sent failed', 'Mark failed', ['id' => $fmId]);
                    }
                } else {
                    $result['tasks_fail']++;
                    $errMsg = "code={$r['code']} errno={$r['errno']}";
                    $result['errors'][] = "Task fm_id=$fmId: $errMsg";
                    try {
                        $markStmt->execute(['failed', $fmId]);
                        $pdo->prepare('UPDATE `filtered_messages` SET `task_error` = ? WHERE `id` = ?')
                            ->execute([substr($errMsg, 0, 255), $fmId]);
                    } catch (Throwable $e) {
                        Logger::warning('mark failed failed', 'Mark failed', ['id' => $fmId]);
                    }
                }
            }
        }

        $result['ok'] = ($classified > 0 && $updatedCount > 0);
        $result['duration'] = time() - $startTime;

        Logger::info('type_detect: کامل شد', 'type_detect: done', $result);
    }
} catch (Throwable $e) {
    $result['ok'] = false;
    $result['errors'][] = $e->getMessage();
    Logger::critical('type_detect: خطای کلی', 'Fatal error', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
}

// ============================================================
// 📤 خروجی
// ============================================================
if ($syncMode || $debugMode) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

flock($lock, LOCK_UN);
fclose($lock);

// ============================================================
// 🔧 توابع کمکی
// ============================================================

function isPdoAlive(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1')->fetchColumn();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function reconnectDb(array $cfg, PDO &$pdo): void
{
    Logger::warning('type_detect: DB reconnect', 'DB reconnect');
    $ref  = new ReflectionClass(Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
    Database::connect($cfg);
    $pdo = $prop->getValue();
}

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
                'ok'    => ($errno === 0 && $code >= 200 && $code < 300),
                'code'  => $code,
                'errno' => $errno,
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

function sendSingleClassifyBatch(array $batch): array
{
    $flat = [];
    if (empty($batch)) return $flat;

    $body = json_encode([
        'model' => AI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => buildClassifyPrompt()],
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
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept-Encoding: gzip, deflate'],
        CURLOPT_ENCODING       => '',
        CURLOPT_TIMEOUT        => AI_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno    = curl_errno($ch);
    curl_close($ch);

    if ($errno || $code !== 200) {
        Logger::warning('classify batch failed', 'Classify failed', [
            'errno' => $errno,
            'code' => $code,
            'raw'   => substr((string) $response, 0, 200),
        ]);
        return $flat;
    }

    $decoded = json_decode((string) $response, true);
    $aiText  = $decoded['choices'][0]['message']['content'] ?? '';
    $parsed  = extractJsonFromAi($aiText);

    if ($parsed === null) {
        Logger::warning('classify JSON failed', 'JSON failed', ['raw' => substr($aiText, 0, 300)]);
        return $flat;
    }

    $results = $parsed['results'] ?? $parsed['matches'] ?? $parsed ?? [];
    if (!is_array($results)) return $flat;

    foreach ($results as $r) {
        if (!is_array($r)) continue;
        $rid  = (int) ($r['i'] ?? 0);
        $type = strtoupper((string) ($r['c'] ?? $r['type'] ?? ''));
        if ($rid > 0 && in_array($type, ['SUPPORT', 'LEAD', 'OTHER'], true)) {
            $flat[$rid] = $type;
        }
    }

    return $flat;
}

function buildClassifyPrompt(): string
{
    return <<<EOT
دسته‌بندی پیام‌های فارسی مرتبط با تاپین و لجستیک.

## SUPPORT
درخواست پشتیبانی، رهگیری مرسوله، گزارش خطا، مشکل افزونه، نصب، تنظیمات، پیگیری خدمات فعلی تاپین.
مثال: «کد رهگیری نمیاد»، «افزونه کار نمیکنه»، «مرسوله من کجاست؟»

## LEAD
پرس‌وجو درباره قیمت، تعرفه، امکانات، معرفی تاپین، یا تمایل به شروع استفاده از خدمات.
مثال: «تاپین چیه؟»، «تعرفه چقدره؟»، «میخوام پنل بخرم»، «تاپین چطور کار میکنه؟»

## OTHER
سلام، تشکر، تبلیغات، گفت‌وگوی عمومی، فاقد شواهد.
مثال: «سلام»، «ممنون»، «کانفیگ VPN»

## قوانین
- مشکل/درخواست رسیدگی → SUPPORT
- شناخت/شروع استفاده از تاپین → LEAD
- عمومی/مبهم → OTHER
- صرف وجود «تاپین» برای SUPPORT کافی نیست
- تبلیغات → OTHER

## ورودی
[{"i":id,"t":"متن"}, ...]

## خروجی
{"results":[{"i":id,"c":"SUPPORT|LEAD|OTHER"}]}
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
