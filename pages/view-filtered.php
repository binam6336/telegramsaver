<div class="page-header">
    <h1>پیام‌های فیلترشده</h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="جستجو در متن یا فرستنده...">
        </div>
        <select id="keywordFilter" class="form-select" style="min-width:180px">
            <option value="">همه کلمات کلیدی</option>
        </select>
    </div>
</div>

<div class="tabs" id="tabs">
    <button class="tab active" data-tab="all">
        همه <span class="count" id="cnt-all">0</span>
    </button>
    <button class="tab" data-tab="viewed">
        <i class="fas fa-check-circle" style="color:#10b981"></i>
        مشاهده‌شده <span class="count" id="cnt-viewed">0</span>
    </button>
    <button class="tab" data-tab="irrelevant">
        <i class="fas fa-ban" style="color:#ef4444"></i>
        غیرمرتبط <span class="count" id="cnt-irrelevant">0</span>
    </button>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:60px">#</th>
                <th style="width:110px">کلمه</th>
                <th style="width:140px">فرستنده</th>
                <th>متن پیام</th>
                <th style="width:150px">تاریخ</th>
                <th style="width:250px">عملیات</th>
            </tr>
        </thead>
        <tbody id="rowsBody">
            <tr>
                <td colspan="6" class="loading">در حال بارگذاری...</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="pagination" id="pagination"></div>

<style>
    /* ---------- Tabs ---------- */
    .tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 16px;
        border-bottom: 1px solid #e2e8f0
    }

    .tab {
        padding: 11px 18px;
        border: 0;
        background: transparent;
        cursor: pointer;
        font-family: inherit;
        font-size: .85rem;
        font-weight: 600;
        color: #64748b;
        border-bottom: 2px solid transparent;
        transition: color .2s, border-color .2s;
        position: relative;
        top: 1px;
        display: flex;
        align-items: center;
        gap: 7px
    }

    .tab:hover {
        color: #4c8bf5
    }

    .tab.active {
        color: #4c8bf5;
        border-bottom-color: #4c8bf5
    }

    .tab .count {
        background: #e2e8f0;
        color: #475569;
        font-size: .7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 10px;
        min-width: 22px;
        text-align: center
    }

    .tab.active .count {
        background: #dbeafe;
        color: #1d4ed8
    }

    /* ---------- Actions ---------- */
    .action-cell {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: nowrap
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 11px;
        border-radius: 8px;
        border: 0;
        cursor: pointer;
        font-family: inherit;
        font-size: .75rem;
        font-weight: 700;
        text-decoration: none;
        color: #fff;
        transition: opacity .2s, background-color .2s, transform .1s;
        white-space: nowrap
    }

    .btn-action:hover {
        opacity: .9
    }

    .btn-action:active {
        transform: scale(.96)
    }

    .btn-action.blue {
        background: #4c8bf5
    }

    .btn-action.green {
        background: #10b981
    }

    .btn-action.green.is-active {
        background: #059669;
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .35)
    }

    .btn-action.red {
        background: #ef4444
    }

    .btn-action.red.is-active {
        background: #b91c1c;
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .35)
    }

    .btn-action.disabled,
    .btn-action:disabled {
        background: #e2e8f0;
        color: #94a3b8;
        cursor: not-allowed;
        pointer-events: none;
        opacity: 1
    }

    .badge-clicks {
        background: rgba(255, 255, 255, .28);
        padding: 1px 7px;
        border-radius: 10px;
        font-size: .7rem;
        font-weight: 700;
        min-width: 20px;
        text-align: center
    }

    /* ---------- Keyword badge ---------- */
    .badge-keyword {
        display: inline-block;
        padding: 3px 9px;
        background: #eef4ff;
        color: #2f6bdc;
        border-radius: 6px;
        font-size: .75rem;
        font-weight: 700
    }

    /* ---------- Form select ---------- */
    .form-select {
        padding: 11px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        font-family: inherit;
        font-size: .85rem;
        color: #334155;
        outline: none;
        cursor: pointer;
        transition: border-color .2s
    }

    .form-select:focus {
        border-color: #4c8bf5;
        box-shadow: 0 0 0 3px rgba(76, 139, 245, .15)
    }

    /* ---------- Toast ---------- */
    .toast {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: #1e293b;
        color: #fff;
        padding: 11px 20px;
        border-radius: 10px;
        font-size: .83rem;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
        opacity: 0;
        transition: opacity .3s, transform .3s;
        z-index: 9999;
        pointer-events: none
    }

    .toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0)
    }

    .toast.success {
        background: #059669
    }

    .toast.error {
        background: #dc2626
    }
</style>

<div class="toast" id="toast"></div>

<script>
    (function() {
        const tbody = document.getElementById('rowsBody');
        const pagin = document.getElementById('pagination');
        const searchBox = document.getElementById('searchInput');
        const kwSelect = document.getElementById('keywordFilter');
        const tabsBox = document.getElementById('tabs');
        const toastBox = document.getElementById('toast');
        const ENDPOINT = 'api/filtered.php';

        const state = {
            tab: 'all',
            q: '',
            keyword: '',
            page: 1
        };
        let searchTimer = null;
        let keywordsLoaded = false;

        /* ---------- utils ---------- */
        const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g,
            c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));

        const truncate = (s, n) => {
            s = String(s ?? '');
            return s.length > n ? s.slice(0, n) + '…' : s;
        };

        function toast(msg, type = 'success') {
            toastBox.textContent = msg;
            toastBox.className = 'toast show ' + type;
            clearTimeout(toast._t);
            toast._t = setTimeout(() => toastBox.className = 'toast ' + type, 2500);
        }

        /* ---------- counts ---------- */
        function renderCounts(counts) {
            document.getElementById('cnt-all').textContent = counts.all ?? 0;
            document.getElementById('cnt-viewed').textContent = counts.viewed ?? 0;
            document.getElementById('cnt-irrelevant').textContent = counts.irrelevant ?? 0;
        }

        /* ---------- keywords ---------- */
        function renderKeywords(list) {
            if (keywordsLoaded) return;
            keywordsLoaded = true;
            kwSelect.innerHTML = '<option value="">همه کلمات کلیدی</option>' +
                list.map(k => `<option value="${escapeHtml(k)}">${escapeHtml(k)}</option>`).join('');
            kwSelect.value = state.keyword;
        }

        /* ---------- render rows ---------- */
        function renderRows(items) {
            if (!items.length) {
                const msg = state.tab === 'viewed' ? 'هیچ پیام مشاهده‌شده‌ای نیست' :
                    state.tab === 'irrelevant' ? 'هیچ پیام غیرمرتبطی ثبت نشده' :
                    'پیامی یافت نشد';
                tbody.innerHTML = `<tr><td colspan="6" class="empty">${msg}</td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(m => {
                /* دکمه آبی: مشاهده لینک */
                const viewBtn = m.link ?
                    `<a class="btn-action blue"
                       href="${escapeHtml(m.link)}"
                       target="_blank" rel="noopener"
                       data-action="click" data-id="${m.id}"
                       title="باز کردن لینک">
                       <i class="fas fa-eye"></i>
                       <span>مشاهده</span>
                       <span class="badge-clicks">${m.click_count}</span>
                   </a>` :
                    `<span class="btn-action blue disabled" title="لینکی ثبت نشده">
                       <i class="fas fa-eye-slash"></i>
                       <span>مشاهده</span>
                       <span class="badge-clicks">${m.click_count}</span>
                   </span>`;

                /* دکمه سبز: مشاهده‌شده */
                const viewedBtn = `<button type="button"
                       class="btn-action green ${m.is_viewed ? 'is-active' : ''}"
                       data-action="viewed" data-id="${m.id}"
                       title="${m.is_viewed ? 'حذف از مشاهده‌شده‌ها' : 'علامت‌گذاری به‌عنوان مشاهده‌شده'}">
                       <i class="fas ${m.is_viewed ? 'fa-check-circle' : 'fa-check'}"></i>
                   </button>`;

                /* دکمه قرمز: غیرمرتبط */
                const irrelBtn = `<button type="button"
                       class="btn-action red ${m.is_irrelevant ? 'is-active' : ''}"
                       data-action="irrelevant" data-id="${m.id}"
                       title="${m.is_irrelevant ? 'حذف از غیرمرتبط‌ها' : 'علامت‌گذاری به‌عنوان غیرمرتبط'}">
                       <i class="fas fa-ban"></i>
                   </button>`;

                return `<tr data-id="${m.id}">
                <td>${m.id}</td>
                <td><span class="badge-keyword">${escapeHtml(m.matched_keyword || '-')}</span></td>
                <td>${escapeHtml(m.sender_id || '-')}</td>
                <td class="cell-text" title="${escapeHtml(m.text)}">${escapeHtml(truncate(m.text, 85)) || '-'}</td>
                <td>${escapeHtml(m.date_human || '-')}</td>
                <td>
                    <div class="action-cell">
                        ${viewedBtn}
                        ${viewBtn}
                        ${irrelBtn}
                    </div>
                </td>
            </tr>`;
            }).join('');
        }

        /* ---------- pagination ---------- */
        function renderPagination(page, pages) {
            if (pages <= 1) {
                pagin.innerHTML = '';
                return;
            }

            const mk = (p, label, cls = '') =>
                `<button class="page-btn ${cls}" data-page="${p}">${label}</button>`;

            let html = '';
            if (page > 1) html += mk(page - 1, 'قبلی');

            const start = Math.max(1, page - 2);
            const end = Math.min(pages, page + 2);
            for (let p = start; p <= end; p++) html += mk(p, p, p === page ? 'active' : '');

            if (page < pages) html += mk(page + 1, 'بعدی');

            pagin.innerHTML = html;
        }

        /* ---------- load ---------- */
        function load(page = 1) {
            state.page = page;
            tbody.innerHTML = '<tr><td colspan="6" class="loading">در حال بارگذاری...</td></tr>';

            const url = new URL(ENDPOINT, location.href);
            url.searchParams.set('action', 'list');
            url.searchParams.set('tab', state.tab);
            url.searchParams.set('page', page);
            if (state.q) url.searchParams.set('q', state.q);
            if (state.keyword) url.searchParams.set('keyword', state.keyword);

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message || 'خطا');
                    renderRows(data.items);
                    renderPagination(data.page, data.pages);
                    renderCounts(data.counts || {});
                    if (Array.isArray(data.keywords)) renderKeywords(data.keywords);
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="6" class="error">خطا: ${escapeHtml(err.message)}</td></tr>`;
                });
        }

        /* ---------- post helper ---------- */
        function postAction(action, id, onSuccess) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id', id);

            fetch(ENDPOINT, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status !== 'success') throw new Error(d.message || 'خطا');
                    if (d.counts) renderCounts(d.counts);
                    onSuccess && onSuccess(d);
                })
                .catch(err => toast(err.message, 'error'));
        }

        /* ---------- events: rows ---------- */
        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn || btn.classList.contains('disabled')) return;

            const id = parseInt(btn.dataset.id, 10);
            const action = btn.dataset.action;

            /* دکمه مشاهده — شمارش کلیک + باز شدن لینک در تب جدید (پیش‌فرض ادامه پیدا می‌کند) */
            if (action === 'click') {
                const fd = new FormData();
                fd.append('action', 'increment_click');
                fd.append('id', id);
                /* keepalive اجازه می‌دهد درخواست در پس‌زمینه ارسال شود */
                fetch(ENDPOINT, {
                        method: 'POST',
                        body: fd,
                        keepalive: true,
                        credentials: 'same-origin'
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.status !== 'success') return;
                        const badge = btn.querySelector('.badge-clicks');
                        if (badge) badge.textContent = d.click_count;
                    })
                    .catch(() => {});
                return; // لینک به‌طور طبیعی باز می‌شود
            }

            /* دکمه سبز — toggle مشاهده‌شده */
            if (action === 'viewed') {
                e.preventDefault();
                postAction('toggle_viewed', id, d => {
                    if (state.tab === 'viewed' || state.tab === 'all') {
                        load(state.page);
                    } else {
                        btn.classList.toggle('is-active', !!d.is_viewed);
                    }
                    toast(d.is_viewed ? 'به مشاهده‌شده‌ها اضافه شد' : 'از مشاهده‌شده‌ها حذف شد');
                });
                return;
            }

            /* دکمه قرمز — toggle غیرمرتبط */
            if (action === 'irrelevant') {
                e.preventDefault();
                postAction('toggle_irrelevant', id, d => {
                    if (state.tab === 'irrelevant' || state.tab === 'all') {
                        load(state.page);
                    } else {
                        btn.classList.toggle('is-active', !!d.is_irrelevant);
                    }
                    toast(d.is_irrelevant ? 'به غیرمرتبط‌ها اضافه شد' : 'از غیرمرتبط‌ها حذف شد');
                });
                return;
            }
        });

        /* ---------- events: tabs ---------- */
        tabsBox.addEventListener('click', e => {
            const btn = e.target.closest('.tab');
            if (!btn) return;

            tabsBox.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');

            state.tab = btn.dataset.tab;
            load(1);
        });

        /* ---------- events: search ---------- */
        searchBox.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                state.q = searchBox.value.trim();
                load(1);
            }, 350);
        });

        /* ---------- events: keyword filter ---------- */
        kwSelect.addEventListener('change', () => {
            state.keyword = kwSelect.value;
            load(1);
        });

        /* ---------- events: pagination ---------- */
        pagin.addEventListener('click', e => {
            const btn = e.target.closest('.page-btn');
            if (btn) load(parseInt(btn.dataset.page, 10));
        });

        /* ---------- init ---------- */
        load(1);
    })();
</script>