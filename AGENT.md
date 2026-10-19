# 📄 `AGENT.md`

```markdown
# مستندات پروژه Telegram Message Filter

سامانه‌ای برای دریافت، ذخیره، فیلتر و پردازش پیام‌های تلگرام با استفاده از هوش مصنوعی.

---

## 📋 فهرست

- [۱. این پروژه چیکار می‌کنه؟](#۱-این-پروژه-چیکار-میکنه)
- [۲. معماری کلی](#۲-معماری-کلی)
- [۳. ساختار فایل‌ها](#۳-ساختار-فایلها)
- [۴. تکنولوژی‌ها](#۴-تکنولوژیها)
- [۵. جریان داده (Data Flow)](#۵-جریان-داده-data-flow)
- [۶. اجزای اصلی](#۶-اجزای-اصلی)
- [۷. دیتابیس](#۷-دیتابیس)
- [۸. لاگر](#۸-لاگر)
- [۹. هوش مصنوعی](#۹-هوش-مصنوعی)
- [۱۰. کرون جاب‌ها](#۱۰-کرون-جابها)
- [۱۱. اگه بخوای جایی رو تغییر بدی](#۱۱-اگه-بخوای-جایی-رو-تغییر-بدی)
- [۱۲. عیب‌یابی](#۱۲-عیبیابی)
- [۱۳. امنیت](#۱۳-امنیت)
- [۱۴. محدودیت‌ها و TODO](#۱۴-محدودیتها-و-todo)

---

## ۱. این پروژه چیکار می‌کنه؟

سامانه‌ای که پیام‌های تلگرام رو از طریق یک API می‌گیره، توی صف ذخیره می‌کنه، ورکر اون‌ها رو توی دیتابیس درج می‌کنه، و بعد یه سیستم فیلتر هوشمند بر اساس کلمات کلیدی (با تحمل خطای تایپی و تطابق معنایی) پیام‌های مرتبط رو جدا می‌کنه.

### قابلیت‌های اصلی

- ✅ دریافت پیام‌ها از طریق **REST API** (POST JSON array)
- ✅ ذخیره در **صف فایل‌محور** برای تحمل حجم بالا
- ✅ درج انبوه در دیتابیس با **INSERT IGNORE** (ضد تکرار)
- ✅ فیلتر بر اساس **کلمات کلیدی** با:
  - تطابق دقیق (نرمال‌سازی فارسی)
  - **تحمل خطای تایپی** (تکرار حرف، جابجایی، حذف/اضافه)
  - **تطابق معنایی** (مترادف‌ها و هم‌معنی‌ها)
- ✅ فیلتر با **هوش مصنوعی** (موازی، سریع)
- ✅ لاگر حرفه‌ای با پیام‌های فارسی و انگلیسی
- ✅ پاک‌سازی خودکار و دستی

---

## ۲. معماری کلی

```
┌────────────────────┐
│  Telegram Bot /    │
│  External Source   │
└─────────┬──────────┘
          │ POST JSON
          ▼
┌────────────────────┐
│     api.php        │ ◄─── نقطه ورودی
└─────────┬──────────┘
          │ ذخیره اتمیک
          ▼
┌────────────────────┐
│  storage/queue/    │ ◄─── صف فایل‌محور
└─────────┬──────────┘
          │ کرون هر دقیقه
          ▼
┌────────────────────┐
│    worker.php      │ ◄─── درج در دیتابیس
└─────────┬──────────┘
          │ INSERT IGNORE (batch)
          ▼
┌────────────────────┐
│   MySQL: messages  │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│    filter.php      │ ◄─── فیلتر هوشمند
└─────────┬──────────┘
          │
          ├─► لایه ۱: نرمال‌سازی
          ├─► لایه ۲: فازی Levenshtein
          └─► لایه ۳: هوش مصنوعی
          │
          ▼
┌────────────────────┐
│ MySQL: filtered_   │
│ messages           │
└────────────────────┘

┌────────────────────┐
│    purge.php       │ ◄─── فیلتر + پاک‌سازی
└────────────────────┘
```

### اصول طراحی

1. **جدایی مسئولیت‌ها (Single Responsibility)** — هر فایل یه کار
2. **صف فایل‌محور** — API کند نمی‌شه، دیتابیس تحت فشار نیست
3. **اتمیک بودن عملیات** — نوشتن با `tmp + rename`
4. **قفل‌گذاری (Locking)** — جلوگیری از اجرای همزمان ورکرها
5. **Idempotency** — اجرای مکرر، داده تکراری ایجاد نمی‌کنه
6. **Graceful Degradation** — اگه AI کار نکرد، پیش‌فیلتر محلی کار خودش رو می‌کنه

---

## ۳. ساختار فایل‌ها

```
project/
│
├── 📄 api.php                    ← نقطه ورودی POST
├── 📄 worker.php                 ← ورکر کرون (صف → دیتابیس)
├── 📄 filter.php                 ← فیلتر هوشمند (AI + نرمال‌سازی)
├── 📄 purge.php                  ← فیلتر + پاک‌سازی دیتابیس
├── 📄 bootstrap.php              ← راه‌انداز مشترک
├── 📄 config.php                 ← تنظیمات (DB، مسیرها، AI)
│
├── 📁 src/
│   ├── 📄 Logger.php             ← لاگر حرفه‌ای دو‌زبانه
│   ├── 📄 Database.php           ← لایه PDO
│   └── 📄 Queue.php              ← مدیریت صف فایل‌محور
│
├── 📁 storage/
│   ├── 📁 queue/                 ← فایل‌های در انتظار
│   ├── 📁 processing/            ← فایل‌های در حال پردازش
│   ├── 📁 failed/                ← فایل‌های ناموفق
│   └── 📄 worker.lock            ← فایل قفل ورکر
│
└── 📁 logs/
    └── 📄 YYYY-MM-DD.log         ← لاگ روزانه
```

### جدول مسئولیت‌ها

| فایل | مسئولیت | اجرا با |
|------|---------|---------|
| `api.php` | دریافت POST، اعتبارسنجی، ذخیره در صف | HTTP |
| `worker.php` | خواندن صف، درج در DB | کرون هر دقیقه |
| `filter.php` | فیلتر پیام‌ها با AI + نرمال‌سازی | HTTP یا کرون |
| `purge.php` | فیلتر + پاک‌سازی DB و صف | HTTP (دستی) |
| `bootstrap.php` | راه‌اندازی Logger, DB, Queue | by require |
| `config.php` | تنظیمات مرکزی | by require |

---

## ۴. تکنولوژی‌ها

| مورد | نسخه / توضیح |
|-------|--------------|
| **PHP** | 7.4+ (کد با 7.4 سازگاره، در 8.x هم اجرا می‌شه) |
| **MySQL** | 5.7+ (InnoDB، utf8mb4) |
| **PDO** | برای اتصال دیتابیس |
| **cURL** | برای درخواست به AI |
| **curl_multi** | برای ارسال موازی |
| **فایل‌سیستم** | به عنوان صف (بدون Redis/RabbitMQ) |
| **flock** | برای قفل فایل |
| **JSON** | فرمت داده ورودی/خروجی |

### چرا این تکنولوژی‌ها؟

- **PHP**: سبک، ارزان، روی هر هاست اشتراکی کار می‌کنه
- **فایل‌سیستم به‌جای صف‌های سنگین**: بدون نیاز به Redis/RabbitMQ، ساده‌تر برای نگهداری
- **cURL multi**: موازی‌سازی بدون نیاز به PHP extensions خاص
- **PDO**: امن در برابر SQL Injection

---

## ۵. جریان داده (Data Flow)

### مسیر ۱: ورود داده

```
1. منبع خارجی JSON می‌فرسته به api.php
2. api.php اعتبارسنجی می‌کنه (size, JSON, schema)
3. نرمال‌سازی: هر ردیف به ساختار داخلی تبدیل
4. ذخیره اتمیک در storage/queue/YYYYMMDD_His_RANDOM_PID.json
5. پاسخ JSON برمی‌گردونه
```

### مسیر ۲: درج در دیتابیس

```
1. کرون هر دقیقه worker.php رو صدا می‌زنه
2. worker.php قفل flock می‌گیره
3. لیست فایل‌های queue/ رو می‌خونه
4. هر فایل:
   a. claim (rename به processing/)
   b. خواندن JSON
   c. array_chunk(batch_size = 500)
   d. INSERT IGNORE INTO messages
   e. موفق → unlink | ناموفق → failed/
5. قفل آزاد می‌شه
```

### مسیر ۳: فیلتر هوشمند

```
1. filter.php صدا زده می‌شه
2. کلمات کلیدی از جدول filters خونده می‌شه
3. پیام‌ها از messages خونده می‌شه

لایه ۱ — نرمال‌سازی خشن:
   - حذف ZWNJ، اعراب، فاصله‌ها
   - یکسان‌سازی ی/ک/ه عربی
   - فروپاشی حروف تکراری (سس → س)
   - lowercase
   → اگه زیررشته match شد → درج فوری

لایه ۲ — فازی Levenshtein:
   - پنجره لغزان روی کلمات
   - فاصله یونیکد Levenshtein
   → اگه ≤ 1 بود → درج فوری

لایه ۳ — هوش مصنوعی (موازی):
   - پیام‌های باقی‌مونده batch می‌شن (200 تایی)
   - با curl_multi موازی (15x) به AI فرستاده می‌شن
   - پرامپت: تحمل خطای تایپی + معنایی
   → matches برگشت → درج
```

### مسیر ۴: پاک‌سازی

```
1. purge.php صدا زده می‌شه
2. اول یه بار منطق filter.php اجرا می‌شه (فیلتر قبل از پاک)
3. DELETE FROM messages
4. ALTER TABLE AUTO_INCREMENT = 1
5. پاک کردن همه فایل‌های صف
```

---

## ۶. اجزای اصلی

### 6.1 `api.php`

**وظیفه:** نقطه ورودی REST API

**ورودی:** POST JSON array
```json
[
  {
    "id": 31652,
    "chat_id": -1003472580791,
    "sender_id": 7914913303,
    "text": "خطای دیتابیس",
    "link": "https://t.me/c/...",
    "date": 1791380766,
    "date_human": "2026-10-07 15:46:06"
  }
]
```

**خروجی:** JSON
```json
{"ok":true,"queued":2,"skipped":0,"file":"20261008_...json"}
```

**محافظت‌ها:**
- بررسی `REQUEST_METHOD === POST`
- بررسی خالی نبودن body
- بررسی `max_payload_size`
- اعتبارسنجی JSON
- `ob_start()` برای حذف BOM/whitespace

### 6.2 `worker.php`

**وظیفه:** انتقال از صف به دیتابیس

**محافظت‌ها:**
- `flock(LOCK_EX | LOCK_NB)` — ضد اجرای همزمان
- `max_runtime` — جلوگیری از اجرای طولانی
- `claim` اتمیک (rename)
- `failed/` برای فایل‌های خراب

**بهینه‌سازی:**
- `INSERT IGNORE` + `UNIQUE KEY`
- `array_chunk` (batch 500)

### 6.3 `filter.php`

**وظیفه:** فیلتر هوشمند پیام‌ها

**سه لایه:**
1. نرمال‌سازی خشن (`normalizePersianAggressive`)
2. فازی Levenshtein (`hasFuzzyMatch`, `utf8Levenshtein`)
3. هوش مصنوعی موازی (`sendBatchesInParallel`)

**نکات:**
- `set_time_limit(0)` — بدون محدودیت زمان
- `ignore_user_abort(true)` — ادامه در صورت قطع اتصال
- `AI_MAX_RUNTIME` — سقف زمان کل
- `curl_multi` — ۱۵ درخواست همزمان
- Keep-Alive + gzip

### 6.4 `purge.php`

**وظیفه:** فیلتر + پاک‌سازی

**مراحل:**
1. اجرای منطق فیلتر (تابع `runFilterBeforePurge`)
2. `DELETE FROM messages`
3. `ALTER TABLE AUTO_INCREMENT = 1`
4. پاک کردن فایل‌های صف

### 6.5 `bootstrap.php`

**وظیفه:** راه‌انداز مشترک

**کارها:**
- تنظیم timezone (`Asia/Tehran`)
- `mb_internal_encoding('UTF-8')`
- لود `config.php`
- لود کلاس‌ها (Logger, Database, Queue)
- `Logger::init`
- ثبت `set_error_handler`, `set_exception_handler`, `register_shutdown_function`

### 6.6 `src/Logger.php`

**وظیفه:** لاگر حرفه‌ای دو‌زبانه

**سطوح:** DEBUG، INFO، WARNING، ERROR، CRITICAL

**فرمت:**
```
[2026-10-08 11:13:59] [CRITICAL] [pid:16640] [fa: استثنای مدیریت‌نشده] [en: Uncaught exception] | {"message":"..."}
```

**فایل:** `logs/YYYY-MM-DD.log`

### 6.7 `src/Database.php`

**وظیفه:** لایه PDO

**متدها:**
- `connect(array $cfg)` — اتصال singleton
- `insertMessages(array $rows)` — درج انبوه

**تنظیمات PDO:**
- `ERRMODE_EXCEPTION`
- `DEFAULT_FETCH_MODE = ASSOC`
- `EMULATE_PREPARES = false`

### 6.8 `src/Queue.php`

**وظیفه:** مدیریت صف فایل‌محور

**متدها:**
- `push(array $messages)` — ذخیره اتمیک
- `listPending(int $limit)` — لیست فایل‌ها
- `claim(string $path)` — انتقال به processing
- `done(string $path)` — حذف
- `fail(string $path)` — انتقال به failed

---

## ۷. دیتابیس


### 7.1 جدول `messages`

| ستون | نوع | توضیح |
|------|-----|-------|
| `id` | BIGINT UNSIGNED | PK |
| `telegram_id` | BIGINT | شناسه پیام در تلگرام (UNIQUE) |
| `chat_id` | BIGINT | شناسه چت |
| `group_name` | VARCHAR(190) | نام گروه (جدید) |
| `sender_id` | BIGINT | شناسه فرستنده |
| `sender_name` | VARCHAR(190) | نام فرستنده (جدید) |
| `text` | TEXT | متن پیام |
| `link` | VARCHAR(512) | لینک پیام |
| `date` | BIGINT | timestamp |
| `date_human` | VARCHAR(32) | تاریخ خوانا |
| `created_at` | DATETIME | زمان درج |



### 7.2 جدول `filters`

```sql
CREATE TABLE `filters` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword`    VARCHAR(190) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 7.3 جدول `filtered_messages`

```sql
CREATE TABLE `filtered_messages` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id`          BIGINT UNSIGNED NOT NULL,
  `telegram_id`         BIGINT NULL,
  `chat_id`             BIGINT NULL,
  `sender_id`           BIGINT NULL,
  `text`                TEXT NULL,
  `link`                VARCHAR(512) NULL,
  `date`                BIGINT NULL,
  `date_human`          VARCHAR(32) NULL,
  `matched_keyword`     VARCHAR(190) NOT NULL,
  `original_created_at` DATETIME NULL,
  `filtered_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_msg_keyword` (`message_id`, `matched_keyword`),
  KEY `idx_telegram_id` (`telegram_id`),
  KEY `idx_chat_id` (`chat_id`),
  KEY `idx_keyword` (`matched_keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### چرا این ایندکس‌ها؟

| ایندکس | دلیل |
|--------|-------|
| `uniq_telegram_id` | جلوگیری از درج تکراری |
| `idx_chat_id` | کوئری‌های «پیام‌های این چت» |
| `idx_chat_date` | کوئری‌های «این چت در بازه زمانی» |
| `uniq_msg_keyword` | جلوگیری از درج تکراری در filter |

---

## ۸. لاگر

### ساختار لاگ

```
[زمان] [سطح] [pid:XXXX] [fa: پیام فارسی] [en: English message] | {context_json}
```

### مثال‌ها

```
[2026-10-08 11:13:59] [INFO] [pid:2231] [fa: درخواست با موفقیت در صف ذخیره شد] [en: Request queued successfully] | {"count":2,"file":"..."}
[2026-10-08 11:14:12] [WARNING] [pid:2235] [fa: حجم درخواست بیش از حد مجاز] [en: Payload too large] | {"size":25000000}
[2026-10-08 11:14:30] [CRITICAL] [pid:2236] [fa: اتصال به دیتابیس برقرار نشد] [en: Database connection failed] | {"message":"..."}
```

### سطوح

| سطح | کاربرد |
|-----|--------|
| `DEBUG` | جزئیات اجرای داخلی (فقط برای توسعه) |
| `INFO` | رویدادهای عادی (شروع، پایان، موفقیت) |
| `WARNING` | مشکل جزئی (داده نامعتبر، تلاش مجدد) |
| `ERROR` | خطای قابل بازیابی (فایل شکست، درج شکست) |
| `CRITICAL` | خطای کشنده (اتصال DB، استثنا) |

### پاک‌سازی خودکار

```bash
# کرون ماهانه برای لاگ‌های قدیمی
0 4 1 * * find /path/logs -name "*.log" -mtime +30 -delete
```

---

## ۹. هوش مصنوعی

### پیکربندی

```php
const AI_URL         = 'https://hermes.ai.unturf.com/v1/chat/completions';
const AI_MODEL       = 'turboderp/Qwen3.8-27B-exl3';
const AI_TIMEOUT     = 180;
const AI_BATCH_SIZE  = 200;    // پیام در هر درخواست
const AI_PARALLEL    = 15;     // درخواست همزمان
const AI_MAX_RUNTIME = 600;    // ۱۰ دقیقه
const AI_TEXT_LIMIT  = 500;    // کاراکتر متن هر پیام
```

### پرامپت

پرامپت طوری طراحی شده که:

1. **اولویت ۱: معنایی** — اگر معنا مربوط است، MATCH
2. **اولویت ۲: تایپی** — ۸ نوع خطای رایج:
   - تکرار حرف
   - حذف حرف
   - اضافه حرف
   - جابجایی حرف
   - اشتباه حرف مشابه
   - فاصله وسط کلمه
   - حذف فاصله
   - حروف عربی/فارسی

3. **قانون طلایی:** «اگر شک داری، MATCH کن»

### بهینه‌سازی‌ها

| تکنیک | تأثیر |
|-------|-------|
| `curl_multi` | ۱۵x موازی |
| Batch 200 | درخواست کمتر |
| `Connection: keep-alive` | بدون TLS handshake مکرر |
| gzip | payload کوچک‌تر |
| `max_tokens = 1500` | پاسخ کوتاه‌تر |
| متن محدود به 500 کاراکتر | توکن کمتر |
| کلیدهای کوتاه `i`,`t` | JSON کوچک‌تر |

### نرمال‌سازی فارسی قبل از ارسال

تابع `normalizePersianAggressive`:
1. حذف ZWNJ، نیم‌فاصله، BOM
2. حذف اعراب (`ً ٌ ٍ َ ُ ِ ّ ْ`)
3. یکسان‌سازی ی/ک/ه عربی
4. یکسان‌سازی اعداد
5. حذف فاصله‌ها
6. **فروپاشی حروف تکراری** (`سس → س`)
7. lowercase

**مثال:**
```
"اررسال بسسته"
  ↓
"ارسالبتسه"  ← بعد از فروپاشی و حذف فاصله
```

---

## ۱۰. کرون جاب‌ها

### ورکر (هر دقیقه)

```cron
* * * * * /usr/bin/php /home/USER/public_html/telegram/worker.php >> /dev/null 2>&1
```

**چرا هر دقیقه؟**
- `max_runtime = 50s` → هر اجرا خودش رو می‌بنده
- `flock` → ضد اجرای همزمان
- ترافیک بالا → پردازش مداوم

### فیلتر (اختیاری - هر ۱۵ دقیقه)

```cron
*/15 * * * * /usr/bin/php /home/USER/public_html/telegram/filter.php >> /dev/null 2>&1
```

### پاک‌سازی (روزانه ۳ صبح)

```cron
0 3 * * * /usr/bin/php /home/USER/public_html/telegram/cleanup.php >> /dev/null 2>&1
```

### پاک کردن لاگ‌های قدیمی (ماهانه)

```cron
0 4 1 * * find /home/USER/public_html/telegram/logs -name "*.log" -mtime +30 -delete
```

---

## ۱۱. اگه بخوای جایی رو تغییر بدی

### 🔧 تغییرات رایج

#### ۱۱.۱ عوض کردن URL یا مدل AI

**فایل:** `filter.php`
**خط:**
```php
const AI_URL   = 'https://your-new-ai-endpoint/...';
const AI_MODEL = 'your-new-model-name';
```

**نکته:** اگه endpoint جدید OpenAI-compatible نیست، تابع `makeHandle` رو باید تغییر بدی.

#### ۱۱.۲ کند/تند کردن AI

**فایل:** `filter.php`

| پارامتر | افزایش | کاهش |
|---------|--------|------|
| `AI_PARALLEL` | سریع‌تر ولی فشار بیشتر | کندتر ولی پایدارتر |
| `AI_BATCH_SIZE` | درخواست کمتر، توکن بیشتر هر درخواست | درخواست بیشتر |
| `AI_TEXT_LIMIT` | دقت بیشتر، توکن بیشتر | سرعت بیشتر |
| `FUZZY_MAX_DIST` | دقت بیشتر، false positive بیشتر | سخت‌گیرتر |

#### ۱۱.۳ اضافه کردن کلمه فیلتر جدید

فقط با SQL:
```sql
INSERT INTO `filters` (`keyword`) VALUES ('کلمه جدید');
```

**نکته:** نرمال‌سازی و فروپاشی خودکاره، نیازی به تغییر کد نیست.

#### ۱۱.۴ تغییر اندازه batch در ورکر

**فایل:** `config.php`
```php
'worker' => [
    'batch_size'    => 500,   // ← این رو تغییر بده
    'max_runtime'   => 50,
    'max_files_run' => 500,
],
```

#### ۱۱.۵ تغییر مسیرها

**فایل:** `config.php` → بخش `paths`

```php
'paths' => [
    'logs'       => __DIR__ . '/logs',
    'queue'      => __DIR__ . '/storage/queue',
    'processing' => __DIR__ . '/storage/processing',
    'failed'     => __DIR__ . '/storage/failed',
    'lock'       => __DIR__ . '/storage/worker.lock',
],
```

#### ۱۱.۶ تغییر سطح لاگ

**فایل:** `config.php`
```php
'log_level' => 'INFO',  // DEBUG | INFO | WARNING | ERROR | CRITICAL
```

#### ۱۱.۷ اضافه کردن فیلد به `messages`

1. `ALTER TABLE messages ADD COLUMN new_field VARCHAR(50);`
2. `api.php` → در نرمال‌سازی ردیف اضافه کن
3. `src/Database.php` → در `insertMessages` ستون و placeholder اضافه کن
4. `worker.php` → نیازی به تغییر نیست

#### ۱۱.۸ تغییر منطق فیلتر (بدون AI)

**فایل:** `filter.php` → تابع `normalizePersianAggressive`
- مثلاً اگه نمی‌خوای فروپاشی حروف تکراری انجام بشه، بخش ۶ رو حذف کن

#### ۱۱.۹ اضافه کردن pre-filter برای زبان دیگه

**فایل:** `filter.php`
- تابع `normalizePersianAggressive` رو با یه نسخه برای زبان جدید تعویض کن
- یا در کنارش یه `normalizeXxx` بذار و بر اساس زبان انتخاب کن

#### ۱۱.۱۰ تغییر فرمت لاگ

**فایل:** `src/Logger.php` → متد `write`
```php
$line = sprintf(
    "[%s] [%s] [pid:%d] [fa: %s] [en: %s]%s%s",  // ← این خط
    ...
);
```

#### ۱۱.۱۱ حذف بخشی از پرامپت AI

**فایل:** `filter.php` → تابع `buildSystemPrompt`

کل محتوای `<<<EOT ... EOT;` قابل ویرایشه.

#### ۱۱.۱۲ اضافه کردن retry به AI

**فایل:** `filter.php` → `sendBatchesInParallel`

بخش خطا رو اینطوری تغییر بده:
```php
if ($errno || $httpCode !== 200) {
    // تلاش مجدد
    $retryCh = $makeHandle($batches[$batchIdx]);
    curl_multi_add_handle($mh, $retryCh);
    ...
}
```

### 📊 جدول تصمیم سریع

| می‌خوام... | فایل |
|------------|------|
| endpoint یا مدل AI | `filter.php` (بالای فایل) |
| سرعت/دقت AI | `filter.php` (تنظیمات AI) |
| batch size ورکر | `config.php` |
| کلمات فیلتر | جدول `filters` (SQL) |
| منطق نرمال‌سازی | `filter.php` → `normalizePersianAggressive` |
| فرمت لاگ | `src/Logger.php` |
| سطح لاگ | `config.php` |
| مسیر صف | `config.php` |
| ایندکس‌ها | SQL |
| منطق API | `api.php` |
| منطق ورکر | `worker.php` |
| منطق purge | `purge.php` |

---

## ۱۲. عیب‌یابی

### ۱۲.۱ خطای «Database» در پاسخ

**علت:** Fatal Error با قالب مخفی هاست

**راه‌حل:**
```php
error_reporting(E_ALL);
ini_set('display_errors', '1');
```
تو فایل موقت یا `logs/` رو چک کن.

### ۱۲.۲ «headers already sent»

**علت:** BOM یا whitespace قبل از `<?php`

**راه‌حل:**
- فایل‌ها رو با **UTF-8 without BOM** ذخیره کن
- `ob_start()` در ابتدای فایل

### ۱۲.۳ خطای «Class 'Database' not found»

**علت:** فایل `src/Database.php` خراب یا ناقص

**راه‌حل:** محتواش رو چک کن، باید شروعش `<?php` باشه

### ۱۲.۴ پیام‌ها در دیتابیس درج نمی‌شن

**چک‌لیست:**
- [ ] `storage/queue/` پرمیشن ۷۷۵ داره
- [ ] `flock` جلوی ورکر رو نگرفته (`rm storage/worker.lock`)
- [ ] `logs/YYYY-MM-DD.log` رو ببین
- [ ] `config.php` → اطلاعات DB درسته

### ۱۲.۵ AI پاسخ نمی‌ده

**چک‌لیست:**
- [ ] endpoint درسته
- [ ] مدل درسته
- [ ] `curl` فعاله: `php -m | grep curl`
- [ ] `AI_PARALLEL` رو کم کن (شاید rate limit)
- [ ] `AI_TIMEOUT` رو زیاد کن

### ۱۲.۶ فیلتر خطای تایپی رو نمی‌گیره

**چک:**
- `FUZZY_MAX_DIST` رو ببر بالا (۲)
- `FUZZY_MIN_WORD` رو کم کن (۳)
- `normalizePersianAggressive` رو تست کن:
```php
var_dump(normalizePersianAggressive("بسسته"));
```

### ۱۲.۷ لاگ‌ها رشد بی‌رویه می‌کنن

**راه‌حل:**
```bash
# کرون ماهانه
0 4 1 * * find /path/logs -name "*.log" -mtime +30 -delete
```

### ۱۲.۸ فایل‌های `failed/` انبار می‌شن

**راه‌حل:**
- `logs/` رو چک کن ببین چرا شکست خورده
- فایل‌های موفق رو پاک کن
- `cleanup.php` بساز برای پاک‌سازی روزانه

---

## ۱۳. امنیت

### ۱۳.۱ نکات مهم

- ❌ **فایل‌های `peek.php`، `worker_debug.php`، `fix_bom.php` رو بعد از استفاده پاک کن**
- 🔐 **`purge.php` و `filter.php` رو با `.htaccess` محدود کن به IP خودت**
- 🔐 **`config.php` رو بیرون از `public_html` بذار** (اگه امکان داره)
- 🔐 **لاگ‌ها نباید قابل دسترسی از مرورگر باشن**
- 🔐 **`storage/` نباید مستقیم قابل دسترسی باشه**

### ۱۳.۲ نمونه `.htaccess`

```apache
# محافظت از فایل‌های حساس
<FilesMatch "^(config\.php|bootstrap\.php)$">
    Require all denied
</FilesMatch>

# محافظت از پوشه‌های storage و logs
RedirectMatch 403 ^/telegram/storage/.*$
RedirectMatch 403 ^/telegram/logs/.*$

# محدود کردن purge و filter به IP خودت
<Files "purge.php">
    Require ip YOUR_HOME_IP
</Files>
```

### ۱۳.۳ اگه API عمومی است

- اضافه کردن توکن احراز هویت
- Rate limiting بر اساس IP
- اعتبارسنجی دقیق‌تر schema

---

## ۱۴. محدودیت‌ها و TODO

### ۱۴.۱ محدودیت‌های فعلی

| محدودیت | توضیح |
|---------|-------|
| **تک سرور** | بدون توزیع‌شدگی |
| **بدون Redis/RabbitMQ** | صف روی فایل |
| **بدون احراز هویت API** | هر کسی می‌تونه POST کنه |
| **AI اجباری برای بخشی** | اگه AI down باشه، فیلتر معنایی کار نمی‌کنه |
| **بدون monitoring** | فقط لاگ دستی |

### ۱۴.۲ TODO های پیشنهادی

- [ ] اضافه کردن احراز هویت API (توکن Bearer)
- [ ] Rate limiting
- [ ] Retry با exponential backoff برای AI
- [ ] Dead Letter Queue بهتر
- [ ] داشبورد وب برای دیدن صف
- [ ] لاگ کردن پاسخ AI برای دیباگ
- [ ] Cache کردن نرمال‌سازی برای پیام‌های تکراری
- [ ] Unit test برای نرمال‌سازی و فازی
- [ ] Migration از صف فایل به Redis (اگه حجم > 1M/min)
- [ ] Partition کردن جدول `messages` بر اساس `date`

### ۱۴.۳ مقیاس‌پذیری

| حجم روزانه | راه‌حل |
|-----------|--------|
| < 100K | ✅ همین معماری |
| 100K - 1M | 🔧 ایندکس بهتر + MySQL tuning |
| 1M - 10M | 🔧 Redis برای صف + Partition |
| > 10M | 🔧 Sharding + Kafka |

---

## 🎯 خلاصه سریع برای مهاجر جدید

1. **`config.php`** رو پر کن (DB، paths، AI)
2. **جداول** رو با SQL بساز (messages, filters, filtered_messages)
3. **`storage/`** و **`logs/`** رو بساز و chmod 775
4. **کرون** رو برای `worker.php` هر دقیقه تنظیم کن
5. **تست**: با curl به `api.php` پیام بفرست
6. **چک**: `logs/` و جدول `messages`
7. **فیلتر**: با مرورگر `filter.php` رو صدا بزن

---

**نسخه:** 1.0
**آخرین به‌روزرسانی:** 2026-10-10
**زبان:** فارسی
```

---

این فایل رو به‌عنوان `AGENT.md` توی ریشه پروژه بذار. هر توسعه‌دهنده‌ای که اومد روی پروژه، با خوندن این فایل، همه چیز رو می‌فهمه. 🚀

می‌خوای بخش خاصی رو بازتر کنم یا فایل دیگه‌ای مثل `CHANGELOG.md` یا `SETUP.md` هم بسازم؟


v 1.0.0 released


## 📄 UPDATE Block — به انتهای `AGENT.md` اضافه کن

```markdown
---

# 🔄 UPDATE — نسخه 1.1 (2026-10-11)

این بخش، تغییرات اعمال‌شده بعد از نسخه ۱.۰ رو توضیح می‌ده. اگه از نسخه قدیمی پروژه استفاده می‌کنی، این تغییرات رو باید اعمال کنی.

---

## U1. فیلدهای جدید در API

### ورودی API جدید

به JSON ورودی، دو فیلد جدید اضافه شده:

```json
{
  "id": 31652,
  "chat_id": -1003472580791,
  "sender_id": 7914913303,
  "sender_name": "علی رضایی",         ← جدید
  "group_name": "گروه پشتیبانی",      ← جدید
  "text": "ارسال دیجی‌کالا",
  "link": "https://t.me/c/3472580791/31652",
  "date": 1791380766,
  "date_human": "2026-10-07 15:46:06"
}
```

| فیلد | نوع | اجباری؟ | توضیح |
|------|-----|---------|-------|
| `sender_name` | string | اختیاری | نام نمایشی فرستنده |
| `group_name` | string | اختیاری | نام گروه/چت |

### تغییرات فایل‌ها

| فایل | تغییر |
|------|-------|
| `api.php` | `$normalized[]` — اضافه کردن `sender_name`, `group_name` |
| `src/Database.php` | `insertMessages()` — اضافه کردن دو ستون |
| `filter.php` | SELECT و INSERT — اضافه کردن دو ستون |
| `purge.php` | `runFilterBeforePurge()` — اضافه کردن دو ستون |

---

## U2. اصلاح مشکل پیام‌های تکراری 🔴 مهم

### مشکل

قبل از نسخه ۱.۱، UNIQUE KEY فقط روی `telegram_id` بود:

```sql
UNIQUE KEY `uniq_telegram_id` (`telegram_id`)  ❌ اشتباه
```

**چرا اشتباه بود؟**

`telegram_id` (که از `id` میاد) فقط **داخل هر چت** یکتاست. مثلاً:

| chat_id | telegram_id | نتیجه |
|---------|-------------|-------|
| -1003472580791 | 31652 | پیام اول |
| -1003311694628 | 31652 | پیام دوم — ولی UNIQUE جلوش رو می‌گیره! ❌ |

### راه‌حل

UNIQUE KEY روی **ترکیب دو ستون**:

```sql
UNIQUE KEY `uniq_chat_telegram` (`chat_id`, `telegram_id`)  ✅ صحیح
```

### تغییرات `src/Database.php`

| قبل | بعد |
|-----|-----|
| `INSERT IGNORE` | `INSERT ... ON DUPLICATE KEY UPDATE` |
| فقط جلوی تکراری رو می‌گرفت | جلوی تکراری رو می‌گیره + فیلدها رو به‌روز می‌کنه |
| `chat_id` می‌تونه NULL باشه | اجبار به عدد (0 اگه NULL) |

### تغییرات `api.php`

اگه `chat_id` در ورودی نبود یا NULL بود، **رکورد skip می‌شه** (چون بدون اون نمی‌شه تکراری‌ها رو تشخیص داد).

---

## U3. بهینه‌سازی `filter.php` — نسخه سریع

### مشکلات قبلی

1. **Timeout**: سرور بعد از ۳۰-۶۰ ثانیه اتصال رو قطع می‌کرد
2. **بدون resume**: اگه قطع بشه، از صفر شروع می‌کرد
3. **AI کند**: پارامترها محافظه‌کارانه بودن

### راه‌حل‌ها

#### 🚀 ۱) پاسخ فوری به کاربر

```php
// پاسخ JSON فوری برمی‌گرده
echo json_encode(['ok' => true, 'message' => 'پردازش در پس‌زمینه شروع شد']);

// اتصال به کاربر بسته می‌شه
fastcgi_finish_request();

// ادامه پردازش در background (کاربر منتظر نیست)
```

**نتیجه:** کاربر هیچ‌وقت timeout نمی‌گیره. ✅

#### 🗂 ۲) State File برای Resume

فایل `storage/filter_state.json`:

```json
{
  "last_processed_message_id": 31653,
  "keyword_hash": "abc123...",
  "updated_at": "2026-10-11 14:30:00"
}
```

- **بار اول**: از `id=0` شروع می‌کنه (کل دیتابیس)
- **اگه قطع شد**: بار بعد از آخرین `id` ادامه می‌ده
- **اگه کلیدواژه‌ها عوض شدن**: خودکار از صفر شروع می‌کنه
- **دستی از صفر**: `?reset=1`

#### ⚡ ۳) پارامترهای بهینه

| پارامتر | قبل | بعد |
|---------|:---:|:---:|
| `AI_PARALLEL` | 15 | **25** |
| `AI_BATCH_SIZE` | 200 | **300** |
| `AI_TEXT_LIMIT` | 500 | **300** |
| `AI_TIMEOUT` | 180 | **120** |
| `AI_MAX_RUNTIME` | 600 | **900** |

**نتیجه:** حدود **۳ برابر سریع‌تر** برای حجم بالا.

#### 📌 ۴) ذخیره State بعد از هر batch

اگه وسط کار قطع بشه، state حفظ می‌شه و بار بعد ادامه می‌ده.

### نحوه استفاده

| حالت | آدرس |
|------|------|
| بار اول (کل DB) | `filter.php` |
| ادامه از آخرین نقطه | `filter.php` (خودکار) |
| از صفر | `filter.php?reset=1` |

### کرون پیشنهادی

```cron
*/5 * * * * /usr/bin/php /home/USER/public_html/telegram/filter.php > /dev/null 2>&1
```

هر ۵ دقیقه ادامه می‌ده تا همه پیام‌ها پردازش بشن.

---

## U4. منطق فیلتر سه‌لایه

### لایه ۱ — نرمال‌سازی خشن (`normalizePersianAggressive`)

| مرحله | مثال |
|-------|------|
| حذف ZWNJ | `نیم‌فاصله` → `نیمفاصله` |
| حذف اعراب | `مُشکِل` → `مشکل` |
| یکسان‌سازی ی/ک/ه | `مشكل` → `مشکل` |
| یکسان‌سازی اعداد | `۱۲۳` → `123` |
| حذف فاصله‌ها | `تا پین` → `تاپین` |
| **فروپاشی تکراری** | `بسسته` → `بسته` |
| lowercase | `TAPIN` → `tapin` |

### لایه ۲ — فازی Levenshtein

- پنجره لغزان روی کلمات متن
- `utf8Levenshtein` (تابع سفارشی برای یونیکد)
- اگه فاصله ≤ `FUZZY_MAX_DIST` (پیش‌فرض ۱) → MATCH

### لایه ۳ — هوش مصنوعی

- با `curl_multi` موازی
- پرامپت قوی برای معنایی + تایپی
- فقط پیام‌های باقی‌مونده (بعد از لایه ۱ و ۲)

### ترتیب اولویت پرامپت

1. **🥇 معنایی** (مهم‌ترین)
2. **🥈 تایپی** (خیلی مهم)
3. **📌 قانون طلایی**: «اگر شک داری، MATCH کن»

---

## U5. Migration SQL کامل

فایل `database.sql` ساخته شد که همه تغییرات رو یک‌جا داره:

```sql
-- 1) CREATE TABLE IF NOT EXISTS برای همه جداول
-- 2) ALTER TABLE ADD COLUMN sender_name, group_name
-- 3) DELETE تکراری‌های موجود
-- 4) DROP INDEX های اشتباه
-- 5) ADD UNIQUE KEY صحیح: uniq_chat_telegram
-- 6) ADD ایندکس‌های بهینه
```

### UNIQUE KEY های صحیح

| جدول | UNIQUE KEY |
|------|------------|
| `messages` | `uniq_chat_telegram (chat_id, telegram_id)` |
| `filtered_messages` | `uniq_msg_keyword (message_id, matched_keyword)` |
| `filters` | `uniq_keyword (keyword)` |

### ایندکس‌های جدید

| جدول | ایندکس | کاربرد |
|------|--------|--------|
| `messages` | `idx_group_name` | جستجو بر اساس گروه |
| `messages` | `idx_chat_date` | پیام‌های چت در بازه زمانی |
| `messages` | `idx_created_at` | مرتب‌سازی بر اساس زمان درج |
| `filtered_messages` | `idx_filtered_at` | پیام‌های فیلترشده اخیر |

---

## U6. جدول تغییرات خلاصه

| نسخه | تاریخ | تغییرات کلیدی |
|------|-------|---------------|
| 1.0 | 2026-10-08 | نسخه اولیه |
| **1.1** | **2026-10-11** | sender_name/group_name، رفع تکراری، بهینه‌سازی filter |

---

## U7. چک‌لیست به‌روزرسانی از 1.0 به 1.1

- [ ] اجرای `database.sql` (Migration)
- [ ] تأیید `SHOW INDEX FROM messages` → `uniq_chat_telegram`
- [ ] تأیید `SHOW INDEX FROM filtered_messages` → `uniq_msg_keyword`
- [ ] جایگزینی `api.php`
- [ ] جایگزینی `src/Database.php`
- [ ] جایگزینی `filter.php`
- [ ] جایگزینی `purge.php`
- [ ] اطمینان از پرمیشن ۷۷۵ برای `storage/`
- [ ] تست با curl (دو بار با یه پیام)
- [ ] تأیید COUNT = 1

---

## U8. پارامترهای قابل تنظیم سریع

### `filter.php`

```php
// سرعت بالا (پیشنهادی):
const AI_PARALLEL   = 25;
const AI_BATCH_SIZE = 300;
const AI_TEXT_LIMIT = 300;

// سرور خیلی قوی:
const AI_PARALLEL   = 35;
const AI_BATCH_SIZE = 400;

// اگه AI rate limit داد:
const AI_PARALLEL   = 15;
const AI_BATCH_SIZE = 200;
```

### `config.php`

```php
'api' => [
    'max_payload_size'  => 5 * 1024 * 1024,  // 5MB
    'max_rows_per_file' => 1000,              // حداکثر ردیف در هر فایل صف
],

'worker' => [
    'max_runtime'     => 50,   // ثانیه
    'batch_size'      => 500,  // تعداد ردیف در هر INSERT
    'max_files_run'   => 500,
],
```

---

## U9. دستورات مفید روزمره

```bash
# چک لاگ امروز
tail -f logs/$(date +%Y-%m-%d).log

# فقط خطاها
grep -E "\[ERROR\]|\[CRITICAL\]" logs/$(date +%Y-%m-%d).log

# چک state فیلتر
cat storage/filter_state.json

# ریست state فیلتر
rm storage/filter_state.json

# ریست کل کار (DB + صف)
# از مرورگر: purge.php
```

### کوئری‌های مفید

```sql
-- آمار کلی
SELECT 
  (SELECT COUNT(*) FROM messages) AS total_messages,
  (SELECT COUNT(*) FROM filtered_messages) AS total_filtered,
  (SELECT COUNT(DISTINCT matched_keyword) FROM filtered_messages) AS unique_keywords;

-- پرتکرارترین فیلترها
SELECT matched_keyword, COUNT(*) c 
FROM filtered_messages 
GROUP BY matched_keyword 
ORDER BY c DESC;

-- پیام‌های فیلترشده اخیر
SELECT id, group_name, sender_name, matched_keyword, LEFT(text, 60) AS preview
FROM filtered_messages 
ORDER BY id DESC LIMIT 20;

-- چک تکراری (باید 0 باشه)
SELECT chat_id, telegram_id, COUNT(*) c
FROM messages
GROUP BY chat_id, telegram_id
HAVING c > 1;
```

---

## U10. محدودیت‌ها و نکات

### `fastcgi_finish_request`

اگه هاست از **mod_php** استفاده می‌کنه (نه PHP-FPM)، این تابع وجود نداره. در این حالت:
- fallback به `flush()` می‌ره
- ممکنه بازم timeout رخ بده
- **ولی state file نجاتت می‌ده** — بار بعد ادامه می‌ده

### پردازش چند‌باره

اگه با کرون `filter.php` رو هر ۵ دقیقه صدا بزنی:
- هر بار فقط پیام‌های جدید (`id > last_processed`) رو پردازش می‌کنه
- سریع و سبک

### کلیدواژه جدید

اگه یه کلیدواژه جدید به جدول `filters` اضافه کنی:
- خودکار کلیدواژه‌ها رو fresh می‌خونه
- ولی چون hash عوض می‌شه، **از صفر شروع می‌کنه** (کل دیتابیس رو دوباره فیلتر می‌کنه)
- برای همین بار اول بعد از اضافه کردن کلیدواژه، ممکنه طول بکشه

**راه‌حل:** اگه می‌خوای از صفر نره، `filter_state.json` رو دستی ویرایش کن و `keyword_hash` رو به hash جدید تغییر بده.

---

**نسخه 1.1 — تاریخ: 2026-10-11**
```

---

## 🎯 نحوه استفاده از این بلاک

1. فایل `AGENT.md` رو باز کن
2. **کل این بلاک** رو کپی کن
3. به **انتهای فایل** (بعد از بخش «نسخه 1.0») پیست کن
4. ذخیره کن

این بلاک رو به‌عنوان UPDATE در انتهای AGENT.md بذار تا هر کسی که از نسخه قدیمی میاد، بدونه چی تغییر کرده. اگه خواستی بخش خاصی رو بازتر کنم بگو 🚀ب
```markdown
---

# 🔄 UPDATE — نسخه 1.2 (2026-10-11)

این بخش، تغییرات بعد از نسخه ۱.۱ رو توضیح می‌ده. **مهم‌ترین تغییر:** یونیک بودن از دیتابیس به کد منتقل شد.

---

## U1. حذف تمام UNIQUE KEY ها از دیتابیس 🔴 مهم

### علت تغییر

UNIQUE KEY ها در دیتابیس باعث مشکلات زیر شده بودن:

| مشکل | توضیح |
|------|-------|
| **پیام‌های از دست رفته** | وقتی `telegram_id` تکراری می‌آمد، با `INSERT IGNORE` کاملاً نادیده گرفته می‌شد — حتی اگه متن جدید بود |
| **پیچیدگی در purge** | بعد از `purge.php`، `AUTO_INCREMENT` صفر می‌شد ولی state file دست‌نخورده می‌موند |
| **عدم انعطاف** | نمی‌شد منطق پیچیده‌تر (مثل ترکیب چند فیلد) رو اعمال کرد |

### SQL اجرا شده

```sql
-- حذف UNIQUE ها از همه جداول
ALTER TABLE `messages`            DROP INDEX `uniq_telegram_id`;
ALTER TABLE `filtered_messages`   DROP INDEX `uniq_msg_keyword`;
ALTER TABLE `filtered_messages`   DROP INDEX `uniq_telegram_keyword`;
ALTER TABLE `filters`             DROP INDEX `uniq_keyword`;
ALTER TABLE `blocked_chats`       DROP INDEX `uniq_chat_id`;
ALTER TABLE `irrelevant_messages` DROP INDEX `uniq_filtered`;
ALTER TABLE `viewed_messages`     DROP INDEX `uniq_filtered`;
```

### نتیجه

- ❌ **دیگه هیچ UNIQUE KEY ای در دیتابیس نیست** (فقط PRIMARY)
- ✅ **یونیک بودن الان در کد PHP چک می‌شه**
- ✅ **ایندکس‌های معمولی (`NON_UNIQUE = 1`) برای سرعت باقی موندن**

---

## U2. یونیک بودن در کد PHP ✅

### `src/Database.php` — یونیک بر اساس `telegram_id`

قبل از هر INSERT، کد این مراحل رو انجام می‌ده:

```php
// ۱) گرفتن همه telegram_id های ورودی
$telegramIds = [];
foreach ($rows as $r) {
    $tid = (int) ($r['telegram_id'] ?? 0);
    if ($tid > 0) $telegramIds[] = $tid;
}

// ۲) کوئری: کدوم‌ها از قبل تو دیتابیس هستن؟
$checkStmt = self::$pdo->prepare(
    "SELECT `telegram_id` FROM `messages` WHERE `telegram_id` IN ($placeholders)"
);
$checkStmt->execute($telegramIds);
$existingSet = array_flip(array_map('intval', $checkStmt->fetchAll(PDO::FETCH_COLUMN)));

// ۳) فقط ردیف‌های جدید رو درج کن
$newRows = [];
foreach ($rows as $r) {
    $tid = (int) ($r['telegram_id'] ?? 0);
    if ($tid > 0 && !isset($existingSet[$tid])) {
        $newRows[] = $r;
    }
}

// ۴) INSERT معمولی (بدون ON DUPLICATE KEY UPDATE)
$sql = 'INSERT INTO `messages` (...) VALUES ...';
```

**یونیک:** `telegram_id` (فقط یک ستون)
**محل:** کد PHP

---

### `filter.php` — یونیک بر اساس `(telegram_id, matched_keyword)`

قبل از شروع پردازش، تمام ترکیب‌های قبلاً فیلترشده از دیتابیس لود می‌شن:

```php
// کش ترکیب‌های قبلی
$alreadyFiltered = [];
$chkStmt = $pdo->query('SELECT `telegram_id`, `matched_keyword` FROM `filtered_messages`');
while ($row = $chkStmt->fetch(PDO::FETCH_ASSOC)) {
    $key = ((int) $row['telegram_id']) . '|' . $row['matched_keyword'];
    $alreadyFiltered[$key] = true;
}
```

قبل از هر INSERT:

```php
$key = $tid . '|' . $kw;
if (isset($alreadyFiltered[$key])) {
    continue;   // skip
}
$insertStmt->execute([...]);
if ($insertStmt->rowCount() > 0) {
    $alreadyFiltered[$key] = true;   // ثبت برای همین اجرا
}
```

**یونیک:** ترکیب `(telegram_id, matched_keyword)`
**محل:** کد PHP

---

### `purge.php` — همان منطق `filter.php`

تابع `runFilterBeforePurge` هم باید با چک دستی کار کنه:

```php
function runFilterBeforePurge(PDO $pdo): array
{
    // کش ترکیب‌های قبلی
    $alreadyFiltered = [];
    $chkStmt = $pdo->query('SELECT `telegram_id`, `matched_keyword` FROM `filtered_messages`');
    while ($row = $chkStmt->fetch(PDO::FETCH_ASSOC)) {
        $alreadyFiltered[((int) $row['telegram_id']) . '|' . $row['matched_keyword']] = true;
    }

    // برای هر پیام منطبق، چک تکراری قبل از INSERT
    foreach ($matched as $msg) {
        $key = ((int) $msg['telegram_id']) . '|' . $keyword;
        if (isset($alreadyFiltered[$key])) continue;
        $insertStmt->execute([...]);
        if ($insertStmt->rowCount() > 0) {
            $alreadyFiltered[$key] = true;
        }
    }
}
```

---

## U3. اصلاح باگ State در `filter.php` 🐛

### باگ ۱: مقدار اولیه اشتباه `$lastSavedId`

```php
// ❌ قبل:
$lastSavedId = $lastPreFilterId;   // آخرین id این اجرا
// در نتیجه: شرط if ($lastId > $lastSavedId) هیچ‌وقت true نمی‌شد

// ✅ بعد:
$lastSavedId = $lastProcessedId;   // آخرین id قبل از شروع
```

### باگ ۲: State ذخیره‌شده بعد از purge

بعد از `purge.php`، `messages` خالی می‌شد و `AUTO_INCREMENT` صفر می‌شد. ولی state file همچنان `last_processed_message_id` قدیمی (مثلاً 1709) رو داشت. نتیجه: `SELECT ... WHERE id > 1709` هیچ پیامی برنمی‌گردوند.

### راه‌حل: محافظت خودکار

```php
$maxMessageId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM `messages`')->fetchColumn();
if ($lastProcessedId > $maxMessageId) {
    Logger::warning('state بزرگ‌تر از max id دیتابیس است، ریست می‌شود', ...);
    $lastProcessedId = 0;
    $resumeMode = false;
}
```

### باگ ۳: ذخیره state نهایی AI

بعد از همه batch ها، state با `max id` آخرین پیام پردازش‌شده ذخیره می‌شه:

```php
if (!empty($payloads)) {
    $finalMaxId = (int) end($payloads)['i'];
    if ($finalMaxId > $lastSavedId) {
        $lastSavedId = $finalMaxId;
        saveState($lastSavedId, $keywordHash);
    }
}
```

---

## U4. جدول خلاصه یونیک بودن

| فایل | جدول هدف | یونیک بر اساس | محل چک |
|------|---------|--------------|--------|
| `src/Database.php` | `messages` | `telegram_id` | **کد PHP** |
| `filter.php` | `filtered_messages` | `(telegram_id, matched_keyword)` | **کد PHP** |
| `purge.php` | `filtered_messages` | `(telegram_id, matched_keyword)` | **کد PHP** |

> ⚠️ **دیگه هیچ UNIQUE KEY ای در دیتابیس نیست.** تمام یونیک بودن در PHP چک می‌شه.

---

## U5. پیامدها و نکات مهم

### ✅ مزایا

- **کنترل کامل** — می‌تونی منطق یونیک رو هر وقت خواستی عوض کنی
- **پیام‌های جدید نمی‌آن نادیده گرفته بشن** — چون INSERT IGNORE حذف شد
- **انعطاف** — می‌شه ترکیب‌های جدید (مثل `(chat_id, telegram_id)`) رو راحت اضافه کرد

### ⚠️ معایب

- **کندتر** — قبل از هر INSERT، یه SELECT لازمه
- **بار کد بیشتر** — منطق تکراری توی ۳ فایل تکرار شده
- **ریسک race condition** — اگه دو ورکر همزمان اجرا شن، ممکنه هر دو یه پیام رو درج کنن (ولی `flock` ورکر رو قفل می‌کنه، پس در عمل مشکلی نیست)

### 🔒 نکته امنیتی

- **`worker.php`** — با `flock` قفل می‌شه، پس race condition نداره
- **`filter.php`** — اگه همزمان دو بار صدا زده بشه، ممکنه تکراری درست بشه. برای جلوگیری، می‌تونی یه `flock` هم روی `filter.php` بذاری

---

## U6. کوئری‌های مفید

### چک تکراری‌ها

```sql
-- messages
SELECT telegram_id, COUNT(*) c
FROM messages
GROUP BY telegram_id
HAVING c > 1;

-- filtered_messages
SELECT telegram_id, matched_keyword, COUNT(*) c
FROM filtered_messages
GROUP BY telegram_id, matched_keyword
HAVING c > 1;
```

### پاک کردن تکراری‌ها

```sql
-- messages (نگه‌داشتن قدیمی‌ترین)
DELETE m1 FROM `messages` m1
INNER JOIN `messages` m2
   ON m1.`telegram_id` = m2.`telegram_id`
  AND m1.`id` > m2.`id`;

-- filtered_messages
DELETE f1 FROM `filtered_messages` f1
INNER JOIN `filtered_messages` f2
   ON f1.`telegram_id`      = f2.`telegram_id`
  AND f1.`matched_keyword`  = f2.`matched_keyword`
  AND f1.`id` > f2.`id`;
```

### چک نهایی (باید خالی باشه)

```sql
SELECT TABLE_NAME, INDEX_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mahyarsa_telegram'
  AND NON_UNIQUE = 0
  AND INDEX_NAME != 'PRIMARY';
```

---

## U7. چک‌لیست به‌روزرسانی از 1.1 به 1.2

- [ ] SQL حذف UNIQUE ها رو اجرا کن
- [ ] تأیید کن خروجی کوئری چک خالی باشه (فقط PRIMARY)
- [ ] `src/Database.php` رو با نسخه چک‌دستی جایگزین کن
- [ ] `filter.php` رو با نسخه چک‌دستی + باگ‌فیکس state جایگزین کن
- [ ] `purge.php` رو با نسخه چک‌دستی جایگزین کن
- [ ] تکراری‌های موجود رو پاک کن (کوئری بالا)
- [ ] `state` رو ریست کن: `rm storage/filter_state.json`
- [ ] تست با curl (دو بار با یه پیام) → COUNT باید 1 بمونه

---

## U8. مقایسه نسخه‌ها

| ویژگی | 1.0 | 1.1 | 1.2 |
|-------|:---:|:---:|:---:|
| UNIQUE در DB | ✅ | ✅ | ❌ |
| چک در کد | ❌ | ❌ | ✅ |
| `INSERT IGNORE` | ✅ | ✅ | ❌ |
| `ON DUPLICATE KEY UPDATE` | ✅ | ✅ | ❌ |
| محافظت state | ❌ | ❌ | ✅ |
| فیلد `sender_name`/`group_name` | ❌ | ✅ | ✅ |
| State resume | ❌ | ✅ | ✅ |

---

## U9. مستندات به‌روز شده دیتابیس

### جدول `messages`

| ستون | نوع | نکته |
|------|-----|------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY |
| `telegram_id` | BIGINT | **یونیک در کد** (نه DB) |
| `chat_id` | BIGINT | ایندکس معمولی |
| `group_name` | VARCHAR(190) | ایندکس معمولی |
| `sender_id` | BIGINT | — |
| `sender_name` | VARCHAR(190) | — |
| `text` | TEXT | — |
| `link` | VARCHAR(512) | — |
| `date` | BIGINT | — |
| `date_human` | VARCHAR(32) | — |
| `created_at` | DATETIME | پیش‌فرض CURRENT_TIMESTAMP |

### جدول `filtered_messages`

| ستون | نوع | نکته |
|------|-----|------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY |
| `message_id` | BIGINT UNSIGNED | ایندکس معمولی |
| `telegram_id` | BIGINT | **یونیک در کد با `matched_keyword`** |
| `chat_id` | BIGINT | ایندکس معمولی |
| `group_name` | VARCHAR(190) | — |
| `sender_id` | BIGINT | — |
| `sender_name` | VARCHAR(190) | — |
| `text` | TEXT | — |
| `link` | VARCHAR(512) | — |
| `date` | BIGINT | — |
| `date_human` | VARCHAR(32) | — |
| `matched_keyword` | VARCHAR(190) | ایندکس معمولی |
| `click_count` | INT UNSIGNED | پیش‌فرض 0 |
| `original_created_at` | DATETIME | — |
| `filtered_at` | DATETIME | پیش‌فرض CURRENT_TIMESTAMP |

---

**نسخه 1.2 — تاریخ: 2026-10-11**
**موضوع اصلی:** انتقال یونیک بودن از دیتابیس به کد PHP
```

---

این بلاک رو به آخر `AGENT.md` (بعد از نسخه 1.1) اضافه کن. کل تغییرات این session رو پوشش می‌ده 🚀

v 1.0.1 released

Real v 0.0.1 released