<?php
$nav = $navActive ?? '';
$counts = ['comments' => 0, 'inbox' => 0, 'orders' => 0];
try {
    $db = db_connect();
    $counts['comments'] = (int) $db->table('comments')->where('status', 'pending')->where('deleted_at', null)->countAllResults();
    $counts['inbox']    = (int) $db->table('form_submissions')->where('is_read', 0)->countAllResults();
    $counts['orders']   = (int) $db->table('orders')->where('status', 'new')->countAllResults();
} catch (\Throwable) {
}

$item = static function (string $key, string $url, string $icon, string $label, int $badge = 0) use ($nav): string {
    $active = $nav === $key ? ' active' : '';
    $html = '<li class="nav-item"><a href="' . esc($url, 'attr') . '" class="nav-link' . $active . '">'
        . '<i class="' . esc($icon, 'attr') . ' nav-icon"></i><p>' . esc($label) . '</p>';
    if ($badge > 0) {
        $html .= '<span class="dst-nav-badge">' . ($badge > 99 ? '99+' : $badge) . '</span>';
    }
    return $html . '</a></li>';
};
$group = static function (string $label): string {
    return '<li class="nav-item" role="presentation"><span class="nav-link dst-group-label">' . esc($label) . '</span></li>';
};
?>
<aside class="app-sidebar shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <span class="dst-brand-mark">D</span>
        <span>
            <span class="dst-brand-name">DESTRA</span>
            <span class="dst-brand-sub d-block">Content platform</span>
        </span>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-1" role="navigation">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <?= $group('Overview') ?>
                <?= $item('dashboard', admin_url(), 'fa-solid fa-gauge-high', 'Dashboard') ?>
                <?= $item('reports', admin_url('reports'), 'fa-solid fa-chart-line', 'Reports') ?>
                <?= $item('calendar', admin_url('calendar'), 'fa-regular fa-calendar-days', 'Calendar') ?>

                <?= $group('Content') ?>
                <?= $item('content', admin_url('content'), 'fa-regular fa-file-lines', 'All content') ?>
                <?= $item('content-post', admin_url('content?type=post'), 'fa-regular fa-newspaper', 'Posts') ?>
                <?= $item('content-page', admin_url('content?type=page'), 'fa-regular fa-file', 'Pages') ?>
                <?= $item('content-doc', admin_url('content?type=doc'), 'fa-solid fa-book-open', 'Documentation') ?>
                <?= $item('sections', admin_url('sections'), 'fa-solid fa-layer-group', 'Sections') ?>
                <?= $item('categories', admin_url('categories'), 'fa-solid fa-folder-tree', 'Categories') ?>
                <?= $item('tags', admin_url('tags'), 'fa-solid fa-tags', 'Tags') ?>
                <?= $item('comments', admin_url('comments'), 'fa-regular fa-comments', 'Comments', $counts['comments']) ?>
                <?= $item('media', admin_url('media'), 'fa-regular fa-images', 'Media library') ?>

                <?= $group('Engagement') ?>
                <?= $item('forms', admin_url('forms'), 'fa-brands fa-wpforms', 'Forms') ?>
                <?= $item('inbox', admin_url('inbox'), 'fa-regular fa-envelope-open', 'Inbox', $counts['inbox']) ?>
                <?= $item('orders', admin_url('orders'), 'fa-solid fa-bag-shopping', 'Orders', $counts['orders']) ?>
                <?= $item('newsletter', admin_url('newsletter'), 'fa-regular fa-paper-plane', 'Newsletter') ?>
                <?= $item('banners', admin_url('banners'), 'fa-regular fa-rectangle-ad', 'Banners & popups') ?>

                <?= $group('Structure') ?>
                <?= $item('menus', admin_url('menus'), 'fa-solid fa-bars', 'Menus') ?>
                <?= $item('languages', admin_url('languages'), 'fa-solid fa-language', 'Languages') ?>
                <?= $item('redirects', admin_url('redirects'), 'fa-solid fa-signs-post', 'Redirects') ?>
                <?= $item('seo', admin_url('seo'), 'fa-solid fa-magnifying-glass-chart', 'SEO suite') ?>

                <?= $group('People') ?>
                <?= $item('users', admin_url('users'), 'fa-solid fa-users', 'Users') ?>
                <?= $item('roles', admin_url('roles'), 'fa-solid fa-user-shield', 'Roles & permissions') ?>

                <?= $group('Appearance') ?>
                <?= $item('themes', admin_url('themes'), 'fa-solid fa-palette', 'Themes') ?>
                <?= $item('plugins', admin_url('plugins'), 'fa-solid fa-plug', 'Plugins') ?>
                <?= $item('settings', admin_url('settings'), 'fa-solid fa-sliders', 'Settings') ?>
                <?php if (user_can('settings.manage')): ?>
                    <?= $item('social-login', admin_url('social-login'), 'fa-solid fa-share-nodes', 'Social sign-in') ?>
                <?php endif; ?>

                <?= $group('System') ?>
                <?= $item('tools', admin_url('tools'), 'fa-solid fa-screwdriver-wrench', 'Tools & cache') ?>
                <?= $item('api', admin_url('api-tokens'), 'fa-solid fa-code', 'API tokens') ?>
                <?= $item('notifications', admin_url('notifications'), 'fa-regular fa-bell', 'Notifications') ?>
            </ul>
        </nav>
    </div>

    <div class="dst-sidebar-foot">
        <strong><?= esc($siteName ?? 'DESTRA CMS') ?></strong><br>
        v<?= esc(config(\Config\Destra::class)->version) ?> · <?= esc(config(\Config\Destra::class)->codename) ?>
    </div>
</aside>
