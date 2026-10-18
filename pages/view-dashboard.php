<div class="page-header">
    <h1>داشبورد تحلیل</h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <button type="button" id="refreshBtn" class="btn-refresh">
            <i class="fas fa-sync-alt" id="refreshIcon"></i>
            <span id="refreshLabel">به‌روزرسانی</span>
        </button>
    </div>
</div>

<!-- ═══ Stats Cards ═══ -->
<div class="stats-grid" id="statsGrid">
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

<!-- ═══ Keyword Charts ═══ -->
<section class="dashboard-section">
    <h2 class="section-title">
        <i class="fas fa-tags"></i>
        تحلیل کلمات کلیدی
        <span class="section-subtitle">به ازای هر کلمه — گروه‌های پرکاربردتر</span>
    </h2>
    <div id="keywordCharts" class="keyword-grid">
        <div class="skeleton" style="grid-column:1/-1">در حال بارگذاری...</div>
    </div>
</section>

<!-- ═══ Irrelevant by Group ═══ -->
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

<!-- ═══ Viewed by Group (bonus) ═══ -->
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

<!-- ═══ Daily Activity (bonus) ═══ -->
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

    /* ═══ Bar chart ═══ */
    .bar-list {
        display: flex;
        flex-direction: column;
        gap: 11px
    }

    .bar-item {}

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

    /* ═══ Daily activity ═══ */
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

    /* ═══ Skeleton / states ═══ */
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
    }
</style>

<script>
    (function() {
        const ENDPOINT = 'api/dashboard.php';
        const refreshBtn = document.getElementById('refreshBtn');
        const refreshIco = document.getElementById('refreshIcon');
        const refreshLbl = document.getElementById('refreshLabel');

        const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g,
            c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));

        const fmt = n => Number(n || 0).toLocaleString('en-US');

        /* ═══ stats cards ═══ */
        function renderStats(s) {
            document.getElementById('statTotal').textContent = fmt(s.total_messages);
            document.getElementById('statFiltered').textContent = fmt(s.filtered);
            document.getElementById('statViewed').textContent = fmt(s.viewed);
            document.getElementById('statIrrelevant').textContent = fmt(s.irrelevant);
            document.getElementById('statGroups').textContent = fmt(s.unique_groups);
            document.getElementById('statSenders').textContent = fmt(s.unique_senders);
        }

        /* ═══ keyword cards ═══ */
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
                    return `
                    <div class="bar-item">
                        <div class="bar-info">
                            <span class="bar-label" title="${escapeHtml(g.group_name)}">${escapeHtml(g.group_name)}</span>
                            <span class="bar-value">${fmt(g.count)}</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:${pct}%"></div>
                        </div>
                    </div>
                `;
                }).join('');

                return `
                <div class="keyword-card">
                    <div class="keyword-head">
                        <span class="badge-keyword">${escapeHtml(k.keyword)}</span>
                        <span class="keyword-total">${fmt(k.total)} پیام</span>
                    </div>
                    <div class="bar-list">${bars}</div>
                </div>
            `;
            }).join('');
        }

        /* ═══ simple bar list (irrelevant / viewed) ═══ */
        function renderBarList(containerId, rows, variant = '') {
            const container = document.getElementById(containerId);

            if (!rows || !rows.length) {
                container.innerHTML = '<div class="empty-state">داده‌ای موجود نیست</div>';
                return;
            }

            const max = Math.max(...rows.map(r => r.count), 1);

            container.innerHTML = rows.map(r => {
                const pct = (r.count / max * 100).toFixed(0);
                return `
                <div class="bar-item">
                    <div class="bar-info">
                        <span class="bar-label" title="${escapeHtml(r.group_name)}">${escapeHtml(r.group_name)}</span>
                        <span class="bar-value">${fmt(r.count)}</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill ${variant}" style="width:${pct}%"></div>
                    </div>
                </div>
            `;
            }).join('');
        }

        /* ═══ daily chart ═══ */
        function renderDaily(rows) {
            const container = document.getElementById('dailyChart');

            if (!rows || !rows.length) {
                container.innerHTML = '<div class="empty-state">داده‌ای موجود نیست</div>';
                return;
            }

            const max = Math.max(...rows.map(r => r.count), 1);

            container.innerHTML = rows.map(r => {
                const pct = (r.count / max * 100).toFixed(0);
                const label = (r.day || '').slice(5); // YYYY-MM-DD → MM-DD
                return `
                <div class="daily-bar">
                    <div class="daily-bar-value">${fmt(r.count)}</div>
                    <div class="daily-bar-track">
                        <div class="daily-bar-fill" style="height:${pct}%"></div>
                    </div>
                    <div class="daily-bar-label">${escapeHtml(label)}</div>
                </div>
            `;
            }).join('');
        }

        /* ═══ load ═══ */
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
                    renderKeywords(data.keywords || []);
                    renderBarList('irrelevantChart', data.irrelevant_by_group || [], 'red');
                    renderBarList('viewedChart', data.viewed_by_group || [], 'green');
                    renderDaily(data.daily_activity || []);
                })
                .catch(err => {
                    document.getElementById('keywordCharts').innerHTML =
                        `<div class="error-state" style="grid-column:1/-1">خطا: ${escapeHtml(err.message)}</div>`;
                })
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshIco.classList.remove('is-spinning');
                    refreshLbl.textContent = 'به‌روزرسانی';
                });
        }

        refreshBtn.addEventListener('click', load);
        load();
    })();
</script>