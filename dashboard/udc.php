<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['UDC']);

$allowed_categories = get_user_allowed_categories();
$module_properties = [];
if (!empty($allowed_categories)) {
    $placeholders = implode(',', array_fill(0, count($allowed_categories), '?'));
    $sql = "SELECT id, scheme_name, property_code, status, price FROM properties WHERE category IN ($placeholders) ORDER BY created_at DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($allowed_categories);
    $module_properties = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UDC Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand">
            <span>🟢</span>
            <span>UDC - Auction Module</span>
        </div>
        <div class="portal-header-actions">
            <span class="text-white">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <button type="button" class="btn btn-sm btn-outline-light page-back-button" onclick="if (history.length > 1) { history.back(); } else { window.location.href = '../index.php'; }"><i class="fas fa-arrow-left me-1"></i>Back</button>
            <a href="../logout.php" class="btn btn-sm btn-light">Logout</a>
        </div>
    </header>

    <div class="portal-content">
        <aside class="portal-sidebar portal-sidebar-left" id="mainSidebar">
            <button type="button" class="sidebar-collapse-button" data-sidebar-target="mainSidebar" aria-label="Hide main navigation"><i class="fas fa-chevron-left"></i></button>
            <div class="portal-sidebar-section">
                <div class="portal-sidebar-title">Main</div>
                <a href="udc.php" class="portal-nav-item active">📊 Dashboard</a>
                <a href="properties.php" class="portal-nav-item">📋 All Properties</a>
            </div>

        </aside>

        <aside class="portal-sidebar portal-sidebar-right" id="moduleSidebar">
            <button type="button" class="sidebar-collapse-button" data-sidebar-target="moduleSidebar" aria-label="Hide modules navigation"><i class="fas fa-chevron-right"></i></button>
            <div class="portal-sidebar-section">
                <div class="portal-sidebar-title">Module</div>
                <a href="module_auction.php" class="portal-nav-item">🔨 Auction Process</a>
            </div>
            </div>
        </aside>

        <main class="portal-main">
            <div class="portal-page-header mb-4">
                <h1 class="portal-page-title">UDC Dashboard</h1>
                <p class="portal-page-subtitle">Manage auction-related properties and processes.</p>
            </div>

            <div class="container">
                <div class="dashboard-shell">
                    <h5 class="mb-3">Auction Properties</h5>
                    <a href="module_auction.php" class="btn btn-sm btn-primary mb-3">Open Auction Module</a>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Property ID</th>
                                    <th>Scheme</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($module_properties)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">No properties are assigned to this account.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($module_properties as $serial => $item): ?>
                                    <tr>
                                    <td><?= $serial + 1 ?></td>
                                        <td><?= htmlspecialchars($item['property_code']) ?></td>
                                        <td><?= htmlspecialchars($item['scheme_name']) ?></td>
                                        <td><?= htmlspecialchars($item['status']) ?></td>
                                        <td>INR <?= number_format($item['price'], 2) ?></td>
                                        <td><a href="property_detail.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Open</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
document.querySelectorAll('[data-sidebar-target]').forEach((button) => button.addEventListener('click', () => { const sidebar = document.getElementById(button.dataset.sidebarTarget); sidebar.classList.toggle('sidebar-collapsed'); button.querySelector('i').className = sidebar.classList.contains('sidebar-collapsed') ? 'fas fa-chevron-right' : 'fas fa-chevron-left'; }));
</script>
</body>
</html>
