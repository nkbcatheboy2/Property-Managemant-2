<?php
session_start();
require_once 'config/db.php';

if (empty($_SESSION['citizen_id']) || empty($_SESSION['citizen_phone'])) {
    header('Location: index.php');
    exit;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS citizen_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    citizen_id INT NOT NULL,
    property_id INT NOT NULL,
    request_type ENUM('Mutation','KYC','NOC','Surrender') NOT NULL,
    details TEXT,
    status ENUM('Submitted','Under Review','Approved','Rejected') DEFAULT 'Submitted',
    reference_number VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (citizen_id) REFERENCES citizens(id),
    FOREIGN KEY (property_id) REFERENCES properties(id)
)");
try {
    $pdo->exec("ALTER TABLE citizen_requests MODIFY request_type ENUM('Mutation','KYC','NOC','Surrender') NOT NULL");
} catch (PDOException $exception) {
    // The table may already have the current enum definition.
}

$phone = $_SESSION['citizen_phone'];
$citizen_id = (int) $_SESSION['citizen_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = (int) ($_POST['campaign_id'] ?? 0);
    if ($campaign_id > 0) {
        $campaign_check = $pdo->prepare("SELECT id FROM lottery_campaigns WHERE id = ? AND status = 'Published' AND start_date <= CURDATE() AND end_date >= CURDATE()");
        $campaign_check->execute([$campaign_id]);
        if (!$campaign_check->fetch()) {
            $error = 'This campaign is not currently open.';
        } else {
            $application_number = 'CAMP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $campaign_apply = $pdo->prepare("INSERT INTO campaign_applications
                (campaign_id, citizen_id, application_number, details) VALUES (?, ?, ?, ?)");
            $campaign_apply->execute([$campaign_id, $citizen_id, $application_number, trim($_POST['campaign_details'] ?? '')]);
            $message = "Campaign application submitted. Reference: {$application_number}";
        }
    }

    if ($campaign_id > 0) {
        // Campaign applications are handled above; do not process the property service form.
    } else {
    $property_id = (int) ($_POST['property_id'] ?? 0);
    $request_type = $_POST['request_type'] ?? '';
    $details = trim($_POST['details'] ?? '');
    $old_name = trim($_POST['old_allottee_name'] ?? '');
    $new_name = trim($_POST['new_allottee_name'] ?? '');
    $relation = trim($_POST['relation_to_old_allottee'] ?? '');

    $owned = $pdo->prepare("SELECT properties.id FROM properties
        INNER JOIN allottees ON allottees.property_id = properties.id
        WHERE properties.id = ? AND REPLACE(allottees.mobile, ' ', '') IN (?, ?)");
    $owned->execute([$property_id, $phone, '+91' . $phone]);

    if (!$owned->fetch()) {
        $error = 'This property is not linked to your verified mobile number.';
    } elseif (!in_array($request_type, ['Mutation', 'KYC', 'NOC', 'Surrender'], true)) {
        $error = 'Please select a valid application type.';
    } elseif ($request_type === 'Mutation' && ($old_name === '' || $new_name === '' || $relation === '')) {
        $error = 'Mutation ke liye old name, new owner name aur relation zaroori hain.';
    } else {
        $reference = 'LDA-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $insert = $pdo->prepare("INSERT INTO citizen_requests
            (citizen_id, property_id, request_type, details, old_allottee_name, new_allottee_name, relation_to_old_allottee, reference_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$citizen_id, $property_id, $request_type, $details, $old_name ?: null, $new_name ?: null, $relation ?: null, $reference]);
        $message = "{$request_type} application submitted. Reference: {$reference}";
    }
    }
}

$properties_stmt = $pdo->prepare("SELECT properties.id, properties.scheme_name, properties.property_no,
    properties.property_code, properties.address, properties.status, allottees.allottee_name,
    allottees.aadhar_no, allottees.pan_no, properties.price,
    COALESCE(payment_totals.total_paid, 0) AS total_paid
    FROM properties INNER JOIN allottees ON allottees.property_id = properties.id
    LEFT JOIN (SELECT property_id, SUM(amount) AS total_paid FROM property_payments GROUP BY property_id) AS payment_totals
        ON payment_totals.property_id = properties.id
    WHERE REPLACE(allottees.mobile, ' ', '') IN (?, ?)
    ORDER BY properties.created_at DESC");
$properties_stmt->execute([$phone, '+91' . $phone]);
$properties = $properties_stmt->fetchAll();
foreach ($properties as &$property) {
    $property['display_status'] = (float) $property['total_paid'] >= (float) $property['price']
        ? 'Assigned for Registry'
        : 'Allotted';
}
unset($property);

$owner_ledger = [];
if ($properties) {
    $property_ids = array_column($properties, 'id');
    $placeholders = implode(',', array_fill(0, count($property_ids), '?'));
    $ledger_stmt = $pdo->prepare("SELECT property_payments.payment_date, property_payments.amount,
        property_payments.payment_mode, property_payments.reference_no, properties.property_code
        FROM property_payments INNER JOIN properties ON properties.id = property_payments.property_id
        WHERE property_payments.property_id IN ($placeholders)
        ORDER BY property_payments.payment_date DESC, property_payments.created_at DESC");
    $ledger_stmt->execute($property_ids);
    $owner_ledger = $ledger_stmt->fetchAll();
}

$requests_stmt = $pdo->prepare("SELECT citizen_requests.*, properties.property_code
    FROM citizen_requests INNER JOIN properties ON properties.id = citizen_requests.property_id
    WHERE citizen_requests.citizen_id = ? ORDER BY citizen_requests.created_at DESC");
$requests_stmt->execute([$citizen_id]);
$requests = $requests_stmt->fetchAll();
$campaign_requests_stmt = $pdo->prepare("SELECT campaign_applications.*, lottery_campaigns.campaign_type, lottery_campaigns.lottery_name, properties.property_code
    FROM campaign_applications INNER JOIN lottery_campaigns ON lottery_campaigns.id = campaign_applications.campaign_id
    LEFT JOIN properties ON properties.id = campaign_applications.property_id
    WHERE campaign_applications.citizen_id = ? ORDER BY campaign_applications.created_at DESC");
$campaign_requests_stmt->execute([$citizen_id]);
$campaign_requests = $campaign_requests_stmt->fetchAll();

$campaigns = $pdo->query("SELECT id, campaign_type, lottery_name, scheme_name, plot_count, property_type, price_per_unit, start_date, end_date
    FROM lottery_campaigns WHERE status = 'Published' AND start_date <= CURDATE() AND end_date >= CURDATE()
    ORDER BY end_date ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Portal - LDA Property Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="citizen-portal-page">
    <header class="citizen-header">
        <div class="container d-flex justify-content-between align-items-center gap-3">
            <a href="index.php" class="citizen-brand">LDA Citizen Portal</a>
            <div class="d-flex align-items-center gap-3">
                <span class="citizen-phone">Verified: <?= htmlspecialchars($phone) ?></span>
                <a href="citizen-logout.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </header>

    <main class="container citizen-main">
        <div class="citizen-intro">
            <div>
                <p class="section-kicker">MY PROPERTY SERVICES</p>
                <h1>Welcome<?= $_SESSION['citizen_name'] ? ', ' . htmlspecialchars($_SESSION['citizen_name']) : '' ?></h1>
                <p>Properties linked with your verified mobile number and their online services.</p>
            </div>
            <a href="index.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Home</a>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <section class="citizen-guide citizen-section" aria-labelledby="guide-title">
            <div class="citizen-guide-heading">
                <div>
                    <p class="section-kicker">PORTAL GUIDE</p>
                    <h2 id="guide-title">Yahan se aap kya kar sakte hain?</h2>
                    <p>Lottery, e-auction aur property services ko simple steps mein samjhein.</p>
                </div>
                <a class="btn btn-outline-primary" href="contact.php"><i class="fas fa-headset me-2"></i>Help & Contact</a>
            </div>
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <article class="citizen-info-card">
                        <div class="citizen-info-image" style="background-image: url('assets/images/citizen-lottery.svg');"><span class="citizen-info-icon"><i class="fas fa-ticket"></i></span></div>
                        <div class="citizen-info-body"><span class="citizen-info-label">01 · Lottery</span><h3>Lottery kya hoti hai?</h3><p>Available plots ya flats ke liye eligible applicants mein computerised draw hota hai. Selection chance-based hota hai; result ke baad successful applicant ko allotment milta hai.</p><a href="#open-lottery">Active lottery dekhein <i class="fas fa-arrow-right"></i></a></div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="citizen-info-card">
                        <div class="citizen-info-image" style="background-image: url('assets/images/citizen-auction.svg');"><span class="citizen-info-icon"><i class="fas fa-gavel"></i></span></div>
                        <div class="citizen-info-body"><span class="citizen-info-label">02 · E-Auction</span><h3>E-Auction mein kya hota hai?</h3><p>Authority property ko online bidding ke liye publish karti hai. Registration aur terms padhkar eligible bidder bid karta hai; highest valid bid ke rules ke mutabik allotment hota hai.</p><a href="#open-lottery">Open opportunities dekhein <i class="fas fa-arrow-right"></i></a></div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="citizen-info-card">
                        <div class="citizen-info-image" style="background-image: url('assets/images/citizen-fcfs.svg');"><span class="citizen-info-icon"><i class="fas fa-house"></i></span></div>
                        <div class="citizen-info-body"><span class="citizen-info-label">03 · FCFS</span><h3>First Come, First Served</h3><p>FCFS mein eligible application ki priority submission time ke basis par hoti hai. Documents, payment aur campaign ki last date ko dhyan se check karein.</p><a href="#open-lottery">Campaign details dekhein <i class="fas fa-arrow-right"></i></a></div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="citizen-info-card citizen-info-card-plain">
                        <div class="citizen-info-symbol"><i class="fas fa-file-signature"></i></div>
                        <div class="citizen-info-body"><span class="citizen-info-label">04 · Property Services</span><h3>Mutation, KYC aur NOC</h3><p>Linked property par Apply Service se Mutation ke liye ownership transfer, KYC ke liye identity update aur NOC ke liye certificate request submit karein.</p><a href="#my-properties">Apni property par jayein <i class="fas fa-arrow-right"></i></a></div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="citizen-info-card citizen-info-card-plain">
                        <div class="citizen-info-symbol payment"><i class="fas fa-indian-rupee-sign"></i></div>
                        <div class="citizen-info-body"><span class="citizen-info-label">05 · Payments</span><h3>Payment aur ledger</h3><p>Owner Payment Ledger mein payment date, amount, mode aur reference number check karein. Har transaction ka reference sambhal kar rakhein.</p><a href="#payment-ledger">Payment history dekhein <i class="fas fa-arrow-right"></i></a></div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="citizen-info-card citizen-info-card-plain">
                        <div class="citizen-info-symbol support"><i class="fas fa-life-ring"></i></div>
                        <div class="citizen-info-body"><span class="citizen-info-label">06 · Status & Support</span><h3>Application track karein</h3><p>Reference number ke saath My Applications mein status dekhein. Portal access ya record issue ho to support team se contact karein.</p><a href="contact.php"><i class="fas fa-phone me-1"></i>Support se baat karein</a></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="citizen-section" id="open-lottery">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Open Lottery Campaigns</h2>
                <span class="badge text-bg-warning"><?= count($campaigns) ?> active</span>
            </div>
            <?php if (!$campaigns): ?>
                <div class="empty-state mb-4">No active lottery campaign at this time.</div>
            <?php else: ?>
                <div class="row g-3 mb-4">
                    <?php foreach ($campaigns as $serial => $campaign): ?>
                        <div class="col-lg-6">
                            <article class="campaign-public-card">
                                <span class="citizen-property-number">S.No. <?= $serial + 1 ?></span>
                                <span class="badge text-bg-warning mb-2"><?= htmlspecialchars($campaign['campaign_type'] ?? 'Lottery') ?></span>
                                <h3><?= htmlspecialchars($campaign['lottery_name']) ?></h3>
                                <p class="mb-2"><strong><?= htmlspecialchars($campaign['scheme_name']) ?></strong> · <?= htmlspecialchars($campaign['property_type']) ?></p>
                                <p class="text-muted mb-2"><?= (int) $campaign['plot_count'] ?> units · ₹<?= number_format($campaign['price_per_unit'], 2) ?> per unit</p>
                                <small class="text-muted d-block mb-3">Open: <?= htmlspecialchars($campaign['start_date']) ?> to <?= htmlspecialchars($campaign['end_date']) ?></small>
                                <form method="post"><input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>"><input type="text" name="campaign_details" class="form-control form-control-sm mb-2" placeholder="Optional details"><button class="btn btn-sm btn-primary">Apply Now</button></form>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="citizen-section" id="my-properties">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>My Properties</h2>
                <span class="badge text-bg-primary"><?= count($properties) ?> linked</span>
            </div>
            <?php if (!$properties): ?>
                <div class="empty-state">No property is linked to this mobile number yet. Ask the office to update the allottee mobile number.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($properties as $serial => $property): ?>
                        <div class="col-lg-6">
                            <article class="citizen-property-card">
                                <span class="citizen-property-number">S.No. <?= $serial + 1 ?></span>
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <p class="property-code">Property ID: <?= htmlspecialchars($property['property_code']) ?></p>
                                        <h3><?= htmlspecialchars($property['scheme_name']) ?></h3>
                                        <p class="text-muted mb-1">Property No: <?= htmlspecialchars($property['property_no']) ?></p>
                                        <p class="text-muted mb-3"><?= htmlspecialchars($property['address']) ?></p>
                                    </div>
                                    <span class="badge <?= $property['display_status'] === 'Assigned for Registry' ? 'text-bg-success' : 'text-bg-primary' ?> align-self-start"><?= htmlspecialchars($property['display_status']) ?></span>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#applyModal"
                                            data-property-id="<?= $property['id'] ?>" data-property-name="<?= htmlspecialchars($property['scheme_name']) ?>">
                                        <i class="fas fa-file-signature me-1"></i>Apply Service
                                    </button>
                                    <?php if ($property['aadhar_no'] && $property['pan_no']): ?>
                                        <span class="btn btn-sm btn-outline-success disabled"><i class="fas fa-check me-1"></i>KYC details available</span>
                                    <?php else: ?>
                                        <span class="btn btn-sm btn-outline-warning disabled">KYC incomplete</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="citizen-section" id="my-applications">
            <h2 class="mb-3">My Applications</h2>
            <div class="table-responsive">
                <table class="table align-middle bg-white">
                    <thead><tr><th>S.No.</th><th>Reference</th><th>Property</th><th>Service</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if (!$requests): ?><tr><td colspan="6" class="text-center text-muted">No applications submitted yet.</td></tr><?php endif; ?>
                        <?php foreach ($requests as $serial => $request): ?>
                        <tr>
                            <td><?= $serial + 1 ?></td>
                            <td><?= htmlspecialchars($request['reference_number']) ?></td>
                            <td><?= htmlspecialchars($request['property_code']) ?></td>
                            <td><?= htmlspecialchars($request['request_type']) ?></td>
                            <td><span class="badge text-bg-info"><?= htmlspecialchars($request['status']) ?></span></td>
                            <td><?= date('d M Y', strtotime($request['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="citizen-section" id="payment-ledger">
            <h2 class="mb-3">Owner Payment Ledger</h2>
            <div class="table-responsive"><table class="table align-middle bg-white"><thead><tr><th>S.No.</th><th>Date</th><th>Property</th><th>Mode</th><th>Amount</th><th>Reference</th></tr></thead><tbody>
                <?php if (!$owner_ledger): ?><tr><td colspan="6" class="text-center text-muted">No payment entries yet.</td></tr><?php endif; ?>
                <?php foreach ($owner_ledger as $serial => $entry): ?><tr><td><?= $serial + 1 ?></td><td><?= htmlspecialchars($entry['payment_date']) ?></td><td><?= htmlspecialchars($entry['property_code']) ?></td><td><?= htmlspecialchars($entry['payment_mode']) ?></td><td>₹<?= number_format($entry['amount'], 2) ?></td><td><?= htmlspecialchars($entry['reference_no'] ?: '-') ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </section>

        <section class="citizen-section">
            <h2 class="mb-3">Campaign Applications</h2>
            <div class="table-responsive"><table class="table align-middle bg-white"><thead><tr><th>S.No.</th><th>Application No.</th><th>Type</th><th>Campaign</th><th>Property</th><th>Status</th><th>Date</th></tr></thead><tbody>
                <?php if (!$campaign_requests): ?><tr><td colspan="7" class="text-center text-muted">No campaign applications submitted yet.</td></tr><?php endif; ?>
                <?php foreach ($campaign_requests as $serial => $request): ?><tr><td><?= $serial + 1 ?></td><td><?= htmlspecialchars($request['application_number']) ?></td><td><?= htmlspecialchars($request['campaign_type'] ?? 'Lottery') ?></td><td><?= htmlspecialchars($request['lottery_name']) ?></td><td><?= htmlspecialchars($request['property_code'] ?: '-') ?></td><td><span class="badge text-bg-<?= $request['status'] === 'Assigned' ? 'success' : ($request['status'] === 'Lottery Failed' || $request['status'] === 'Rejected' ? 'secondary' : 'info') ?>"><?= htmlspecialchars($request['status']) ?></span><?php if ($request['status'] === 'Assigned'): ?><div class="small text-success fw-bold">Congratulations! Property allotted.</div><?php elseif ($request['status'] === 'Lottery Failed'): ?><div class="small text-muted">Not selected in this campaign.</div><?php endif; ?></td><td><?= date('d M Y', strtotime($request['created_at'])) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </section>
    </main>

    <div class="modal fade" id="applyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header"><h5 class="modal-title">Apply for service</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p class="text-muted">Property: <strong id="modalPropertyName"></strong></p>
                        <input type="hidden" name="property_id" id="modalPropertyId">
                        <label class="form-label" for="request_type">Select service</label>
                        <select class="form-select mb-3" name="request_type" id="request_type" required>
                            <option value="">Choose...</option><option value="Mutation">Mutation</option><option value="KYC">KYC Update</option><option value="NOC">NOC Request</option><option value="Surrender">Surrender / Cancel Property</option>
                        </select>
                        <div id="mutationFields" class="border rounded p-3 mb-3" style="display:none;">
                            <label class="form-label" for="old_allottee_name">Current owner name</label>
                            <input class="form-control mb-2" name="old_allottee_name" id="old_allottee_name">
                            <label class="form-label" for="new_allottee_name">New owner name</label>
                            <input class="form-control mb-2" name="new_allottee_name" id="new_allottee_name">
                            <label class="form-label" for="relation_to_old_allottee">Relation / reason</label>
                            <input class="form-control" name="relation_to_old_allottee" id="relation_to_old_allottee" placeholder="Bahu, Bhatija, legal heir etc.">
                        </div>
                        <label class="form-label" for="details">Details</label>
                        <textarea class="form-control" name="details" id="details" rows="4" placeholder="Add any details for the LDA team"></textarea>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Submit Application</button></div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('applyModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('modalPropertyId').value = button.dataset.propertyId;
            document.getElementById('modalPropertyName').textContent = button.dataset.propertyName;
        });
        document.getElementById('request_type').addEventListener('change', function () {
            document.getElementById('mutationFields').style.display = this.value === 'Mutation' ? 'block' : 'none';
        });
    </script>
</body>
</html>
