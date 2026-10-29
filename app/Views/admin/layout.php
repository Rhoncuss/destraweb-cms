<?php
$dir     = service('localization')->isRtl() ? 'rtl' : 'ltr';
$theme   = $_COOKIE['dst_theme'] ?? 'light';
$theme   = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
$prefix  = $adminPrefix ?? 'admin';
$crumbs  = $breadcrumbs ?? [];
$adminUser = $adminUser ?? [];
?>
<!DOCTYPE html>
<html lang="<?= esc(current_locale()) ?>" dir="<?= esc($dir, 'attr') ?>" data-base="<?= base_url('/') ?>" data-bs-theme="<?= esc($theme, 'attr') ?>" data-scheme="<?= esc($scheme ?? 'midnight', 'attr') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($pageTitle ?? 'Dashboard') ?> · <?= esc($siteName ?? 'DESTRA CMS') ?></title>
    <meta name="destra_csrf" content="<?= esc(csrf_hash(), 'attr') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('vendor/adminlte/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= admin_asset('admin.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="layout-fixed sidebar-expand-lg app-body">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link dst-icon-btn" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                            <i class="fa-solid fa-bars-staggered"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-flex align-items-center ms-2">
                        <form class="dst-search" action="<?= admin_url('search') ?>" method="get" role="search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" name="q" value="<?= esc(service('request')->getGet('q') ?? '') ?>" placeholder="Search content, media, users…" aria-label="Search">
                            <kbd>⌘K</kbd>
                        </form>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    <li class="nav-item dropdown d-none d-sm-block">
                        <a class="nav-link dst-icon-btn" href="<?= base_url('/') ?>" target="_blank" rel="noopener" title="View website">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dst-icon-btn" href="<?= admin_url('notifications') ?>" title="Notifications">
                            <i class="fa-regular fa-bell"></i>
                            <?php if (! empty($unreadNotifications)): ?>
                                <span class="dst-dot"><?= $unreadNotifications > 9 ? '9+' : (int) $unreadNotifications ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <button class="nav-link dst-icon-btn" type="button" data-dst-theme-toggle title="Toggle dark mode">
                            <i class="fa-solid fa-circle-half-stroke"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <button class="dst-user-btn dropdown-toggle" data-bs-toggle="dropdown" type="button" aria-expanded="false">
                            <span class="dst-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($adminUser['name'] ?? 'A'), 0, 1))) ?></span>
                            <span class="d-none d-lg-block">
                                <span class="dst-user-name d-block"><?= esc($adminUser['name'] ?? 'Administrator') ?></span>
                                <span class="dst-user-role"><?= esc($adminUser['role_name'] ?? 'Member') ?></span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:14px;min-width:230px;">
                            <li class="px-3 py-2">
                                <div class="fw-semibold"><?= esc($adminUser['name'] ?? '') ?></div>
                                <div class="text-muted-2 small"><?= esc($adminUser['email'] ?? '') ?></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= admin_url('profile') ?>"><i class="fa-regular fa-user me-2"></i>My profile</a></li>
                            <li><a class="dropdown-item" href="<?= admin_url('settings') ?>"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('/') ?>" target="_blank"><i class="fa-solid fa-globe me-2"></i>View website</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= site_url('logout') ?>">
                                    <?= csrf_field() ?>
                                    <button class="dropdown-item text-danger" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <?= $this->include('admin/partials/sidebar') ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h1><?= esc($pageTitle ?? 'Dashboard') ?></h1>
                            <?php if (! empty($pageSubtitle)): ?>
                                <div class="subtitle"><?= esc($pageSubtitle) ?></div>
                            <?php endif; ?>
                            <?php if (! empty($crumbs)): ?>
                                <ol class="breadcrumb mt-2">
                                    <li class="breadcrumb-item"><a href="<?= admin_url() ?>">Dashboard</a></li>
                                    <?php foreach ($crumbs as $crumb): ?>
                                        <?php if (! empty($crumb['url'])): ?>
                                            <li class="breadcrumb-item"><a href="<?= esc($crumb['url'], 'attr') ?>"><?= esc($crumb['label']) ?></a></li>
                                        <?php else: ?>
                                            <li class="breadcrumb-item active" aria-current="page"><?= esc($crumb['label']) ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5 text-md-end page-actions">
                            <?= $this->renderSection('actions') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="dst-flash"
                         data-success="<?= esc((string) (session()->getFlashdata('success') ?? ''), 'attr') ?>"
                         data-error="<?= esc((string) (session()->getFlashdata('error') ?? ''), 'attr') ?>"></div>
                    <?= $this->renderSection('content') ?>
                </div>
            </div>
        </main>

        <footer class="app-footer">
            <div class="container-fluid d-flex flex-wrap justify-content-between gap-2 py-2">
                <span>&copy; <?= date('Y') ?> <?= esc($siteName ?? 'DESTRA CMS') ?>. All rights reserved.</span>
                <span>DESTRA CMS v<?= esc(config(\Config\Destra::class)->version) ?> · <?= esc(config(\Config\Destra::class)->codename) ?></span>
            </div>
        </footer>
    </div>

    <nav class="dst-mobile-nav" aria-label="Quick navigation">
        <a href="<?= admin_url() ?>" class="<?= ($navActive ?? '') === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i>Home</a>
        <a href="<?= admin_url('content') ?>" class="<?= ($navActive ?? '') === 'content' ? 'active' : '' ?>"><i class="fa-regular fa-file-lines"></i>Content</a>
        <a href="<?= admin_url('media') ?>" class="<?= ($navActive ?? '') === 'media' ? 'active' : '' ?>"><i class="fa-regular fa-images"></i>Media</a>
        <a href="<?= admin_url('comments') ?>" class="<?= ($navActive ?? '') === 'comments' ? 'active' : '' ?>"><i class="fa-regular fa-comments"></i>Comments</a>
        <a href="#" data-lte-toggle="sidebar"><i class="fa-solid fa-bars"></i>Menu</a>
    </nav>

    <script src="<?= base_url('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('vendor/adminlte/js/adminlte.min.js') ?>"></script>
    <script src="<?= base_url('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
    <script src="<?= base_url('vendor/chartjs/chart.umd.js') ?>"></script>
    <script src="<?= base_url('vendor/sortablejs/Sortable.min.js') ?>"></script>
    <script src="<?= admin_asset('admin.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
