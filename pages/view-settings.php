<div class="page-header">
    <h1>تنظیمات</h1>
</div>

<!-- ============ تنظیم اول: مسدودسازی چت ============ -->
<section class="settings-card">
    <header class="card-head">
        <div class="card-title">
            <i class="fas fa-ban"></i>
            <div>
                <h2>مسدودسازی چت</h2>
                <p>پیام‌های چت‌های مسدود همچنان در دیتابیس ذخیره می‌شوند، اما در صفحه «همه پیام‌ها» و «پیام‌های فیلترشده» نمایش داده نمی‌شوند.</p>
            </div>
        </div>
    </header>

    <div class="card-body">

        <!-- فرم افزودن -->
        <form id="blockForm" class="block-form">
            <div class="field">
                <label for="chatIdInput">شناسه چت (chat_id) <span class="req">*</span></label>
                <input type="text" id="chatIdInput" name="chat_id"
                    placeholder="مثال: -1003311694628 یا 123456789"
                    autocomplete="off" inputmode="numeric">
                <small class="hint">شناسه چت گروه در تلگرام. برای گروه‌ها معمولاً با <code>-100</code> شروع می‌شود.</small>
            </div>

            <div class="field">
                <label for="groupNameInput">نام گروه (اختیاری)</label>
                <input type="text" id="groupNameInput" name="group_name"
                    placeholder="مثال: گروه سیگنال‌ها"
                    autocomplete="off" maxlength="190">
                <small class="hint">برای یادآوری اینکه این شناسه مربوط به کدام گروه است.</small>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-plus"></i>
                افزودن به لیست مسدود
            </button>
        </form>

        <div id="alertBox" class="alert" style="display:none"></div>

        <!-- جدول لیست -->
        <div class="table-mini">
            <table>
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:220px">شناسه چت</th>
                        <th>نام گروه</th>
                        <th style="width:160px">تاریخ افزودن</th>
                        <th style="width:90px">حذف</th>
                    </tr>
                </thead>
                <tbody id="blockedBody">
                    <tr>
                        <td colspan="5" class="loading">در حال بارگذاری...</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</section>

<style>
    /* ---------- Settings card ---------- */
    .settings-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
        margin-bottom: 20px;
        overflow: hidden
    }

    .card-head {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc
    }

    .card-title {
        display: flex;
        gap: 14px;
        align-items: flex-start
    }

    .card-title>i {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0
    }

    .card-title h2 {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px
    }

    .card-title p {
        font-size: .82rem;
        color: #64748b;
        line-height: 1.6
    }

    .card-body {
        padding: 24px
    }

    /* ---------- Form ---------- */
    .block-form {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 14px;
        align-items: end;
        padding-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 20px
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 6px
    }

    .field label {
        font-size: .78rem;
        font-weight: 700;
        color: #475569
    }

    .field .req {
        color: #dc2626
    }

    .field input {
        padding: 11px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-family: inherit;
        font-size: .87rem;
        color: #1e293b;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        background: #fff
    }

    .field input:focus {
        border-color: #4c8bf5;
        box-shadow: 0 0 0 3px rgba(76, 139, 245, .15)
    }

    .field .hint {
        font-size: .72rem;
        color: #94a3b8;
        line-height: 1.5
    }

    .field .hint code {
        background: #f1f5f9;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: .72rem;
        color: #be185d
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 20px;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(135deg, #4c8bf5 0%, #2f6bdc 100%);
        color: #fff;
        font-family: inherit;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        height: 42px;
        box-shadow: 0 3px 12px rgba(76, 139, 245, .28);
        transition: opacity .2s, transform .1s;
        white-space: nowrap
    }

    .btn-primary:hover:not(:disabled) {
        opacity: .92
    }

    .btn-primary:active:not(:disabled) {
        transform: scale(.97)
    }

    .btn-primary:disabled {
        opacity: .6;
        cursor: not-allowed
    }

    /* ---------- Alert ---------- */
    .alert {
        margin-bottom: 16px;
        padding: 11px 16px;
        border-radius: 10px;
        font-size: .83rem;
        font-weight: 600;
        animation: fadeIn .25s ease
    }

    .alert.success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0
    }

    .alert.error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-4px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    /* ---------- Mini table ---------- */
    .table-mini {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 10px
    }

    .table-mini table {
        width: 100%;
        border-collapse: collapse;
        font-size: .85rem
    }

    .table-mini th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        text-align: right;
        padding: 11px 14px;
        border-bottom: 1px solid #e2e8f0;
        font-size: .78rem
    }

    .table-mini td {
        padding: 11px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle
    }

    .table-mini tr:last-child td {
        border-bottom: 0
    }

    .table-mini tr:hover td {
        background: #f8fafc
    }

    .table-mini .loading,
    .table-mini .empty {
        text-align: center;
        color: #94a3b8;
        padding: 24px !important
    }

    .table-mini .error {
        color: #dc2626;
        text-align: center;
        padding: 24px !important
    }

    .chat-id-code {
        font-family: 'Courier New', monospace;
        font-size: .83rem;
        color: #be185d;
        background: #fdf2f8;
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid #fbcfe8;
        direction: ltr;
        display: inline-block;
        user-select: all
    }

    .group-name-cell {
        color: #334155;
        font-weight: 500
    }

    .group-name-cell.empty {
        color: #94a3b8;
        font-style: italic
    }

    .btn-trash {
        width: 32px;
        height: 32px;
        border: 0;
        background: #fee2e2;
        color: #dc2626;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color .2s, color .2s
    }

    .btn-trash:hover {
        background: #fecaca;
        color: #991b1b
    }

    .btn-trash:active {
        transform: scale(.94)
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

    @media (max-width:820px) {
        .block-form {
            grid-template-columns: 1fr
        }

        .card-body {
            padding: 18px
        }
    }
</style>

<div class="toast" id="toast"></div>

<script>
    (function() {
        const form = document.getElementById('blockForm');
        const chatInput = document.getElementById('chatIdInput');
        const nameInput = document.getElementById('groupNameInput');
        const tbody = document.getElementById('blockedBody');
        const alertBox = document.getElementById('alertBox');
        const toastBox = document.getElementById('toast');

        const ENDPOINT = 'api/settings.php';

        const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g,
            c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));

        function toast(msg, type = 'success') {
            toastBox.textContent = msg;
            toastBox.className = 'toast show ' + type;
            clearTimeout(toast._t);
            toast._t = setTimeout(() => toastBox.className = 'toast ' + type, 2500);
        }

        function showAlert(msg, type = 'success') {
            alertBox.textContent = msg;
            alertBox.className = 'alert ' + type;
            alertBox.style.display = 'block';
            clearTimeout(showAlert._t);
            showAlert._t = setTimeout(() => alertBox.style.display = 'none', 3000);
        }

        function render(items) {
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="empty">هیچ چتی مسدود نشده است</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(item => {
                const nameCell = item.group_name ?
                    `<span class="group-name-cell">${escapeHtml(item.group_name)}</span>` :
                    `<span class="group-name-cell empty">— بدون نام —</span>`;

                return `<tr data-id="${item.id}">
                <td>${item.id}</td>
                <td><code class="chat-id-code">${escapeHtml(item.chat_id)}</code></td>
                <td>${nameCell}</td>
                <td>${escapeHtml(item.created_at)}</td>
                <td>
                    <button type="button" class="btn-trash" data-delete="${item.id}" title="حذف">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
            }).join('');
        }

        function load() {
            tbody.innerHTML = '<tr><td colspan="5" class="loading">در حال بارگذاری...</td></tr>';
            fetch(ENDPOINT + '?action=list', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status !== 'success') throw new Error(d.message || 'خطا');
                    render(d.items || []);
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="5" class="error">خطا: ${escapeHtml(err.message)}</td></tr>`;
                });
        }

        form.addEventListener('submit', e => {
            e.preventDefault();
            const chatId = chatInput.value.trim();
            const groupName = nameInput.value.trim();

            if (!chatId) {
                showAlert('شناسه چت را وارد کنید', 'error');
                chatInput.focus();
                return;
            }
            if (!/^-?\d+$/.test(chatId)) {
                showAlert('شناسه چت باید عددی باشد', 'error');
                chatInput.focus();
                return;
            }

            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'add');
            fd.append('chat_id', chatId);
            fd.append('group_name', groupName);

            fetch(ENDPOINT, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status !== 'success') throw new Error(d.message || 'خطا');
                    chatInput.value = '';
                    nameInput.value = '';
                    chatInput.focus();
                    showAlert('با موفقیت به لیست مسدود اضافه شد', 'success');
                    load();
                })
                .catch(err => showAlert(err.message, 'error'))
                .finally(() => {
                    btn.disabled = false;
                });
        });

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-delete]');
            if (!btn) return;
            const id = btn.dataset.delete;
            if (!confirm('این چت از لیست مسدود حذف شود؟ پیام‌های آن دوباره نمایش داده خواهند شد.')) return;

            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', id);

            fetch(ENDPOINT, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status !== 'success') throw new Error(d.message || 'خطا');
                    showAlert('حذف شد', 'success');
                    load();
                })
                .catch(err => showAlert(err.message, 'error'));
        });

        load();
    })();
</script>