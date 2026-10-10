<div class="page-header">
    <h1>فیلترها</h1>
</div>

<div class="table-wrap" style="padding:20px">
    <form id="filterForm" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
        <div class="search-box" style="flex:1;min-width:220px">
            <i class="fas fa-filter"></i>
            <input type="text" id="keywordInput" name="keyword"
                placeholder="کلمه مورد نظر را وارد کنید..." autocomplete="off">
        </div>
        <button type="submit" class="btn-view" style="padding:11px 20px">
            <i class="fas fa-plus"></i> افزودن
        </button>
    </form>

    <div id="filterAlert" style="display:none;margin-bottom:14px;padding:10px 14px;border-radius:8px;font-size:.85rem;font-weight:600"></div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:70px">#</th>
                <th>کلمه</th>
                <th style="width:170px">تاریخ ثبت</th>
                <th style="width:90px">حذف</th>
            </tr>
        </thead>
        <tbody id="filtersBody">
            <tr>
                <td colspan="4" class="loading">در حال بارگذاری...</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    (function() {
        const ENDPOINT = 'api/filters.php';
        const form = document.getElementById('filterForm');
        const input = document.getElementById('keywordInput');
        const tbody = document.getElementById('filtersBody');
        const alertBox = document.getElementById('filterAlert');

        const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g,
            c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));

        function showAlert(msg, type = 'success') {
            alertBox.textContent = msg;
            alertBox.style.display = 'block';
            alertBox.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
            alertBox.style.color = type === 'success' ? '#166534' : '#991b1b';
            clearTimeout(showAlert._t);
            showAlert._t = setTimeout(() => alertBox.style.display = 'none', 2800);
        }

        function render(items) {
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty">هیچ کلمه‌ای ثبت نشده است</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(f => `
            <tr data-id="${f.id}">
                <td>${f.id}</td>
                <td>${escapeHtml(f.keyword)}</td>
                <td>${escapeHtml(f.created_at)}</td>
                <td>
                    <button class="btn-view" style="background:#ef4444;padding:6px 10px"
                            data-delete="${f.id}" title="حذف">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        }

        function load() {
            tbody.innerHTML = '<tr><td colspan="4" class="loading">در حال بارگذاری...</td></tr>';
            fetch(ENDPOINT + '?action=list', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status !== 'success') throw new Error(d.message || 'خطا');
                    render(d.items);
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="4" class="error">خطا: ${escapeHtml(err.message)}</td></tr>`;
                });
        }

        form.addEventListener('submit', e => {
            e.preventDefault();
            const keyword = input.value.trim();
            if (!keyword) {
                showAlert('کلمه را وارد کنید', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'add');
            fd.append('keyword', keyword);

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
                    input.value = '';
                    showAlert('با موفقیت افزوده شد', 'success');
                    load();
                })
                .catch(err => showAlert(err.message, 'error'));
        });

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-delete]');
            if (!btn) return;
            if (!confirm('این کلمه حذف شود؟')) return;

            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', btn.dataset.delete);

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