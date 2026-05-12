<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamoMenu </title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="logo" href="#accueil">DynamoMenu</a>
            <nav class="main-nav">
                <a href="#accueil" class="active">Accueil</a>
                <a href="menu.php">Menu</a>
                <a href="menu.php">Commander</a>
                <a href="auth/login.php">Espace personnel</a>
                <a href="#contact">Contact</a>
            </nav>
            <a class="btn btn-primary" href="menu.php">Voir le menu</a>
        </div>
    </header>

    <main>
        <section class="hero" id="accueil">
            <div class="container hero-content">
                <div class="hero-copy">
                    <span class="eyebrow">Expérience client moderne</span>
                    <h1>Consultez le menu, commandez en autonomie et recevez l’addition instantanée.</h1>
                    <p>Un système digital entièrement pensé pour les restaurants qui veulent accélérer le service et alléger le travail du personnel.</p>
                    <div class="hero-actions">
                        <a class="btn btn-primary" href="menu.php">Voir le menu</a>
                        <a class="btn btn-secondary" href="menu.php">Commander maintenant</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="dish-card">
                        <img class="hero-image" src="assets/images/Fruits%20de%20mer.jpg" alt="Assiette de fruits de mer">
                        <span class="tag">Plat signature</span>
                        <h2>Saint-Jacques au safran</h2>
                        <p>Une étoile gastronomique, servie avec une sauce onctueuse et légumes de saison.</p>
                        <div class="dish-meta">
                            <span>€18,90</span>
                            <span>Disponible</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="features">
            <div class="container section-grid">
                <article class="feature-card">
                    <h3>Menu digital intuitif</h3>
                    <p>Un affichage clair et attractif pour présenter vos plats avec élégance.</p>
                </article>
                <article class="feature-card">
                    <h3>Commande autonome</h3>
                    <p>Les clients passent leur commande sans attendre le serveur, directement depuis l’écran.</p>
                </article>
                <article class="feature-card">
                    <h3>Addition immédiate</h3>
                    <p>Une facture générée automatiquement à la validation de la commande.</p>
                </article>
            </div>
        </section>

        <section class="menu-preview" id="menu">
            <div class="container">
                <div class="section-header">
                    <span class="eyebrow">Menu</span>
                    <h2>Nos plats les plus demandés</h2>
                    <p>Découvrez une sélection raffinée, pensée pour séduire rapidement vos clients.</p>
                </div>
                <div class="menu-grid">
                    <article class="menu-item">
                        <img src="assets/images/Riz%20blanc.jpg" alt="Riz blanc parfumé">
                        <h3>Riz blanc parfumé</h3>
                        <p>Accompagnement léger et soyeux pour sublimer chaque plat principal.</p>
                        <div class="item-meta"><span>€6,50</span></div>
                    </article>
                    <article class="menu-item">
                        <img src="assets/images/Poulet%20mayo.jpg" alt="Poulet mayo gourmand">
                        <h3>Poulet mayo gourmand</h3>
                        <p>Filet de poulet croustillant, nappé d’une mayonnaise maison onctueuse.</p>
                        <div class="item-meta"><span>€12,90</span></div>
                    </article>
                    <article class="menu-item">
                        <img src="assets/images/Poisson%20fumé.jpg" alt="Poisson fumé rôti">
                        <h3>Poisson fumé rôti</h3>
                        <p>Un plat délicat, parfumé et servi avec une touche de citron et fines herbes.</p>
                        <div class="item-meta"><span>€17,50</span></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="cta-panel" id="commande">
            <div class="container cta-card">
                <div>
                    <h2>Prêt à améliorer votre service ?</h2>
                    <p>Gagnez en efficacité, réduisez les files d’attente et proposez une expérience client premium.</p>
                </div>
                <a class="btn btn-primary" href="#menu">Commencer</a>
            </div>
        </section>
    </main>

    <footer class="site-footer" id="contact">
        <div class="container footer-grid">
            <div>
                <h3>DynamoMenu</h3>
                <p>Solution de commande autonome pour restaurants, conçue pour une image haut de gamme et un service optimisé.</p>
            </div>
            <div>
                <h4>Contact</h4>
                <p>contact@dynamomenu.local</p>
                <p>+33 1 23 45 67 89</p>
            </div>
        </div>
    </footer>
</body>
</html>