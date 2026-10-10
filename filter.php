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
const AI_TIMEOUT     = 180;
const AI_BATCH_SIZE  = 200;
const AI_PARALLEL    = 15;
const AI_MAX_RUNTIME = 600;
const AI_TEXT_LIMIT  = 500;

const FUZZY_MAX_DIST = 1;     // حداکثر فاصله Levenshtein
const FUZZY_MIN_WORD = 4;     // حداقل طول کلمه برای چک فازی

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
        echo json_encode(['ok' => true, 'keywords' => 0, 'total_new' => 0], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // نسخه نرمال‌شده هر کلیدواژه
    $normalizedKeywords = [];
    foreach ($keywordList as $kw) {
        $normalizedKeywords[$kw] = normalizePersianAggressive($kw);
    }

    Logger::info('شروع فیلترگذاری', 'Filtering started', ['keywords' => $keywordList]);

    // ============================================================
    // ۲) پیام‌ها
    // ============================================================
    $stmt = $pdo->query(
        'SELECT `id`, `telegram_id`, `chat_id`, `sender_id`, `text`, `link`,
                `date`, `date_human`, `created_at`
         FROM `messages`
         WHERE `text` IS NOT NULL AND `text` != ""
         ORDER BY `id` ASC'
    );
    $allMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalMessages = count($allMessages);

    if ($totalMessages === 0) {
        Logger::info('پیامی وجود ندارد', 'No messages');
        echo json_encode(['ok' => true, 'keywords' => count($keywordList), 'total_new' => 0], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // ۳) statement درج
    // ============================================================
    $insertStmt = $pdo->prepare(
        'INSERT IGNORE INTO `filtered_messages`
        (`message_id`, `telegram_id`, `chat_id`, `sender_id`, `text`, `link`,
         `date`, `date_human`, `matched_keyword`, `original_created_at`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $messagesById = [];
    foreach ($allMessages as $m) {
        $messagesById[(int) $m['id']] = $m;
    }

    // ============================================================
    // ۴) لایه ۱+۲: نرمال‌سازی + فازی روی همه پیام‌ها
    // ============================================================
    $totalNew    = 0;
    $preFilterHit = [];
    $remaining    = [];

    foreach ($allMessages as $m) {
        $text   = (string) $m['text'];
        $norm   = normalizePersianAggressive($text);
        $words  = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $matchedKw = null;

        foreach ($normalizedKeywords as $origKw => $normKw) {
            if ($normKw === '') continue;

            // ---- چک ۱: زیررشته در متن نرمال‌شده ----
            if (mb_strpos($norm, $normKw) !== false) {
                $matchedKw = $origKw;
                break;
            }

            // ---- چک ۲: فازی روی کلمه‌به‌کلمه ----
            if (hasFuzzyMatch($words, $normKw, $origKw)) {
                $matchedKw = $origKw;
                break;
            }
        }

        if ($matchedKw !== null) {
            $preFilterHit[(int) $m['id']] = $matchedKw;
        } else {
            $remaining[] = $m;
        }
    }

    // درج نتایج پیش‌فیلتر
    $preFilterCount = 0;
    foreach ($preFilterHit as $msgId => $kw) {
        $msg = $messagesById[$msgId] ?? null;
        if (!$msg) continue;

        $insertStmt->execute([
            $msg['id'], $msg['telegram_id'], $msg['chat_id'], $msg['sender_id'],
            $msg['text'], $msg['link'], $msg['date'], $msg['date_human'],
            $kw, $msg['created_at'],
        ]);
        if ($insertStmt->rowCount() > 0) $preFilterCount++;
    }
    $totalNew += $preFilterCount;

    Logger::info('پیش‌فیلتر انجام شد', 'Pre-filter done', [
        'matched'   => $preFilterCount,
        'remaining' => count($remaining),
    ]);

    // ============================================================
    // ۵) لایه ۳: AI روی بقیه
    // ============================================================
    $aiMatched = 0;

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
            'batches'  => $totalBatches,
            'parallel' => AI_PARALLEL,
        ]);

        $batchResults = sendBatchesInParallel($batches, $keywordList, $startTime);

        foreach ($batchResults as $aiResult) {
            if (empty($aiResult['matches'])) continue;

            foreach ($aiResult['matches'] as $row) {
                $msgId = (int) ($row['i'] ?? 0);
                if ($msgId <= 0 || !isset($messagesById[$msgId])) continue;

                $aiMatched++;
                $msg = $messagesById[$msgId];
                $matchedWord = (string) ($row['w'] ?? 'AI-match');

                $insertStmt->execute([
                    $msg['id'], $msg['telegram_id'], $msg['chat_id'], $msg['sender_id'],
                    $msg['text'], $msg['link'], $msg['date'], $msg['date_human'],
                    mb_substr($matchedWord, 0, 190), $msg['created_at'],
                ]);
                if ($insertStmt->rowCount() > 0) $totalNew++;
            }
        }
    }

    // ============================================================
    // ۶) آمار
    // ============================================================
    $totalInTable = (int) $pdo->query('SELECT COUNT(*) FROM `filtered_messages`')->fetchColumn();
    $duration     = time() - $startTime;

    Logger::info('فیلترگذاری کامل شد', 'Filtering completed', [
        'pre_filter'     => $preFilterCount,
        'ai_matched'     => $aiMatched,
        'total_new'      => $totalNew,
        'total_in_table' => $totalInTable,
        'duration'       => $duration,
    ]);

    echo json_encode([
        'ok'             => true,
        'keywords'       => count($keywordList),
        'messages'       => $totalMessages,
        'pre_filter'     => $preFilterCount,
        'ai_matched'     => $aiMatched,
        'total_new'      => $totalNew,
        'total_in_table' => $totalInTable,
        'duration_sec'   => $duration,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    Logger::critical('خطای کلی', 'Fatal error', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'خطای داخلی سرور'], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// 🧹 نرمال‌سازی خشن فارسی
// - حذف ZWNJ، نیم‌فاصله، BOM
// - حذف اعراب
// - یکسان‌سازی ی/ک/ه عربی
// - حذف فاصله‌ها و نویسه‌های کنترلی
// - فروپاشی حروف تکراری (سس → س، رر → ر ...)
// - lowercase
// ============================================================
function normalizePersianAggressive(string $text): string
{
    // 1. ZWNJ و BOM و نویسه‌های نامرئی
    $text = str_replace(
        ["\xE2\x80\x8C", "\xE2\x80\x8B", "\xEF\xBB\xBF", "\xE2\x80\x8D", "\xE2\x80\x8E", "\xE2\x80\x8F"],
        '',
        $text
    );

    // 2. اعراب
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text);

    // 3. یکسان‌سازی حروف عربی/فارسی
    $map = [
        'ي' => 'ی', 'ى' => 'ی', 'ﻯ' => 'ی', 'ﻰ' => 'ی', 'ﻱ' => 'ی',
        'ك' => 'ک', 'ﻙ' => 'ک', 'ﻚ' => 'ک',
        'ة' => 'ه', 'ۀ' => 'ه', 'ﻩ' => 'ه', 'ﻪ' => 'ه',
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ٲ' => 'ا', 'ٳ' => 'ا',
        'ؤ' => 'و', 'ئ' => 'ی',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];
    $text = strtr($text, $map);

    // 4. lowercase
    $text = mb_strtolower($text, 'UTF-8');

    // 5. حذف همه فاصله‌ها و کاراکترهای کنترلی
    $text = preg_replace('/[\s\x{2000}-\x{200F}\x{2028}-\x{202F}\x{205F}\x{3000}]+/u', '', $text);

    // 6. فروپاشی حروف تکراری (سس → س، ررر → ر)
    //    این کار باعث می‌شه «بسسته» = «بسته» و «اررسال» = «ارسال»
    if ($text !== '') {
        $chars  = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        $prev   = null;
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
// 🔍 تطابق فازی روی کلمات متن
// - پنجره لغزان روی کلمات همسایه (برای عبارت‌های چندکلمه‌ای)
// - مقایسه با Levenshtein یونیکد
// ============================================================
function hasFuzzyMatch(array $words, string $normKeyword, string $originalKeyword): bool
{
    $kwLen = mb_strlen($normKeyword);
    if ($kwLen < FUZZY_MIN_WORD) {
        return false;
    }

    // تعداد کلمه‌هایی که کلیدواژه به آن‌ها تقسیم می‌شود
    $kwWordCount = max(1, count(preg_split('/\s+/u', trim($originalKeyword), -1, PREG_SPLIT_NO_EMPTY)));
    $wordCount   = count($words);

    // پنجره لغزان با اندازه‌های مختلف
    for ($windowSize = max(1, $kwWordCount - 1); $windowSize <= $kwWordCount + 1; $windowSize++) {
        for ($i = 0; $i + $windowSize <= $wordCount; $i++) {
            $chunk = array_slice($words, $i, $windowSize);
            $chunkJoined = normalizePersianAggressive(implode('', $chunk));
            if ($chunkJoined === '') continue;

            $chunkLen = mb_strlen($chunkJoined);

            // اختلاف طول بیش از حد → رد کن
            if (abs($chunkLen - $kwLen) > max(FUZZY_MAX_DIST, 2)) {
                continue;
            }

            if (utf8Levenshtein($normKeyword, $chunkJoined) <= FUZZY_MAX_DIST) {
                return true;
            }
        }
    }

    return false;
}

// ============================================================
// 🔤 فاصله Levenshtein یونیکد (فارسی)
// ============================================================
function utf8Levenshtein(string $a, string $b): int
{
    if ($a === $b) return 0;
    if ($a === '') return mb_strlen($b);
    if ($b === '') return mb_strlen($a);

    $aArr = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY);
    $bArr = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY);
    $la   = count($aArr);
    $lb   = count($bArr);

    $prev = range(0, $lb);
    for ($i = 1; $i <= $la; $i++) {
        $curr = [$i];
        for ($j = 1; $j <= $lb; $j++) {
            $cost = ($aArr[$i - 1] === $bArr[$j - 1]) ? 0 : 1;
            $curr[$j] = min(
                $prev[$j] + 1,
                $curr[$j - 1] + 1,
                $prev[$j - 1] + $cost
            );
        }
        $prev = $curr;
    }
    return $prev[$lb];
}

// ============================================================
// 🚀 ارسال موازی
// ============================================================
function sendBatchesInParallel(array $batches, array $keywords, int $startTime): array
{
    $results   = [];
    $total     = count($batches);
    $nextIndex = 0;
    $active    = [];
    $indexMap  = [];

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
            'max_tokens'  => 1500,
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
                'processed' => $nextIndex, 'total' => $total,
            ]);
            break;
        }

        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 1.0);

        while ($info = curl_multi_info_read($mh)) {
            $ch       = $info['handle'];
            $chId     = (int) $ch;
            $batchIdx = $indexMap[$chId] ?? -1;

            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno    = curl_errno($ch);

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($active[$chId]);

            if ($errno || $httpCode !== 200) {
                Logger::warning('خطا در batch', 'AI batch failed', [
                    'batch' => $batchIdx + 1, 'errno' => $errno, 'code' => $httpCode,
                ]);
                $results[$batchIdx] = ['matches' => []];
            } else {
                $decoded = json_decode((string) $response, true);
                $aiText  = $decoded['choices'][0]['message']['content'] ?? '';
                $parsed  = extractJsonFromAi($aiText);

                if ($parsed === null) {
                    Logger::warning('JSON استخراج نشد', 'JSON extraction failed', [
                        'batch' => $batchIdx + 1,
                        'raw'   => substr($aiText, 0, 200),
                    ]);
                    $results[$batchIdx] = ['matches' => []];
                } else {
                    $results[$batchIdx] = ['matches' => $parsed['matches'] ?? []];
                    Logger::info('batch انجام شد', 'AI batch done', [
                        'batch'   => ($batchIdx + 1) . '/' . $total,
                        'matched' => count($parsed['matches'] ?? []),
                    ]);
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
// 📝 پرامپت — با تأکید شدید روی تایپی + معنایی
// ============================================================
function buildSystemPrompt(string $keywords): string
{
    return <<<EOT
فیلتر هوشمند متن فارسی. کلمات فیلتر: {$keywords}

## دو وظیفه اصلی به ترتیب اهمیت:

### 🥇 وظیفه ۱ (مهم‌ترین): تطابق معنایی
اگر پیام از نظر **معنا** به کلمه فیلتر مربوط است، MATCH کن — حتی اگر هیچ‌کدام از کلمات فیلتر در متن نیست.
- مثال: فیلتر="بسته" → "ارسال بسته"، "پست بسته"، "مرسوله"، "بار"، "کولیکردن" همه MATCH
- مثال: فیلتر="مشکل" → "ایراد"، "عیب"، "خرابی"، "نقص"، "خراب"، "معیوب" همه MATCH
- مثال: فیلتر="تاپین" → "آپدیت کردن"، "بروزرسانی" MATCH

### 🥈 وظیفه ۲ (خیلی مهم): تحمل خطای تایپی
کاربران فارسی معمولاً کلمات را اشتباه می‌نویسند. **همه این حالت‌ها MATCH هستند**:
1. **تکرار حرف**: بسسته=بسته | اررسال=ارسال | مششکل=مشکل | تاپپین=تاپین
2. **حذف حرف**: بست=بسته | مشل=مشکل | ایرد=ایراد
3. **اضافه حرف**: بستته=بسته | ماشکل=مشکل
4. **جابجایی حرف**: بتسه=بسته | مشلک=مشکل
5. **اشتباه یک حرف مشابه**: تابین=تاپین | بسطة=بسته | مشگل=مشکل
6. **فاصله اشتباه وسط کلمه**: تا پین=تاپین | بس ته=بسته | مس ئله=مسئله
7. **حذف فاصله بین دو کلمه**: ارسالبسته=ارسال بسته | بستههمین=بسته همین
8. **حروف عربی/فارسی**: مشكل=مشکل | تاپين=تاپین

### 📌 قانون طلایی
اگر **شک داری** که پیام به کلمه فیلتر مربوط است یا نه، **MATCH کن**. ضرر false positive کمتر از false negative است.

## ورودی
[{"i":id,"t":"متن پیام"}, ...]

## خروجی
فقط JSON معتبر، بدون هیچ توضیح:
{"matches":[{"i":id,"w":"کلمه فیلتری که match شد"}]}

اگر هیچ پیامی MATCH نشد:
{"matches":[]}
EOT;
}

// ============================================================
// 🔍 استخراج JSON
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