<?php
// Dashboard administrateur
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin - Dashboard</title>
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/style.css">
	<style>
		:root{ --accent-color: #f4c95a; --bg-panel:#0b0b0c; --panel-2:#0f0f10; }
		body { background: linear-gradient(180deg,#070707,#0b0b0d); color: #e6e6e6; }
		.admin-sidebar{ width:240px; background: var(--bg-panel); min-height:100vh; box-shadow: 6px 0 18px rgba(0,0,0,0.6); }
		.admin-sidebar .brand { text-align:center; padding:18px 0; }
		.admin-sidebar .nav-link{ color:rgba(255,255,255,0.65); padding:12px 14px; border-radius:10px; }
		.admin-sidebar .nav-link:hover{ background: rgba(255,255,255,0.02); color:#fff; }
		.admin-sidebar .nav-link.active{ background: linear-gradient(90deg, rgba(244,201,90,0.06), rgba(244,201,90,0.02)); color:var(--accent-color); box-shadow: inset 0 0 0 1px rgba(244,201,90,0.04); }
		.card-dark{ background: linear-gradient(180deg,#0f0f10,#0e0e10); border-radius:12px; border:1px solid rgba(255,255,255,0.03); color:#eaeaea; box-shadow: 0 6px 20px rgba(0,0,0,0.5); }
		.small-muted{ color:rgba(255,255,255,0.6); font-size:0.9rem; }
		.stat-value{ font-size:1.6rem; font-weight:700; color:#fff; }
		.orders-table td, .orders-table th{ border-top:0; }
		.chip{ background: rgba(255,255,255,0.03); padding:8px 12px; border-radius:999px; color:#fff; }
		.stat-card .icon-wrap{ width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,0.03);display:inline-flex;align-items:center;justify-content:center;margin-right:12px }
		.stat-card .meta{ font-size:0.85rem;color:rgba(255,255,255,0.7) }
		.orders-table tbody tr:hover{ background: rgba(255,255,255,0.01); }
		.right-column .card-dark{ margin-bottom:16px }
	</style>
</head>
<body>
	<div class="d-flex">
		<aside class="admin-sidebar p-3 d-flex flex-column">
			<div class="mb-4 text-center">
				<div class="rounded-circle bg-warning d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px">DM</div>
				<div class="mt-2 fw-bold">DynamoMenu</div>
			</div>
			<nav class="nav flex-column mb-4">
				<a class="nav-link active mb-1" href="dashboard.php">Dashboard</a>
				<a class="nav-link mb-1" href="../admin/commandes.php">Orders</a>
				<a class="nav-link mb-1" href="../admin/plats.php">Menu</a>
				<a class="nav-link mb-1" href="../admin/utilisateurs.php">Utilisateurs</a>
				<a class="nav-link mb-1" href="#">Analytics</a>
				<a class="nav-link mb-1" href="#">Settings</a>
			</nav>
			<div class="mt-auto small-muted">Premium Plan<br><strong>Unlimited Access</strong></div>
		</aside>

		<main class="flex-grow-1 p-4">
			<header class="d-flex align-items-center justify-content-between mb-4">
				<div>
					<h2 class="mb-0">Welcome back, Admin</h2>
					<div class="small-muted">Friday, May 23, 2026</div>
				</div>
				<div class="d-flex align-items-center gap-3">
					<div class="chip">Search...</div>
					<div class="position-relative">
						<a class="btn btn-outline-light position-relative" href="#">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-bell" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 1.985-1.75H6.015A2 2 0 0 0 8 16z"/></svg>
							<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">3</span>
						</a>
					</div>
					<img src="../assets/images/user.png" alt="user" style="width:40px;height:40px;border-radius:50%"/>
				</div>
			</header>

			<div class="mb-4 row g-3">
				<div class="col-sm-6 col-lg-3">
					<div class="card card-dark p-3 stat-card d-flex align-items-center">
						<div class="icon-wrap">
							<svg width="20" height="20" fill="currentColor" style="color:var(--accent-color)" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0z"/></svg>
						</div>
						<div>
							<div class="meta small-muted">Total Orders</div>
							<div class="stat-value">1,247</div>
							<div class="small-muted">+12.5%</div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-lg-3">
					<div class="card card-dark p-3 stat-card d-flex align-items-center">
						<div class="icon-wrap">
							<svg width="20" height="20" fill="currentColor" style="color:var(--accent-color)" viewBox="0 0 16 16"><path d="M4 4h8v2H4z"/></svg>
						</div>
						<div>
							<div class="meta small-muted">Active Restaurants</div>
							<div class="stat-value">45</div>
							<div class="small-muted">+3 New</div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-lg-3">
					<div class="card card-dark p-3 stat-card d-flex align-items-center">
						<div class="icon-wrap">
							<svg width="20" height="20" fill="currentColor" style="color:var(--accent-color)" viewBox="0 0 16 16"><path d="M0 0h16v16H0z"/></svg>
						</div>
						<div>
							<div class="meta small-muted">Revenue</div>
							<div class="stat-value">$284,750</div>
							<div class="small-muted">+18.2%</div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-lg-3">
					<div class="card card-dark p-3 stat-card d-flex align-items-center">
						<div class="icon-wrap">
							<svg width="20" height="20" fill="currentColor" style="color:var(--accent-color)" viewBox="0 0 16 16"><path d="M8 1l2 4H6l2-4z"/></svg>
						</div>
						<div>
							<div class="meta small-muted">Customer Satisfaction</div>
							<div class="stat-value">4.9/5.00</div>
							<div class="small-muted">+0.2</div>
						</div>
					</div>
				</div>
			</div>

			<div class="row g-4">
				<div class="col-lg-8">
					<div class="card card-dark p-3 mb-4">
						<div class="d-flex justify-content-between align-items-center mb-2">
							<div class="fw-bold">Revenue Growth</div>
							<div class="small-muted">Last 7 Month</div>
						</div>
						<canvas id="revenueChart" height="160"></canvas>
					</div>

					<div class="card card-dark p-3">
						<div class="d-flex justify-content-between align-items-center mb-2">
							<div class="fw-bold">Recent Orders</div>
							<a href="../admin/commandes.php" class="small-muted">See All</a>
						</div>
						<div class="table-responsive">
							<table class="table orders-table table-borderless text-white mb-0">
								<thead>
									<tr class="small-muted">
										<th>Order ID</th>
										<th>Customer</th>
										<th>Total</th>
										<th>Status</th>
										<th>Time</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>#ORD-1024</td>
										<td>Emily Watson</td>
										<td>$285</td>
										<td><span class="badge bg-warning text-dark">Preparing</span></td>
										<td>10 mins ago</td>
									</tr>
									<tr>
										<td>#ORD-1023</td>
										<td>James Anderson</td>
										<td>$199</td>
										<td><span class="badge bg-danger">Cancelled</span></td>
										<td>15 mins ago</td>
									</tr>
									<tr>
										<td>#ORD-1022</td>
										<td>Sophia Harris</td>
										<td>$320</td>
										<td><span class="badge bg-success">Delivered</span></td>
										<td>25 mins ago</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="col-lg-4">
					<div class="card card-dark p-3 mb-3">
						<div class="fw-bold mb-2">Customer by location</div>
						<div class="d-flex align-items-center justify-content-center" style="height:150px">
							<canvas id="pieChart" width="200" height="150"></canvas>
						</div>
					</div>

					<div class="card card-dark p-3">
						<div class="fw-bold mb-2">Order History</div>
						<ul class="list-unstyled mb-0">
							<li class="d-flex align-items-center mb-2">
								<img src="../assets/images/user.png" style="width:36px;height:36px;border-radius:50%"/>
								<div class="ms-2">
									<div class="fw-bold">James Anderson</div>
									<div class="small-muted">Grilled Chicken Set · Apr 25</div>
								</div>
							</li>
							<li class="d-flex align-items-center mb-2">
								<img src="../assets/images/user.png" style="width:36px;height:36px;border-radius:50%"/>
								<div class="ms-2">
									<div class="fw-bold">Emily Roberts</div>
									<div class="small-muted">Vegan Salad Bowl · Apr 25</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</main>
	</div>

	<script src="../assets/js/chart.umd.min.js"></script>
	<script>
		const ctx = document.getElementById('revenueChart').getContext('2d');
		new Chart(ctx, {
			type: 'line',
			data: {
				labels: ['Feb','Mar','Apr','May','Jun','Jul','Aug'],
						datasets: [{
					label: 'Revenue',
					data: [40,50,70,30,60,95,80],
					borderColor: (getComputedStyle(document.documentElement).getPropertyValue('--accent-color') || '#f4c95a').trim(),
					backgroundColor: 'rgba(244,201,90,0.06)',
					tension: 0.35,
					pointRadius:5,
					pointBackgroundColor: (getComputedStyle(document.documentElement).getPropertyValue('--accent-color') || '#f4c95a').trim(),
				}]
			},
			options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ grid:{ color:'rgba(255,255,255,0.04)', borderDash:[6,4] }, ticks:{ color:'#ddd' } }, x:{ ticks:{ color:'#ddd' }, grid:{ color:'rgba(255,255,255,0.01)' } } }
		});

		const pctx = document.getElementById('pieChart').getContext('2d');
		new Chart(pctx, { type:'doughnut', data:{ labels:['UK','USA','London','Japan'], datasets:[{ data:[35,25,20,20], backgroundColor:['#f4c95a','#6bd6b8','#7aa2ff','#e36b8c'] }] }, options:{ plugins:{ legend:{ position:'bottom', labels:{ color:'#ddd' } } } });
	</script>
</body>
</html>
