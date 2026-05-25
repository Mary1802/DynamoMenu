<?php
// Page d'accueil client
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamoMenu - Accueil</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark px-4 py-3">
        <a class="navbar-brand fw-bold text-white" href="index.php">DynamoMenu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link text-white" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="menu.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#contact.php">Contact</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../login.php">Employé</a></li>
            </ul>
            <a class="btn btn-primary ms-lg-4" href="#contact.php">Contact Now</a>
        </div>
    </header>

    <main class="container-fluid px-4 py-5 hero-section">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="text-uppercase text-warning mb-3">Commandez. Mangez. Profitez ! </p>
                <h1 class="display-4 hero-title mb-4">Une nouvelle façon de commander :<br>rapide, pratique et totalement digitale</h1>
                <p class="hero-subtitle mb-4">Commandez votre repas en un clic et profitez-en dès maintenant.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a class="btn btn-primary btn-lg" href="menu.php">Commander</a>
                    <a class="btn btn-outline-light btn-lg" href="menu.php">Voir menu</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-card p-3 rounded-4 shadow-lg"></div>
            </div>
        </div>
    </main>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
