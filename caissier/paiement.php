<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/staff_auth.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';
require_once __DIR__ . '/../includes/money.php';

use App\Controller\Caissier\PaiementController;

staff_require(['caissier']);

$data = (new PaiementController())->handle($_GET, $_POST);
$error = $data['error'];
$commandes_a_payer = $data['commandes_a_payer'];
$commande_details = $data['commande_details'];
$commande_lignes = $data['commande_lignes'];
$paiements_recents = $data['paiements_recents'];
$demandes_paiement = $data['demandes_paiement'];
$stats_jour = $data['stats_jour'];
$dashboard_error = $data['dashboard_error'];
$notif_items = $data['notif_items'];
$notif_count = $data['notif_count'];
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Caissier - Paiement des commandes'); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <header class="dashboard-topbar">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="dashboardSidebar">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div class="dashboard-topbar-brand">Dynamo<span>Menu</span></div>
        <div style="width: 42px;"></div>
    </header>

    <div class="dashboard-shell dashboard-container">
        <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">DM</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Caisse</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link active" href="paiement.php">
                        <span class="nav-icon"><i class="bi bi-credit-card" aria-hidden="true"></i></span>
                        <span>Paiements</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="rapports.php">
                        <span class="nav-icon"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i></span>
                        <span>Rapports</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="commandes.php">
                        <span class="nav-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                        <span>Commandes</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="parametres.php">
                        <span class="nav-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
                        <span>Paramètres</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <?php dashboard_sidebar_user_footer('caissier'); ?>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main flex-grow-1">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow">Caisse</span>
                    <h1>Paiement des commandes</h1>
                    <p>Facturation des commandes livrées aux tables</p>
                </div>
                
                <div class="header-actions">
                    <div class="search-box">
                        <input type="search" class="search-input" data-dashboard-search placeholder="Nom, tél., table, n° commande…" aria-label="Rechercher une commande">
                        <span class="search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
                    </div>
                    
                    <?php dashboard_render_notifications('caissier', $notif_items, $notif_count); ?>
                </div>
            </header>

            <?php if (isset($_GET['success'])): ?>
            <div class="success-message" role="status">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                Paiement de la commande #<?php echo htmlspecialchars($_GET['commande'] ?? '', ENT_QUOTES, 'UTF-8'); ?> effectué avec succès.
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="success-message" style="color: var(--danger-color); border-color: rgba(220,53,69,0.35); background: rgba(220,53,69,0.1);">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($dashboard_error)): ?>
            <div class="success-message" style="color: var(--danger-color); border-color: rgba(220,53,69,0.35); background: rgba(220,53,69,0.1);">
                <?php echo htmlspecialchars($dashboard_error); ?>
                <div class="mt-2 small">
                    <a href="../run_update.php" class="link-invoice">Exécuter run_update.php</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="stats-row stats-row--3">
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($commandes_a_payer); ?></div>
                    <div class="stat-label">À Payer</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo format_money((float) ($stats_jour['total_ca'] ?? 0)); ?></div>
                    <div class="stat-label">CA Aujourd'hui</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $stats_jour['total_paiements'] ?? 0; ?></div>
                    <div class="stat-label">Paiements</div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="main-content">
                <!-- Section Demandes de paiement -->
                <?php if (!empty($demandes_paiement)): ?>
                <div class="commandes-section mb-4">
                    <div class="section-title">
                        Demandes de paiement
                        <span class="section-count">(<?php echo count($demandes_paiement); ?> en attente)</span>
                    </div>
                    
                    <div class="row g-3">
                        <?php foreach ($demandes_paiement as $demande): ?>
                        <div class="col-12">
                            <div class="commande-item demande-highlight" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($demande)); ?>">
                                <div class="commande-header">
                                    <div class="commande-id">
                                        Demande #<?php echo str_pad($demande['id_demande'], 4, '0', STR_PAD_LEFT); ?>
                                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                                            (Commande #<?php echo str_pad($demande['num_commande'], 5, '0', STR_PAD_LEFT); ?>)
                                        </span>
                                    </div>
                                    <div class="commande-montant"><?php echo format_money((float) $demande['montant']); ?></div>
                                </div>
                                
                                <div class="commande-details">
                                    <div>
                                        <span>Table <?php echo $demande['num_table'] ?? 'N/A'; ?></span>
                                        <span> • </span>
                                        <span><?php echo htmlspecialchars(trim(($demande['prenom_client'] ?? '') . ' ' . ($demande['nom_client'] ?? ''))); ?></span>
                                    </div>
                                    <div>
                                        <?php
                                        $mode_labels = [
                                            'carte' => 'Carte',
                                            'especes' => 'Espèces',
                                            'mobile' => 'Mobile',
                                        ];
                                        ?>
                                        <span class="mode-badge mode-<?php echo htmlspecialchars($demande['mode_paiement'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo $mode_labels[$demande['mode_paiement']] ?? ucfirst($demande['mode_paiement']); ?>
                                        </span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($demande['date_demande'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="commande-actions">
                                    <a href="?voir_commande=<?php echo $demande['num_commande']; ?>" class="btn-details">
                                        <i class="bi bi-list-ul" aria-hidden="true"></i> Voir détails
                                    </a>
                                    <a href="?voir_commande=<?php echo $demande['num_commande']; ?>" class="btn-payer">
                                        <i class="bi bi-cash-coin" aria-hidden="true"></i> Traiter le paiement
                                    </a>
                                    <form method="POST" class="d-flex flex-grow-1">
                                        <input type="hidden" name="demande_id" value="<?php echo $demande['id_demande']; ?>">
                                        <button type="submit" name="annuler_demande" class="btn-details btn-danger-outline w-100">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i> Annuler
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Section Commandes à payer -->
                <div class="commandes-section">
                    <div class="section-title">
                        Commandes livrées à encaisser
                        <span class="section-count">(<?php echo count($commandes_a_payer); ?> en attente)</span>
                    </div>
                    <div class="caisse-section-scroll order-scroll-panel">
                    <?php if (empty($commandes_a_payer)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></div>
                        <h4>Aucune commande à payer</h4>
                        <p>Toutes les commandes ont été réglées</p>
                    </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($commandes_a_payer as $commande): ?>
                        <div class="col-12">
                            <div class="commande-item" data-commande-id="<?php echo $commande['num_commande']; ?>" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($commande)); ?>">
                                <div class="commande-header">
                                    <div class="commande-id">Commande #<?php echo str_pad($commande['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                                    <div class="commande-montant"><?php echo format_money((float) $commande['montant_total']); ?></div>
                                </div>
                                
                                <div class="commande-details">
                                    <div>
                                        <span>Table <?php echo htmlspecialchars((string) ($commande['num_table'] ?? 'N/A')); ?></span>
                                        <span> • </span>
                                        <span><?php echo htmlspecialchars(trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? 'Client'))); ?></span>
                                        <?php if (!empty($commande['telephone_client'])): ?>
                                        <span> • <?php echo htmlspecialchars($commande['telephone_client']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($commande['mode_paiement_souhaite'])): ?>
                                        <span> • <?php echo $commande['mode_paiement_souhaite'] === 'mobile_money' ? 'Mobile money' : 'Cash'; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span><?php echo $commande['nombre_items']; ?> article(s)</span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($commande['date_commande'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="commande-actions">
                                    <a href="?voir_commande=<?php echo $commande['num_commande']; ?>" class="btn-details">
                                        <i class="bi bi-list-ul" aria-hidden="true"></i> Voir détails
                                    </a>
                                    <a href="?voir_commande=<?php echo $commande['num_commande']; ?>" class="btn-payer">
                                        <i class="bi bi-cash-coin" aria-hidden="true"></i> Payer maintenant
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
                
                <!-- Section Paiements récents -->
                <div class="paiements-section">
                    <div class="section-title">Paiements récents</div>
                    <div class="caisse-section-scroll order-scroll-panel">
                    <?php if (empty($paiements_recents)): ?>
                    <div class="empty-state py-3">
                        <div class="empty-icon"><i class="bi bi-receipt" aria-hidden="true"></i></div>
                        <p>Aucun paiement récent</p>
                    </div>
                    <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($paiements_recents as $paiement): ?>
                        <div class="col-12">
                            <div class="paiement-item" data-searchable data-search="<?php echo htmlspecialchars(dashboard_paiement_search_blob($paiement)); ?>">
                                <div class="paiement-header">
                                    <div class="paiement-id">Facture #F-<?php echo str_pad($paiement['num_facture'], 4, '0', STR_PAD_LEFT); ?></div>
                                    <div class="paiement-montant"><?php echo format_money((float) $paiement['total_paye']); ?></div>
                                </div>
                                
                                <div class="paiement-details">
                                    <div>
                                        <span>Table <?php echo $paiement['num_table'] ?? 'N/A'; ?></span>
                                        <span> • </span>
                                        <span><?php echo htmlspecialchars(trim(($paiement['prenom_client'] ?? '') . ' ' . ($paiement['nom_client'] ?? ''))); ?></span>
                                    </div>
                                    <div>
                                        <span class="mode-badge mode-<?php echo htmlspecialchars($paiement['mode_paiement'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo ucfirst($paiement['mode_paiement']); ?>
                                        </span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($paiement['date_facture'])); ?></span>
                                        <span> • </span>
                                        <a href="generer_facture.php?facture=<?php echo $paiement['num_facture']; ?>" target="_blank" rel="noopener" class="link-invoice">
                                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Facture
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div><!-- /.dashboard-shell -->

    <!-- Modal de paiement -->
    <div class="modal-overlay" id="paiementModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Paiement de la commande</div>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            
            <div class="modal-body">
                <?php if ($commande_details): ?>
                <div class="commande-info-grid">
                    <div class="info-item">
                        <div class="info-label">N° Commande</div>
                        <div class="info-value">#<?php echo str_pad($commande_details['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Table</div>
                        <div class="info-value"><?php echo $commande_details['table_num'] ?? $commande_details['num_table'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Client</div>
                        <div class="info-value"><?php echo htmlspecialchars(trim(($commande_details['prenom_client'] ?? '') . ' ' . ($commande_details['nom_client'] ?? 'Client'))); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value"><?php echo htmlspecialchars($commande_details['telephone_client'] ?? '—'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date</div>
                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($commande_details['date_commande'])); ?></div>
                    </div>
                </div>
                
                <table class="items-table-detail">
                    <thead>
                        <tr>
                            <th>Article</th>
                            <th>Qté</th>
                            <th>Prix unit.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($commande_lignes as $ligne): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(dashboard_line_label($ligne)); ?></td>
                            <td><?php echo (int) $ligne['quantite']; ?></td>
                            <td><?php echo format_money((float) $ligne['prix']); ?></td>
                            <td><?php echo format_money((float) $ligne['sous_total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php
                $ttc = (float) $commande_details['montant_total'];
                $ht = round($ttc / 1.2, 0);
                $tva = $ttc - $ht;
                ?>
                <div class="total-row">
                    <div>Total HT</div>
                    <div><?php echo format_money($ht); ?></div>
                </div>
                <div class="total-row">
                    <div>TVA (20 %)</div>
                    <div><?php echo format_money($tva); ?></div>
                </div>
                <div class="total-row">
                    <div><strong>Total TTC</strong></div>
                    <div><strong><?php echo format_money($ttc); ?></strong></div>
                </div>
                
                <?php
                $defaultMode = 'especes';
                if (!empty($commande_details['mode_paiement_souhaite'])) {
                    $defaultMode = $commande_details['mode_paiement_souhaite'] === 'mobile_money' ? 'mobile' : 'especes';
                }
                ?>
                <form method="POST" id="paiementForm">
                    <input type="hidden" name="commande_id" value="<?php echo $commande_details['num_commande']; ?>">
                    <input type="hidden" name="montant_paye" value="<?php echo $commande_details['montant_total']; ?>">
                    <input type="hidden" name="mode_paiement" value="<?php echo htmlspecialchars($defaultMode); ?>" id="selectedMode">
                    
                    <div class="paiement-options">
                        <div class="paiement-option<?php echo $defaultMode === 'especes' ? ' active' : ''; ?>" data-mode="especes" role="button" tabindex="0">
                            <div class="option-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></div>
                            <div class="option-label">Cash</div>
                        </div>
                        <div class="paiement-option<?php echo $defaultMode === 'mobile' ? ' active' : ''; ?>" data-mode="mobile" role="button" tabindex="0">
                            <div class="option-icon"><i class="bi bi-phone" aria-hidden="true"></i></div>
                            <div class="option-label">Mobile money</div>
                        </div>
                    </div>
                    
                    <button type="submit" name="payer_commande" class="btn-confirm">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        Confirmer le paiement de <?php echo format_money((float) $commande_details['montant_total']); ?>
                    </button>
                </form>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></div>
                    <p>Impossible de charger les détails de la commande</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php dashboard_scripts(); ?>
    <script>
        function closeModal() {
            document.getElementById('paiementModal').style.display = 'none';
            // Rediriger pour fermer le paramètre URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('paiementModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Gestion des options de paiement
        document.addEventListener('DOMContentLoaded', function() {
            const options = document.querySelectorAll('.paiement-option');
            const modeInput = document.getElementById('selectedMode');
            
            if (modeInput) {
                options.forEach(option => {
                    option.addEventListener('click', function() {
                        options.forEach(opt => opt.classList.remove('active'));
                        this.classList.add('active');
                        modeInput.value = this.getAttribute('data-mode') || 'especes';
                    });
                });
            }
            
            // Ouvrir le modal si on a un paramètre dans l'URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('voir_commande')) {
                document.getElementById('paiementModal').style.display = 'flex';
            }
        });
    </script>
</body>
</html>