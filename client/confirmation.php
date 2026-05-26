<?php
session_start();

// Vérifier que le panier n'est pas vide
if (empty($_SESSION['panier'])) {
    header('Location: panier.php');
    exit;
}

// Configuration de la base de données
$db_config = require '../config/db.php';
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

// Récupérer les tables disponibles
$tables = $pdo->query("SELECT * FROM table_restaurant ORDER BY num_table")->fetchAll(PDO::FETCH_ASSOC);

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

$selected_table = trim($_POST['num_table'] ?? '1');
$availableTableNumbers = array_column($tables, 'num_table');
if (!in_array($selected_table, $availableTableNumbers)) {
    $selected_table = $availableTableNumbers[0] ?? '1';
}

// Traiter la confirmation de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_commande'])) {
    // Récupérer les données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $num_table = trim($_POST['num_table'] ?? $selected_table);
    
    // Vérifier les données
    if (empty($nom) || empty($prenom) || empty($num_table)) {
        $error = "Veuillez remplir tous les champs obligatoires";
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
            } else {
                $stmt = $pdo->prepare("INSERT INTO client (nom_client, prenom_client, email_client, telephone_client) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $prenom, $email, $telephone]);
                $id_client = $pdo->lastInsertId();
            }
            
            // 2. Créer la commande
            $stmt = $pdo->prepare("
                INSERT INTO commande (id_client, num_table, montant_total, statut) 
                VALUES (?, ?, ?, 'en_attente')
            ");
            $stmt->execute([$id_client, $num_table, $total_ttc]);
            $num_commande = $pdo->lastInsertId();
            
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
                'table' => $num_table
            ];
            unset($_SESSION['panier']);
            
            header('Location: confirmation_success.php?commande=' . $num_commande);
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
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?php echo $_POST['email'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" name="telephone" class="form-control"
                                           value="<?php echo $_POST['telephone'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Numéro de table</label>
                            <div class="table-select">
                                <?php foreach ($tables as $table): ?>
                                <input type="radio" name="num_table" value="<?php echo $table['num_table']; ?>" 
                                       id="table_<?php echo $table['num_table']; ?>" 
                                       class="table-option" required
                                       <?php echo ($selected_table == $table['num_table']) ? 'checked' : ''; ?>>
                                <label for="table_<?php echo $table['num_table']; ?>" class="table-label">
                                    <span class="table-number"><?php echo $table['num_table']; ?></span>
                                    <span class="table-places"><?php echo $table['nombre_place']; ?> places</span>
                                </label>
                                <?php endforeach; ?>
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
                        
                        <div class="recap-total">
                            <div>Total TTC</div>
                            <div>€<?php echo number_format($total_ttc, 2); ?></div>
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
        
        // Initialiser la première table comme sélectionnée
        document.querySelector('.table-option').checked = true;
        document.querySelector('.table-option').dispatchEvent(new Event('change'));
    </script>
</body>
</html>