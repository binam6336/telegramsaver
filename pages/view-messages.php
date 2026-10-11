<div class="page-header">
    <h1>پیام‌های دریافتی</h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <button type="button" id="refreshBtn" class="btn-refresh">
            <i class="fas fa-sync-alt" id="refreshIcon"></i>
            <span id="refreshLabel">به‌روزرسانی پیام‌ها</span>
        </button>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="جستجو در متن، فرستنده یا گروه...">
        </div>
    </div>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:60px">#</th>
                <th style="width:160px">گروه</th>
                <th style="width:150px">فرستنده</th>
                <th>متن پیام</th>
                <th style="width:150px">تاریخ</th>
                <th style="width:110px">عملیات</th>
            </tr>
        </thead>
        <tbody id="messagesBody">
            <tr>
                <td colspan="6" class="loading">در حال بارگذاری...</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="pagination" id="pagination"></div>

<!-- ⭐ Modal نمایش chat_id -->
<div class="modal-overlay" id="chatModal" aria-hidden="true">
    <div class="modal-box" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3>
                <i class="fas fa-users" style="color:#4c8bf5"></i>
                اطلاعات گروه
            </h3>
            <button type="button" class="modal-close" id="modalClose" aria-label="بستن">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="info-row">
                <span class="info-label">نام گروه</span>
                <span class="info-value" id="modalGroupName">—</span>
            </div>
            <div class="info-row">
                <span class="info-label">شناسه چت (chat_id)</span>
                <div class="info-value-row">
                    <code class="info-code" id="modalChatId">—</code>
                    <button type="button" class="btn-copy" id="copyChatId" title="کپی">
                        <i class="fas fa-copy"></i>
                        <span>کپی</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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

    /* ---------- Badges ---------- */
    .badge-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        background: #f0f9ff;
        color: #0369a1;
        border: 0;
        border-radius: 6px;
        font-family: inherit;
        font-size: .75rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color .2s, transform .1s;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap
    }

    .badge-group:hover {
        background: #e0f2fe
    }

    .badge-group:active {
        transform: scale(.97)
    }

    .badge-group i {
        font-size: .68rem;
        opacity: .75
    }

    .badge-group.is-empty {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: default;
        pointer-events: none
    }

    .badge-sender {
        display: inline-block;
        padding: 3px 9px;
        background: #f5f3ff;
        color: #6d28d9;
        border-radius: 6px;
        font-size: .75rem;
        font-weight: 700
    }

    /* ---------- Modal ---------- */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .2s ease, visibility 0s linear .2s
    }

    .modal-overlay.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transition: opacity .2s ease, visibility 0s linear 0s
    }

    .modal-box {
        background: #fff;
        border-radius: 14px;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
        transform: scale(.94);
        transition: transform .2s cubic-bezier(.25, 1, .5, 1)
    }

    .modal-overlay.is-open .modal-box {
        transform: scale(1)
    }

    .modal-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0
    }

    .modal-head h3 {
        font-size: .95rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 9px;
        color: #1e293b
    }

    .modal-close {
        width: 32px;
        height: 32px;
        border: 0;
        background: #f1f5f9;
        color: #64748b;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color .2s, color .2s
    }

    .modal-close:hover {
        background: #e2e8f0;
        color: #1e293b
    }

    .modal-body {
        padding: 20px
    }

    .info-row {
        margin-bottom: 16px
    }

    .info-row:last-child {
        margin-bottom: 0
    }

    .info-label {
        display: block;
        font-size: .75rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 6px;
        letter-spacing: .3px
    }

    .info-value {
        display: block;
        font-size: .9rem;
        color: #1e293b;
        font-weight: 600;
        padding: 10px 12px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0
    }

    .info-value-row {
        display: flex;
        gap: 8px;
        align-items: stretch
    }

    .info-code {
        flex: 1;
        font-family: 'Courier New', monospace;
        font-size: .85rem;
        color: #be185d;
        background: #fdf2f8;
        border: 1px solid #fbcfe8;
        padding: 10px 12px;
        border-radius: 8px;
        direction: ltr;
        text-align: left;
        user-select: all;
        word-break: break-all
    }

    .btn-copy {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 14px;
        border: 0;
        background: #4c8bf5;
        color: #fff;
        border-radius: 8px;
        cursor: pointer;
        font-family: inherit;
        font-size: .8rem;
        font-weight: 700;
        transition: background-color .2s, transform .1s;
        white-space: nowrap
    }

    .btn-copy:hover {
        background: #2f6bdc
    }

    .btn-copy:active {
        transform: scale(.96)
    }

    .btn-copy.copied {
        background: #10b981
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
        z-index: 99999;
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

    @media (max-width:600px) {
        .modal-box {
            max-width: 100%
        }

        .info-value-row {
            flex-direction: column
        }

        .btn-copy {
            padding: 11px 14px;
            justify-content: center
        }
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

        const modal = document.getElementById('chatModal');
        const modalClose = document.getElementById('modalClose');
        const modalName = document.getElementById('modalGroupName');
        const modalId = document.getElementById('modalChatId');
        const copyBtn = document.getElementById('copyChatId');

        const ENDPOINT = 'api/messages.php';
        const REFRESH_EP = 'api/refresh.php?job=worker';

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
            toast._t = setTimeout(() => toastBox.className = 'toast ' + type, 2500);
        }

        /* ---------- modal ---------- */
        function openModal(groupName, chatId) {
            modalName.textContent = groupName || '—';
            modalId.textContent = chatId !== null && chatId !== undefined && chatId !== '' ?
                String(chatId) :
                '—';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        modalClose.addEventListener('click', closeModal);

        modal.addEventListener('click', e => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
        });

        copyBtn.addEventListener('click', () => {
            const val = modalId.textContent;
            if (!val || val === '—') return;

            const done = () => {
                copyBtn.classList.add('copied');
                copyBtn.innerHTML = '<i class="fas fa-check"></i><span>کپی شد</span>';
                setTimeout(() => {
                    copyBtn.classList.remove('copied');
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i><span>کپی</span>';
                }, 1600);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(val).then(done).catch(() => {
                    fallbackCopy(val);
                    done();
                });
            } else {
                fallbackCopy(val);
                done();
            }
        });

        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
            } catch (e) {}
            document.body.removeChild(ta);
        }

        /* ---------- render ---------- */
        function render(items) {
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty">پیامی یافت نشد</td></tr>';
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

                /* ⭐ نام گروه به‌صورت دکمه با data-attribute */
                const groupCell = m.group_name ?
                    `<button type="button" class="badge-group"
                           data-group="${escapeHtml(m.group_name)}"
                           data-chat-id="${escapeHtml(m.chat_id ?? '')}"
                           title="برای دیدن شناسه چت کلیک کنید">
                       <i class="fas fa-users"></i>
                       <span>${escapeHtml(m.group_name)}</span>
                   </button>` :
                    '<span class="badge-group is-empty">-</span>';

                const senderCell = m.sender_name ?
                    `<span class="badge-sender">${escapeHtml(m.sender_name)}</span>` :
                    '<span style="color:#94a3b8">-</span>';

                return `<tr>
                <td>${m.id ?? '-'}</td>
                <td>${groupCell}</td>
                <td>${senderCell}</td>
                <td class="cell-text" title="${escapeHtml(m.text)}">${escapeHtml(truncate(m.text, 80)) || '-'}</td>
                <td>${escapeHtml(m.date || '-')}</td>
                <td>${btn}</td>
            </tr>`;
            }).join('');
        }

        /* ---------- event: کلیک روی نام گروه ---------- */
        tbody.addEventListener('click', e => {
            const gbtn = e.target.closest('.badge-group');
            if (gbtn && !gbtn.classList.contains('is-empty')) {
                openModal(gbtn.dataset.group || '', gbtn.dataset.chatId || '');
            }
        });

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

        function load(page = 1) {
            tbody.innerHTML = '<tr><td colspan="6" class="loading">در حال بارگذاری...</td></tr>';

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
                    tbody.innerHTML = `<tr><td colspan="6" class="error">خطا: ${escapeHtml(err.message)}</td></tr>`;
                });
        }

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