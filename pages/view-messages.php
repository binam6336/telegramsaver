<div class="page-header">
    <h1>پیام‌های دریافتی</h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <button type="button" id="refreshBtn" class="btn-refresh">
            <i class="fas fa-sync-alt" id="refreshIcon"></i>
            <span id="refreshLabel">به‌روزرسانی پیام‌ها</span>
        </button>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="جستجو در متن یا فرستنده...">
        </div>
    </div>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:70px">#</th>
                <th style="width:180px">فرستنده</th>
                <th>متن پیام</th>
                <th style="width:170px">تاریخ</th>
                <th style="width:130px">عملیات</th>
            </tr>
        </thead>
        <tbody id="messagesBody">
            <tr>
                <td colspan="5" class="loading">در حال بارگذاری...</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="pagination" id="pagination"></div>

<style>
    /* ---------- Refresh button ---------- */
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
        transition: opacity .2s, transform .1s, box-shadow .2s
    }

    .btn-refresh:hover:not(:disabled) {
        opacity: .92;
        box-shadow: 0 4px 16px rgba(76, 139, 245, .4)
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
        pointer-events: none;
        max-width: 80vw
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
        const tbody = document.getElementById('messagesBody');
        const pagin = document.getElementById('pagination');
        const searchBox = document.getElementById('searchInput');
        const toastBox = document.getElementById('toast');
        const refreshBtn = document.getElementById('refreshBtn');
        const refreshIco = document.getElementById('refreshIcon');
        const refreshLbl = document.getElementById('refreshLabel');

        const ENDPOINT = 'api/messages.php';
        const REFRESH_EP = 'api/refresh.php?job=worker'; // ⭐ فقط worker

        let currentQuery = '';
        let searchTimer = null;

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
            toast._t = setTimeout(() => toastBox.className = 'toast ' + type, 2800);
        }

        /* ---------- render ---------- */
        function render(items) {
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="empty">پیامی یافت نشد</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(m => {
                const btn = m.link ?
                    `<a class="btn-view" href="${escapeHtml(m.link)}" target="_blank" rel="noopener">
                       <i class="fas fa-eye"></i> مشاهده
                   </a>` :
                    `<span class="btn-view disabled" title="لینکی برای این پیام ثبت نشده">
                       <i class="fas fa-eye-slash"></i> مشاهده
                   </span>`;

                return `<tr>
                <td>${m.id ?? '-'}</td>
                <td>${escapeHtml(m.sender || '-')}</td>
                <td class="cell-text" title="${escapeHtml(m.text)}">${escapeHtml(truncate(m.text, 90)) || '-'}</td>
                <td>${escapeHtml(m.date || '-')}</td>
                <td>${btn}</td>
            </tr>`;
            }).join('');
        }

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
            tbody.innerHTML = '<tr><td colspan="5" class="loading">در حال بارگذاری...</td></tr>';

            const url = new URL(ENDPOINT, location.href);
            url.searchParams.set('page', page);
            if (currentQuery) url.searchParams.set('q', currentQuery);

            return fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message || 'خطا در دریافت داده');
                    render(data.items);
                    renderPagination(data.page, data.pages);
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="5" class="error">خطا: ${escapeHtml(err.message)}</td></tr>`;
                });
        }

        /* ---------- refresh (فقط worker) ---------- */
        function refreshMessages() {
            refreshBtn.disabled = true;
            refreshIco.classList.add('is-spinning');
            refreshLbl.textContent = 'در حال اجرا...';

            fetch(REFRESH_EP, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') {
                        toast('دریافت پیام‌های جدید انجام شد', 'success');
                    } else {
                        const failed = (d.results || []).find(x => x.status === 'error');
                        toast(failed?.message || d.message || 'خطا در اجرای worker', 'error');
                    }
                })
                .catch(() => toast('خطا در ارتباط با سرور', 'error'))
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshIco.classList.remove('is-spinning');
                    refreshLbl.textContent = 'به‌روزرسانی پیام‌ها';
                    load(1);
                });
        }

        refreshBtn.addEventListener('click', refreshMessages);

        /* ---------- events ---------- */
        pagin.addEventListener('click', e => {
            const btn = e.target.closest('.page-btn');
            if (btn) load(parseInt(btn.dataset.page, 10));
        });

        searchBox.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentQuery = searchBox.value.trim();
                load(1);
            }, 350);
        });

        /* ---------- init ---------- */
        load(1);
    })();
</script>