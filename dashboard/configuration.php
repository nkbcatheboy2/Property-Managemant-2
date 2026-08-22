<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revert') {
    $type = $_POST['type'] ?? '';
    $request_id = (int) ($_POST['request_id'] ?? 0);
    try {
        $pdo->beginTransaction();
        if ($type === 'campaign') {
            $stmt = $pdo->prepare("SELECT campaign_applications.*, citizens.phone_number FROM campaign_applications INNER JOIN citizens ON citizens.id = campaign_applications.citizen_id WHERE campaign_applications.id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            if (!$request) throw new RuntimeException('Campaign application not found.');
            if ($request['property_id']) {
                $pdo->prepare("DELETE FROM allottees WHERE property_id = ? AND mobile = ?")->execute([$request['property_id'], $request['phone_number']]);
                $pdo->prepare("UPDATE properties SET status = 'Available' WHERE id = ?")->execute([$request['property_id']]);
            }
            $pdo->prepare("UPDATE campaign_applications SET property_id = NULL, status = 'Submitted' WHERE id = ?")->execute([$request_id]);
        } elseif ($type === 'mutation') {
            $stmt = $pdo->prepare("SELECT * FROM citizen_requests WHERE id = ? AND request_type = 'Mutation'");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            if (!$request) throw new RuntimeException('Mutation request not found.');
            if ($request['status'] === 'Approved') {
                $pdo->prepare("UPDATE allottees SET allottee_name = ? WHERE property_id = ? AND allottee_name = ?")->execute([$request['old_allottee_name'], $request['property_id'], $request['new_allottee_name']]);
            }
            $pdo->prepare("UPDATE citizen_requests SET status = 'Submitted' WHERE id = ?")->execute([$request_id]);
        } elseif ($type === 'kyc') {
            $pdo->prepare("UPDATE citizen_requests SET status = 'Submitted' WHERE id = ? AND request_type = 'KYC'")->execute([$request_id]);
        } else {
            throw new RuntimeException('Invalid application type.');
        }
        $pdo->commit();
        $success = 'Decision revert ho gaya. Application Configuration mein wapas aa gayi.';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $exception->getMessage();
    }
}

$campaigns = $pdo->query("SELECT campaign_applications.id, campaign_applications.application_number, campaign_applications.status, campaign_applications.created_at, campaign_applications.property_id, lottery_campaigns.campaign_type, lottery_campaigns.lottery_name, citizens.phone_number, properties.property_code FROM campaign_applications INNER JOIN lottery_campaigns ON lottery_campaigns.id = campaign_applications.campaign_id INNER JOIN citizens ON citizens.id = campaign_applications.citizen_id LEFT JOIN properties ON properties.id = campaign_applications.property_id WHERE campaign_applications.status IN ('Approved','Assigned','Rejected','Lottery Failed') ORDER BY campaign_applications.created_at DESC")->fetchAll();
$mutations = $pdo->query("SELECT citizen_requests.id, citizen_requests.reference_number, citizen_requests.status, citizen_requests.created_at, citizen_requests.old_allottee_name, citizen_requests.new_allottee_name, properties.property_code FROM citizen_requests INNER JOIN properties ON properties.id = citizen_requests.property_id WHERE citizen_requests.request_type = 'Mutation' AND citizen_requests.status IN ('Approved','Rejected') ORDER BY citizen_requests.created_at DESC")->fetchAll();
$kyc = $pdo->query("SELECT citizen_requests.id, citizen_requests.reference_number, citizen_requests.status, citizen_requests.created_at, properties.property_code FROM citizen_requests INNER JOIN properties ON properties.id = citizen_requests.property_id WHERE citizen_requests.request_type = 'KYC' AND citizen_requests.status IN ('Approved','Rejected') ORDER BY citizen_requests.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Configuration - Applications</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head>
<body><div class="portal-wrapper"><header class="portal-header"><div class="portal-brand"><span>⚙️</span><span>Configuration</span></div><div class="portal-header-actions"><a href="admin.php" class="btn btn-sm btn-outline-light">Dashboard</a><a href="../logout.php" class="btn btn-sm btn-light">Logout</a></div></header><main class="portal-main"><div class="portal-page-header"><h1 class="portal-page-title">Application Configuration</h1><p class="portal-page-subtitle">New applications arrive here. Use Property Management to review assigned decisions and revert them when needed.</p></div><?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?><div class="row g-4"><div class="col-12"><div class="dashboard-shell"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Lottery / E-Auction Decisions</h5><a href="campaign_applications.php" class="btn btn-sm btn-outline-primary">Open Review</a></div><div class="table-responsive"><table class="table table-sm"><thead><tr><th>S.No.</th><th>Application</th><th>Type</th><th>Campaign</th><th>Property</th><th>Status</th><th>Revert</th></tr></thead><tbody><?php foreach ($campaigns as $serial => $item): ?><tr><td><?= $serial + 1 ?></td><td><?= htmlspecialchars($item['application_number']) ?></td><td><?= htmlspecialchars($item['campaign_type']) ?></td><td><?= htmlspecialchars($item['lottery_name']) ?></td><td><?= htmlspecialchars($item['property_code'] ?: '-') ?></td><td><?= htmlspecialchars($item['status']) ?></td><td><form method="post"><input type="hidden" name="action" value="revert"><input type="hidden" name="type" value="campaign"><input type="hidden" name="request_id" value="<?= $item['id'] ?>"><button class="btn btn-sm btn-outline-warning">Revert</button></form></td></tr><?php endforeach; ?><?php if (!$campaigns): ?><tr><td colspan="7" class="text-center text-muted">No decided campaign applications.</td></tr><?php endif; ?></tbody></table></div></div></div><div class="col-lg-6"><div class="dashboard-shell"><h5>Mutation Decisions</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>S.No.</th><th>Reference</th><th>Property</th><th>Status</th><th>Revert</th></tr></thead><tbody><?php foreach ($mutations as $serial => $item): ?><tr><td><?= $serial + 1 ?></td><td><?= htmlspecialchars($item['reference_number']) ?></td><td><?= htmlspecialchars($item['property_code']) ?></td><td><?= htmlspecialchars($item['status']) ?></td><td><form method="post"><input type="hidden" name="action" value="revert"><input type="hidden" name="type" value="mutation"><input type="hidden" name="request_id" value="<?= $item['id'] ?>"><button class="btn btn-sm btn-outline-warning">Revert</button></form></td></tr><?php endforeach; ?><?php if (!$mutations): ?><tr><td colspan="5" class="text-center text-muted">No decided mutation requests.</td></tr><?php endif; ?></tbody></table></div></div></div><div class="col-lg-6"><div class="dashboard-shell"><h5>KYC Decisions</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>S.No.</th><th>Reference</th><th>Property</th><th>Status</th><th>Revert</th></tr></thead><tbody><?php foreach ($kyc as $serial => $item): ?><tr><td><?= $serial + 1 ?></td><td><?= htmlspecialchars($item['reference_number']) ?></td><td><?= htmlspecialchars($item['property_code']) ?></td><td><?= htmlspecialchars($item['status']) ?></td><td><form method="post"><input type="hidden" name="action" value="revert"><input type="hidden" name="type" value="kyc"><input type="hidden" name="request_id" value="<?= $item['id'] ?>"><button class="btn btn-sm btn-outline-warning">Revert</button></form></td></tr><?php endforeach; ?><?php if (!$kyc): ?><tr><td colspan="5" class="text-center text-muted">No decided KYC requests.</td></tr><?php endif; ?></tbody></table></div></div></div></div></main></div></body></html>
