<div class="page-header">
    <h1>پیام‌های دریافتی</h1>
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="جستجو در متن یا فرستنده...">
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

<script>
    (function() {
        const tbody = document.getElementById('messagesBody');
        const pagin = document.getElementById('pagination');
        const searchBox = document.getElementById('searchInput');
        const ENDPOINT = 'api/messages.php';

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
            for (let p = start; p <= end; p++) {
                html += mk(p, p, p === page ? 'active' : '');
            }
            if (page < pages) html += mk(page + 1, 'بعدی');

            pagin.innerHTML = html;
        }

        function load(page = 1) {
            tbody.innerHTML = '<tr><td colspan="5" class="loading">در حال بارگذاری...</td></tr>';

            const url = new URL(ENDPOINT, location.href);
            url.searchParams.set('page', page);
            if (currentQuery) url.searchParams.set('q', currentQuery);

            fetch(url, {
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

        load(1);
    })();
</script>