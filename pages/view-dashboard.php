<div class="page-header">
    <h1>داشبورد تحلیل</h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <button type="button" id="refreshBtn" class="btn-refresh">
            <i class="fas fa-sync-alt" id="refreshIcon"></i>
            <span id="refreshLabel">به‌روزرسانی</span>
        </button>
    </div>
</div>

<!-- ═══ Stats ═══ -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-comments"></i></div>
        <div>
            <div class="stat-value" id="statTotal">—</div>
            <div class="stat-label">کل پیام‌ها</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-filter"></i></div>
        <div>
            <div class="stat-value" id="statFiltered">—</div>
            <div class="stat-label">فیلترشده</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="stat-value" id="statViewed">—</div>
            <div class="stat-label">مشاهده‌شده</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-ban"></i></div>
        <div>
            <div class="stat-value" id="statIrrelevant">—</div>
            <div class="stat-label">غیرمرتبط</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-users"></i></div>
        <div>
            <div class="stat-value" id="statGroups">—</div>
            <div class="stat-label">گروه‌های فعال</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-user"></i></div>
        <div>
            <div class="stat-value" id="statSenders">—</div>
            <div class="stat-label">فرستندگان</div>
        </div>
    </div>
</div>

<!-- ═══ Trend Chart ═══ -->
<section class="dashboard-section">
    <h2 class="section-title">
        <i class="fas fa-chart-area"></i>
        پیشرفت کلمات کلیدی در زمان
        <span class="section-subtitle" id="trendSubtitle">—</span>
    </h2>

    <div class="trend-filters">
        <button type="button" class="trend-filter" data-range="2days">۲ روز اخیر</button>
        <button type="button" class="trend-filter" data-range="week">هفته اخیر</button>
        <button type="button" class="trend-filter active" data-range="month">ماه اخیر</button>
        <button type="button" class="trend-filter" data-range="year">سال اخیر</button>
    </div>

    <div class="table-wrap" style="padding:20px 22px">
        <div id="trendChart" class="trend-chart">
            <div class="skeleton">در حال بارگذاری...</div>
        </div>
    </div>
</section>

<!-- ═══ Keyword Charts ═══ -->
<section class="dashboard-section">
    <h2 class="section-title">
        <i class="fas fa-tags"></i>
        تحلیل کلمات کلیدی — گروه‌ها
        <span class="section-subtitle">به ازای هر کلمه — گروه‌های پرکاربردتر</span>
    </h2>
    <div id="keywordCharts" class="keyword-grid">
        <div class="skeleton" style="grid-column:1/-1">در حال بارگذاری...</div>
    </div>
</section>

<!-- ═══ Top Senders ═══ -->
<section class="dashboard-section">
    <h2 class="section-title">
        <i class="fas fa-user-tag" style="color:#7c3aed"></i>
        فرستندگان پرکاربرد کلمات کلیدی
        <span class="section-subtitle">top 15</span>
    </h2>
    <div class="table-wrap" style="padding:20px 22px">
        <div id="topSendersChart" class="bar-list">
            <div class="skeleton">در حال بارگذاری...</div>
        </div>
    </div>
</section>

<!-- ═══ Irrelevant ═══ -->
<section class="dashboard-section">
    <h2 class="section-title">
        <i class="fas fa-ban" style="color:#ef4444"></i>
        گروه‌های با بیشترین پیام غیرمرتبط
        <span class="section-subtitle">top 10</span>
    </h2>
    <div class="table-wrap" style="padding:20px 22px">
        <div id="irrelevantChart" class="bar-list">
            <div class="skeleton">در حال بارگذاری...</div>
        </div>
    </div>
</section>

<!-- ═══ Viewed ═══ -->
<section class="dashboard-section">
    <h2 class="section-title">
        <i class="fas fa-check-circle" style="color:#10b981"></i>
        گروه‌های با بیشترین پیام مشاهده‌شده
        <span class="section-subtitle">top 10</span>
    </h2>
    <div class="table-wrap" style="padding:20px 22px">
        <div id="viewedChart" class="bar-list">
            <div class="skeleton">در حال بارگذاری...</div>
        </div>
    </div>
</section>

<!-- ═══ Daily Activity ═══ -->
<section class="dashboard-section">
    <h2 class="section-title">
        <i class="fas fa-chart-line"></i>
        فعالیت ۱۴ روز اخیر
    </h2>
    <div class="table-wrap" style="padding:20px 22px">
        <div id="dailyChart" class="daily-chart">
            <div class="skeleton">در حال بارگذاری...</div>
        </div>
    </div>
</section>

<!-- ═══ Ideas ═══ -->
<section class="dashboard-section">
    <h2 class="section-title">
        <i class="fas fa-lightbulb" style="color:#f59e0b"></i>
        ایده‌های توسعه بعدی
    </h2>
    <div class="ideas-grid">
        <div class="idea-card">
            <i class="fas fa-clock"></i>
            <h4>تحلیل زمانی</h4>
            <p>پیام‌ها در کدام ساعات شبانه‌روز بیشتر ارسال می‌شوند؟ Heatmap فعالیت</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-trophy"></i>
            <h4>لیدربورد فرستندگان</h4>
            <p>پرفعال‌ترین کاربران به ترتیب تعداد پیام، کلیک، و امتیاز تعامل</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-mouse-pointer"></i>
            <h4>نرخ تعامل (CTR)</h4>
            <p>نسبت کلیک به پیام برای هر گروه. کدام گروه بیشتر جذب می‌کند؟</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-link"></i>
            <h4>تحلیل لینک‌ها</h4>
            <p>پرتکرارترین دامنه‌ها، لینک‌های پرکلیک، لینک‌های مخرب احتمالی</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-arrow-trend-up"></i>
            <h4>کلمات در حال رشد</h4>
            <p>کدام کلمه کلیدی در ۷ روز اخیر رشد داشته؟ Trending topics</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-clone"></i>
            <h4>تشخیص اسپم</h4>
            <p>پیام‌های تکراری، لینک‌های مشابه، رفتار مشکوک</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-scale-balanced"></i>
            <h4>مقایسه گروه‌ها</h4>
            <p>فعال vs غیرفعال. کدام گروه کیفیت بالا تولید می‌کند؟</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-face-smile"></i>
            <h4>تحلیل احساسات</h4>
            <p>مثبت/منفی/خنثی بر اساس تحلیل متن (نیاز به NLP)</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-ruler-horizontal"></i>
            <h4>توزیع طول پیام</h4>
            <p>هیستوگرام طول پیام. بیشتر پیام‌ها کوتاه، متوسط یا بلند؟</p>
        </div>
        <div class="idea-card">
            <i class="fas fa-fire"></i>
            <h4>ساعات پیک</h4>
            <p>چه ساعتی از روز بیشترین ترافیک پیام وجود دارد؟</p>
        </div>
    </div>
</section>

<style>
    /* ═══ Stats ═══ */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        margin-bottom: 28px
    }

    .stat-card {
        background: #fff;
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
        transition: transform .2s, box-shadow .2s
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(15, 23, 42, .1)
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0
    }

    .stat-icon.blue {
        background: #eef4ff;
        color: #2f6bdc
    }

    .stat-icon.green {
        background: #ecfdf5;
        color: #059669
    }

    .stat-icon.red {
        background: #fef2f2;
        color: #dc2626
    }

    .stat-icon.purple {
        background: #f5f3ff;
        color: #7c3aed
    }

    .stat-icon.amber {
        background: #fffbeb;
        color: #d97706
    }

    .stat-icon.cyan {
        background: #ecfeff;
        color: #0891b2
    }

    .stat-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1
    }

    .stat-label {
        font-size: .75rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 4px
    }

    /* ═══ Sections ═══ */
    .dashboard-section {
        margin-bottom: 28px
    }

    .section-title {
        font-size: .95rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0
    }

    .section-title i {
        font-size: 1rem
    }

    .section-subtitle {
        font-size: .75rem;
        color: #94a3b8;
        font-weight: 500;
        margin-right: auto
    }

    /* ═══ Trend filters ═══ */
    .trend-filters {
        display: flex;
        gap: 6px;
        margin-bottom: 14px;
        flex-wrap: wrap
    }

    .trend-filter {
        padding: 8px 16px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        border-radius: 8px;
        font-family: inherit;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s
    }

    .trend-filter:hover {
        border-color: #4c8bf5;
        color: #4c8bf5
    }

    .trend-filter.active {
        background: #4c8bf5;
        border-color: #4c8bf5;
        color: #fff;
        box-shadow: 0 2px 8px rgba(76, 139, 245, .3)
    }

    .trend-filter:disabled {
        opacity: .5;
        cursor: not-allowed
    }

    /* ═══ Trend chart ═══ */
    .trend-chart {
        position: relative;
        min-height: 240px
    }

    .trend-chart svg {
        display: block;
        width: 100%;
        height: auto;
        overflow: visible
    }

    .trend-chart .trend-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        gap: 10px;
        flex-wrap: wrap
    }

    .trend-chart .trend-total {
        font-size: .85rem;
        color: #475569;
        font-weight: 600
    }

    .trend-chart .trend-total strong {
        color: #4c8bf5;
        font-size: 1.05rem
    }

    .trend-chart .trend-peak {
        font-size: .78rem;
        color: #94a3b8;
        font-weight: 600
    }

    .trend-chart circle {
        cursor: pointer;
        transition: r .15s
    }

    .trend-chart circle:hover {
        r: 6
    }

    /* ═══ Keyword cards ═══ */
    .keyword-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 16px
    }

    .keyword-card {
        background: #fff;
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
        border-top: 3px solid #4c8bf5;
        transition: transform .2s
    }

    .keyword-card:hover {
        transform: translateY(-2px)
    }

    .keyword-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        gap: 10px;
        flex-wrap: wrap
    }

    .keyword-total {
        font-size: .75rem;
        color: #64748b;
        font-weight: 600;
        background: #f1f5f9;
        padding: 3px 10px;
        border-radius: 10px
    }

    .badge-keyword {
        display: inline-block;
        padding: 3px 9px;
        background: #eef4ff;
        color: #2f6bdc;
        border-radius: 6px;
        font-size: .75rem;
        font-weight: 700
    }

    /* ═══ Bar chart ═══ */
    .bar-list {
        display: flex;
        flex-direction: column;
        gap: 11px
    }

    .bar-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
        gap: 8px
    }

    .bar-label {
        font-size: .78rem;
        color: #475569;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
        min-width: 0
    }

    .bar-value {
        font-size: .75rem;
        color: #1e293b;
        font-weight: 700;
        flex-shrink: 0;
        background: #f8fafc;
        padding: 1px 8px;
        border-radius: 8px
    }

    .bar-track {
        background: #f1f5f9;
        border-radius: 4px;
        height: 8px;
        overflow: hidden
    }

    .bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #4c8bf5, #2f6bdc);
        border-radius: 4px;
        transition: width .6s cubic-bezier(.25, 1, .5, 1);
        min-width: 4px
    }

    .bar-fill.red {
        background: linear-gradient(90deg, #ef4444, #dc2626)
    }

    .bar-fill.green {
        background: linear-gradient(90deg, #10b981, #059669)
    }

    .bar-fill.purple {
        background: linear-gradient(90deg, #8b5cf6, #7c3aed)
    }

    .bar-meta {
        font-size: .68rem;
        color: #94a3b8;
        font-weight: 600;
        margin-right: 6px
    }

    /* ═══ Daily ═══ */
    .daily-chart {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        height: 180px;
        padding: 0 4px;
        overflow-x: auto
    }

    .daily-bar {
        flex: 1;
        min-width: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        height: 100%
    }

    .daily-bar-track {
        flex: 1;
        width: 100%;
        background: #f1f5f9;
        border-radius: 6px 6px 0 0;
        display: flex;
        align-items: flex-end;
        overflow: hidden
    }

    .daily-bar-fill {
        width: 100%;
        background: linear-gradient(180deg, #4c8bf5, #2f6bdc);
        border-radius: 6px 6px 0 0;
        transition: height .6s ease;
        min-height: 3px
    }

    .daily-bar-label {
        font-size: .65rem;
        color: #64748b;
        font-weight: 600;
        white-space: nowrap
    }

    .daily-bar-value {
        font-size: .68rem;
        color: #4c8bf5;
        font-weight: 700
    }

    /* ═══ Ideas ═══ */
    .ideas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 14px
    }

    .idea-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px 18px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .05);
        border-right: 3px solid #f59e0b;
        transition: transform .2s, box-shadow .2s
    }

    .idea-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(15, 23, 42, .1)
    }

    .idea-card i {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: #fffbeb;
        color: #d97706;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        margin-bottom: 10px
    }

    .idea-card h4 {
        font-size: .85rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 6px
    }

    .idea-card p {
        font-size: .75rem;
        color: #64748b;
        line-height: 1.6
    }

    /* ═══ States ═══ */
    .skeleton {
        color: #94a3b8;
        text-align: center;
        padding: 32px 16px;
        font-size: .85rem
    }

    .empty-state {
        color: #94a3b8;
        text-align: center;
        padding: 36px 16px;
        font-size: .85rem
    }

    .error-state {
        color: #dc2626;
        text-align: center;
        padding: 32px 16px;
        font-size: .85rem
    }

    /* ═══ Refresh button ═══ */
    .btn-refresh {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(135deg, #4c8bf5 0%, #2f6bdc 100%);
        color: #fff;
        font-family: inherit;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 3px 12px rgba(76, 139, 245, .28);
        transition: opacity .2s, transform .1s
    }

    .btn-refresh:hover:not(:disabled) {
        opacity: .92
    }

    .btn-refresh:active:not(:disabled) {
        transform: scale(.97)
    }

    .btn-refresh:disabled {
        opacity: .65;
        cursor: not-allowed
    }

    .btn-refresh .fa-sync-alt.is-spinning {
        animation: spin 1s linear infinite
    }

    @keyframes spin {
        from {
            transform: rotate(0)
        }

        to {
            transform: rotate(360deg)
        }
    }

    @media (max-width:600px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr)
        }

        .stat-card {
            padding: 14px
        }

        .stat-icon {
            width: 40px;
            height: 40px
        }

        .stat-value {
            font-size: 1.15rem
        }

        .trend-filter {
            padding: 7px 12px;
            font-size: .75rem
        }
    }
</style>

<script>
    (function() {
        const ENDPOINT = 'api/dashboard.php';
        const refreshBtn = document.getElementById('refreshBtn');
        const refreshIco = document.getElementById('refreshIcon');
        const refreshLbl = document.getElementById('refreshLabel');

        const trendState = {
            range: 'month'
        };

        const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g,
            c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));

        const fmt = n => Number(n || 0).toLocaleString('en-US');

        /* ═══════════════════════════════════════════════════════
           تبدیل تاریخ میلادی → شمسی (الگوریتم استاندارد)
           ═══════════════════════════════════════════════════════ */
        const JALALI_MONTHS = [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ];
        const JALALI_MONTHS_SHORT = [
            'فرو', 'ارد', 'خرد', 'تیر', 'مرد', 'شهر', 'مهر', 'آبا', 'آذر', 'دی', 'بهم', 'اسف'
        ];

        function gregorianToJalali(gy, gm, gd) {
            const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            let jy = (gy <= 1600) ? 0 : 979;
            gy -= (gy <= 1600) ? 621 : 1600;
            const gy2 = (gm > 2) ? (gy + 1) : gy;
            let days = (365 * gy) + Math.floor((gy2 + 3) / 4) -
                Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) -
                80 + gd + g_d_m[gm - 1];
            jy += 33 * Math.floor(days / 12053);
            days %= 12053;
            jy += 4 * Math.floor(days / 1461);
            days %= 1461;
            if (days > 365) {
                jy += Math.floor((days - 1) / 365);
                days = (days - 1) % 365;
            }
            const jm = (days < 186) ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
            const jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
            return [jy, jm, jd];
        }

        function toJalaliLabel(bucket, short = false) {
            // bucket: "YYYY-MM" or "YYYY-MM-DD"
            const p = bucket.split('-').map(Number);
            if (p.length === 2) {
                const [jy, jm] = gregorianToJalali(p[0], p[1], 15);
                return short ? JALALI_MONTHS_SHORT[jm - 1] : JALALI_MONTHS[jm - 1];
            }
            const [jy, jm, jd] = gregorianToJalali(p[0], p[1], p[2]);
            return short ? `${jd} ${JALALI_MONTHS_SHORT[jm - 1]}` : `${jd} ${JALALI_MONTHS[jm - 1]} ${jy}`;
        }

        /* ═══════════════════════════════════════════════════════
           نمایش نمودار SVG
           ═══════════════════════════════════════════════════════ */
        function renderTrend(trend) {
            const container = document.getElementById('trendChart');

            if (!trend || !trend.length) {
                container.innerHTML = '<div class="empty-state">داده‌ای موجود نیست</div>';
                return;
            }

            const W = 800,
                H = 240;
            const P = {
                top: 30,
                right: 24,
                bottom: 46,
                left: 44
            };
            const CW = W - P.left - P.right;
            const CH = H - P.top - P.bottom;

            const counts = trend.map(d => d.count);
            const max = Math.max(...counts, 1);
            const sum = counts.reduce((a, b) => a + b, 0);
            const peakIdx = counts.indexOf(Math.max(...counts));

            const stepX = trend.length > 1 ? CW / (trend.length - 1) : 0;
            const points = trend.map((d, i) => {
                const x = P.left + i * stepX;
                const y = P.top + CH - (d.count / max) * CH;
                return {
                    x,
                    y,
                    ...d
                };
            });

            const linePath = points
                .map((p, i) => (i === 0 ? 'M' : 'L') + p.x.toFixed(1) + ' ' + p.y.toFixed(1))
                .join(' ');

            const areaPath = linePath +
                ' L' + (P.left + CW).toFixed(1) + ' ' + (P.top + CH).toFixed(1) +
                ' L' + P.left.toFixed(1) + ' ' + (P.top + CH).toFixed(1) + ' Z';

            /* نقاط */
            const circles = points.map(p => {
                const tip = `${toJalaliLabel(p.bucket, false)} — ${fmt(p.count)} پیام`;
                return `<circle cx="${p.x.toFixed(1)}" cy="${p.y.toFixed(1)}"
                            r="3.5" fill="#4c8bf5" stroke="#fff" stroke-width="2">
                        <title>${escapeHtml(tip)}</title>
                    </circle>`;
            }).join('');

            /* برچسب X — بسته به تعداد نقاط، هر چند نقطه یک برچسب */
            const every = Math.max(1, Math.ceil(trend.length / 8));
            const xLabels = points.map((p, i) => {
                if (i % every !== 0 && i !== trend.length - 1) return '';
                return `<text x="${p.x.toFixed(1)}" y="${H - 14}"
                          text-anchor="middle" font-size="10" fill="#94a3b8">
                        ${escapeHtml(toJalaliLabel(p.bucket, true))}
                    </text>`;
            }).join('');

            /* خطوط افقی راهنما */
            const yLines = [0, 0.5, 1].map(ratio => {
                const y = P.top + CH * (1 - ratio);
                const val = Math.round(max * ratio);
                return `<line x1="${P.left}" y1="${y}" x2="${P.left + CW}" y2="${y}"
                          stroke="#e2e8f0" stroke-dasharray="3 3"/>
                    <text x="${P.left - 8}" y="${y + 4}" text-anchor="end"
                          font-size="10" fill="#94a3b8">${val}</text>`;
            }).join('');

            const peakLabel = trend[peakIdx] ?
                `${toJalaliLabel(trend[peakIdx].bucket, false)} با ${fmt(counts[peakIdx])} پیام` :
                '—';

            container.innerHTML = `
            <div class="trend-summary">
                <span class="trend-total">مجموع: <strong>${fmt(sum)}</strong> پیام</span>
                <span class="trend-peak">بیشترین: ${escapeHtml(peakLabel)}</span>
            </div>
            <svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="xMidYMid meet">
                <defs>
                    <linearGradient id="trendArea" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%"  stop-color="#4c8bf5" stop-opacity="0.35"/>
                        <stop offset="100%" stop-color="#4c8bf5" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                ${yLines}
                <path d="${areaPath}" fill="url(#trendArea)"/>
                <path d="${linePath}" fill="none" stroke="#4c8bf5"
                      stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                ${circles}
                ${xLabels}
            </svg>
        `;

            // برچسب زیرعنوان
            const subtitle = {
                '2days': '۲ روز اخیر — روزانه',
                'week': 'هفته اخیر — روزانه',
                'month': 'ماه اخیر — روزانه',
                'year': 'سال اخیر — ماهانه',
            } [trendState.range] || '';
            document.getElementById('trendSubtitle').textContent = subtitle;
        }

        /* ═══════════════════════════════════════════════════════
           Fetch trend بر اساس بازه
           ═══════════════════════════════════════════════════════ */
        function loadTrend(range) {
            trendState.range = range;
            document.getElementById('trendChart').innerHTML =
                '<div class="skeleton">در حال بارگذاری...</div>';

            document.querySelectorAll('.trend-filter').forEach(b => {
                b.classList.toggle('active', b.dataset.range === range);
                b.disabled = true;
            });

            fetch(ENDPOINT + '?action=trend&range=' + encodeURIComponent(range), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status !== 'success') throw new Error(d.message || 'خطا');
                    renderTrend(d.trend || []);
                })
                .catch(err => {
                    document.getElementById('trendChart').innerHTML =
                        `<div class="error-state">خطا: ${escapeHtml(err.message)}</div>`;
                })
                .finally(() => {
                    document.querySelectorAll('.trend-filter').forEach(b => b.disabled = false);
                });
        }

        /* ═══════════════════════════════════════════════════════
           Stats, Keywords, TopSenders, Bars, Daily
           ═══════════════════════════════════════════════════════ */
        function renderStats(s) {
            document.getElementById('statTotal').textContent = fmt(s.total_messages);
            document.getElementById('statFiltered').textContent = fmt(s.filtered);
            document.getElementById('statViewed').textContent = fmt(s.viewed);
            document.getElementById('statIrrelevant').textContent = fmt(s.irrelevant);
            document.getElementById('statGroups').textContent = fmt(s.unique_groups);
            document.getElementById('statSenders').textContent = fmt(s.unique_senders);
        }

        function renderKeywords(keywords) {
            const container = document.getElementById('keywordCharts');
            if (!keywords || !keywords.length) {
                container.innerHTML = '<div class="empty-state" style="grid-column:1/-1">' +
                    '<i class="fas fa-tags" style="font-size:2rem;opacity:.3;display:block;margin-bottom:10px"></i>' +
                    'هیچ کلمه کلیدی فیلترشده‌ای یافت نشد</div>';
                return;
            }
            container.innerHTML = keywords.map(k => {
                const max = Math.max(...k.groups.map(g => g.count), 1);
                const bars = k.groups.map(g => {
                    const pct = (g.count / max * 100).toFixed(0);
                    return `<div class="bar-item">
                    <div class="bar-info">
                        <span class="bar-label" title="${escapeHtml(g.group_name)}">${escapeHtml(g.group_name)}</span>
                        <span class="bar-value">${fmt(g.count)}</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill" style="width:${pct}%"></div></div>
                </div>`;
                }).join('');
                return `<div class="keyword-card">
                <div class="keyword-head">
                    <span class="badge-keyword">${escapeHtml(k.keyword)}</span>
                    <span class="keyword-total">${fmt(k.total)} پیام</span>
                </div>
                <div class="bar-list">${bars}</div>
            </div>`;
            }).join('');
        }

        function renderTopSenders(rows) {
            const container = document.getElementById('topSendersChart');
            if (!rows || !rows.length) {
                container.innerHTML = '<div class="empty-state">داده‌ای موجود نیست</div>';
                return;
            }
            const max = Math.max(...rows.map(r => r.count), 1);
            container.innerHTML = rows.map(r => {
                const pct = (r.count / max * 100).toFixed(0);
                const kwInfo = r.keywords_used > 0 ?
                    `<span class="bar-meta">${fmt(r.keywords_used)} کلمه</span>` : '';
                return `<div class="bar-item">
                <div class="bar-info">
                    <span class="bar-label" title="${escapeHtml(r.sender_name)}">
                        <i class="fas fa-user" style="font-size:.7rem;color:#8b5cf6;margin-left:4px"></i>
                        ${escapeHtml(r.sender_name)}
                    </span>
                    <span>${kwInfo}<span class="bar-value">${fmt(r.count)}</span></span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill purple" style="width:${pct}%"></div>
                </div>
            </div>`;
            }).join('');
        }

        function renderBarList(id, rows, variant = '') {
            const container = document.getElementById(id);
            if (!rows || !rows.length) {
                container.innerHTML = '<div class="empty-state">داده‌ای موجود نیست</div>';
                return;
            }
            const max = Math.max(...rows.map(r => r.count), 1);
            container.innerHTML = rows.map(r => {
                const pct = (r.count / max * 100).toFixed(0);
                return `<div class="bar-item">
                <div class="bar-info">
                    <span class="bar-label" title="${escapeHtml(r.group_name)}">${escapeHtml(r.group_name)}</span>
                    <span class="bar-value">${fmt(r.count)}</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill ${variant}" style="width:${pct}%"></div>
                </div>
            </div>`;
            }).join('');
        }

        function renderDaily(rows) {
            const container = document.getElementById('dailyChart');
            if (!rows || !rows.length) {
                container.innerHTML = '<div class="empty-state">داده‌ای موجود نیست</div>';
                return;
            }
            const max = Math.max(...rows.map(r => r.count), 1);
            container.innerHTML = rows.map(r => {
                const pct = (r.count / max * 100).toFixed(0);
                const label = (r.day || '').slice(5);
                return `<div class="daily-bar">
                <div class="daily-bar-value">${fmt(r.count)}</div>
                <div class="daily-bar-track">
                    <div class="daily-bar-fill" style="height:${pct}%"></div>
                </div>
                <div class="daily-bar-label">${escapeHtml(label)}</div>
            </div>`;
            }).join('');
        }

        /* ═══════════════════════════════════════════════════════
           Load full dashboard
           ═══════════════════════════════════════════════════════ */
        function load() {
            refreshBtn.disabled = true;
            refreshIco.classList.add('is-spinning');
            refreshLbl.textContent = 'در حال بارگذاری...';

            fetch(ENDPOINT, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message || 'خطا');
                    renderStats(data.stats || {});
                    renderTrend(data.trend || []);
                    renderKeywords(data.keywords || []);
                    renderTopSenders(data.top_senders || []);
                    renderBarList('irrelevantChart', data.irrelevant_by_group || [], 'red');
                    renderBarList('viewedChart', data.viewed_by_group || [], 'green');
                    renderDaily(data.daily_activity || []);
                })
                .catch(err => {
                    document.getElementById('trendChart').innerHTML =
                        `<div class="error-state">خطا: ${escapeHtml(err.message)}</div>`;
                })
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshIco.classList.remove('is-spinning');
                    refreshLbl.textContent = 'به‌روزرسانی';
                });
        }

        /* ═══════════════════════════════════════════════════════
           Events
           ═══════════════════════════════════════════════════════ */
        document.querySelectorAll('.trend-filter').forEach(btn => {
            btn.addEventListener('click', () => loadTrend(btn.dataset.range));
        });

        refreshBtn.addEventListener('click', load);

        /* ═══════════════════════════════════════════════════════
           Init
           ═══════════════════════════════════════════════════════ */
        load();
    })();
</script>