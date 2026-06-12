<?php

use App\View\Admin\AdminLayoutView;
use App\View\Staff\DashboardLayoutView;
?>
<!doctype html>
<html lang="fr">
<head>
    <?php DashboardLayoutView::assetLinks($title); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <header class="dashboard-topbar">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
        <div class="dashboard-topbar-brand">Dynamo<span>Menu</span></div>
        <div style="width:42px;"></div>
    </header>
    <?php AdminLayoutView::sidebar($active); ?>
    <div class="dashboard-shell">
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow"><?php echo htmlspecialchars($eyebrow); ?></span>
                    <h1><?php echo htmlspecialchars($heading); ?></h1>
                    <?php if ($subtitle !== ''): ?><p><?php echo htmlspecialchars($subtitle); ?></p><?php endif; ?>
                </div>
            </header>
