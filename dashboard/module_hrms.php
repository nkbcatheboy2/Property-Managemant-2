<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login();

if (!user_has_category_access('HRMS')) {
    die('Access denied: you do not have access to the HRMS module.');
}

$properties = $pdo->prepare("SELECT id, scheme_name, property_code, address, status, price FROM properties WHERE category = 'HRMS' ORDER BY created_at DESC");
$properties->execute();
$properties = $properties->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand">
            <span>👔</span>
            <span>HRMS / Direct Allotment Module</span>
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
                <a href="<?= dashboard_redirect_path($_SESSION['role_name']) ?>" class="portal-nav-item">📊 Dashboard</a>
                <a href="properties.php" class="portal-nav-item">📋 All Properties</a>
            </div>

        </aside>

        <aside class="portal-sidebar portal-sidebar-right" id="moduleSidebar">
            <button type="button" class="sidebar-collapse-button" data-sidebar-target="moduleSidebar" aria-label="Hide modules navigation"><i class="fas fa-chevron-right"></i></button>
            <div class="portal-sidebar-section">
                <div class="portal-sidebar-title">Modules</div>
                <a href="module_lottery.php" class="portal-nav-item">🎰 Lottery</a>
                <a href="module_auction.php" class="portal-nav-item">🔨 Auction</a>
                <a href="module_fcfs.php" class="portal-nav-item">📦 FCFS</a>
                <a href="module_hrms.php" class="portal-nav-item active">👔 HRMS</a>
            </div>
            </div>
        </aside>

        <main class="portal-main">
            <div class="portal-page-header mb-4">
                <h1 class="portal-page-title">HRMS / Direct Allotment</h1>
                <p class="portal-page-subtitle">Manage HRMS staff and direct allotment properties.</p>
            </div>

            <div class="container">
                <div class="dashboard-shell">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Property ID</th>
                                    <th>Scheme</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($properties)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No HRMS properties are available.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($properties as $serial => $item): ?>
                                    <tr>
                                    <td><?= $serial + 1 ?></td>
                                        <td><?= htmlspecialchars($item['property_code']) ?></td>
                                        <td><?= htmlspecialchars($item['scheme_name']) ?></td>
                                        <td><?= htmlspecialchars($item['address']) ?></td>
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
