<?php
session_start();

// Vérifier qu'une commande a été confirmée
if (!isset($_SESSION['commande_confirmee'])) {
    header('Location: index.php');
    exit;
}

$commande = $_SESSION['commande_confirmee'];
$num_commande = $_GET['commande'] ?? $commande['num_commande'];
require_once __DIR__ . '/../includes/money.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commande Confirmée - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(255,111,31,0.14), transparent 22%),
                        linear-gradient(180deg, #071119 0%, #0f172a 50%, #111827 100%);
            color: #f8fafc;
        }

        .success-container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 3rem 1rem 4rem;
            text-align: center;
        }

        .success-card {
            background: rgba(15, 23, 42, 0.96);
            border-radius: 24px;
            border: 1px solid rgba(255, 111, 31, 0.18);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.32);
            padding: 2.5rem;
            margin-top: 2rem;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            background: rgba(255, 111, 31, 0.16);
            color: #ffb47f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }

        .success-title {
            color: #f8fafc;
            font-size: 2.6rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }

        .success-subtitle {
            color: rgba(226, 232, 240, 0.82);
            font-size: 1.05rem;
            line-height: 1.7;
            max-width: 720px;
            margin: 0 auto;
        }

        .commande-info {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 1.75rem;
            margin: 2rem 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(226, 232, 240, 0.88);
        }

        .info-row:last-child {
            border-bottom: none;
            font-weight: 700;
            color: #ffb47f;
            font-size: 1.1rem;
        }

        .status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }

        .status-timeline::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 0;
            right: 0;
            height: 2px;
            background: rgba(255, 255, 255, 0.12);
            z-index: 1;
        }

        .status-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            width: 90px;
        }

        .step-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 0.5rem;
        }

        .step-icon.active {
            background: #ff6f1f;
            border-color: #ff6f1f;
            color: white;
        }

        .step-label {
            font-size: 0.9rem;
            color: rgba(226, 232, 240, 0.74);
            text-align: center;
            line-height: 1.4;
        }

        .step-label.active {
            color: #ffb47f;
            font-weight: 600;
        }

        .instructions-box {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 111, 31, 0.12);
            border-radius: 18px;
            padding: 1.75rem;
            margin-top: 2rem;
            text-align: left;
            color: rgba(226, 232, 240, 0.9);
        }

        .instructions-box h5 {
            margin-bottom: 1rem;
            color: #f8fafc;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .instructions-box ol {
            padding-left: 1.2rem;
            margin: 0;
        }

        .instructions-box li {
            margin-bottom: 0.8rem;
            line-height: 1.65;
        }

        .note-text {
            margin-top: 1rem;
            font-style: italic;
            color: rgba(226, 232, 240, 0.72);
        }

        .btn-primary,
        .btn-secondary {
            border-radius: 14px;
            padding: 1rem 2rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ff8a3d, #ff6f1f);
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(255, 111, 31, 0.28);
            color: white;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.14);
            color: white;
        }

        .navbar-brand span:last-child {
            color: #f8fafc;
            margin-left: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-relative py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <span style="color: #ff6f1f; font-weight: 700;">Dynamo</span><span>Menu</span>
            </a>
        </div>
    </nav>

    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1 class="success-title">Commande confirmée</h1>
        <p class="success-subtitle">Votre commande a bien été transmise à la cuisine. Nous préparons tout pour que le repas arrive rapidement.</p>

        <div class="success-card">
            <div class="text-center mb-4">
                <h3 style="color: #f8fafc; font-weight: 800;">Commande #<?php echo str_pad($num_commande, 5, '0', STR_PAD_LEFT); ?></h3>
            </div>

            <div class="commande-info">
                <div class="info-row">
                    <span>Numéro de commande</span>
                    <strong>#<?php echo str_pad($num_commande, 5, '0', STR_PAD_LEFT); ?></strong>
                </div>
                <div class="info-row">
                    <span>Table</span>
                    <strong>Table <?php echo $commande['table']; ?></strong>
                </div>
                <div class="info-row">
                    <span>Date et heure</span>
                    <strong><?php echo date('d/m/Y H:i'); ?></strong>
                </div>
                <div class="info-row">
                    <span>Total à payer</span>
                    <strong><?php echo format_money((float) $commande['total']); ?></strong>
                </div>
            </div>

            <div class="status-timeline">
                <div class="status-step">
                    <div class="step-icon active">1</div>
                    <div class="step-label active">Commande<br>confirmée</div>
                </div>
                <div class="status-step">
                    <div class="step-icon">2</div>
                    <div class="step-label">En préparation</div>
                </div>
                <div class="status-step">
                    <div class="step-icon">3</div>
                    <div class="step-label">Prête à servir</div>
                </div>
                <div class="status-step">
                    <div class="step-icon">4</div>
                    <div class="step-label">Paiement à la fin</div>
                </div>
            </div>

            <div class="instructions-box">
                <h5>Prochaines étapes</h5>
                <ol>
                    <li>Votre commande est maintenant en préparation en cuisine.</li>
                    <li>Les plats et boissons seront servis à la table <?php echo $commande['table']; ?> dès qu'ils seront prêts.</li>
                    <li>Un serveur confirmera l'arrivée de votre commande.</li>
                    <li>Le paiement pourra être réglé à la fin du repas.</li>
                </ol>
                <p class="note-text">
                    Note : vous pouvez suivre l'avancement de la commande auprès du personnel.
                </p>
            </div>

            <div class="mt-4 text-center">
                <a href="index.php" class="btn-primary me-2">Retour à l'accueil</a>
                <a href="paiement_client.php?commande=<?php echo $num_commande; ?>" class="btn-secondary">Voir les détails de paiement</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(() => {
            const nextStep = document.querySelectorAll('.status-step')[1];
            if (nextStep) {
                nextStep.querySelector('.step-icon').classList.add('active');
                nextStep.querySelector('.step-label').classList.add('active');
            }
        }, 2000);
    </script>
</body>
</html>