<?php
require_once __DIR__ . '/../includes/client_session.php';
client_session_start();
$db_config = require __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/table_context.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';
require_once __DIR__ . '/../includes/client_footer.php';
require_once __DIR__ . '/../includes/client_header.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    bootstrap_table_context($pdo);
    table_redirect_after_scan('index.php');
} catch (PDOException $e) {
    die('Erreur de connexion');
}

$tableCtx = table_session();
$tableError = $_SESSION['table_error'] ?? null;
unset($_SESSION['table_error']);
$scanError = isset($_GET['err']) && $_GET['err'] === 'table';
$menuUrl = table_link('menu.php');
$panierUrl = table_link('panier.php');
$appConfig = require __DIR__ . '/../config/app.php';
$contactRows = [];
try {
    $contactRows = dashboard_contact_list($pdo);
} catch (Throwable $e) {
    // fallback to config file
}
if ($contactRows === [] && is_array($appConfig['contacts'] ?? null) && $appConfig['contacts'] !== []) {
    $contactRows = [$appConfig['contacts']];
}

$hasContactSection = false;
foreach ($contactRows as $row) {
    $nom = trim((string) ($row['nom'] ?? $row['nom_etablissement'] ?? ''));
    if ($nom !== ''
        || trim((string) ($row['adresse'] ?? '')) !== ''
        || trim((string) ($row['horaires'] ?? '')) !== ''
        || trim((string) ($row['telephone'] ?? '')) !== ''
        || trim((string) ($row['email'] ?? '')) !== ''
        || trim((string) ($row['whatsapp'] ?? '')) !== '') {
        $hasContactSection = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamoMenu - Accueil</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=7">
</head>
<body class="client-site">
    <header class="navbar navbar-expand-lg navbar-dark px-4 py-3">
        <a class="navbar-brand fw-bold text-white" href="<?php echo htmlspecialchars(table_link('index.php')); ?>">DynamoMenu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo htmlspecialchars(table_link('index.php')); ?>">Accueil</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo htmlspecialchars($menuUrl); ?>">Menu</a></li>
                <li class="nav-item">
                    <a class="nav-link text-white position-relative" href="<?php echo htmlspecialchars($panierUrl); ?>">
                        Panier
                        <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">0</span>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link text-white" href="../login.php">Employé</a></li>
            </ul>
            <a class="btn btn-primary ms-lg-4" href="#contact">Nous contacter</a>
        </div>
    </header>

    <main class="container-fluid px-4 py-5 hero-section">
        <?php if ($tableError || $scanError): ?>
            <?php render_client_table_error($tableError ?: 'QR code invalide. Rescannez le code affiché sur votre table.'); ?>
        <?php elseif ($tableCtx): ?>
            <?php render_client_table_welcome($tableCtx); ?>
        <?php endif; ?>
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="text-uppercase text-warning mb-3">Commandez. Mangez. Profitez !</p>
                <h1 class="display-4 hero-title mb-4">Une nouvelle façon de commander :<br>rapide, pratique et totalement digitale</h1>
                <p class="hero-subtitle mb-4">Commandez votre repas en un clic et profitez-en dès maintenant.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a class="btn btn-primary btn-lg" href="<?php echo htmlspecialchars($menuUrl); ?>">Commander</a>
                    <a class="btn btn-outline-light btn-lg" href="<?php echo htmlspecialchars($menuUrl); ?>">Voir menu</a>
                </div>
            </div>
            <div class="col-lg-6 text-center hero-image-col d-none d-lg-block">
                <img src="../assets/images/combo/combo burger frites poulet.jpg" alt="Combo Burger Frites Poulet" class="img-fluid rounded-4 shadow-lg hero-home-image">
            </div>
        </div>
    </main>

    <?php if ($hasContactSection): ?>
    <section id="contact" class="home-info-section container-fluid px-4 pb-5">
        <div class="row justify-content-center mb-4 mb-lg-5">
            <div class="col-lg-8 text-center">
                <p class="text-uppercase text-warning mb-2 small fw-semibold">Informations pratiques</p>
                <h2 class="menu-title display-6 fw-bold mb-3">Nous trouver &amp; nous contacter</h2>
                <p class="hero-subtitle mb-0">Toutes les informations utiles pour votre visite, au même endroit.</p>
            </div>
        </div>

        <?php foreach ($contactRows as $contactIndex => $contacts):
            $contactNom = trim((string) ($contacts['nom'] ?? $contacts['nom_etablissement'] ?? 'DynamoMenu'));
            $contactInfos = trim((string) ($contacts['infos'] ?? $contacts['description'] ?? ''));
            if ($contactInfos === '') {
                $contactInfos = 'Restaurant avec service sur place. Commandez depuis votre table via le menu digital.';
            }
            $contactAdresse = trim((string) ($contacts['adresse'] ?? ''));
            $contactHoraires = trim((string) ($contacts['horaires'] ?? ''));
            $contactTel = trim((string) ($contacts['telephone'] ?? ''));
            $contactEmail = trim((string) ($contacts['email'] ?? ''));
            $contactWhatsapp = trim((string) ($contacts['whatsapp'] ?? ''));
            if ($contactNom === '' && $contactAdresse === '' && $contactHoraires === ''
                && $contactTel === '' && $contactEmail === '' && $contactWhatsapp === '') {
                continue;
            }
            $horairesLines = [];
            if ($contactHoraires !== '') {
                foreach (preg_split('/[\n;|]+/', $contactHoraires) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $horairesLines[] = $line;
                    }
                }
                if ($horairesLines === []) {
                    $horairesLines[] = $contactHoraires;
                }
            }
            $rowClass = 'row g-4 g-lg-4 justify-content-center';
            if ($contactIndex > 0) {
                $rowClass .= ' mt-4 pt-4 border-top border-secondary border-opacity-25';
            }
        ?>
        <div class="<?php echo $rowClass; ?>">
            <?php if (count($contactRows) > 1): ?>
            <div class="col-12 text-center mb-2">
                <h3 class="h5 text-warning mb-0"><?php echo htmlspecialchars($contactNom); ?></h3>
            </div>
            <?php endif; ?>
            <div class="col-md-6 col-lg-4">
                <article class="home-info-card h-100">
                    <div class="home-info-card-accent" aria-hidden="true"></div>
                    <div class="home-info-card-body">
                        <header class="home-info-card-header">
                            <span class="home-info-card-icon"><i class="bi bi-shop" aria-hidden="true"></i></span>
                            <div>
                                <p class="home-info-card-label">À propos</p>
                                <h3 class="home-info-card-title">Établissement</h3>
                            </div>
                        </header>
                        <div class="home-info-card-content">
                            <p class="home-info-name"><?php echo htmlspecialchars($contactNom); ?></p>
                            <?php if ($contactInfos !== ''): ?>
                            <p class="home-info-desc"><?php echo htmlspecialchars($contactInfos); ?></p>
                            <?php endif; ?>
                            <?php if ($contactAdresse !== ''): ?>
                            <div class="home-info-detail-box">
                                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars($contactAdresse); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </div>

            <div class="col-md-6 col-lg-4">
                <article class="home-info-card h-100">
                    <div class="home-info-card-accent" aria-hidden="true"></div>
                    <div class="home-info-card-body">
                        <header class="home-info-card-header">
                            <span class="home-info-card-icon"><i class="bi bi-clock" aria-hidden="true"></i></span>
                            <div>
                                <p class="home-info-card-label">Ouverture</p>
                                <h3 class="home-info-card-title">Horaires</h3>
                            </div>
                        </header>
                        <div class="home-info-card-content">
                            <?php if ($horairesLines !== []): ?>
                            <ul class="home-info-schedule">
                                <?php foreach ($horairesLines as $line): ?>
                                <li><?php echo htmlspecialchars($line); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <p class="home-info-desc mb-0">Horaires communiqués à l'accueil du restaurant.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </div>

            <div class="col-md-12 col-lg-4">
                <article class="home-info-card h-100">
                    <div class="home-info-card-accent" aria-hidden="true"></div>
                    <div class="home-info-card-body">
                        <header class="home-info-card-header">
                            <span class="home-info-card-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                            <div>
                                <p class="home-info-card-label">Joignez-nous</p>
                                <h3 class="home-info-card-title">Contact</h3>
                            </div>
                        </header>
                        <div class="home-info-card-content home-info-card-content--contact">
                            <?php if ($contactTel !== ''): ?>
                            <a class="home-info-contact-row" href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $contactTel)); ?>">
                                <span class="home-info-contact-icon"><i class="bi bi-telephone-fill" aria-hidden="true"></i></span>
                                <span class="home-info-contact-text">
                                    <span class="home-info-contact-kind">Téléphone</span>
                                    <span class="home-info-contact-value"><?php echo htmlspecialchars($contactTel); ?></span>
                                </span>
                                <i class="bi bi-chevron-right home-info-contact-arrow" aria-hidden="true"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($contactEmail !== ''): ?>
                            <a class="home-info-contact-row" href="mailto:<?php echo htmlspecialchars($contactEmail); ?>">
                                <span class="home-info-contact-icon"><i class="bi bi-envelope-fill" aria-hidden="true"></i></span>
                                <span class="home-info-contact-text">
                                    <span class="home-info-contact-kind">Email</span>
                                    <span class="home-info-contact-value"><?php echo htmlspecialchars($contactEmail); ?></span>
                                </span>
                                <i class="bi bi-chevron-right home-info-contact-arrow" aria-hidden="true"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($contactWhatsapp !== ''): ?>
                            <a class="home-info-contact-row home-info-contact-row--whatsapp" href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $contactWhatsapp)); ?>" target="_blank" rel="noopener">
                                <span class="home-info-contact-icon"><i class="bi bi-whatsapp" aria-hidden="true"></i></span>
                                <span class="home-info-contact-text">
                                    <span class="home-info-contact-kind">WhatsApp</span>
                                    <span class="home-info-contact-value"><?php echo htmlspecialchars($contactWhatsapp); ?></span>
                                </span>
                                <i class="bi bi-chevron-right home-info-contact-arrow" aria-hidden="true"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($contactTel === '' && $contactEmail === '' && $contactWhatsapp === ''): ?>
                            <p class="home-info-desc mb-0">Coordonnées disponibles à l'accueil.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php render_client_footer(); ?>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCartBadge() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = data.count === 0 ? 'none' : 'block';
                    }
                });
        }
        document.addEventListener('DOMContentLoaded', updateCartBadge);
        setInterval(updateCartBadge, 5000);
    </script>
</body>
</html>
