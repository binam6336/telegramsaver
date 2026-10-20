<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
auth_boot();
auth_require();

$page = $_GET['page'] ?? 'messages';

$allowed = ['dashboard', 'messages', 'filtered', 'settings'];
if (!in_array($page, $allowed, true)) $page = 'messages';

$titles = [
    'dashboard' => 'داشبورد',
    'messages'  => 'پیام‌ها',
    'filtered'  => 'پیام‌های فیلترشده',
    'settings'  => 'تنظیمات',
];

$title = $titles[$page] ?? 'پنل مدیریت';

require __DIR__ . '/head.php';
require __DIR__ . '/sidebar.php';

render_sidebar($page);

echo '<main class="content">';

$viewFile = __DIR__ . "/view-{$page}.php";
if (is_file($viewFile)) {
    require $viewFile;
} else {
    echo '<div class="table-wrap" style="padding:28px;text-align:center;color:#dc2626">'
        . 'صفحه مورد نظر یافت نشد.'
        . '</div>';
}

echo '</main>';

require __DIR__ . '/foot.php';
