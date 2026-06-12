<div class="user-info">
    <div class="user-avatar"><?php echo strtoupper(substr($nom, 0, 1)); ?></div>
    <div class="user-details">
        <div class="user-name"><?php echo htmlspecialchars($nom); ?></div>
        <div class="user-role"><?php echo htmlspecialchars($roleLabel); ?></div>
    </div>
</div>
<a href="../logout.php" class="sidebar-logout-btn">
    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
    <span>Déconnexion</span>
</a>
