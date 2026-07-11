<?php

$contactNom = trim((string) ($contact['nom'] ?? 'DynamoMenu'));
$contactInfos = trim((string) ($contact['infos'] ?? ''));
$contactAdresse = trim((string) ($contact['adresse'] ?? ''));
$contactTel = trim((string) ($contact['telephone'] ?? ''));
$contactEmail = trim((string) ($contact['email'] ?? ''));
$contactWhatsapp = trim((string) ($contact['whatsapp'] ?? ''));
$hasContactDetails = $contactTel !== '' || $contactEmail !== '' || $contactWhatsapp !== '';
$hasContactBlock = $contact !== null;
?>
<footer class="client-footer" role="contentinfo">
    <div class="client-footer__inner">
        <?php if ($hasInfo): ?>
        <div class="client-footer__grid">
            <div class="client-footer__col client-footer__col--brand">
                <a class="client-footer-brand" href="<?php echo htmlspecialchars($homeHref); ?>">
                    Dynamo<span>Menu</span>
                </a>
                <?php if ($contactInfos !== ''): ?>
                <p class="client-footer__tagline"><?php echo htmlspecialchars($contactInfos); ?></p>
                <?php endif; ?>
                <nav class="client-footer__nav" aria-label="Navigation pied de page">
                    <a href="<?php echo htmlspecialchars($homeHref); ?>">Accueil</a>
                    <a href="<?php echo htmlspecialchars($menuHref); ?>">Menu</a>
                    <a href="<?php echo htmlspecialchars($aboutHref); ?>">À propos</a>
                </nav>
            </div>

            <?php if (!empty($horairesLines)): ?>
            <div class="client-footer__col">
                <h3 class="client-footer__title">Horaires</h3>
                <ul class="client-footer__list client-footer__list--hours">
                    <?php foreach ($horairesLines as $line): ?>
                    <li><?php echo htmlspecialchars($line); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($hasContactBlock): ?>
            <div class="client-footer__col">
                <h3 class="client-footer__title">Établissement</h3>
                <p class="client-footer__name"><?php echo htmlspecialchars($contactNom); ?></p>
                <?php if ($contactAdresse !== ''): ?>
                <p class="client-footer__text">
                    <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($contactAdresse); ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="client-footer__col">
                <h3 class="client-footer__title">Contact</h3>
                <ul class="client-footer__list client-footer__list--contact">
                    <?php if ($contactTel !== ''): ?>
                    <li>
                        <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $contactTel)); ?>">
                            <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($contactTel); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($contactEmail !== ''): ?>
                    <li>
                        <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>">
                            <i class="bi bi-envelope-fill" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($contactEmail); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($contactWhatsapp !== ''): ?>
                    <li>
                        <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $contactWhatsapp)); ?>" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($contactWhatsapp); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!$hasContactDetails): ?>
                    <li class="client-footer__text">Coordonnées disponibles à l'accueil.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="client-footer__grid client-footer__grid--minimal">
            <a class="client-footer-brand" href="<?php echo htmlspecialchars($homeHref); ?>">
                Dynamo<span>Menu</span>
            </a>
            <nav class="client-footer__nav client-footer__nav--minimal" aria-label="Navigation pied de page">
                <a href="<?php echo htmlspecialchars($homeHref); ?>">Accueil</a>
                <a href="<?php echo htmlspecialchars($menuHref); ?>">Menu</a>
                <a href="<?php echo htmlspecialchars($aboutHref); ?>">À propos</a>
            </nav>
        </div>
        <?php endif; ?>

        <div class="client-footer__bar">
            <p class="client-footer-copy">&copy; <?php echo $year; ?> DynamoMenu. Tous droits réservés.</p>
            <a class="client-footer__staff-link" href="../login.php">Espace employé</a>
        </div>
    </div>
</footer>
