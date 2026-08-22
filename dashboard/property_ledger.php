<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login();

$allowed_categories = get_user_allowed_categories();
if (empty($allowed_categories)) {
    die('You do not have permission to view property ledgers.');
}

$placeholders = implode(',', array_fill(0, count($allowed_categories), '?'));
$stmt = $pdo->prepare("SELECT properties.id, properties.scheme_name, properties.property_no, properties.property_code,
    properties.property_type, properties.category, properties.price, properties.allotment_date,
    allottees.allottee_name, COALESCE(payment_totals.total_paid, 0) AS total_paid
    FROM properties
    LEFT JOIN allottees ON allottees.property_id = properties.id
    LEFT JOIN (SELECT property_id, SUM(amount) AS total_paid FROM property_payments GROUP BY property_id) AS payment_totals
        ON payment_totals.property_id = properties.id
    WHERE properties.category IN ($placeholders)
    ORDER BY properties.created_at DESC");
$stmt->execute($allowed_categories);
$properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Ledger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand"><span>🧾</span><span>Property Ledger</span></div>
        <div class="portal-header-actions">
            <span class="text-white">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <a href="admin.php" class="btn btn-sm btn-outline-light">Dashboard</a>
            <a href="../logout.php" class="btn btn-sm btn-light">Logout</a>
        </div>
    </header>
    <main class="portal-main">
        <div class="portal-page-header">
            <h1 class="portal-page-title">Property Ledger</h1>
            <p class="portal-page-subtitle">Owner, allotment, payment received aur balance ek hi list mein.</p>
        </div>
        <div class="dashboard-shell">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>S.No.</th><th>Property</th><th>Owner</th><th>Type / Category</th><th>Allotment Date</th><th>Total Cost</th><th>Paid</th><th>Balance</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (!$properties): ?><tr><td colspan="9" class="text-center text-muted">No properties available.</td></tr><?php endif; ?>
                    <?php foreach ($properties as $serial => $property): $balance = max(0, (float) $property['price'] - (float) $property['total_paid']); ?>
                        <tr>
                            <td><?= $serial + 1 ?></td>
                            <td><strong><?= htmlspecialchars($property['property_code']) ?></strong><br><small><?= htmlspecialchars($property['scheme_name']) ?> · <?= htmlspecialchars($property['property_no']) ?></small></td>
                            <td><?= htmlspecialchars($property['allottee_name'] ?: 'Not allotted') ?></td>
                            <td><?= htmlspecialchars($property['property_type'] ?? 'Residential') ?><br><small><?= htmlspecialchars($property['category']) ?></small></td>
                            <td><?= htmlspecialchars($property['allotment_date'] ?: '-') ?></td>
                            <td>₹<?= number_format($property['price'], 2) ?></td>
                            <td class="text-success fw-bold">₹<?= number_format($property['total_paid'], 2) ?></td>
                            <td class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?> fw-bold">₹<?= number_format($balance, 2) ?></td>
                            <td class="text-nowrap"><a href="payments.php?property_id=<?= $property['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye me-1"></i>View / Print</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
