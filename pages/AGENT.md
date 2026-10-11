# AGENT.md — مستندات کامل فرانت‌اند پروژه ماهیار

```markdown
# AGENT.md — راهنمای فرانت‌اند پنل ماهیار

> **نسخه:** ۱.۱
> **دامنه:** فقط لایه فرانت‌اند (پوشه `pages/`)
> **مخاطب:** توسعه‌دهنده، طراح، یا ادمین سیستم
> **آخرین به‌روزرسانی:** ۱۴۰۵

---

## فهرست

1. [نمای کلی](#۱-نمای-کلی)
2. [تکنولوژی‌های استفاده‌شده](#۲-تکنولوژیهای-استفادهشده)
3. [ساختار پوشه‌ها و فایل‌ها](#۳-ساختار-پوشهها-و-فایلها)
4. [معماری Layout](#۴-معماری-layout)
5. [سیستم طراحی (Design System)](#۵-سیستم-طراحی-design-system)
6. [کامپوننت‌ها](#۶-کامپوننتها)
7. [صفحات پروژه](#۷-صفحات-پروژه)
8. [الگوهای جاوااسکریپت](#۸-الگوهای-جاوااسکریپت)
9. [الگوهای CSS](#۹-الگوهای-css)
10. [واکنش‌گرایی (Responsive)](#۱۰-واکنشگرایی-responsive)
11. [پشتیبانی RTL](#۱۱-پشتیبانی-rtl)
12. [دسترس‌پذیری (Accessibility)](#۱۲-دسترسپذیری-accessibility)
13. [توسعه و افزودن قابلیت جدید](#۱۳-توسعه-و-افزودن-قابلیت-جدید)
14. [دیباگ و عیب‌یابی](#۱۴-دیباگ-و-عیبیابی)
15. [چک‌لیست راه‌اندازی](#۱۵-چکلیست-راهاندازی)

---

## ۱. نمای کلی

پنل فرانت‌اند ماهیار یک **رابط کاربری مدیریتی تحت وب** است که برای مدیریت پیام‌های تلگرام طراحی شده. کل لایه فرانت **بدون هیچ فریم‌ورک یا کتابخانه جانبی JS** پیاده‌سازی شده و به‌طور کامل به HTML خالص، CSS خالص و JavaScript ES6+ وابسته است.

### ویژگی‌های کلیدی

- **بدون Build Step:** بدون npm، webpack، vite، یا composer. فایل‌ها را روی هاست کپی می‌کنید و کار می‌کند.
- **معماری روتری:** یک فایل `index.php` نقش روتر را دارد و بقیه صفحات از طریق `?page=xxx` بارگذاری می‌شوند.
- **AJAX-first:** تمام تعاملات داده‌ای (لیست، جستجو، تغییر وضعیت) از طریق `fetch` و endpointهای JSON انجام می‌شود.
- **کاملاً RTL:** طراحی از راست به چپ با پشتیبانی کامل از فارسی.
- **واکنش‌گرا:** رابط در موبایل، تبلت و دسکتاپ به‌درستی کار می‌کند.
- **Design System یکپارچه:** رنگ‌ها، فاصله‌ها، سایه‌ها و انیمیشن‌ها همه از یک مجموعه متغیر استفاده می‌کنند.

### مخاطب هدف پنل

مدیر سیستم یا تحلیلگری که می‌خواهد:
- پیام‌های تلگرام را از گروه‌های مختلف ببیند
- پیام‌های مرتبط با کلمات کلیدی را فیلتر کند
- پیام‌های غیرمرتبط را علامت بزند
- چت‌های مزاحم را مسدود کند
- کلمات کلیدی را مدیریت کند

---

## ۲. تکنولوژی‌های استفاده‌شده

| لایه | تکنولوژی | نسخه | نقش |
|---|---|---|---|
| ساختار | HTML5 | — | سمانتیک، دسترس‌پذیری |
| ظاهر | CSS3 | — | Flexbox، Grid، Custom Properties، Transitions |
| رفتار | Vanilla JavaScript | ES6+ | Fetch API, URL, FormData, classList |
| روتر | PHP | 8.1+ | `index.php` با `?page=` |
| قالب | PHP Include | — | `head.php`, `foot.php`, `sidebar.php` |
| آیکون | Font Awesome | 6.5 (CDN) | همه آیکون‌ها |
| فونت | Segoe UI / Tahoma | سیستمی | بدون فونت خارجی |

### نکات مهم

- **هیچ فریم‌ورک JS** مثل React/Vue/jQuery استفاده نشده.
- **هیچ CSS Framework** مثل Bootstrap/Tailwind استفاده نشده.
- **هیچ کتابخانه کامپوننت** مثل SweetAlert یا DataTables استفاده نشده — همه دستی.
- **هیچ پروسه بیلد** لازم نیست.

---

## ۳. ساختار پوشه‌ها و فایل‌ها

```
pages/
│
├── config.php              ← کپی کانفیگ اصلی (فقط خواندنی)
├── db.php                  ← تابع db(): PDO (singleton)
│
│   ─── قالب و Layout ───
├── head.php                ← <head> + کل CSS + شروع <body>
├── foot.php                ← بستن layout + بستن </body>
├── sidebar.php             ← منوی کنار + تابع render_sidebar()
├── index.php               ← روتر اصلی
│
│   ─── صفحات (Views) ───
├── view-dashboard.php      ← داشبورد (placeholder)
├── view-messages.php       ← همه پیام‌ها + modal chat_id + refresh
├── view-filtered.php       ← پیام‌های فیلترشده + ۳ تب + ۳ دکمه
├── view-filters.php        ← مدیریت کلمات کلیدی
├── view-settings.php       ← تنظیمات (مسدودسازی چت)
│
│   ─── APIها (JSON) ───
└── api/
    ├── messages.php        ← لیست پیام‌های خام
    ├── filtered.php        ← لیست + toggle viewed/irrelevant
    ├── filters.php         ← CRUD کلمات کلیدی
    ├── settings.php        ← CRUD چت‌های مسدود
    ├── refresh.php         ← اجرای worker + filter
    └── runner.php          ← internal HTTP runner
```

### نقش هر فایل قالب

| فایل | مسئولیت | نکته |
|---|---|---|
| `head.php` | `<html>`, `<head>`, **تمام CSS درون‌خطی** | CSS سراسری اینجاست |
| `foot.php` | بستن `</div>`, `</body>`, `</html>` | فقط ساختاری |
| `sidebar.php` | تابع `render_sidebar($current)` + `<aside class="sidebar">` | آیتم‌ها در آرایه `$items` |
| `index.php` | تعیین `$page`, رندر head + sidebar + view + foot | لیست سفید `$allowed` |

### چرا CSS متمرکز در `head.php`؟

- **بدون build step:** نیازی به فایل `.css` جدا نیست.
- **کش یک‌جا:** یک بار لود می‌شود برای همه صفحات.
- **متغیرهای سراسری:** `:root` و `--sb-*` همه صفحات را پوشش می‌دهد.
- **در صورت نیاز:** می‌توان بعداً به فایل جدا منتقل کرد.

---

## ۴. معماری Layout

### ساختار HTML کلی

```html
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>...</title>
    <link rel="stylesheet" href=".../font-awesome.css">
    <style>/* تمام CSS */</style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">...</aside>
        <main class="content">
            <!-- محتوای view-xxx.php اینجا می‌آید -->
        </main>
    </div>
</body>
</html>
```

### جریان رندر

```
index.php
    │
    ├─ تعیین $page از ?page=
    ├─ چک در لیست سفید $allowed
    │
    ├─ require head.php      → باز شدن layout
    ├─ require sidebar.php
    ├─ render_sidebar($page) → رندر منو با آیتم فعال
    ├─ echo '<main class="content">'
    ├─ require view-{$page}.php → محتوای صفحه
    ├─ echo '</main>'
    │
    └─ require foot.php      → بستن layout
```

### Layout Container

```css
.layout {
    display: flex;
    min-height: 100vh;
}
.sidebar {
    width: 260px;
    flex-shrink: 0;
}
.content {
    flex: 1;
    padding: 28px 32px;
    overflow-x: hidden;
}
```

**نکته:** `dir="rtl"` روی `<html>` باعث می‌شود sidebar به‌طور طبیعی **سمت راست** قرار بگیرد.

---

## ۵. سیستم طراحی (Design System)

### ۵.۱. پالت رنگ

| نقش | کد | کاربرد |
|---|---|---|
| **Primary** | `#4c8bf5` | دکمه‌ها، لینک‌ها، آیکون‌های فعال |
| **Primary Dark** | `#2f6bdc` | hover، سایه گرادیانت |
| **Primary Gradient** | `linear-gradient(135deg, #4c8bf5, #2f6bdc)` | سایدبار، دکمه refresh |
| **Success** | `#10b981` | دکمه سبز (viewed) |
| **Success Dark** | `#059669` | state فعال |
| **Danger** | `#ef4444` | دکمه قرمز (irrelevant) |
| **Danger Dark** | `#b91c1c` | state فعال |
| **Warning** | `#f59e0b` | (رزرو) |
| **Background** | `#f1f5f9` | پس‌زمینه body |
| **Surface** | `#ffffff` | کارت‌ها، جدول‌ها، modal |
| **Border** | `#e2e8f0` | حاشیه‌ها |
| **Border Light** | `#f1f5f9` | خط جداکننده ردیف |
| **Text Primary** | `#1e293b` | متن اصلی |
| **Text Secondary** | `#475569` | متن ثانویه |
| **Text Muted** | `#94a3b8` | placeholder، خط تیره |
| **Text On Primary** | `#ffffff` | متن روی سایدبار و دکمه‌ها |

### ۵.۲. رنگ Badgeها (Semantic)

| Badge | پس‌زمینه | متن | کاربرد |
|---|---|---|---|
| کلمه کلیدی | `#eef4ff` | `#2f6bdc` | کلمه‌ای که تطبیق یافته |
| نام گروه | `#f0f9ff` | `#0369a1` | group_name |
| نام فرستنده | `#f5f3ff` | `#6d28d9` | sender_name |
| chat_id | `#fdf2f8` | `#be185d` | monospace در modal |

### ۵.۳. تایپوگرافی

```css
font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
```

| نقش | اندازه | وزن |
|---|---|---|
| عنوان صفحه (h1) | `1.3rem` | `700` |
| عنوان کارت (h2) | `1rem` | `700` |
| متن پایه | `0.87rem` | `400` |
| متن badge | `0.75rem` | `700` |
| متن hint | `0.72rem` | `400` |
| متن جدول header | `0.8rem` | `700` |

### ۵.۴. فاصله‌ها (Spacing)

مقیاس:

| نام | مقدار |
|---|---|
| xs | `4px` |
| sm | `8px` |
| md | `14px` |
| lg | `20px` |
| xl | `28px` |

Padding داخلی کارت‌ها: `20px` یا `24px`.

### ۵.۵. گوشه‌ها (Border Radius)

| المان | شعاع |
|---|---|
| دکمه | `8px` یا `10px` |
| ورودی | `10px` |
| کارت | `14px` |
| Badge | `6px` |
| Modal | `14px` |
| آواتار/آیکون دایره‌ای | `50%` |

### ۵.۶. سایه‌ها (Shadows)

```css
/* کارت معمولی */
box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);

/* دکمه primary */
box-shadow: 0 3px 12px rgba(76, 139, 245, 0.28);

/* Modal */
box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);

/* Toast */
box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
```

### ۵.۷. انیمیشن‌ها

| مورد | مدت | easing |
|---|---|---|
| hover روی دکمه | `0.2s` | `ease` |
| transition رنگ | `0.25s` | `ease` |
| Modal باز/بسته | `0.2s` | `cubic-bezier(.25,1,.5,1)` |
| Spinner چرخش | `1s` | `linear infinite` |
| Toast | `0.3s` | `ease` |
| سایدبار collapse | `0.9s` | `cubic-bezier(.4,0,.2,1)` |

### ۵.۸. متغیرهای CSS

در `:root` تعریف شده:

```css
--sb-width: 260px;                   /* عرض سایدبار */
--sb-width-collapsed: 72px;          /* سایدبار جمع‌شده */
--sb-duration: 0.9s;
--sb-easing: cubic-bezier(0.4, 0, 0.2, 1);
--sb-text-duration-in: 0.35s;
--sb-text-delay-in: 0.6s;
--sb-submenu-duration: 0.4s;
--sb-submenu-easing: cubic-bezier(0.65, 0, 0.35, 1);
```

---

## ۶. کامپوننت‌ها

### ۶.۱. سایدبار (`.sidebar`)

**مسئولیت:** ناوبری اصلی

**ساختار:**
```html
<aside class="sidebar">
    <div class="sidebar-scroll">
        <div class="sidebar-brand">
            <i class="fas fa-paper-plane"></i>
            <span>ماهیار</span>
        </div>
        <a class="menu-item" href="?page=xxx">
            <i class="fas fa-xxx"></i>
            <span>عنوان</span>
        </a>
        <!-- ... آیتم‌های بیشتر -->
    </div>
</aside>
```

**حالت‌ها:**
- `menu-item` — پیش‌فرض
- `menu-item.active` — صفحه جاری (پس‌زمینه روشن‌تر)
- `menu-item:hover` — پس‌زمینه `rgba(255,255,255,0.14)`

**آرایه آیتم‌ها در `sidebar.php`:**

```php
$items = [
    ['key'=>'dashboard','label'=>'داشبورد',           'icon'=>'fa-th-large', 'href'=>'?page=dashboard'],
    ['key'=>'messages', 'label'=>'پیام‌ها',            'icon'=>'fa-comments', 'href'=>'?page=messages'],
    ['key'=>'filtered', 'label'=>'پیام‌های فیلترشده', 'icon'=>'fa-filter',   'href'=>'?page=filtered'],
    ['key'=>'filters',  'label'=>'فیلترها',           'icon'=>'fa-tags',     'href'=>'?page=filters'],
    ['key'=>'settings', 'label'=>'تنظیمات',           'icon'=>'fa-cog',      'href'=>'?page=settings'],
];
```

**افزودن آیتم جدید:**
فقط یک ردیف به آرایه + کلید در `$allowed` و `$titles` در `index.php`.

**رنگ‌بندی:** گرادیانت `135deg, #4c8bf5 → #2f6bdc`.

---

### ۶.۲. کارت (`.table-wrap` / `.settings-card`)

**مسئولیت:** محفظه سفیدرنگ برای جدول یا محتوا

```html
<div class="table-wrap">
    <table class="data-table">...</table>
</div>
```

```css
.table-wrap {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}
```

**انواع:**
- `table-wrap` — برای جدول (padding صفر)
- `table-wrap` با `style="padding:20px"` — برای فرم و محتوای عادی
- `settings-card` — کارت با header مشخص (صفحه تنظیمات)

---

### ۶.۳. جدول داده (`.data-table`)

**مسئولیت:** نمایش لیست پیام‌ها و کلمات کلیدی

**ساختار:**
```html
<table class="data-table">
    <thead>
        <tr>
            <th>ستون ۱</th>
            <th>ستون ۲</th>
        </tr>
    </thead>
    <tbody id="rowsBody">
        <tr><td colspan="N" class="loading">در حال بارگذاری...</td></tr>
    </tbody>
</table>
```

**حالت‌های `tbody`:**

| کلاس | کاربرد |
|---|---|
| `.loading` | در حال بارگذاری (خاکستری، وسط‌چین) |
| `.empty` | داده‌ای یافت نشد |
| `.error` | خطای سرور (قرمز) |

**استایل:**
- header: `background: #f8fafc`, `color: #64748b`
- ردیف‌ها: حاشیه زیرین `#f1f5f9`
- hover روی ردیف: `background: #f8fafc`

---

### ۶.۴. دکمه‌ها

| کلاس | رنگ | استفاده |
|---|---|---|
| `.btn-view` | آبی `#4c8bf5` | دکمه اصلی (مشاهده در `view-messages`) |
| `.btn-primary` | گرادیانت آبی | افزودن در فرم‌ها |
| `.btn-refresh` | گرادیانت + سایه | دکمه «به‌روزرسانی پیام‌ها» |
| `.btn-action.blue` | آبی | دکمه مشاهده (لینک) |
| `.btn-action.green` | سبز `#10b981` | دکمه viewed |
| `.btn-action.red` | قرمز `#ef4444` | دکمه irrelevant |
| `.btn-trash` | قرمز روشن + آیکون | حذف در جدول کوچک |
| `.btn-copy` | آبی | کپی در modal |

**حالت‌ها:**
- `:hover` → `opacity: 0.9`
- `:active` → `transform: scale(0.96)`
- `:disabled` / `.disabled` → خاکستری، `cursor: not-allowed`
- `.is-active` — برای دکمه‌های toggle سبز/قرمز

---

### ۶.۵. Badgeها

```html
<span class="badge-keyword">رشد</span>       <!-- کلمه کلیدی -->
<span class="badge-group">گروه سیگنال</span> <!-- نام گروه -->
<span class="badge-sender">علی</span>        <!-- نام فرستنده -->
<span class="badge-clicks">۵</span>          <!-- تعداد کلیک -->
```

**نکته:** `.badge-group` می‌تواند به‌صورت `<button>` هم باشد (برای باز شدن modal) و آن‌موقع `cursor: pointer` و hover خاص دارد.

---

### ۶.۶. تب‌ها (`.tabs`)

فقط در `view-filtered.php`.

**ساختار:**
```html
<div class="tabs">
    <button class="tab active" data-tab="all">
        همه <span class="count" id="cnt-all">0</span>
    </button>
    <button class="tab" data-tab="viewed">...</button>
    <button class="tab" data-tab="irrelevant">...</button>
</div>
```

**رفتار:**
- فقط یکی در لحظه `.active` است.
- کلیک → تغییر `state.tab` + `load(1)`.
- `data-tab` به مقدار `tab` در API map می‌شود.

**استایل:**
- خط زیر تب فعال: `border-bottom: 2px solid #4c8bf5`
- badge تعداد تب فعال: رنگ آبی روشن‌تر

---

### ۶.۷. فرم‌ها

**ساختار استاندارد:**

```html
<form id="someForm" class="block-form">
    <div class="field">
        <label for="inputId">عنوان <span class="req">*</span></label>
        <input type="text" id="inputId" name="inputName">
        <small class="hint">توضیح کمکی</small>
    </div>
    <button type="submit" class="btn-primary">
        <i class="fas fa-plus"></i>
        افزودن
    </button>
</form>
```

**ورودی‌ها:**
```css
input, select, textarea {
    padding: 11px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-family: inherit;
    font-size: 0.87rem;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}
input:focus {
    border-color: #4c8bf5;
    box-shadow: 0 0 0 3px rgba(76, 139, 245, 0.15);
}
```

**جستجو (`.search-box`):**
آیکون `fa-search` در سمت راست با `position: absolute`.

---

### ۶.۸. Modal (`.modal-overlay`)

فقط در `view-messages.php` (نمایش `chat_id`).

**ساختار:**
```html
<div class="modal-overlay" id="chatModal" aria-hidden="true">
    <div class="modal-box" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3>...</h3>
            <button class="modal-close" id="modalClose">...</button>
        </div>
        <div class="modal-body">
            <!-- محتوا -->
        </div>
    </div>
</div>
```

**حالت‌ها:**
- `.modal-overlay` — پیش‌فرض: مخفی (`opacity: 0`, `visibility: hidden`, `pointer-events: none`)
- `.modal-overlay.is-open` — باز: `opacity: 1`, `visibility: visible`, `pointer-events: auto`

**بستن Modal:**
- کلیک روی ✕
- کلیک روی overlay (نه روی box)
- کلید `Esc`
- روی `body`: `overflow: hidden` هنگام باز بودن

**دسترس‌پذیری:** `role="dialog"`, `aria-modal="true"`, `aria-hidden`.

---

### ۶.۹. Toast (`.toast`)

**مسئولیت:** نمایش پیام موفقیت/خطا در پایین صفحه.

```html
<div class="toast" id="toast"></div>
```

```css
.toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    opacity: 0;
    transition: opacity .3s, transform .3s;
    pointer-events: none;
}
.toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.toast.success { background: #059669; }
.toast.error   { background: #dc2626; }
```

**API تابع:**
```js
toast('متن پیام', 'success' | 'error');
```

**رفتار:** به‌طور خودکار بعد از ۲۵۰۰ میلی‌ثانیه محو می‌شود.

---

### ۶.۱۰. Pagination (`.pagination`)

**ساختار:**
```html
<div class="pagination" id="pagination"></div>
```

**تولید در JS:** دکمه‌های قبلی/بعدی + ۵ شماره اطراف صفحه جاری.

**استایل:**
```css
.page-btn {
    min-width: 38px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 8px;
}
.page-btn.active {
    background: #4c8bf5;
    color: #fff;
}
```

---

### ۶.۱۱. Alert Box (`.alert`)

فقط در صفحه تنظیمات برای نمایش خطا/موفقیت فرم.

```html
<div class="alert" id="alertBox" style="display:none"></div>
```

**API:**
```js
showAlert('متن', 'success' | 'error');
```

**رفتار:** پس‌زمینه سبز یا قرمز + حذف خودکار پس از ۳ ثانیه.

---

## ۷. صفحات پروژه

### ۷.۱. داشبورد (`view-dashboard.php`)

**وضعیت:** Placeholder — آماده افزودن آمار.

**شامل:**
- عنوان «داشبورد»
- پیام «آماده اتصال به داده‌های آماری»

**توسعه:** برای افزودن کارت‌های آماری (تعداد پیام امروز، تعداد فیلترشده، ...)، یک `api/stats.php` بسازید و در این صفحه `fetch` بزنید.

---

### ۷.۲. پیام‌ها (`view-messages.php`)

**هدف:** نمایش همه پیام‌های خام جدول `messages`.

**اجزای صفحه:**

| المان | توضیح |
|---|---|
| عنوان | «پیام‌های دریافتی» |
| دکمه refresh | `<button id="refreshBtn">` — فقط `worker.php` را اجرا می‌کند |
| جستجو | روی متن، فرستنده، گروه |
| جدول | ۶ ستون: `#`, گروه, فرستنده, متن, تاریخ, عملیات |
| Modal | کلیک روی badge نام گروه → نمایش `chat_id` |

**ستون‌های جدول:**

| ستون | کلاس | رفتار |
|---|---|---|
| `#` | — | شناسه ردیف |
| گروه | `.badge-group` (button) | کلیک → Modal |
| فرستنده | `.badge-sender` | ساده |
| متن | `.cell-text` | کوتاه‌شده با tooltip |
| تاریخ | — | `date_human` |
| عملیات | `.btn-view` | باز کردن لینک تلگرام یا disabled |

**API:** `api/messages.php?page=N&q=word`

**State محلی:**
```js
let currentQuery = '';
```

**رویدادها:**
- `submit` روی فرم جستجو (با debounce 350ms)
- `click` روی `.page-btn` → `load(page)`
- `click` روی `.badge-group` → باز کردن Modal
- `click` روی دکمه refresh → `api/refresh.php?job=worker`
- `Esc` → بستن Modal

**Modal:**
- نام گروه + `chat_id` (monospace)
- دکمه کپی (با `navigator.clipboard` + fallback `execCommand`)

---

### ۷.۳. پیام‌های فیلترشده (`view-filtered.php`)

**هدف:** قلب پنل. مدیریت پیام‌هایی که با کلمات کلیدی تطبیق یافته‌اند.

**اجزای صفحه:**

| المان | توضیح |
|---|---|
| عنوان | «پیام‌های فیلترشده» |
| دکمه refresh | `<button id="refreshBtn">` — worker + filter (job=all) |
| جستجو | روی متن، کلمه، فرستنده، گروه |
| dropdown فیلتر | انتخاب کلمه کلیدی |
| تب‌ها | همه / مشاهده‌شده / غیرمرتبط |
| جدول | ۷ ستون: `#`, کلمه, گروه, فرستنده, متن, تاریخ, عملیات |
| دکمه‌های عملیات | سبز (viewed)، آبی (مشاهده لینک + count)، قرمز (irrelevant) |

**ستون‌های جدول:**

| ستون | نوع | نکته |
|---|---|---|
| `#` | عدد | fm.id |
| کلمه | `.badge-keyword` | matched_keyword |
| گروه | `.badge-group` | از JOIN با messages |
| فرستنده | `.badge-sender` | از JOIN با messages |
| متن | `.cell-text` | ۷۰ کاراکتر |
| تاریخ | — | date_human |
| عملیات | ۳ دکمه | ترتیب: سبز، آبی، قرمز |

**State محلی:**
```js
const state = { tab: 'all', q: '', keyword: '', page: 1 };
```

**تب‌ها:**
- `all` — فقط چت‌های غیرمسدود که نه viewed و نه irrelevant
- `viewed` — فقط در `viewed_messages`
- `irrelevant` — فقط در `irrelevant_messages`

**دکمه آبی (مشاهده):**
- اگر `link` پر باشد → `<a href=link target="_blank">` + `data-action="click"`
- کلیک → `POST {action: increment_click, id}` با `keepalive: true` + باز شدن لینک
- count داخل badge به‌صورت زنده به‌روز می‌شود

**دکمه سبز (viewed toggle):**
- `POST {action: toggle_viewed, id}`
- پاسخ شامل `is_viewed` جدید + `counts` به‌روز
- ری‌لود لیست

**دکمه قرمز (irrelevant toggle):**
- مشابه سبز، با `toggle_irrelevant`

**API:** `api/filtered.php`
- `GET ?action=list&tab=...&q=...&keyword=...&page=N`
- `POST {action: increment_click|toggle_viewed|toggle_irrelevant, id}`

---

### ۷.۴. فیلترها (`view-filters.php`)

**هدف:** CRUD کلمات کلیدی.

**اجزای صفحه:**

| المان | توضیح |
|---|---|
| فرم | ورودی کلمه + دکمه «افزودن» |
| alert | نمایش موفقیت/خطا |
| جدول | ۴ ستون: `#`, کلمه, تاریخ, حذف |

**API:** `api/filters.php`
- `GET ?action=list`
- `POST {action: add, keyword}`
- `POST {action: delete, id}`

**رفتار:**
- Enter در ورودی → submit فرم
- کلمه تکراری → خطای `23000` → پیام «این کلمه قبلاً ثبت شده است»
- حذف با `confirm()`

---

### ۷.۵. تنظیمات (`view-settings.php`)

**هدف:** تنظیمات پنل. اولین تنظیم = «مسدودسازی چت».

**اجزای صفحه:**

| المان | توضیح |
|---|---|
| عنوان | «تنظیمات» |
| کارت | `.settings-card` با `header` و `body` |
| فرم | ورودی `chat_id` (اجباری) + نام گروه (اختیاری) + دکمه افزودن |
| alert | نمایش موفقیت/خطا |
| جدول | ۵ ستون: `#`, chat_id, نام گروه, تاریخ, حذف |

**API:** `api/settings.php`
- `GET ?action=list`
- `POST {action: add, chat_id, group_name}`
- `POST {action: delete, id}`

**اعتبارسنجی:**
- `chat_id` باید با regex `/^-?\d+$/` مطابقت کند
- طول حداکثر ۲۰ رقم (بدون منفی)
- نام گروه اختیاری، حداکثر ۱۹۰ کاراکتر

**اثر مسدودسازی:**
- پیام‌های آن چت در **همه صفحات** مخفی می‌شوند (list, filtered, search)
- اما در دیتابیس باقی می‌مانند
- با حذف از لیست مسدود → فوراً برمی‌گردند

---

## ۸. الگوهای جاوااسکریپت

### ۸.۱. IIFE برای جلوگیری از نشتی

هر `view-*.php` یک IIFE دارد:

```js
(function () {
    // همه کد داخل این closure
})();
```

**دلیل:** متغیرهای محلی با صفحات دیگر یا اسکریپت‌های سراسری تداخل نکنند.

---

### ۸.۲. State مرکزی

الگوی استاندارد:

```js
const state = {
    tab: 'all',
    q: '',
    keyword: '',
    page: 1
};
```

**دلیل:** یک منبع حقیقت برای query params.

---

### ۸.۳. تابع `load(page)`

هر صفحه یک تابع مرکزی `load()` دارد:

```js
function load(page = 1) {
    state.page = page;
    tbody.innerHTML = '<tr><td colspan="N" class="loading">...</td></tr>';
    
    const url = new URL(ENDPOINT, location.href);
    url.searchParams.set('action', 'list');
    url.searchParams.set('page', page);
    if (state.q) url.searchParams.set('q', state.q);
    
    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            render(data.items);
            renderPagination(data.page, data.pages);
        })
        .catch(err => showError(err.message));
}
```

**نکات:**
- `return` می‌دهد تا بتوان زنجیره کرد
- `X-Requested-With` هدر استاندارد AJAX
- هندل خطا با نمایش در `tbody`

---

### ۸.۴. Debounce برای جستجو

```js
let searchTimer = null;
searchBox.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentQuery = searchBox.value.trim();
        load(1);
    }, 350);
});
```

**دلیل:** جلوگیری از ارسال درخواست برای هر کاراکتر.

---

### ۸.۵. Event Delegation

روی `tbody` که محتوایش داینامیک است:

```js
tbody.addEventListener('click', e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    
    const action = btn.dataset.action;
    const id = parseInt(btn.dataset.id, 10);
    // ...
});
```

**دلیل:** چون ردیف‌ها بعداً رندر می‌شوند، listener مستقیم جواب نمی‌دهد.

---

### ۸.۶. Escape کردن HTML

```js
const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g,
    c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
```

**دلیل:** جلوگیری از XSS در متن‌های ورودی کاربر.

**همیشه** قبل از درج در `innerHTML` استفاده شود.

---

### ۸.۷. Template Literal برای رندر

```js
tbody.innerHTML = items.map(m => `
    <tr data-id="${m.id}">
        <td>${escapeHtml(m.text)}</td>
    </tr>
`).join('');
```

**نکته:** `.join('')` حذف کاما بین آیتم‌ها.

---

### ۸.۸. Helper برای POST با FormData

```js
function postAction(action, id, onSuccess) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id', id);
    
    fetch(ENDPOINT, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(d => {
        if (d.status !== 'success') throw new Error(d.message || 'خطا');
        if (d.counts) renderCounts(d.counts);
        onSuccess && onSuccess(d);
    })
    .catch(err => toast(err.message, 'error'));
}
```

---

### ۸.۹. الگوی `keepalive` برای کلیک روی لینک

```js
if (action === 'click') {
    const fd = new FormData();
    fd.append('action', 'increment_click');
    fd.append('id', id);
    
    fetch(ENDPOINT, {
        method: 'POST',
        body: fd,
        keepalive: true,     // ⭐ درخواست در پس‌زمینه ادامه پیدا می‌کند
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(d => {
        const badge = btn.querySelector('.badge-clicks');
        if (badge) badge.textContent = d.click_count;
    })
    .catch(() => {});
    
    return; // ← لینک به‌طور طبیعی باز می‌شود
}
```

**نکته:** `keepalive: true` اجازه می‌دهد درخواست حتی وقتی تب در حال بسته شدن یا لینک در حال باز شدن است تکمیل شود.

---

### ۸.۱۰. Toast Helper

```js
function toast(msg, type = 'success') {
    toastBox.textContent = msg;
    toastBox.className = 'toast show ' + type;
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toastBox.className = 'toast ' + type, 2800);
}
```

**نکته:** ذخیره timer روی خود تابع (`toast._t`) — به‌جای متغیر global.

---

### ۸.۱۱. Modal Helper

```js
function openModal(groupName, chatId) {
    modalName.textContent = groupName || '—';
    modalId.textContent = chatId || '—';
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';  // ⭐ جلوگیری از اسکرول پشت
}

function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}
```

**بستن با Esc:**
```js
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
});
```

---

### ۸.۱۲. کپی به کلیپ‌بورد

```js
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }
    // fallback
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    return Promise.resolve();
}
```

**نکته:** `navigator.clipboard` فقط در HTTPS و secure context کار می‌کند.

---

## ۹. الگوهای CSS

### ۹.۱. نام‌گذاری کلاس‌ها

الگوی عمومی: `component-element-state`

| نمونه | معنی |
|---|---|
| `.menu-item` | کامپوننت |
| `.menu-item.active` | state |
| `.btn-action.green` | variant |
| `.badge-group` | کامپوننت |
| `.modal-overlay.is-open` | state |
| `.btn-refresh:disabled` | pseudo-class |

**نکته:** هیچ از BEM استاندارد (`block__element--modifier`) استفاده نشده — الگوی ساده‌تر `block-element` با hyphen.

### ۹.۲. یکپارچگی در `head.php`

همه CSS پایه در `head.php` قرار دارد. استایل‌های مخصوص هر صفحه در `<style>` داخل همان view قرار دارند. این طراحی باعث می‌شود:

- CSS پایه فقط یک بار لود شود (کش مرورگر).
- CSS صفحه فقط وقتی آن صفحه باز می‌شود لود شود.
- امکان override آسان‌تر.

### ۹.۳. Gradients پرکاربرد

```css
/* سایدبار و دکمه اصلی */
background: linear-gradient(180deg, #4c8bf5 0%, #2f6bdc 100%);

/* دکمه refresh و دکمه افزودن */
background: linear-gradient(135deg, #4c8bf5 0%, #2f6bdc 100%);
```

### ۹.۴. Transitions استاندارد

```css
/* hover ساده */
transition: background-color 0.2s ease, color 0.2s ease;

/* دکمه با مقیاس */
transition: opacity 0.2s, transform 0.1s;
/* و در :active → transform: scale(0.96) */
```

### ۹.۵. Layout با Flexbox

```css
/* جهت افقی، وسط‌چین */
display: flex;
align-items: center;
gap: 10px;

/* فشرده کردن عناصر کنار */
flex-wrap: wrap;
```

### ۹.۶. Grid برای فرم‌های چندستونه

```css
.block-form {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 14px;
    align-items: end;
}
```

**در موبایل:**
```css
@media (max-width: 820px) {
    .block-form { grid-template-columns: 1fr; }
}
```

---

## ۱۰. واکنش‌گرایی (Responsive)

### ۱۰.۱. Breakpoints

| نقطه | کاربرد |
|---|---|
| `max-width: 820px` | فرم‌های چندستونه → تک‌ستونه |
| `max-width: 768px` | سایدبار باریک‌تر، padding کمتر |
| `max-width: 600px` | Modal تمام‌عرض |

### ۱۰.۲. تغییرات در موبایل

```css
@media (max-width: 768px) {
    .sidebar { width: 72px; }
    .sidebar-brand span,
    .menu-item span { display: none; }
    .content { padding: 20px 14px; }
    .cell-text { max-width: 160px; }
}
```

**نتیجه:** سایدبار در موبایل فقط آیکون دارد.

### ۱۰.۳. جدول‌ها در موبایل

جدول‌ها از `overflow-x` در `.table-wrap` استفاده نمی‌کنند؛ به‌جای آن ستون متن با `max-width` محدود می‌شود.

**برای متن‌های بلند:**
```css
.cell-text {
    max-width: 380px;
    color: #475569;
}
```

با `truncate(s, n)` در JS + `title` برای tooltip.

---

## ۱۱. پشتیبانی RTL

### ۱۱.۱. فعال‌سازی

```html
<html lang="fa" dir="rtl">
```

**نتیجه:**
- سایدبار به‌طور طبیعی **سمت راست**
- ترتیب flex از راست به چپ
- `padding-right` و `margin-left` معنی‌های RTL دارند

### ۱۱.۲. استثنائات

برخی المان‌ها نیاز به `direction: ltr` دارند:

| المان | دلیل |
|---|---|
| `chat_id` در modal | عدد و منفی |
| `code` (کد فنی) | monospace English |
| `badge-clicks` | عدد شمارشی |
| `input[type=number]` | عدد خالص |

```css
.info-code {
    direction: ltr;
    text-align: left;
    font-family: 'Courier New', monospace;
}
```

### ۱۱.۳. فونت فارسی

```css
font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
```

**دلیل انتخاب:** Segoe UI در ویندوز، Tahoma در مک و لینوکس، همگی فارسی را به‌خوبی رندر می‌کنند و نیازی به فونت وب جدا نیست.

---

## ۱۲. دسترس‌پذیری (Accessibility)

### ۱۲.۱. آنچه پیاده‌سازی شده

| قابلیت | نحوه |
|---|---|
| زبان سند | `<html lang="fa">` |
| جهت | `<html dir="rtl">` |
| Modal | `role="dialog"`, `aria-modal`, `aria-hidden` |
| بستن با Esc | در `view-messages.php` |
| عنوان دکمه | `title="..."` روی دکمه‌های فقط آیکون |
| برچسب فرم | `<label for="...">` |
| بازخورد وضعیت | `.loading`, `.empty`, `.error` |

### ۱۲.۲. بهبودهای ممکن

- افزودن `aria-label` به همه دکمه‌های آیکون‌دار
- مدیریت `focus trap` در Modal (فعلاً نیست)
- `aria-live="polite"` روی toast
- جداول با `<caption>` (فعلاً نیست)

---

## ۱۳. توسعه و افزودن قابلیت جدید

### ۱۳.۱. افزودن صفحه جدید

**مرحله ۱:** فایل `pages/view-xxx.php` بسازید:
```php
<div class="page-header">
    <h1>عنوان صفحه</h1>
</div>
<div class="table-wrap" style="padding:20px">
    ...
</div>
```

**مرحله ۲:** در `pages/index.php`:
```php
$allowed = [..., 'xxx'];
$titles  = [..., 'xxx' => 'عنوان صفحه'];
```

**مرحله ۳:** در `pages/sidebar.php`:
```php
$items[] = ['key'=>'xxx', 'label'=>'عنوان', 'icon'=>'fa-star', 'href'=>'?page=xxx'];
```

### ۱۳.۲. افزودن API جدید

فایل `pages/api/xxx.php`:
```php
<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../db.php';

try {
    $pdo = db();
    // ...
    echo json_encode(['status' => 'success', 'data' => $result], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
```

### ۱۳.۳. افزودن کامپوننت جدید

**مرحله ۱:** CSS آن را به `head.php` اضافه کنید (اگر سراسری است) یا در `<style>` همان view (اگر محلی است).

**مرحله ۲:** HTML کامپوننت را در همان view درج کنید.

**مرحله ۳:** JS آن را در IIFE همان صفحه پیاده کنید.

### ۱۳.۴. افزودن ستون به جدول

**مرحله ۱:** در API، `SELECT` و `array_map` را آپدیت کنید.

**مرحله ۲:** در view، `<thead>` را با `<th>` جدید گسترش دهید.

**مرحله ۳:** در JS، `renderRows` را برای درج `<td>` جدید آپدیت کنید.

**مرحله ۴:** `colspan` حالت‌های `loading`/`empty`/`error` را یک عدد زیاد کنید.

### ۱۳.۵. تغییر رنگ تم

همه رنگ‌ها در `head.php` تعریف شده‌اند. برای تغییر تم آبی به سبز:

```css
/* قبل */
background: linear-gradient(180deg, #4c8bf5 0%, #2f6bdc 100%);

/* بعد */
background: linear-gradient(180deg, #10b981 0%, #059669 100%);
```

**نکته:** تمام `#4c8bf5` و `#2f6bdc` را باید به رنگ جدید تغییر دهید (Find & Replace).

**بهبود آینده:** انتقال به CSS Variables سراسری:
```css
:root {
    --color-primary: #4c8bf5;
    --color-primary-dark: #2f6bdc;
    --color-success: #10b981;
    --color-danger:  #ef4444;
}
/* سپس استفاده: background: var(--color-primary); */
```

### ۱۳.۶. افزودن صفحه فرعی در سایدبار (Submenu)

سایدبار فعلاً ساده است (بدون زیرمنو). برای افزودن:

**در `sidebar.php`:**
```php
$items = [
    [
        'key' => 'tasks',
        'label' => 'وظایف',
        'icon' => 'fa-tasks',
        'children' => [
            ['key'=>'list', 'label'=>'لیست', 'href'=>'?page=tasks-list'],
            ['key'=>'subjects', 'label'=>'موضوعات', 'href'=>'?page=tasks-subjects'],
        ],
    ],
];
```

**سپس در `render_sidebar` باید logic زیرمنو اضافه شود** — که در پیاده‌سازی فعلی وجود ندارد. اگر نیاز شد، می‌توان الگوی سایدبار قدیمی (با `toggleSidebarGroup`) را احیا کرد.

---

## ۱۴. دیباگ و عیب‌یابی

### ۱۴.۱. ابزارهای مرورگر

| مشکل | ابزار |
|---|---|
| بررسی درخواست AJAX | Network tab → XHR |
| مشاهده CSS اعمال‌شده | Elements tab |
| خطای JS | Console tab |
| Modal یا Toast نامرتب | بررسی `z-index` (Toast: 9999، Modal: 10000) |

### ۱۴.۲. اشتباهات رایج

| مشکل | راه‌حل |
|---|---|
| دکمه کار نمی‌کند | `data-action` یا `data-id` را چک کنید |
| لیست خالی است | Network → پاسخ API را ببینید |
| پاسخ API خالی است | `status` را چک کنید — ممکن است `error` باشد |
| Modal باز نمی‌شود | کلاس `is-open` روی overlay باید اضافه شود |
| Toast نمایش داده نمی‌شود | `z-index` و `pointer-events: none` |
| ستون جدید نمایش نمی‌یابد | `colspan` را در `loading/empty/error` آپدیت کنید |
| صفحه سفید است | Fatal Error در PHP — `error_log` را ببینید |

### ۱۴.۳. بررسی سریع API

در مرورگر باز کنید:
```
http://YOURDOMAIN/pages/api/filtered.php?action=list&tab=all&page=1
```

خروجی JSON باید شامل `status: "success"` و `items[]` باشد.

### ۱۴.۴. پاک کردن کش

بعد از هر تغییر CSS/JS:
- **ویندوز:** `Ctrl + F5`
- **مک:** `Cmd + Shift + R`
- یا DevTools → Network → Disable cache

---

## ۱۵. چک‌لیست راه‌اندازی

### برای توسعه‌دهنده جدید

- [ ] پروژه را روی سرور PHP 8.1+ کپی کنید
- [ ] اطمینان از اجرای `pages/sql/*.sql`
- [ ] تنظیم `pages/config.php` با اطلاعات DB
- [ ] باز کردن `pages/index.php` در مرورگر
- [ ] بررسی console مرورگر برای خطاهای JS
- [ ] بررسی Network برای پاسخ‌های API

### برای اضافه کردن قابلیت

- [ ] فایل `view-xxx.php` جدید
- [ ] افزودن `$allowed` و `$titles` در `index.php`
- [ ] افزودن آیتم در `sidebar.php`
- [ ] API لازم در `pages/api/`
- [ ] تست کامل روی دسکتاپ + موبایل
- [ ] تست RTL (متن فارسی، اعداد، chat_id)
- [ ] بررسی حذف پیام‌ها در چت مسدود (اگر مربوط)

### برای افزودن یک رنگ جدید

- [ ] استفاده از پالت فعلی — از رنگ‌های موجود استفاده کنید
- [ ] اگر لازم شد، در `<style>` موجود اضافه کنید
- [ ] برای تم‌های آینده، در `:root` متغیر تعریف کنید

---

## پیوست: الگوی سریع فایل‌های پروژه

### الگوی یک `view-*.php` استاندارد

```php
<!-- ═══ Header ═══ -->
<div class="page-header">
    <h1>عنوان صفحه</h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <!-- دکمه‌ها، جستجو، فیلترها -->
    </div>
</div>

<!-- ═══ محتوا ═══ -->
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>ستون ۱</th>
                <th>ستون ۲</th>
            </tr>
        </thead>
        <tbody id="rowsBody">
            <tr><td colspan="2" class="loading">در حال بارگذاری...</td></tr>
        </tbody>
    </table>
</div>

<div class="pagination" id="pagination"></div>

<!-- ═══ CSS محلی ═══ -->
<style>
    /* فقط استایل‌های این صفحه */
</style>

<!-- ═══ Toast ═══ -->
<div class="toast" id="toast"></div>

<!-- ═══ JS صفحه ═══ -->
<script>
(function () {
    // state
    const state = { page: 1 };
    
    // helpers (escapeHtml, truncate, toast)
    
    // render functions
    
    // load function
    
    // event listeners
    
    // init
    load(1);
})();
</script>
```

### الگوی یک `api/*.php` استاندارد

```php
<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../db.php';

const TABLE_NAME = 'xxx';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();
    switch ($action) {
        case 'list':   handle_list($pdo);   break;
        case 'create': handle_create($pdo); break;
        case 'delete': handle_delete($pdo); break;
        default:       json_error('اکشن نامعتبر');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

// ---- handlers ----
function handle_list(PDO $pdo): void { /* ... */ json_ok(['items' => $x]); }
function handle_create(PDO $pdo): void { /* ... */ json_ok(['item' => $x]); }
function handle_delete(PDO $pdo): void { /* ... */ json_ok([]); }

// ---- helpers ----
function json_ok(array $d): void {
    echo json_encode(['status' => 'success'] + $d, JSON_UNESCAPED_UNICODE);
    exit;
}
function json_error(string $m, int $c = 400): void {
    http_response_code($c);
    echo json_encode(['status' => 'error', 'message' => $m], JSON_UNESCAPED_UNICODE);
    exit;
}
```

---

**پایان مستندات فرانت‌اند.**

> **نکته نگه‌داری:** اگر کامپوننت جدید یا رنگ جدیدی اضافه کردید، لطفاً این سند را به‌روز کنید. تمام کد در همین سند مرجع یکتاست.
```

---

## خلاصه محتوای این مستند

| بخش | تعداد آیتم‌های پوشش‌داده‌شده |
|---|---|
| تکنولوژی‌ها | ۷ لایه |
| ساختار فایل‌ها | ۱۵+ فایل توضیح داده‌شده |
| کامپوننت‌ها | ۱۱ کامپوننت (Sidebar, Table, Buttons, Badges, Tabs, Forms, Modal, Toast, Pagination, Alert, Cards) |
| صفحات | ۵ صفحه کامل |
| الگوهای JS | ۱۲ الگو |
| الگوهای CSS | ۶ الگو |
| Design System | رنگ‌ها، فاصله‌ها، شعاع‌ها، سایه‌ها، انیمیشن‌ها |
| Responsive | ۳ breakpoint |
| RTL | راهنمای کامل |
| Accessibility | ۷ مورد پیاده‌سازی + پیشنهادها |
| راهنمای توسعه | ۶ سناریو |
| دیباگ | جدول مشکلات + راه‌حل |

این فایل جایگزین کاملی برای AGENT.md قدیمی در بخش فرانت است. اگر خواستید به‌صورت فایل مجزا (`FRONTEND.md`) استفاده شود هم قابل کپی مستقیم است.