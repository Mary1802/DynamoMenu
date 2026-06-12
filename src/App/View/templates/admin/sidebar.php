<?php

use App\View\Staff\DashboardLayoutView;
?>
<aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">DM</div>
        <div class="brand-title">DynamoMenu</div>
        <div class="brand-subtitle">Administration</div>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($items as $slug => $item): ?>
        <div class="nav-item">
            <?php $isActive = $slug === $active; ?>
            <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($item['url']); ?>"<?php echo $isActive ? ' aria-current="page"' : ''; ?>>
                <span class="nav-icon"><i class="bi <?php echo htmlspecialchars($item['icon']); ?>" aria-hidden="true"></i></span>
                <span class="nav-link-label"><?php echo htmlspecialchars($item['label']); ?></span>
            </a>
        </div>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <?php DashboardLayoutView::sidebarUserFooter('admin'); ?>
    </div>
</aside>
