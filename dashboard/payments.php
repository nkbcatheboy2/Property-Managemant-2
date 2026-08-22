<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login();

$property_id = $_GET['property_id'] ?? null;
$message = '';
$message_type = '';

if (!$property_id) {
    die('Invalid property.');
}

$stmt = $pdo->prepare('SELECT id, scheme_name, property_code, price, category, status FROM properties WHERE id = ?');
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    die('Property nahi mili.');
}

$can_manage = can_manage_category($property['category']);
if (!$can_manage && !$can_access_payment_module($property['category'])) {
    die('Access Denied.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($property['status'] !== 'Allotted') {
            $message = 'Payments can only be recorded for allotted properties.';
        $message_type = 'danger';
    } else {
        $amount = trim($_POST['amount'] ?? '');
        $payment_mode = trim($_POST['payment_mode'] ?? '');
        $reference_no = trim($_POST['reference_no'] ?? '');
        $bank_account = trim($_POST['bank_account'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $payment_date = trim($_POST['payment_date'] ?? date('Y-m-d'));

        if ($amount === '' || $payment_mode === '') {
            $message = 'Amount and payment mode are required.';
            $message_type = 'danger';
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO property_payments (property_id, payment_date, amount, payment_mode, reference_no, bank_account, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([$property_id, $payment_date, $amount, $payment_mode, $reference_no, $bank_account, $notes, $_SESSION['user_id']]);
            $message = 'Payment successfully recorded.';
            $message_type = 'success';
        }
    }
}

$ledger_stmt = $pdo->prepare('SELECT pp.*, u.full_name FROM property_payments pp JOIN users u ON pp.recorded_by = u.id WHERE pp.property_id = ? ORDER BY pp.payment_date DESC, pp.created_at DESC');
$ledger_stmt->execute([$property_id]);
$ledger = $ledger_stmt->fetchAll();

$total_paid = array_sum(array_column($ledger, 'amount'));
$balance = max(0, $property['price'] - $total_paid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Property Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-3">
    <span class="navbar-brand mb-0 h1">Payments</span>
    <div class="ms-auto text-white d-flex flex-wrap gap-2 align-items-center">
        <a href="property_detail.php?id=<?= $property['id'] ?>" class="text-white">← Back to Property</a>
        <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="page-header">
        <h4 class="page-title">Payment Ledger</h4>
        <p class="page-subtitle">Property: <?= htmlspecialchars($property['scheme_name']) ?> (<?= htmlspecialchars($property['property_code']) ?>)</p>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Ledger</button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="dashboard-shell mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="module-card text-center">
                    <h6 class="text-muted">Property Price</h6>
                    <h3>₹<?= number_format($property['price'], 2) ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 text-center">
                    <h6 class="text-muted">Total Paid</h6>
                    <h3>₹<?= number_format($total_paid, 2) ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 text-center">
                    <h6 class="text-muted">Balance</h6>
                    <h3>₹<?= number_format($balance, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <?php if ($property['status'] === 'Allotted'): ?>
        <div class="dashboard-shell mb-4">
            <h5 class="mb-3">Record New Payment</h5>
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Mode</label>
                    <select name="payment_mode" class="form-select" required>
                        <option value="">Select</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="UPI">UPI</option>
                        <option value="QR">QR</option>
                        <option value="Cheque">Cheque</option>
                        <option value="DD">DD</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reference / Transaction No.</label>
                    <input type="text" name="reference_no" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bank / Account / QR Info</label>
                    <input type="text" name="bank_account" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Installment, advance, EMI, etc.">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Save Payment</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">This property has not been allotted, so payment entries are unavailable.</div>
    <?php endif; ?>

    <div class="dashboard-shell">
        <h5 class="mb-3">Payment Ledger</h5>
        <div class="table-responsive">
            <table class="table table-bordered bg-white mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>S.No.</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Bank / QR</th>
                        <th>Notes</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ledger)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No payment entries have been recorded yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($ledger as $serial => $entry): ?>
                        <tr>
                            <td><?= $serial + 1 ?></td>
                            <td><?= htmlspecialchars($entry['payment_date']) ?></td>
                            <td><?= htmlspecialchars($entry['payment_mode']) ?></td>
                            <td>₹<?= number_format($entry['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($entry['reference_no'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($entry['bank_account'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($entry['notes'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($entry['full_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
