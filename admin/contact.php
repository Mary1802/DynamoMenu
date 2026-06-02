<?php

require_once __DIR__ . '/../includes/admin_layout.php';

$contacts = dashboard_contacts();

admin_shell_start('Admin — Contact', 'contact', 'Administration', 'Contacts', 'Coordonnées affichées aux équipes et clients.');
?>
<div class="contact-grid">
    <div class="contact-card chart-container">
        <h4><?php echo htmlspecialchars($contacts['nom'] ?? 'DynamoMenu'); ?></h4>
        <p class="text-secondary mb-2"><?php echo htmlspecialchars($contacts['adresse'] ?? ''); ?></p>
        <p class="mb-2"><i class="bi bi-telephone me-2"></i><a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $contacts['telephone'] ?? '')); ?>"><?php echo htmlspecialchars($contacts['telephone'] ?? ''); ?></a></p>
        <?php if (!empty($contacts['whatsapp'])): ?>
        <p class="mb-2"><i class="bi bi-whatsapp me-2"></i><a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $contacts['whatsapp'])); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($contacts['whatsapp']); ?></a></p>
        <?php endif; ?>
        <p class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:<?php echo htmlspecialchars($contacts['email'] ?? ''); ?>"><?php echo htmlspecialchars($contacts['email'] ?? ''); ?></a></p>
        <?php if (!empty($contacts['horaires'])): ?>
        <p class="mb-0 text-secondary small"><i class="bi bi-clock me-2"></i><?php echo htmlspecialchars($contacts['horaires']); ?></p>
        <?php endif; ?>
    </div>
</div>
<p class="text-secondary small mt-3">Modifiez ces informations dans <code>config/app.php</code> (clé <code>contacts</code>).</p>
<?php admin_shell_end(); ?>
