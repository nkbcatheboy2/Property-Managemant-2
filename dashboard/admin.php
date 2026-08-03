<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin']);

// Live stats database se
$total = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$lottery = $pdo->query("SELECT COUNT(*) FROM properties WHERE category='Lottery'")->fetchColumn();
$auction = $pdo->query("SELECT COUNT(*) FROM properties WHERE category='Auction'")->fetchColumn();
$fcfs = $pdo->query("SELECT COUNT(*) FROM properties WHERE category='FCFS'")->fetchColumn();
$direct = $pdo->query("SELECT COUNT(*) FROM properties WHERE category='Direct Allotment'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Property Management - Admin Panel</span>
    <div class="text-white">
        Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>
        (<?= htmlspecialchars($_SESSION['role_name']) ?>)
        | <a href="properties.php" class="text-white">Property List</a>
        | <a href="add_property.php" class="text-white">+ Add Property</a>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <h4>Admin Dashboard</h4>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>Total Properties</h6>
                <h3><?= $total ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>Lottery</h6>
                <h3><?= $lottery ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>Auction</h6>
                <h3><?= $auction ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>FCFS</h6>
                <h3><?= $fcfs ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>Direct Allotment</h6>
                <h3><?= $direct ?></h3>
            </div>
        </div>
    </div>
</div>

</body>
</html>
