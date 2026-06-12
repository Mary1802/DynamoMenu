<?php

if ($lignes === []): ?>
<p class="kitchen-lines-empty text-secondary small mb-0">Aucun détail article.</p>
<?php else: ?>
<ul class="kitchen-lines-list">
    <?php foreach ($lignes as $line):
        $isPlat = !empty($line['nom_plat']);
        $nom = $isPlat ? (string) $line['nom_plat'] : (string) ($line['nom_boisson'] ?? 'Article');
        ?>
    <li class="kitchen-line <?php echo $isPlat ? 'kitchen-line--plat' : 'kitchen-line--boisson'; ?>">
        <span class="kitchen-line-qty">×<?php echo (int) $line['quantite']; ?></span>
        <div class="kitchen-line-body">
            <span class="kitchen-line-name"><?php echo htmlspecialchars($nom); ?></span>
            <?php if ($isPlat && !empty($line['sauces'])): ?>
            <span class="kitchen-line-extra"><i class="bi bi-droplet" aria-hidden="true"></i> Sauces : <strong><?php echo htmlspecialchars((string) $line['sauces']); ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($line['personnalisation_boisson'])): ?>
            <span class="kitchen-line-extra"><i class="bi bi-cup-straw" aria-hidden="true"></i> <?php echo htmlspecialchars((string) $line['personnalisation_boisson']); ?></span>
            <?php endif; ?>
        </div>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
