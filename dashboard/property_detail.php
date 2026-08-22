<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid property.");
}

$stmt = $pdo->prepare(
    "SELECT properties.*, users.full_name AS added_by_name
     FROM properties JOIN users ON properties.added_by = users.id
     WHERE properties.id = ?"
);
$stmt->execute([$id]);
$property = $stmt->fetch();

if (!$property) {
    die("Property not found.");
}

if (!user_has_category_access($property['category'])) {
    die("Access denied: you do not have permission to view this property category.");
}

$allottee_stmt = $pdo->prepare("SELECT * FROM allottees WHERE property_id = ?");
$allottee_stmt->execute([$id]);
$allottee = $allottee_stmt->fetch();

$can_manage = in_array($_SESSION['role_name'], ['Admin', 'Property Officer']) || can_manage_category($property['category']);

$payment_stmt = $pdo->prepare('SELECT pp.*, u.full_name FROM property_payments pp JOIN users u ON pp.recorded_by = u.id WHERE pp.property_id = ? ORDER BY pp.payment_date DESC, pp.created_at DESC');
$payment_stmt->execute([$id]);
$payments = $payment_stmt->fetchAll();
$total_paid = array_sum(array_column($payments, 'amount'));
$balance = max(0, $property['price'] - $total_paid);
$display_status = !$allottee
    ? 'Vacant'
    : ($total_paid >= (float) $property['price'] ? 'Assigned for Registry' : 'Allotted');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand">
            <span>📋</span>
            <span>Property Detail</span>
        </div>
        <div class="portal-header-actions">
            <span class="text-white">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <button type="button" class="btn btn-sm btn-outline-light page-back-button" onclick="if (history.length > 1) { history.back(); } else { window.location.href = '../index.php'; }"><i class="fas fa-arrow-left me-1"></i>Back</button>
            <a href="../logout.php" class="btn btn-sm btn-light">Logout</a>
        </div>
    </header>

    <div class="portal-content">
        <aside class="portal-sidebar">
            <div class="portal-sidebar-section">
                <div class="portal-sidebar-title">Navigation</div>
                <a href="<?= dashboard_redirect_path($_SESSION['role_name']) ?>" class="portal-nav-item">📊 Dashboard</a>
                <a href="properties.php" class="portal-nav-item">📋 Properties</a>
                <a href="property_detail.php?id=<?= $id ?>" class="portal-nav-item active">📌 Detail</a>
            </div>
        </aside>

        <main class="portal-main">
            <div class="portal-page-header mb-4">
                <h1 class="portal-page-title"><?= htmlspecialchars($property['scheme_name']) ?></h1>
                <p class="portal-page-subtitle">Property ID: <?= htmlspecialchars($property['property_code']) ?> | Category: <?= htmlspecialchars($property['category']) ?></p>
            </div>

            <?php if (isset($_GET['saved'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Allottee detail save ho gayi.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="container">
                <div class="row g-3">
                    <!-- Property Info -->
                    <div class="col-md-6">
                        <div class="dashboard-shell">
                            <h5 class="mb-3">Property Information</h5>

                            <?php if ($property['image']): ?>
                                <img src="../assets/uploads/properties/<?= htmlspecialchars($property['image']) ?>" class="img-fluid mb-3 rounded" style="max-height:250px; width:100%; object-fit:cover;">
                            <?php endif; ?>

                            <table class="table table-sm">
                                <tr><th>Scheme Name</th><td><?= htmlspecialchars($property['scheme_name']) ?></td></tr>
                                <tr><th>Scheme Address</th><td><?= htmlspecialchars($property['scheme_address'] ?? '-') ?></td></tr>
                                <tr><th>Property No.</th><td><?= htmlspecialchars($property['property_no']) ?></td></tr>
                                <tr><th>Property ID</th><td><?= htmlspecialchars($property['property_code']) ?></td></tr>
                                <tr><th>Address</th><td><?= htmlspecialchars($property['address']) ?></td></tr>
                                <tr><th>Area Size</th><td><?= htmlspecialchars($property['area_size']) ?></td></tr>
                                <tr><th>Property Type</th><td><?= htmlspecialchars($property['property_type'] ?? 'Residential') ?></td></tr>
                                <tr><th>Allotment Date</th><td><?= htmlspecialchars($property['allotment_date'] ?? '-') ?></td></tr>
                                <tr><th>Price</th><td>₹<?= number_format($property['price'], 2) ?></td></tr>
                                <tr><th>Category</th><td><span class="badge bg-info text-dark"><?= htmlspecialchars($property['category']) ?></span></td></tr>
                                <tr><th>Status</th><td><span class="badge <?= $display_status === 'Assigned for Registry' ? 'bg-success' : ($display_status === 'Allotted' ? 'bg-primary' : 'bg-secondary') ?>"><?= htmlspecialchars($display_status) ?></span></td></tr>
                                <tr><th>Added By</th><td><?= htmlspecialchars($property['added_by_name']) ?></td></tr>
                            </table>

                            <div class="d-flex gap-2 mt-3">
                                <?php if ($can_manage): ?>
                                    <a href="edit_property.php?id=<?= $property['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                <?php endif; ?>
                                <a href="payments.php?property_id=<?= $property['id'] ?>" class="btn btn-primary btn-sm">💳 Payments</a>
                            </div>
                        </div>
                    </div>

                    <!-- Allottee Info -->
                    <div class="col-md-6">
                        <div class="dashboard-shell">
                            <h5 class="mb-3">Allottee Details</h5>

                            <?php if ($allottee): ?>
                                <table class="table table-sm">
                                    <tr><th>Name</th><td><?= htmlspecialchars($allottee['allottee_name']) ?></td></tr>
                                    <tr><th>Father's Name</th><td><?= htmlspecialchars($allottee['father_name']) ?></td></tr>
                                    <tr><th>Mobile</th><td><?= htmlspecialchars($allottee['mobile']) ?></td></tr>
                                    <tr><th>Aadhar No.</th><td><?= htmlspecialchars($allottee['aadhar_no']) ?></td></tr>
                                    <tr><th>PAN No.</th><td><?= htmlspecialchars($allottee['pan_no']) ?></td></tr>
                                    <tr><th>Address</th><td><?= htmlspecialchars($allottee['address']) ?></td></tr>
                                    <tr><th>Allotment Date</th><td><?= htmlspecialchars($allottee['allotment_date']) ?></td></tr>
                                </table>

                                <div class="row g-2 mt-2 mb-3">
                                    <?php if ($allottee['aadhar_photo']): ?>
                                        <div class="col-6">
                                            <label class="form-label text-muted d-block mb-2" style="font-size:0.85rem;">Aadhar Photo</label>
                                            <img src="../assets/uploads/allottees/<?= htmlspecialchars($allottee['aadhar_photo']) ?>" class="img-fluid rounded border" style="max-height:150px; width:100%; object-fit:cover;">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($allottee['pan_photo']): ?>
                                        <div class="col-6">
                                            <label class="form-label text-muted d-block mb-2" style="font-size:0.85rem;">PAN Photo</label>
                                            <img src="../assets/uploads/allottees/<?= htmlspecialchars($allottee['pan_photo']) ?>" class="img-fluid rounded border" style="max-height:150px; width:100%; object-fit:cover;">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($can_manage): ?>
                                    <a href="add_allottee.php?property_id=<?= $property['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit Allottee</a>
                                <?php endif; ?>

                            <?php else: ?>
                                <p class="text-muted mb-3">This property has not been allotted yet.</p>
                                <?php if ($can_manage): ?>
                                    <a href="add_allottee.php?property_id=<?= $property['id'] ?>" class="btn btn-primary btn-sm">➕ Add Allottee</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary & Ledger -->
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div class="dashboard-shell">
                            <h5 class="mb-3">Payment Summary & Ledger</h5>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block">Property Price</small>
                                        <h5 class="mb-0">₹<?= number_format($property['price'], 2) ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block">Total Paid</small>
                                        <h5 class="mb-0 text-success">₹<?= number_format($total_paid, 2) ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block">Balance</small>
                                        <h5 class="mb-0 text-warning">₹<?= number_format($balance, 2) ?></h5>
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-2">Payment History</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>S.No.</th>
                                            <th>Date</th>
                                            <th>Mode</th>
                                            <th>Amount</th>
                                            <th>Reference</th>
                                            <th>Recorded By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payments)): ?>
                                            <tr><td colspan="6" class="text-center text-muted">No payment entries have been recorded yet.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($payments as $serial => $payment): ?>
                                            <tr>
                                            <td><?= $serial + 1 ?></td>
                                                <td><?= htmlspecialchars($payment['payment_date']) ?></td>
                                                <td><?= htmlspecialchars($payment['payment_mode']) ?></td>
                                                <td class="fw-bold">₹<?= number_format($payment['amount'], 2) ?></td>
                                                <td><?= htmlspecialchars($payment['reference_no'] ?: '�') ?></td>
                                                <td><?= htmlspecialchars($payment['full_name']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
