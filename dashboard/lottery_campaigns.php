<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin']);

$error = '';
$success = '';
$property_types = ['Plot', 'Apartment', 'Shop'];
$campaign_types = ['Lottery', 'E-Auction'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
        $campaign_type = $_POST['campaign_type'] ?? 'Lottery';
        $lottery_name = trim($_POST['lottery_name'] ?? '');
        $scheme_name = trim($_POST['scheme_name'] ?? '');
        $plot_count = (int) ($_POST['plot_count'] ?? 0);
        $property_type = $_POST['property_type'] ?? '';
        $price_per_unit = (float) ($_POST['price_per_unit'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';

        if (!in_array($campaign_type, $campaign_types, true) || $lottery_name === '' || $scheme_name === '' || $plot_count < 1 || !in_array($property_type, $property_types, true)
            || $price_per_unit < 0 || $start_date === '' || $end_date === '' || $end_date < $start_date) {
            $error = 'Please fill valid campaign details and check the date range.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO lottery_campaigns
                (campaign_type, lottery_name, scheme_name, plot_count, property_type, price_per_unit, start_date, end_date, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Published', ?)");
            $stmt->execute([$campaign_type, $lottery_name, $scheme_name, $plot_count, $property_type, $price_per_unit, $start_date, $end_date, $_SESSION['user_id']]);
            $success = 'Lottery campaign create ho gayi.';
        }
    } elseif ($action === 'bulk_link') {
        $campaign_id = (int) ($_POST['campaign_id'] ?? 0);
        $property_ids = array_values(array_filter(array_map('intval', $_POST['property_ids'] ?? [])));
        $campaign_stmt = $pdo->prepare('SELECT plot_count FROM lottery_campaigns WHERE id = ?');
        $campaign_stmt->execute([$campaign_id]);
        $campaign = $campaign_stmt->fetch();
        $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM properties WHERE campaign_id = ?');
        $count_stmt->execute([$campaign_id]);
        $new_ids_count = 0;
        if ($property_ids) {
            $selected_placeholders = implode(',', array_fill(0, count($property_ids), '?'));
            $selected_stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE id IN ($selected_placeholders) AND (campaign_id IS NULL OR campaign_id != ?)");
            $selected_stmt->execute(array_merge($property_ids, [$campaign_id]));
            $new_ids_count = (int) $selected_stmt->fetchColumn();
        }
        if (!$campaign || (int) $count_stmt->fetchColumn() + $new_ids_count > (int) $campaign['plot_count']) {
            $error = 'Selected properties campaign ki plot limit se zyada hain.';
        } elseif ($property_ids) {
            $update = $pdo->prepare('UPDATE properties SET campaign_id = ? WHERE id = ? AND (campaign_id IS NULL OR campaign_id = ?)');
            foreach ($property_ids as $property_id) {
                $update->execute([$campaign_id, $property_id, $campaign_id]);
            }
            $success = count($property_ids) . ' properties campaign se link ho gayi.';
        }
    }
}

$campaigns = $pdo->query("SELECT lottery_campaigns.*, COUNT(properties.id) AS linked_count
    FROM lottery_campaigns LEFT JOIN properties ON properties.campaign_id = lottery_campaigns.id
    GROUP BY lottery_campaigns.id ORDER BY lottery_campaigns.start_date DESC")->fetchAll();
$properties = $pdo->query("SELECT id, property_code, scheme_name, campaign_id FROM properties ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lottery Campaigns</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand"><span>🎟️</span><span>Lottery Campaigns</span></div>
        <div class="portal-header-actions">
            <span class="text-white">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <a href="admin.php" class="btn btn-sm btn-outline-light">Dashboard</a>
            <a href="../logout.php" class="btn btn-sm btn-light">Logout</a>
        </div>
    </header>
    <main class="portal-main campaign-page">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <div class="portal-page-header"><h1 class="portal-page-title">Lottery Campaign Manager</h1><p class="portal-page-subtitle">Create public lottery campaigns and link properties to them.</p></div>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="dashboard-shell">
                    <h5 class="mb-3">Create Campaign</h5>
                    <form method="post">
                        <input type="hidden" name="action" value="create">
                        <label class="form-label">Campaign Type</label><select name="campaign_type" class="form-select mb-3" required><?php foreach ($campaign_types as $type): ?><option><?= $type ?></option><?php endforeach; ?></select>
                        <label class="form-label">Lottery / Auction Name</label><input name="lottery_name" class="form-control mb-3" required>
                        <label class="form-label">Scheme Name</label><input name="scheme_name" class="form-control mb-3" required>
                        <div class="row"><div class="col-6"><label class="form-label">Number of Plots</label><input type="number" min="1" name="plot_count" class="form-control mb-3" required></div><div class="col-6"><label class="form-label">Property Type</label><select name="property_type" class="form-select mb-3" required><?php foreach ($property_types as $type): ?><option><?= $type ?></option><?php endforeach; ?></select></div></div>
                        <label class="form-label">Price per Plot/Unit</label><input type="number" min="0" step="0.01" name="price_per_unit" class="form-control mb-3" required>
                        <div class="row"><div class="col-6"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control mb-3" required></div><div class="col-6"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control mb-3" required></div></div>
                        <button class="btn btn-primary w-100">Publish Campaign</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="dashboard-shell mb-4"><h5 class="mb-3">Campaigns</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>S.No.</th><th>Type / Name</th><th>Units</th><th>Price</th><th>Dates</th><th>Linked</th></tr></thead><tbody><?php foreach ($campaigns as $serial => $campaign): ?><tr><td><?= $serial + 1 ?></td><td><span class="badge text-bg-warning"><?= htmlspecialchars($campaign['campaign_type'] ?? 'Lottery') ?></span><br><strong><?= htmlspecialchars($campaign['lottery_name']) ?></strong><br><small><?= htmlspecialchars($campaign['scheme_name']) ?> · <?= htmlspecialchars($campaign['property_type']) ?></small></td><td><?= (int) $campaign['plot_count'] ?></td><td>₹<?= number_format($campaign['price_per_unit'], 2) ?></td><td><?= htmlspecialchars($campaign['start_date']) ?><br><?= htmlspecialchars($campaign['end_date']) ?></td><td><?= (int) $campaign['linked_count'] ?></td></tr><?php endforeach; ?><?php if (!$campaigns): ?><tr><td colspan="6" class="text-muted text-center">No campaigns yet.</td></tr><?php endif; ?></tbody></table></div></div>
                <div class="dashboard-shell"><h5 class="mb-3">Link Multiple Properties</h5><form method="post"><input type="hidden" name="action" value="bulk_link"><label class="form-label">Campaign</label><select name="campaign_id" class="form-select mb-3" required><option value="">Select campaign</option><?php foreach ($campaigns as $campaign): ?><option value="<?= $campaign['id'] ?>"><?= htmlspecialchars(($campaign['campaign_type'] ?? 'Lottery') . ' - ' . $campaign['lottery_name'] . ' (' . $campaign['plot_count'] . ' plots)') ?></option><?php endforeach; ?></select><label class="form-label">Select properties (multiple)</label><div class="campaign-property-picker"><?php foreach ($properties as $property): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="property_ids[]" value="<?= $property['id'] ?>"><span class="form-check-label"><?= htmlspecialchars($property['property_code'] . ' - ' . $property['scheme_name']) ?><?= $property['campaign_id'] ? ' <small>(linked)</small>' : '' ?></span></label><?php endforeach; ?></div><button class="btn btn-outline-primary mt-3">Link Selected Properties</button></form></div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
