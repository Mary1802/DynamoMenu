<?php
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caissier') {
	header('Location: ../client/index.php');
	exit;
}

// Charger quelques paiements fictifs ou depuis la BDD si nécessaire
// Pour l'instant on affiche un exemple statique
?>

<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Caissier - Paiement</title>
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/style.css">
	<style>
		:root{ --accent-color: #f4c95a; --bg-panel:#0b0b0c; }
		body { background: linear-gradient(180deg,#070707,#0b0b0d); color: #e6e6e6; }
		.admin-sidebar{ width:240px; background: var(--bg-panel); min-height:100vh; box-shadow: 6px 0 18px rgba(0,0,0,0.6); }
		.admin-sidebar .brand { text-align:center; padding:18px 0; }
		.admin-sidebar .nav-link{ color:rgba(255,255,255,0.65); padding:12px 14px; border-radius:10px; }
		.card-dark{ background: linear-gradient(180deg,#0f0f10,#0e0e10); border-radius:12px; border:1px solid rgba(255,255,255,0.03); color:#eaeaea; box-shadow: 0 6px 20px rgba(0,0,0,0.5); }
		.small-muted{ color:rgba(255,255,255,0.6); font-size:0.9rem; }
		.chip{ background: rgba(255,255,255,0.03); padding:8px 12px; border-radius:999px; color:#fff; }
		.payments-table td, .payments-table th{ border-top:0; }
	</style>
</head>
<body>
	<div class="d-flex">
		<aside class="admin-sidebar p-3 d-flex flex-column">
			<div class="mb-4 text-center">
				<div class="rounded-circle bg-warning d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px">💳</div>
				<div class="mt-2 fw-bold">DynamoMenu</div>
				<div class="small-muted">Caissier</div>
			</div>
			<nav class="nav flex-column mb-4">
				<a class="nav-link active mb-1" href="paiement.php">Paiements</a>
				<a class="nav-link mb-1" href="/client/commande.php">Commandes</a>
			</nav>
			<div class="mt-auto small-muted">Role: Caissier<br><strong><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Caissier'); ?></strong></div>
		</aside>

		<main class="flex-grow-1 p-4">
			<header class="d-flex align-items-center justify-content-between mb-4">
				<div>
					<h2 class="mb-0">Paiements</h2>
					<div class="small-muted">Historique et encaissements</div>
				</div>
				<div class="d-flex align-items-center gap-3">
					<div class="chip">Rechercher...</div>
					<img src="../assets/images/user.png" alt="user" style="width:40px;height:40px;border-radius:50%"/>
				</div>
			</header>

			<div class="card card-dark p-3">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<div class="fw-bold">Derniers paiements</div>
					<div class="small-muted">Aujourd'hui</div>
				</div>
				<div class="table-responsive">
					<table class="table payments-table table-borderless text-white mb-0">
						<thead>
							<tr class="small-muted">
								<th>Réf</th>
								<th>Client</th>
								<th>Méthode</th>
								<th>Montant</th>
								<th>Heure</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>#P-1001</td>
								<td>Martin Dupont</td>
								<td>CB</td>
								<td>€24.50</td>
								<td>11:23</td>
							</tr>
							<tr>
								<td>#P-1000</td>
								<td>Claire Martin</td>
								<td>Espèces</td>
								<td>€12.00</td>
								<td>10:58</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</main>
	</div>

	<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
