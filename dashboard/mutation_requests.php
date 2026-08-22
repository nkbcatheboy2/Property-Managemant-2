<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin']);

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $decision_remark = trim($_POST['decision_remark'] ?? '');
    $decision_reason = trim($_POST['decision_reason'] ?? '');
    $request_stmt = $pdo->prepare("SELECT citizen_requests.*, properties.id AS property_id
        FROM citizen_requests INNER JOIN properties ON properties.id = citizen_requests.property_id
        WHERE citizen_requests.id = ? AND citizen_requests.request_type = 'Mutation'");
    $request_stmt->execute([$request_id]);
    $request = $request_stmt->fetch();

    if (!$request || !in_array($decision, ['Approved', 'Rejected'], true)) {
        $error = 'Invalid mutation request.';
    } elseif ($decision_remark === '' || $decision_reason === '') {
        $error = 'Remark and reason are required.';
    } else {
        try {
            $pdo->beginTransaction();
            if ($decision === 'Approved') {
                $update_owner = $pdo->prepare("UPDATE allottees SET allottee_name = ? WHERE property_id = ? AND allottee_name = ?");
                $update_owner->execute([$request['new_allottee_name'], $request['property_id'], $request['old_allottee_name']]);
                if ($update_owner->rowCount() === 0) {
                    throw new RuntimeException('Current allottee name does not match the mutation request.');
                }
            }
            $update_request = $pdo->prepare("UPDATE citizen_requests SET status = ?, decision_remark = ?, decision_reason = ? WHERE id = ?");
            $update_request->execute([$decision, $decision_remark, $decision_reason, $request_id]);
            $pdo->commit();
            $success = $decision === 'Approved' ? 'Mutation approve ho gayi aur property owner name update ho gaya.' : 'Mutation request reject kar di gayi.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        }
    }
}

$requests = $pdo->query("SELECT citizen_requests.*, properties.property_code, properties.scheme_name,
    citizens.phone_number FROM citizen_requests
    INNER JOIN properties ON properties.id = citizen_requests.property_id
    INNER JOIN citizens ON citizens.id = citizen_requests.citizen_id
    WHERE citizen_requests.request_type = 'Mutation' ORDER BY citizen_requests.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Mutation Requests</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head>
<body><div class="portal-wrapper"><header class="portal-header"><div class="portal-brand"><span>🔄</span><span>Mutation Requests</span></div><div class="portal-header-actions"><a href="admin.php" class="btn btn-sm btn-outline-light">Dashboard</a><a href="../logout.php" class="btn btn-sm btn-light">Logout</a></div></header><main class="portal-main"><div class="portal-page-header"><h1 class="portal-page-title">Property Name Change Requests</h1><p class="portal-page-subtitle">Review and approve mutation applications submitted by property owners.</p></div><?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?><div class="dashboard-shell"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>S.No.</th><th>Reference</th><th>Property</th><th>Current Name</th><th>New Name</th><th>Relation</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead><tbody><?php if (!$requests): ?><tr><td colspan="9" class="text-center text-muted">No mutation requests yet.</td></tr><?php endif; ?><?php foreach ($requests as $serial => $request): ?><tr><td><?= $serial + 1 ?></td><td><?= htmlspecialchars($request['reference_number']) ?></td><td><?= htmlspecialchars($request['property_code'] . ' - ' . $request['scheme_name']) ?></td><td><?= htmlspecialchars($request['old_allottee_name']) ?></td><td><strong><?= htmlspecialchars($request['new_allottee_name']) ?></strong></td><td><?= htmlspecialchars($request['relation_to_old_allottee']) ?></td><td><?= htmlspecialchars($request['phone_number']) ?></td><td><span class="badge text-bg-<?= $request['status'] === 'Approved' ? 'success' : ($request['status'] === 'Rejected' ? 'secondary' : 'warning') ?>"><?= htmlspecialchars($request['status']) ?></span></td><td><?php if ($request['status'] === 'Submitted' || $request['status'] === 'Under Review'): ?><button type="button" class="btn btn-sm btn-success decision-button" data-request-id="<?= $request['id'] ?>" data-decision="Approved">Approve</button> <button type="button" class="btn btn-sm btn-danger decision-button" data-request-id="<?= $request['id'] ?>" data-decision="Rejected">Reject</button><?php else: ?>Done<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div></main></div><div class="modal fade" id="decisionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><div class="modal-header"><h5 class="modal-title">Decision details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="request_id" id="decisionRequestId"><input type="hidden" name="decision" id="decisionValue"><label class="form-label">Remark</label><input name="decision_remark" class="form-control mb-3" required><label class="form-label">Reason</label><textarea name="decision_reason" class="form-control" rows="4" required></textarea></div><div class="modal-footer"><button class="btn btn-primary">Save Decision</button></div></form></div></div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script>const decisionModal=new bootstrap.Modal(document.getElementById('decisionModal'));document.querySelectorAll('.decision-button').forEach(button=>button.addEventListener('click',()=>{document.getElementById('decisionRequestId').value=button.dataset.requestId;document.getElementById('decisionValue').value=button.dataset.decision;document.querySelector('#decisionModal .modal-title').textContent=button.dataset.decision+' Mutation Request';decisionModal.show();}));</script></body></html>
