<?php



require_once __DIR__ . '/../bootstrap/app.php';



use App\Http\ClientPage;

use App\Http\Kernel;

use App\Support\Money;



$result = Kernel::forFile(__FILE__);

if ($result !== null) {

    extract($result, EXTR_SKIP);

}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes commandes — DynamoMenu</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        body {

            background: linear-gradient(180deg, #071119 0%, #0f172a 100%);

            color: #f8fafc;

            min-height: 100vh;

        }

        .orders-wrap {

            max-width: 640px;

            margin: 0 auto;

            padding: 1rem 1rem 2.5rem;

        }

        .orders-header { margin-bottom: 1.25rem; }

        .orders-header h1 { font-size: 1.35rem; margin-bottom: 0.25rem; }

        .orders-header p { font-size: 0.9rem; margin-bottom: 0; }

        .order-card {

            background: rgba(7, 7, 12, 0.88);

            border: 1px solid rgba(255,111,31,0.18);

            border-radius: 14px;

            padding: 1rem 1.1rem;

            margin-bottom: 0.75rem;

        }

        .order-statut {

            display: inline-block;

            padding: 0.18rem 0.5rem;

            border-radius: 999px;

            background: rgba(255,111,31,0.15);

            color: #f4c95a;

            font-size: 0.72rem;

            font-weight: 600;

            line-height: 1.25;

        }

        .order-statut.is-ready { background: rgba(40,167,69,0.2); color: #7dcea0; }

        .order-statut.is-done { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7); }

        .order-actions {

            display: flex;

            flex-wrap: wrap;

            gap: 0.5rem;

            margin-top: 1.25rem;

        }

        .order-actions .btn { font-size: 0.85rem; }

        .order-detail-btn {

            white-space: nowrap;

            font-size: 0.8rem;

            padding: 0.3rem 0.65rem;

        }

    </style>

</head>

<body class="client-site">

    <?php ClientPage::nav('mes_commandes'); ?>



    <main class="orders-wrap">

        <div class="orders-header">

            <h1 class="text-white">Mes commandes</h1>

            <p class="text-secondary">Détail et suivi de vos commandes sur cette table.</p>

        </div>



        <?php if ($orders === []): ?>

        <div class="order-card text-center py-4">

            <i class="bi bi-receipt text-secondary mb-2 d-block" style="font-size:2rem;"></i>

            <p class="text-secondary small mb-3">Aucune commande récente.</p>

            <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="btn btn-primary btn-sm" style="background:#ff6f1f;border-color:#ff6f1f;">

                Voir le menu

            </a>

        </div>

        <?php else: ?>

        <?php foreach ($orders as $o):

            $statutClass = match ($o['statut']) {

                'prete' => 'is-ready',

                'livree', 'annulee' => 'is-done',

                default => '',

            };

        ?>

        <article class="order-card">

            <div class="d-flex justify-content-between align-items-center gap-2">

                <div class="min-w-0">

                    <div class="text-white fw-semibold" style="font-size:0.95rem;">

                        #<?php echo str_pad((string) $o['num_commande'], 5, '0', STR_PAD_LEFT); ?>

                    </div>

                    <?php if ($o['date_commande'] !== ''): ?>

                    <div class="text-secondary" style="font-size:0.78rem;">

                        <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($o['date_commande']))); ?>

                    </div>

                    <?php endif; ?>

                    <span class="order-statut <?php echo $statutClass; ?> mt-1"><?php echo htmlspecialchars($o['statut_label']); ?></span>

                </div>

                <div class="text-end flex-shrink-0">

                    <div class="text-white fw-bold mb-1" style="font-size:0.95rem;"><?php echo Money::format((float) $o['montant_total']); ?></div>

                    <a href="<?php echo htmlspecialchars($o['detail_url']); ?>" class="btn btn-outline-light order-detail-btn">

                        Détail

                    </a>

                </div>

            </div>

        </article>

        <?php endforeach; ?>

        <?php endif; ?>



        <div class="order-actions">

            <a href="<?php echo htmlspecialchars($indexUrl); ?>" class="btn btn-outline-light btn-sm">

                <i class="bi bi-house-door"></i> Accueil

            </a>

            <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="btn btn-primary btn-sm" style="background:#ff6f1f;border-color:#ff6f1f;">

                <i class="bi bi-plus-lg"></i> Menu

            </a>

        </div>

    </main>

</body>

</html>


