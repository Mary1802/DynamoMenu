<?php
session_start();

if (empty($_SESSION['panier'])) {
    header('Location: panier.php');
    exit;
}

$db_config = require '../config/db.php';
require_once __DIR__ . '/../includes/table_context.php';
require_once __DIR__ . '/../includes/fidelity_service.php';
try {
    $pdo = new PDO(
        "mysql:host=" . $db_config['host'] . ";dbname=" . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Erreur de connexion: ' . $e->getMessage());
}

bootstrap_table_context($pdo);
fidelity_ensure($pdo);
$tableCtx = table_session();
$recompenses_fidelite = fidelity_list_rewards($pdo);
if (!$tableCtx) {
    header('Location: index.php?err=table');
    exit;
}

$tables = $pdo->query('SELECT * FROM table_restaurant WHERE actif = 1 ORDER BY num_table')->fetchAll(PDO::FETCH_ASSOC);

// Vérifier que les colonnes client attendues existent avant d’utiliser ALTER TABLE
$clientColumns = array_column($pdo->query("SHOW COLUMNS FROM client")->fetchAll(PDO::FETCH_ASSOC), 'Field');
if (!in_array('prenom_client', $clientColumns, true)) {
    $pdo->exec("ALTER TABLE client ADD COLUMN prenom_client VARCHAR(100) NULL AFTER nom_client");
    $clientColumns[] = 'prenom_client';
}
if (!in_array('email_client', $clientColumns, true)) {
    $pdo->exec("ALTER TABLE client ADD COLUMN email_client VARCHAR(100) NULL AFTER prenom_client");
    $clientColumns[] = 'email_client';
}
if (!in_array('telephone_client', $clientColumns, true)) {
    $pdo->exec("ALTER TABLE client ADD COLUMN telephone_client VARCHAR(20) NULL AFTER email_client");
}

// Calculer le total
$total_panier = 0;
foreach ($_SESSION['panier'] as $item) {
    $total_panier += $item['sous_total'];
}
$tva_rate = 0.16; // 16% de TVA
$tva_amount = $total_panier * $tva_rate;
$total_ttc = $total_panier + $tva_amount;

$selected_table = (string) $tableCtx['num_table'];

// Traiter la confirmation de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_commande'])) {
    // Récupérer les données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $num_table = $selected_table;
    $mode_paiement = $_POST['mode_paiement_souhaite'] ?? '';
    
    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($num_table)) {
        $error = 'Veuillez remplir tous les champs obligatoires (nom, prénom, email, téléphone).';
    } elseif (!in_array($mode_paiement, ['especes', 'mobile_money'], true)) {
        $error = 'Veuillez choisir un mode de paiement.';
    } else {
        // Commencer une transaction
        $pdo->beginTransaction();
        
        try {
            // 1. Créer ou récupérer le client
            $stmt = $pdo->prepare("SELECT id_client FROM client WHERE email_client = ?");
            $stmt->execute([$email]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client) {
                $id_client = $client['id_client'];
                $stmt = $pdo->prepare('UPDATE client SET nom_client = ?, prenom_client = ?, telephone_client = ? WHERE id_client = ?');
                $stmt->execute([$nom, $prenom, $telephone, $id_client]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO client (nom_client, prenom_client, email_client, telephone_client) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nom, $prenom, $email, $telephone]);
                $id_client = $pdo->lastInsertId();
            }
            
            $remise = 0.0;
            $id_recompense = null;
            $points_utilises = 0;
            $total_avant_remise = $total_panier + $tva_amount;

            if (!empty($_POST['id_recompense'])) {
                try {
                    $applied = fidelity_apply_reward($pdo, (int) $id_client, (int) $_POST['id_recompense'], $total_avant_remise);
                    $remise = $applied['remise'];
                    $id_recompense = (int) $applied['reward']['id_recompense'];
                    $points_utilises = $applied['points_requis'];
                } catch (InvalidArgumentException $ex) {
                    throw new RuntimeException($ex->getMessage());
                }
            }

            $total_ttc = max(0, round($total_avant_remise - $remise, 2));

            table_ensure_schema($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO commande (id_client, num_table, montant_total, remise_montant, id_recompense, mode_paiement_souhaite, statut) 
                VALUES (?, ?, ?, ?, ?, ?, 'en_attente')
            ");
            $stmt->execute([$id_client, $num_table, $total_ttc, $remise, $id_recompense, $mode_paiement]);
            $num_commande = $pdo->lastInsertId();

            if ($id_recompense && $points_utilises > 0) {
                fidelity_redeem_reward($pdo, (int) $id_client, $id_recompense, (int) $num_commande, $points_utilises);
            }
            
            // 3. Ajouter les détails de la commande
            foreach ($_SESSION['panier'] as $item) {
                if ($item['type'] === 'plat') {
                    $stmt = $pdo->prepare("
                        INSERT INTO contient (num_commande, id_plat, quantite, prix, sous_total, sauces)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $num_commande,
                        $item['id'],
                        $item['quantite'],
                        $item['prix'],
                        $item['sous_total'],
                        $item['sauces'] ?? ''
                    ]);
                } elseif ($item['type'] === 'boisson') {
                    $stmt = $pdo->prepare("
                        INSERT INTO contient (num_commande, id_boisson, quantite, prix, sous_total, personnalisation_boisson)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $num_commande,
                        $item['id'],
                        $item['quantite'],
                        $item['prix'],
                        $item['sous_total'],
                        $item['personnalisation'] ?? ''
                    ]);
                }
            }
            
            // 4. Valider la transaction
            $pdo->commit();
            
            // 5. Vider le panier et rediriger
            $_SESSION['commande_confirmee'] = [
                'num_commande' => $num_commande,
                'total' => $total_ttc,
                'table' => $num_table,
                'remise' => $remise,
            ];
            $_SESSION['suivi_commande_id'] = $num_commande;
            unset($_SESSION['panier']);
            
            header('Location: suivi_commande.php?commande=' . $num_commande);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la création de la commande: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmation de Commande - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(255,111,31,0.14), transparent 22%),
                        linear-gradient(180deg, #071119 0%, #0f172a 50%, #111827 100%);
            color: #f8fafc;
        }

        .confirmation-container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 3rem 1rem 4rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #ff6f1f;
        }
        
        .confirmation-card {
            background: rgba(15, 23, 42, 0.94);
            border-radius: 24px;
            border: 1px solid rgba(255,111,31,0.15);
            box-shadow: 0 24px 80px rgba(0,0,0,0.35);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control,
        .form-control:focus,
        .form-control:disabled {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(148,163,184,0.24);
            border-radius: 14px;
            font-size: 1rem;
            background: rgba(15, 23, 42, 0.8);
            color: #e2e8f0;
            box-shadow: none;
        }
        
        .form-control::placeholder {
            color: rgba(226,232,240,0.6);
        }
        
        .form-control:focus {
            border-color: #ff6f1f;
            box-shadow: 0 0 0 4px rgba(255,111,31,0.12);
            outline: none;
        }
        
        .required::after {
            content: ' *';
            color: #f87171;
        }
        
        .recap-panier {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(148,163,184,0.16);
        }
        
        .recap-item {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(148,163,184,0.12);
            color: #cbd5e1;
        }
        
        .recap-item:last-child {
            border-bottom: none;
        }
        
        .recap-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-top: 2px solid rgba(255,111,31,0.24);
            font-weight: 700;
            font-size: 1.2rem;
            color: #f8fafc;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
            border: none;
            border-radius: 14px;
            color: white;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-confirm:hover {
            background: linear-gradient(135deg, #ff8a3d, #ff6f1f);
            transform: translateY(-2px);
            box-shadow: 0 18px 45px rgba(255,111,31,0.28);
        }
        
        .error-message {
            background: rgba(248,215,218,0.18);
            color: #f8d7da;
            padding: 1rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(248,215,218,0.35);
        }
        
        .table-select {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .table-option {
            display: none;
        }
        
        .table-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border: 2px solid rgba(148,163,184,0.24);
            border-radius: 18px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            background: rgba(15, 23, 42, 0.75);
            color: #cbd5e1;
        }
        
        .table-option:checked + .table-label {
            border-color: #ff6f1f;
            background: rgba(255, 111, 31, 0.14);
            color: #f8fafc;
        }
        
        .table-number {
            font-size: 1.6rem;
            font-weight: 700;
            color: inherit;
        }
        
        .table-places {
            font-size: 0.9rem;
            color: rgba(226,232,240,0.72);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-relative py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <span style="color: #ff6f1f; font-weight: 700;">Dynamo</span><span style="color: #f8fafc; margin-left: 0.25rem;">Menu</span>
            </a>
            <div class="d-flex align-items-center">
                <a href="panier.php" class="btn btn-outline-light me-2">← Retour au panier</a>
            </div>
        </div>
    </nav>

    <div class="confirmation-container">
        <div class="mb-4 p-4 rounded-4" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,111,31,0.15);">
            <h1 class="mb-2" style="color: #f8fafc; font-size: 2.75rem; font-weight: 800;">Confirmation de commande</h1>
            <p class="mb-0" style="color: rgba(226,232,240,0.82); font-size: 1.05rem; max-width: 720px; line-height: 1.7;">
                Validez vos informations et envoyez votre commande directement à la cuisine. Le design est inspiré de l’accueil pour une expérience plus cohérente.
            </p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="confirmation-card">
                    <h3 class="section-title">Informations personnelles</h3>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Nom</label>
                                    <input type="text" name="nom" class="form-control" required 
                                           value="<?php echo $_POST['nom'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Prénom</label>
                                    <input type="text" name="prenom" class="form-control" required
                                           value="<?php echo $_POST['prenom'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Email</label>
                                    <input type="email" name="email" id="email" class="form-control" required
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Téléphone</label>
                                    <input type="tel" name="telephone" class="form-control" required
                                           value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Table (via QR)</label>
                            <input type="text" class="form-control" readonly
                                   value="<?php echo htmlspecialchars($tableCtx['label']); ?>">
                            <input type="hidden" name="num_table" value="<?php echo (int) $tableCtx['num_table']; ?>">
                        </div>

                        <div class="form-group" id="fidelityBlock">
                            <label class="form-label">Programme fidélité</label>
                            <p class="small text-secondary mb-2" id="fidelityInfo">Saisissez votre email pour voir vos points.</p>
                            <select name="id_recompense" id="id_recompense" class="form-control">
                                <option value="">Aucune récompense</option>
                                <?php foreach ($recompenses_fidelite as $r): ?>
                                <option value="<?php echo (int) $r['id_recompense']; ?>"
                                    data-points="<?php echo (int) $r['points_requis']; ?>"
                                    data-type="<?php echo htmlspecialchars($r['type_recompense']); ?>"
                                    data-value="<?php echo (float) $r['valeur']; ?>">
                                    <?php echo htmlspecialchars($r['libelle']); ?> (<?php echo (int) $r['points_requis']; ?> pts)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Mode de paiement prévu</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="mode_paiement_souhaite" value="especes" required
                                        <?php echo (($_POST['mode_paiement_souhaite'] ?? '') === 'especes') ? 'checked' : ''; ?>>
                                    Cash
                                </label>
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="mode_paiement_souhaite" value="mobile_money" required
                                        <?php echo (($_POST['mode_paiement_souhaite'] ?? '') === 'mobile_money') ? 'checked' : ''; ?>>
                                    Mobile money
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Instructions spéciales (optionnel)</label>
                            <textarea name="instructions" class="form-control" rows="3" 
                                      placeholder="Allergies, préférences alimentaires, etc."></textarea>
                        </div>
                        
                        <button type="submit" name="confirmer_commande" class="btn-confirm">
                            Confirmer et envoyer la commande à la cuisine
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="confirmation-card">
                    <h3 class="section-title">Récapitulatif</h3>
                    
                    <div class="recap-panier">
                        <?php foreach ($_SESSION['panier'] as $item): ?>
                        <div class="recap-item">
                            <div>
                                <strong><?php echo htmlspecialchars($item['nom']); ?></strong>
                                <div style="font-size: 0.85rem; color: #666;">
                                    x<?php echo $item['quantite']; ?>
                                    <?php if ($item['type'] === 'plat' && !empty($item['sauces'])): ?>
                                    <br>Sauces: <?php echo htmlspecialchars($item['sauces']); ?>
                                    <?php elseif ($item['type'] === 'boisson' && !empty($item['personnalisation'])): ?>
                                    <br><?php echo htmlspecialchars($item['personnalisation']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>€<?php echo number_format($item['sous_total'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="recap-item">
                            <div>Sous-total</div>
                            <div>€<?php echo number_format($total_panier, 2); ?></div>
                        </div>
                        
                        <div class="recap-item">
                            <div>TVA (16%)</div>
                            <div>€<?php echo number_format($tva_amount, 2); ?></div>
                        </div>
                        
                        <div class="recap-item" id="recapRemiseRow" style="display:none;">
                            <div>Réduction fidélité</div>
                            <div id="recapRemise">- €0.00</div>
                        </div>
                        <div class="recap-total">
                            <div>Total TTC</div>
                            <div id="recapTotal">€<?php echo number_format($total_ttc, 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de la sélection des tables
        document.querySelectorAll('.table-option').forEach(option => {
            option.addEventListener('change', function() {
                document.querySelectorAll('.table-label').forEach(label => {
                    label.style.borderColor = '#ddd';
                    label.style.background = 'white';
                });
                
                if (this.checked) {
                    const label = this.nextElementSibling;
                    label.style.borderColor = '#ff6f1f';
                    label.style.background = 'rgba(255, 111, 31, 0.1)';
                }
            });
        });
        
        const firstTable = document.querySelector('.table-option');
        if (firstTable) {
            firstTable.checked = true;
            firstTable.dispatchEvent(new Event('change'));
        }

        const totalAvantRemise = <?php echo json_encode(round($total_ttc, 2)); ?>;
        const emailEl = document.getElementById('email');
        const rewardSelect = document.getElementById('id_recompense');
        const fidelityInfo = document.getElementById('fidelityInfo');
        let clientPoints = 0;

        function computeRemise() {
            const opt = rewardSelect.options[rewardSelect.selectedIndex];
            if (!opt || !opt.value) return 0;
            const type = opt.dataset.type;
            const value = parseFloat(opt.dataset.value || '0');
            if (type === 'pourcentage') return Math.round(totalAvantRemise * (value / 100) * 100) / 100;
            if (type === 'montant_fixe') return Math.min(totalAvantRemise, value);
            return 0;
        }

        function refreshRecap() {
            const remise = computeRemise();
            const row = document.getElementById('recapRemiseRow');
            const totalEl = document.getElementById('recapTotal');
            if (remise > 0) {
                row.style.display = 'flex';
                document.getElementById('recapRemise').textContent = '- €' + remise.toFixed(2);
                totalEl.textContent = '€' + Math.max(0, totalAvantRemise - remise).toFixed(2);
            } else {
                row.style.display = 'none';
                totalEl.textContent = '€' + totalAvantRemise.toFixed(2);
            }
        }

        function filterRewards() {
            Array.from(rewardSelect.options).forEach((opt, i) => {
                if (i === 0) return;
                const need = parseInt(opt.dataset.points || '0', 10);
                opt.disabled = clientPoints < need;
            });
        }

        async function loadFidelity() {
            const email = (emailEl?.value || '').trim();
            if (!email) return;
            try {
                const res = await fetch('../api/fidelite/fidelite.php?email=' + encodeURIComponent(email));
                const data = await res.json();
                if (data.error) return;
                clientPoints = data.points || 0;
                if (data.exists) {
                    fidelityInfo.textContent = clientPoints + ' points — niveau ' + (data.niveau_label || data.niveau) + '.';
                } else {
                    fidelityInfo.textContent = 'Nouveau client : vous gagnerez des points après paiement.';
                }
                filterRewards();
            } catch (e) {}
        }

        emailEl?.addEventListener('blur', loadFidelity);
        rewardSelect?.addEventListener('change', refreshRecap);
        refreshRecap();
    </script>
</body>
</html>