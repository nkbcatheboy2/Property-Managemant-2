<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin']);

$error = '';
$success = '';
$type_filter = in_array($_GET['type'] ?? '', ['Lottery', 'E-Auction'], true) ? $_GET['type'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = (int) ($_POST['application_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $property_id = (int) ($_POST['property_id'] ?? 0);
    $application_stmt = $pdo->prepare("SELECT campaign_applications.*, lottery_campaigns.campaign_type,
        citizens.phone_number, citizens.full_name FROM campaign_applications
        INNER JOIN lottery_campaigns ON lottery_campaigns.id = campaign_applications.campaign_id
        INNER JOIN citizens ON citizens.id = campaign_applications.citizen_id
        WHERE campaign_applications.id = ?");
    $application_stmt->execute([$application_id]);
    $application = $application_stmt->fetch();

    if (!$application || !in_array($decision, ['Approved', 'Rejected', 'Lottery Failed', 'Assigned'], true)) {
        $error = 'Invalid campaign decision.';
    } elseif ($decision === 'Assigned') {
        $property_stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND campaign_id = ? AND status = 'Available'");
        $property_stmt->execute([$property_id, $application['campaign_id']]);
        $property = $property_stmt->fetch();
        if (!$property) {
            $error = 'Select an available property from the same campaign.';
        } else {
            try {
                $pdo->beginTransaction();
                $owner_name = $application['full_name'] ?: 'Citizen ' . $application['phone_number'];
                $allottee = $pdo->prepare("INSERT INTO allottees (property_id, allottee_name, mobile, allotment_date) VALUES (?, ?, ?, CURDATE())");
                $allottee->execute([$property_id, $owner_name, $application['phone_number']]);
                $pdo->prepare("UPDATE properties SET status = 'Allotted' WHERE id = ?")->execute([$property_id]);
                $pdo->prepare("UPDATE campaign_applications SET property_id = ?, status = 'Assigned' WHERE id = ?")->execute([$property_id, $application_id]);
                $pdo->commit();
                $success = 'Property assign ho gayi. Citizen ko result portal par dikhega.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = $exception->getMessage();
            }
        }
    } else {
        $stmt = $pdo->prepare("UPDATE campaign_applications SET status = ? WHERE id = ?");
        $stmt->execute([$decision, $application_id]);
        $success = 'Application status update ho gaya.';
    }
}

$campaigns = $pdo->query("SELECT id, campaign_type, lottery_name, plot_count FROM lottery_campaigns ORDER BY start_date DESC")->fetchAll();
$available_properties = $pdo->query("SELECT id, property_code, scheme_name, campaign_id FROM properties WHERE status = 'Available' ORDER BY property_code")->fetchAll();
$sql = "SELECT campaign_applications.*, lottery_campaigns.campaign_type, lottery_campaigns.lottery_name,
    citizens.phone_number, citizens.full_name, properties.property_code
    FROM campaign_applications INNER JOIN lottery_campaigns ON lottery_campaigns.id = campaign_applications.campaign_id
    INNER JOIN citizens ON citizens.id = campaign_applications.citizen_id LEFT JOIN properties ON properties.id = campaign_applications.property_id";
$params = [];
if ($type_filter) { $sql .= ' WHERE lottery_campaigns.campaign_type = ?'; $params[] = $type_filter; }
$sql .= ' ORDER BY campaign_applications.created_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Campaign Applications</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head>
<body><div class="portal-wrapper"><header class="portal-header"><div class="portal-brand"><span>📥</span><span><?= $type_filter ? htmlspecialchars($type_filter) : 'Campaign' ?> Applications</span></div><div class="portal-header-actions"><a href="admin.php" class="btn btn-sm btn-outline-light">Dashboard</a><a href="../logout.php" class="btn btn-sm btn-light">Logout</a></div></header><main class="portal-main"><div class="portal-page-header"><h1 class="portal-page-title">Lottery / E-Auction Allocation</h1><p class="portal-page-subtitle">Admin controls approval, rejection, lottery failure and property assignment.</p></div><?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?><div class="mb-3"><a class="btn btn-sm <?= !$type_filter ? 'btn-primary' : 'btn-outline-primary' ?>" href="campaign_applications.php">All</a> <a class="btn btn-sm <?= $type_filter === 'Lottery' ? 'btn-primary' : 'btn-outline-primary' ?>" href="campaign_applications.php?type=Lottery">Lottery</a> <a class="btn btn-sm <?= $type_filter === 'E-Auction' ? 'btn-primary' : 'btn-outline-primary' ?>" href="campaign_applications.php?type=E-Auction">E-Auction</a></div><div class="dashboard-shell"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>S.No.</th><th>Application</th><th>Campaign</th><th>Citizen</th><th>Result</th><th>Assign Property</th><th>Status / Decision</th></tr></thead><tbody><?php if (!$applications): ?><tr><td colspan="7" class="text-center text-muted">No applications yet.</td></tr><?php endif; ?><?php foreach ($applications as $serial => $app): ?><tr><td><?= $serial + 1 ?></td><td><?= htmlspecialchars($app['application_number']) ?><br><small><?= date('d M Y', strtotime($app['created_at'])) ?></small></td><td><span class="badge text-bg-warning"><?= htmlspecialchars($app['campaign_type']) ?></span><br><?= htmlspecialchars($app['lottery_name']) ?></td><td><?= htmlspecialchars($app['full_name'] ?: 'Citizen') ?><br><?= htmlspecialchars($app['phone_number']) ?></td><td><?= $app['property_code'] ? '<strong>Property ' . htmlspecialchars($app['property_code']) . '</strong>' : '-' ?></td><td><?php if (in_array($app['status'], ['Submitted', 'Under Review', 'Approved'], true)): ?><form method="post" class="d-flex gap-1"><input type="hidden" name="application_id" value="<?= $app['id'] ?>"><select name="property_id" class="form-select form-select-sm" required><option value="">Choose linked plot</option><?php foreach ($available_properties as $property): ?><?php if ((int) $property['campaign_id'] === (int) $app['campaign_id']): ?><option value="<?= $property['id'] ?>"><?= htmlspecialchars($property['property_code'] . ' - ' . $property['scheme_name']) ?></option><?php endif; ?><?php endforeach; ?></select><button name="decision" value="Assigned" class="btn btn-sm btn-success">Assign</button></form><?php else: ?>-<?php endif; ?></td><td><span class="badge text-bg-<?= in_array($app['status'], ['Assigned', 'Approved']) ? 'success' : ($app['status'] === 'Rejected' || $app['status'] === 'Lottery Failed' ? 'secondary' : 'warning') ?>"><?= htmlspecialchars($app['status']) ?></span><?php if (in_array($app['status'], ['Submitted', 'Under Review'], true)): ?><form method="post" class="mt-1"><input type="hidden" name="application_id" value="<?= $app['id'] ?>"><button name="decision" value="Approved" class="btn btn-xs btn-outline-success">Approve</button> <button name="decision" value="Lottery Failed" class="btn btn-xs btn-outline-warning">Fail</button> <button name="decision" value="Rejected" class="btn btn-xs btn-outline-danger">Reject</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div></main></div></body></html>
