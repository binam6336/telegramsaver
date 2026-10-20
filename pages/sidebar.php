<?php
require_once __DIR__ . '/auth.php';

function render_sidebar(string $current = ''): void
{
    $user     = auth_user();
    $username = $user['username'] ?? '';

    $items = [
        ['key' => 'dashboard', 'label' => 'داشبورد',           'icon' => 'fa-th-large', 'href' => '?page=dashboard'],
        ['key' => 'messages', 'label' => 'پیام‌ها',            'icon' => 'fa-comments', 'href' => '?page=messages'],
        ['key' => 'filtered', 'label' => 'پیام‌های فیلترشده', 'icon' => 'fa-filter',   'href' => '?page=filtered'],
        ['key' => 'settings', 'label' => 'تنظیمات',           'icon' => 'fa-cog',      'href' => '?page=settings'],
    ];
?>
    <aside class="sidebar">
        <div class="sidebar-scroll">
            <div class="sidebar-brand">
                <i class="fas fa-paper-plane"></i>
                <span>ماهیار</span>
            </div>
            <?php foreach ($items as $it): ?>
                <a class="menu-item <?= $current === $it['key'] ? 'active' : '' ?>"
                    href="<?= htmlspecialchars($it['href']) ?>">
                    <i class="fas <?= htmlspecialchars($it['icon']) ?>"></i>
                    <span><?= htmlspecialchars($it['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-info">
                <i class="fas fa-user-circle"></i>
                <span title="<?= htmlspecialchars($username) ?>"><?= htmlspecialchars($username) ?></span>
            </div>
            <a href="logout.php" class="sidebar-logout" title="خروج از حساب">
                <i class="fas fa-sign-out-alt"></i>
                <span>خروج</span>
            </a>
        </div>
    </aside>
<?php
}
