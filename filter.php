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
const AI_TIMEOUT     = 120;
const AI_BATCH_SIZE  = 300;
const AI_PARALLEL    = 25;
const AI_MAX_RUNTIME = 900;
const AI_TEXT_LIMIT  = 500;   // افزایش یافت برای درک بهتر context

const FUZZY_MAX_DIST = 1;
const FUZZY_MIN_WORD = 4;

const STATE_FILE = __DIR__ . '/storage/filter_state.json';

// ============================================================
// 🚀 پاسخ فوری
// ============================================================
$reset = isset($_GET['reset']) && $_GET['reset'] === '1';

echo json_encode([
    'ok'         => true,
    'message'    => 'پردازش در پس‌زمینه شروع شد.',
    'started_at' => date('Y-m-d H:i:s'),
    'reset'      => $reset,
], JSON_UNESCAPED_UNICODE);

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    while (ob_get_level() > 0) ob_end_flush();
    flush();
}

try {
    Database::connect($config['db']);

    $ref  = new ReflectionClass(Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $pdo  = $prop->getValue();

    $startTime = time();

    // ============================================================
    // ۱) کلیدواژه‌ها
    // ============================================================
    $rawKeywords = $pdo->query('SELECT `keyword` FROM `filters`')->fetchAll(PDO::FETCH_COLUMN);
    $keywordList = array_values(array_filter(array_map('trim', $rawKeywords), fn($k) => $k !== ''));

    if (empty($keywordList)) {
        Logger::info('جدول فیلتر خالی است', 'Filters table is empty');
        exit;
    }

    $keywordHash = md5(implode('|', $keywordList));

    // ============================================================
    // ۲) State
    // ============================================================
    $state = loadState();
    $lastProcessedId = 0;
    $resumeMode = false;

    if ($reset) {
        Logger::info('شروع از صفر (reset=1)', 'Starting from scratch (reset=1)');
    } elseif ($state['keyword_hash'] === $keywordHash && $state['last_processed_message_id'] > 0) {
        $lastProcessedId = (int) $state['last_processed_message_id'];
        $resumeMode = true;
        Logger::info('ادامه از آخرین نقطه', 'Resuming from last point', ['last_id' => $lastProcessedId]);
    } else {
        Logger::info('شروع از صفر', 'Starting from scratch');
    }

    $maxMessageId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM `messages`')->fetchColumn();
    if ($lastProcessedId > $maxMessageId) {
        Logger::warning('state بزرگ‌تر از max id', 'State > max id, resetting', [
            'state_id' => $lastProcessedId,
            'max_db_id' => $maxMessageId
        ]);
        $lastProcessedId = 0;
        $resumeMode = false;
    }

    // ============================================================
    // ۳) نرمال‌سازی کلیدواژه‌ها
    // ============================================================
    $normalizedKeywords = [];
    foreach ($keywordList as $kw) {
        $normalizedKeywords[$kw] = normalizePersianAggressive($kw);
    }

    // ============================================================
    // ۴) کش ترکیب‌های قبلاً فیلترشده
    // ============================================================
    $alreadyFiltered = [];
    $chkStmt = $pdo->query('SELECT `telegram_id`, `matched_keyword` FROM `filtered_messages`');
    while ($row = $chkStmt->fetch(PDO::FETCH_ASSOC)) {
        $key = ((int) $row['telegram_id']) . '|' . $row['matched_keyword'];
        $alreadyFiltered[$key] = true;
    }

    Logger::info('کش فیلترهای قبلی', 'Already-filtered cache', ['count' => count($alreadyFiltered)]);

    // ============================================================
    // ۵) پیام‌ها
    // ============================================================
    $stmt = $pdo->prepare(
        'SELECT `id`, `telegram_id`, `chat_id`, `group_name`, `sender_id`, `sender_name`,
                `text`, `link`, `date`, `date_human`, `created_at`
         FROM `messages`
         WHERE `text` IS NOT NULL AND `text` != "" AND `id` > ?
         ORDER BY `id` ASC'
    );
    $stmt->execute([$lastProcessedId]);
    $allMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalMessages = count($allMessages);

    if ($totalMessages === 0) {
        Logger::info('پیام جدیدی برای فیلتر نیست', 'No new messages', ['last_id' => $lastProcessedId]);
        exit;
    }

    Logger::info('پیام‌ها آماده پردازش', 'Messages ready', [
        'total' => $totalMessages,
        'from_id' => $lastProcessedId,
        'resume' => $resumeMode ? 'yes' : 'no',
    ]);

    // ============================================================
    // ۶) statement درج
    // ============================================================
    $insertStmt = $pdo->prepare(
        'INSERT INTO `filtered_messages`
        (`message_id`, `telegram_id`, `chat_id`, `group_name`, `sender_id`, `sender_name`,
         `text`, `link`, `date`, `date_human`, `matched_keyword`, `original_created_at`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $messagesById = [];
    foreach ($allMessages as $m) {
        $messagesById[(int) $m['id']] = $m;
    }

    // ============================================================
    // ۷) پیش‌فیلتر (نرمال‌سازی + فازی)
    // ⚠️ پیش‌فیلتر فقط سیگنال اولیه است — نه تصمیم نهایی
    // ============================================================
    $totalNew        = 0;
    $preFilterHit    = [];
    $remaining       = [];
    $lastPreFilterId = $lastProcessedId;

    foreach ($allMessages as $m) {
        $id = (int) $m['id'];
        $lastPreFilterId = $id;

        $text  = (string) $m['text'];
        $norm  = normalizePersianAggressive($text);
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $matchedKw = null;

        foreach ($normalizedKeywords as $origKw => $normKw) {
            if ($normKw === '') continue;

            if (mb_strpos($norm, $normKw) !== false) {
                $matchedKw = $origKw;
                break;
            }

            if (hasFuzzyMatch($words, $normKw, $origKw)) {
                $matchedKw = $origKw;
                break;
            }
        }

        if ($matchedKw !== null) {
            $preFilterHit[$id] = $matchedKw;
        } else {
            $remaining[] = $m;
        }
    }

    // درج نتایج پیش‌فیلتر — اینا قطعاً شامل خودِ کلمه هستن
    $preFilterCount = 0;
    foreach ($preFilterHit as $msgId => $kw) {
        $msg = $messagesById[$msgId] ?? null;
        if (!$msg) continue;

        $tid = (int) ($msg['telegram_id'] ?? 0);
        $key = $tid . '|' . $kw;

        if (isset($alreadyFiltered[$key])) continue;

        $insertStmt->execute([
            $msg['id'],
            $msg['telegram_id'],
            $msg['chat_id'],
            $msg['group_name'],
            $msg['sender_id'],
            $msg['sender_name'],
            $msg['text'],
            $msg['link'],
            $msg['date'],
            $msg['date_human'],
            $kw,
            $msg['created_at'],
        ]);

        if ($insertStmt->rowCount() > 0) {
            $preFilterCount++;
            $alreadyFiltered[$key] = true;
        }
    }
    $totalNew += $preFilterCount;

    Logger::info('پیش‌فیلتر انجام شد', 'Pre-filter done', [
        'matched'   => $preFilterCount,
        'remaining' => count($remaining),
    ]);

    if (empty($remaining)) {
        saveState($lastPreFilterId, $keywordHash);
        Logger::info('فقط pre-filter بود، state ذخیره شد', 'Only pre-filter, state saved');
    }

    // ============================================================
    // ۸) AI موازی
    // ============================================================
    $aiMatched = 0;
    $lastSavedId = $lastProcessedId;

    if (!empty($remaining)) {
        $payloads = [];
        foreach ($remaining as $m) {
            $text = (string) $m['text'];
            if (mb_strlen($text) > AI_TEXT_LIMIT) {
                $text = mb_substr($text, 0, AI_TEXT_LIMIT);
            }
            $payloads[] = ['i' => (int) $m['id'], 't' => $text];
        }

        $batches      = array_chunk($payloads, AI_BATCH_SIZE);
        $totalBatches = count($batches);

        Logger::info('شروع AI موازی', 'Parallel AI started', [
            'batches'    => $totalBatches,
            'parallel'   => AI_PARALLEL,
            'batch_size' => AI_BATCH_SIZE,
            'remaining'  => count($remaining),
        ]);

        $onBatchDone = function (int $batchIdx) use ($batches, $keywordHash, &$lastSavedId) {
            $lastMsg = end($batches[$batchIdx]);
            $lastId  = (int) ($lastMsg['i'] ?? 0);
            if ($lastId > $lastSavedId) {
                $lastSavedId = $lastId;
                saveState($lastSavedId, $keywordHash);
            }
        };

        $batchResults = sendBatchesInParallel($batches, $keywordList, $startTime, $onBatchDone);

        foreach ($batchResults as $aiResult) {
            if (empty($aiResult['matches'])) continue;

            foreach ($aiResult['matches'] as $row) {
                $msgId = (int) ($row['i'] ?? 0);
                if ($msgId <= 0 || !isset($messagesById[$msgId])) continue;

                $msg = $messagesById[$msgId];
                $matchedWord = (string) ($row['w'] ?? 'AI-match');

                $tid = (int) ($msg['telegram_id'] ?? 0);
                $key = $tid . '|' . $matchedWord;

                if (isset($alreadyFiltered[$key])) continue;

                $aiMatched++;

                $insertStmt->execute([
                    $msg['id'],
                    $msg['telegram_id'],
                    $msg['chat_id'],
                    $msg['group_name'],
                    $msg['sender_id'],
                    $msg['sender_name'],
                    $msg['text'],
                    $msg['link'],
                    $msg['date'],
                    $msg['date_human'],
                    mb_substr($matchedWord, 0, 190),
                    $msg['created_at'],
                ]);

                if ($insertStmt->rowCount() > 0) {
                    $totalNew++;
                    $alreadyFiltered[$key] = true;
                }
            }
        }

        if (!empty($payloads)) {
            $finalMaxId = (int) end($payloads)['i'];
            if ($finalMaxId > $lastSavedId) {
                $lastSavedId = $finalMaxId;
                saveState($lastSavedId, $keywordHash);
                Logger::info('state نهایی AI ذخیره شد', 'Final AI state saved', ['id' => $finalMaxId]);
            }
        }
    }

    // ============================================================
    // ۹) آمار
    // ============================================================
    $totalInTable = (int) $pdo->query('SELECT COUNT(*) FROM `filtered_messages`')->fetchColumn();
    $duration     = time() - $startTime;

    Logger::info('فیلترگذاری کامل شد', 'Filtering completed', [
        'messages'       => $totalMessages,
        'pre_filter'     => $preFilterCount,
        'ai_matched'     => $aiMatched,
        'total_new'      => $totalNew,
        'total_in_table' => $totalInTable,
        'last_saved_id'  => $lastSavedId,
        'duration'       => $duration,
    ]);
} catch (Throwable $e) {
    Logger::critical('خطای کلی', 'Fatal error', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
}

// ============================================================
// 🗂 State
// ============================================================
function loadState(): array
{
    $default = ['last_processed_message_id' => 0, 'keyword_hash' => ''];
    if (!file_exists(STATE_FILE)) return $default;

    $json = @file_get_contents(STATE_FILE);
    if ($json === false || $json === '') return $default;

    $data = json_decode($json, true);
    if (!is_array($data)) return $default;

    return [
        'last_processed_message_id' => (int) ($data['last_processed_message_id'] ?? 0),
        'keyword_hash'              => (string) ($data['keyword_hash'] ?? ''),
    ];
}

function saveState(int $lastId, string $keywordHash): void
{
    $data = [
        'last_processed_message_id' => $lastId,
        'keyword_hash'              => $keywordHash,
        'updated_at'                => date('Y-m-d H:i:s'),
    ];

    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    $tmp  = STATE_FILE . '.tmp';

    if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
        @rename($tmp, STATE_FILE);
    }
}

// ============================================================
// 🧹 نرمال‌سازی
// ============================================================
function normalizePersianAggressive(string $text): string
{
    $text = str_replace(
        ["\xE2\x80\x8C", "\xE2\x80\x8B", "\xEF\xBB\xBF", "\xE2\x80\x8D", "\xE2\x80\x8E", "\xE2\x80\x8F"],
        '',
        $text
    );

    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text);

    $map = [
        'ي' => 'ی',
        'ى' => 'ی',
        'ﻯ' => 'ی',
        'ﻰ' => 'ی',
        'ﻱ' => 'ی',
        'ك' => 'ک',
        'ﻙ' => 'ک',
        'ﻚ' => 'ک',
        'ة' => 'ه',
        'ۀ' => 'ه',
        'ﻩ' => 'ه',
        'ﻪ' => 'ه',
        'أ' => 'ا',
        'إ' => 'ا',
        'آ' => 'ا',
        'ٱ' => 'ا',
        'ٲ' => 'ا',
        'ٳ' => 'ا',
        'ؤ' => 'و',
        'ئ' => 'ی',
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
    ];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[\s\x{2000}-\x{200F}\x{2028}-\x{202F}\x{205F}\x{3000}]+/u', '', $text);

    if ($text !== '') {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        $prev = null;
        foreach ($chars as $ch) {
            if ($ch !== $prev) {
                $result[] = $ch;
                $prev = $ch;
            }
        }
        $text = implode('', $result);
    }
    return $text;
}

// ============================================================
// 🔍 فازی
// ============================================================
function hasFuzzyMatch(array $words, string $normKeyword, string $originalKeyword): bool
{
    $kwLen = mb_strlen($normKeyword);
    if ($kwLen < FUZZY_MIN_WORD) return false;

    $kwWordCount = max(1, count(preg_split('/\s+/u', trim($originalKeyword), -1, PREG_SPLIT_NO_EMPTY)));
    $wordCount = count($words);

    for ($windowSize = max(1, $kwWordCount - 1); $windowSize <= $kwWordCount + 1; $windowSize++) {
        for ($i = 0; $i + $windowSize <= $wordCount; $i++) {
            $chunk = array_slice($words, $i, $windowSize);
            $chunkJoined = normalizePersianAggressive(implode('', $chunk));
            if ($chunkJoined === '') continue;
            $chunkLen = mb_strlen($chunkJoined);
            if (abs($chunkLen - $kwLen) > max(FUZZY_MAX_DIST, 2)) continue;
            if (utf8Levenshtein($normKeyword, $chunkJoined) <= FUZZY_MAX_DIST) return true;
        }
    }
    return false;
}

// ============================================================
// 🔤 Levenshtein
// ============================================================
function utf8Levenshtein(string $a, string $b): int
{
    if ($a === $b) return 0;
    if ($a === '') return mb_strlen($b);
    if ($b === '') return mb_strlen($a);

    $aArr = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY);
    $bArr = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY);
    $la = count($aArr);
    $lb = count($bArr);

    $prev = range(0, $lb);
    for ($i = 1; $i <= $la; $i++) {
        $curr = [$i];
        for ($j = 1; $j <= $lb; $j++) {
            $cost = ($aArr[$i - 1] === $bArr[$j - 1]) ? 0 : 1;
            $curr[$j] = min($prev[$j] + 1, $curr[$j - 1] + 1, $prev[$j - 1] + $cost);
        }
        $prev = $curr;
    }
    return $prev[$lb];
}

// ============================================================
// 🚀 AI موازی
// ============================================================
function sendBatchesInParallel(array $batches, array $keywords, int $startTime, ?callable $onBatchDone = null): array
{
    $results = [];
    $total = count($batches);
    $nextIndex = 0;
    $active = [];
    $indexMap = [];

    $systemPrompt = buildSystemPrompt(implode('، ', $keywords));

    $makeHandle = function (array $batch) use ($systemPrompt) {
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
            CURLOPT_TCP_KEEPALIVE  => 1,
            CURLOPT_TCP_KEEPIDLE   => 30,
            CURLOPT_TCP_KEEPINTVL  => 10,
            CURLOPT_FORBID_REUSE   => false,
            CURLOPT_FRESH_CONNECT  => false,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        return $ch;
    };

    $mh = curl_multi_init();
    if (defined('CURLMOPT_MAX_TOTAL_CONNECTIONS')) {
        curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, AI_PARALLEL + 2);
    }
    if (defined('CURLMOPT_MAX_HOST_CONNECTIONS')) {
        curl_multi_setopt($mh, CURLMOPT_MAX_HOST_CONNECTIONS, AI_PARALLEL + 2);
    }

    while (count($active) < AI_PARALLEL && $nextIndex < $total) {
        $ch = $makeHandle($batches[$nextIndex]);
        curl_multi_add_handle($mh, $ch);
        $active[(int) $ch]   = $ch;
        $indexMap[(int) $ch] = $nextIndex;
        $nextIndex++;
    }

    do {
        if (time() - $startTime >= AI_MAX_RUNTIME) {
            Logger::warning('زمان AI تمام شد', 'AI runtime limit reached', [
                'processed' => $nextIndex,
                'total' => $total,
            ]);
            break;
        }

        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 1.0);

        while ($info = curl_multi_info_read($mh)) {
            $ch = $info['handle'];
            $chId = (int) $ch;
            $batchIdx = $indexMap[$chId] ?? -1;

            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno    = curl_errno($ch);

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($active[$chId]);

            $success = false;

            if ($errno || $httpCode !== 200) {
                Logger::warning('خطا در batch', 'AI batch failed', [
                    'batch' => $batchIdx + 1,
                    'errno' => $errno,
                    'code' => $httpCode,
                ]);
                $results[$batchIdx] = ['matches' => []];
            } else {
                $decoded = json_decode((string) $response, true);
                $aiText  = $decoded['choices'][0]['message']['content'] ?? '';
                $parsed  = extractJsonFromAi($aiText);

                if ($parsed === null) {
                    Logger::warning('JSON استخراج نشد', 'JSON extraction failed', [
                        'batch' => $batchIdx + 1,
                        'raw' => substr($aiText, 0, 200),
                    ]);
                    $results[$batchIdx] = ['matches' => []];
                } else {
                    $results[$batchIdx] = ['matches' => $parsed['matches'] ?? []];
                    $success = true;
                    Logger::info('batch انجام شد', 'AI batch done', [
                        'batch'   => ($batchIdx + 1) . '/' . $total,
                        'matched' => count($parsed['matches'] ?? []),
                    ]);
                }
            }

            if ($success && $onBatchDone !== null && $batchIdx >= 0) {
                try {
                    $onBatchDone($batchIdx);
                } catch (Throwable $e) {
                    Logger::warning('خطا در ذخیره state', 'State save failed', ['error' => $e->getMessage()]);
                }
            }

            if ($nextIndex < $total) {
                $newCh = $makeHandle($batches[$nextIndex]);
                curl_multi_add_handle($mh, $newCh);
                $active[(int) $newCh]   = $newCh;
                $indexMap[(int) $newCh] = $nextIndex;
                $nextIndex++;
            }
        }

        if (empty($active) && $nextIndex >= $total) break;
    } while ($running > 0 || !empty($active));

    foreach ($active as $ch) {
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    return $results;
}

// ============================================================
// 📝 پرامپت تقویت‌شده — با قوانین دقیق برای حذف false positive
// ============================================================
function buildSystemPrompt(string $keywords): string
{
    return <<<EOT
فیلتر هوشمند پیام‌های فارسی. کلمات کلیدی: {$keywords}

## 🎯 هدف اصلی
پیام‌هایی را پیدا کن که **موضوع اصلی‌شان** ارسال بسته/مرسوله و خدمات پستی است.
پیام باید **درباره‌ی** ارسال کالا و لجستیک باشد — نه فقط کلمه کلیدی را در خود داشته باشد.

## ❗ قانون طلایی (مهم‌ترین)
**تطابق کلمه = کافی نیست.** باید مفهوم پیام با لجستیک/ارسال مرتبط باشد.
اگر با جایگزین کردن کلمه کلیدی با یک کلمه هم‌معنی دیگر، پیام **همچنان** درباره ارسال بسته باشد → MATCH
اگر با این جایگزینی، پیام کاملاً بی‌ربط شود → NO MATCH

## 🚫 استثنائات مهم (باید NO MATCH شوند):

### «پست» — تفکیک حیاتی!
❌ **NO MATCH** اگر پست به این معانی باشد:
- پست اینستاگرام/تلگرام/شبکه اجتماعی → «پست و استوری»، «پست بذار»، «پست بگذار»، «کپشن پست»، «پست جدیدم»، «زیر پست»، «پست گذاشتیم»
- پست خبری → «زیر پست خبر»، «پست اخبار»، «کانال پست»
- پست تبلیغاتی/محتوایی → «پست تبلیغاتی»، «طراحی پست»، «تولید پست»
- پست = مقام/جایگاه → «پست مدیریت»، «پست ریاست»
- پُست (فعل انگلیسی) → «پست کردن محتوا»

✅ **MATCH** فقط اگر پست به این معانی باشد:
- ارسال از طریق پست: «با پست بفرست»، «پست کردم»، «پست مرسوله»، «از اداره پست»، «هزینه پست»، «تعرفه پست»، «پست پیشتاز»، «پست سفارشی»
- سامانه پستی: «سامانه پست»، «پست تاپین»، «پست و تاپین»

### «بسته بندی» — تفکیک حیاتی!
❌ **NO MATCH** اگر بسته بندی به این معانی باشد:
- طراحی گرافیکی → «طراحی بسته بندی»، «بسته بندی و تصویرسازی»، «بسته بندی خلاقانه» (در آگهی گرافیک)
- پکیج نرم‌افزاری → «بسته بندی محصول»، «پکیج آموزش»
- آگهی خدمات گرافیک/تولید محتوا که فقط کلمه رو داره

✅ **MATCH** فقط اگر:
- بسته بندی فیزیکی برای ارسال: «هزینه بسته بندی»، «بسته بندی برای پست»، «بسته بندی مرسوله»، «بسته بندی حبابدار»، «نایلون بسته بندی»، «کارتن بسته بندی»
- شکایت یا سوال درباره بسته بندی محصول ارسالی

### استثنائات عمومی:
❌ **NO MATCH** این دسته‌ها حتی اگر کلمه کلیدی داشته باشند:
1. **آگهی استخدام** — «استخدام»، «نیازمند نیرو»، «حقوق ماهانه»، «رزومه بفرستید»
2. **آگهی خدمات گرافیک** — «طراحی گرافیک»، «طراحی لوگو»، «طراحی پست و استوری»
3. **آگهی تولید محتوا** — «تولید محتوا»، «محصول گذاری»، «سئو»
4. **اخبار عمومی/سیاسی** — «بازداشت»، «وزیر»، «انتخابات»، «جنگ»
5. **فروش VPN/فیلترشکن** — «کانفیگ»، «v2ray»، «vpن»، «فیلترشکن»
6. **تبلیغات ارز دیجیتال** — «تتر»، «بیت کوین»، «سیگنال»، «صرافی»
7. **رپورتاژ آگهی** — «رپورتاژ»، «تبلیغات در سایت»
8. **درخواست خدمات وب/طراحی** — «طراحی سایت»، «هاست»، «دامین»
9. **چت عمومی/سلام و احوالپرسی** — «سلام»، «درود»، «خوبی»
10. **دعوا و فحش** — محتوای توهین‌آمیز

## ✅ چه زمانی MATCH کنیم؟

پیام باید **درباره یکی از این موضوعات** باشد:
- استفاده از **تاپین** برای ثبت/ارسال مرسوله
- استفاده از **پست** (شرکت پست) برای ارسال کالا
- استفاده از **تیپاکس** برای ارسال
- استفاده از **قطاربار** برای ارسال
- شکایت/سوال درباره **ارسال بسته**، **مرسوله**، **رهگیری**
- هزینه ارسال/بسته بندی **در بستر ارسال کالا**
- مشکل با **کد رهگیری**، **بارکد پستی**، **پستچی**
- پرسش از **نحوه کار تاپین/پست/تیپاکس**
- تجربه استفاده از **خدمات پستی**

## 🔍 غلط تایپی و نویسه‌ای
کاربران فارسی معمولاً اشتباه می‌نویسند — اگر منظور **واقعاً** همان کلمه بود و **پیام درباره ارسال** بود:
- تکرار حرف: تاپپین=تاپین | بسسته=بسته
- حذف/اضافه حرف: تابین=تاپین | بستته=بسته
- جابجایی: بتسه=بسته | مشلک=مشکل
- فاصله اشتباه: تا پین=تاپین | بس ته=بسته
- حذف فاصله: ارسالبسته=ارسال بسته
- حروف عربی: مشكل=مشکل | تاپين=تاپین

**نکته:** غلط تایپی فقط وقتی MATCH می‌شود که **بستر پیام** درباره ارسال باشد.

## 📋 نمونه‌های MATCH:
✅ «ارسال تاپین از انبار فروشنده»
✅ «میخوام با پست بسته بفرستم»
✅ «تا پین ثبت شد» (تاپین با فاصله)
✅ «هزینه بسته بندی چقدره برای پست؟»
✅ «مرسوله تاپین در انباره»
✅ «کد رهگیری پست نمیاد»
✅ «با تیپاکس بفرستم ارزونتره؟»
✅ «سامانه پست تاپین چطور کار میکنه؟»

## 🚫 نمونه‌های NO MATCH:
❌ «پست جدیدم رو لایک کن» (پست اینستاگرام)
❌ «زیر پست خبر بازداشتش نوشتن...» (پست خبری)
❌ «طراحی پست و استوری اینستاگرام» (آگهی گرافیک)
❌ «بسته بندی، تصویرسازی چهره و کاراکتر» (آگهی طراحی گرافیک)
❌ «هزینه بسته بندی: ثابت یا پلکانی» (پنل قیمت گذاری)
❌ «استخدام کارشناس محصول‌گذاری در ترب» (آگهی استخدام)
❌ «کانفیگ گیگی ۵ هزار تومان» (فروش VPN)
❌ «سیگنال XAUUSD بخرید» (تبلیغ ارز)
❌ «رپورتاژ آگهی در رسانه‌های...» (تبلیغات)
❌ «سلام دوستان، خوبید؟» (چت عمومی)

## 📌 الگوریتم تصمیم
برای هر پیام:
1. **موضوع اصلی چیست؟** — ارسال کالا؟ یا چیز دیگر؟
2. اگر موضوع اصلی **ارسال کالا/لجستیک** بود → بررسی کلمات کلیدی و تایپی
3. اگر موضوع اصلی **چیز دیگر** بود → NO MATCH (حتی اگر کلمه کلیدی باشد)
4. اگر مطمئن نیستی → **MATCH نکن**

## ورودی
[{"i":id,"t":"متن پیام"}, ...]

## خروجی
فقط JSON معتبر، بدون توضیح:
{"matches":[{"i":id,"w":"کلمه فیلتری که match شد"}]}

اگر هیچ MATCH نشد:
{"matches":[]}
EOT;
}

// ============================================================
// 🔍 استخراج JSON از پاسخ AI
// ============================================================
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
    $d = json_decode(trim($text), true);
    return is_array($d) ? $d : null;
}
