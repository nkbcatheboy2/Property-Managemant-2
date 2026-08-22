<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Workflow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand"><span>💼</span><span>Sale Workflow</span></div>
        <div class="portal-header-actions"><a href="admin.php" class="btn btn-sm btn-outline-light">Dashboard</a><a href="../logout.php" class="btn btn-sm btn-light">Logout</a></div>
    </header>
    <main class="portal-main">
        <div class="portal-page-header">
            <h1 class="portal-page-title">Sale Process</h1>
            <p class="portal-page-subtitle">Application se payment schedule tak property sale workflow.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6"><article class="dashboard-shell sale-workflow-card" id="application-process"><span class="sale-workflow-icon">📝</span><h3>Application Process</h3><p>Lottery, e-auction aur FCFS applications ko review karein.</p><a href="campaign_applications.php" class="btn btn-outline-primary">Open Applications</a></article></div>
            <div class="col-md-6"><article class="dashboard-shell sale-workflow-card" id="allotment-create"><span class="sale-workflow-icon">✅</span><h3>Create Allotment</h3><p>Eligible application ko property assign karke allotment complete karein.</p><a href="property_management.php" class="btn btn-outline-primary">Open Allotment</a></article></div>
            <div class="col-md-6"><article class="dashboard-shell sale-workflow-card" id="final-costing"><span class="sale-workflow-icon">💰</span><h3>Final Costing</h3><p>Property cost, received payment aur remaining balance check karein.</p><a href="property_ledger.php" class="btn btn-outline-primary">Open Costing</a></article></div>
            <div class="col-md-6"><article class="dashboard-shell sale-workflow-card" id="payment-schedule"><span class="sale-workflow-icon">📅</span><h3>Payment Schedule</h3><p>Payment entries, dates, references aur customer-ready print view dekhein.</p><a href="property_ledger.php" class="btn btn-outline-primary">Open Schedule</a></article></div>
        </div>
    </main>
</div>
</body>
</html>
