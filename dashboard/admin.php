<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin']);

// Live dashboard summary: status is derived from allottee and payment data.
$summary = $pdo->query("SELECT
    COUNT(*) AS total_properties,
    SUM(CASE WHEN allottees.property_id IS NULL THEN 1 ELSE 0 END) AS vacant_properties,
    SUM(CASE WHEN allottees.property_id IS NOT NULL
        AND COALESCE(payment_totals.total_paid, 0) < properties.price THEN 1 ELSE 0 END) AS allotted_properties,
    SUM(CASE WHEN allottees.property_id IS NOT NULL
        AND COALESCE(payment_totals.total_paid, 0) >= properties.price THEN 1 ELSE 0 END) AS registry_properties,
    COALESCE(SUM(payment_totals.total_paid), 0) AS total_received,
    COALESCE(SUM(properties.price), 0) AS total_property_value
    FROM properties
    LEFT JOIN allottees ON allottees.property_id = properties.id
    LEFT JOIN (SELECT property_id, SUM(amount) AS total_paid FROM property_payments GROUP BY property_id) AS payment_totals
        ON payment_totals.property_id = properties.id")->fetch();

$total = (int) $summary['total_properties'];
$vacant = (int) $summary['vacant_properties'];
$allotted = (int) $summary['allotted_properties'];
$registry = (int) $summary['registry_properties'];
$total_received = (float) $summary['total_received'];
$total_value = (float) $summary['total_property_value'];

$category_counts = $pdo->query("SELECT category, COUNT(*) AS total FROM properties GROUP BY category ORDER BY category")->fetchAll();

$property_detail_list = $pdo->query("SELECT properties.property_code, properties.scheme_name, properties.category,
        CASE WHEN allottees.property_id IS NULL THEN 'Vacant'
             WHEN COALESCE(payment_totals.total_paid, 0) >= properties.price THEN 'Assigned for Registry'
             ELSE 'Allotted' END AS display_status
    FROM properties LEFT JOIN allottees ON allottees.property_id = properties.id
    LEFT JOIN (SELECT property_id, SUM(amount) AS total_paid FROM property_payments GROUP BY property_id) AS payment_totals
        ON payment_totals.property_id = properties.id ORDER BY properties.created_at DESC")->fetchAll();
$property_lists = [
    'properties' => $property_detail_list,
    'vacant' => array_values(array_filter($property_detail_list, static fn($property) => $property['display_status'] === 'Vacant')),
    'allotted' => array_values(array_filter($property_detail_list, static fn($property) => $property['display_status'] === 'Allotted')),
    'registry' => array_values(array_filter($property_detail_list, static fn($property) => $property['display_status'] === 'Assigned for Registry'))
];

$today_payment = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM property_payments WHERE payment_date = CURDATE()")->fetchColumn();
$week_payment = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM property_payments WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetchColumn();
$today_payment_list = $pdo->query("SELECT property_payments.payment_date, property_payments.amount,
    property_payments.payment_mode, properties.property_code, properties.scheme_name
    FROM property_payments JOIN properties ON properties.id = property_payments.property_id
    WHERE property_payments.payment_date = CURDATE() ORDER BY property_payments.created_at DESC")->fetchAll();
$week_payment_list = $pdo->query("SELECT property_payments.payment_date, property_payments.amount,
    property_payments.payment_mode, properties.property_code, properties.scheme_name
    FROM property_payments JOIN properties ON properties.id = property_payments.property_id
    WHERE property_payments.payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    ORDER BY property_payments.payment_date DESC, property_payments.created_at DESC")->fetchAll();

try {
    $today_work_list = $pdo->query("SELECT request_type, reference_number, status, created_at
        FROM citizen_requests WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $exception) {
    $today_work_list = [];
}

$employees = $pdo->query("SELECT full_name, username, email, status FROM users ORDER BY full_name")->fetchAll();
$dashboard_lists = [
    'properties' => $property_lists['properties'],
    'vacant' => $property_lists['vacant'],
    'allotted' => $property_lists['allotted'],
    'registry' => $property_lists['registry'],
    'today_payment' => $today_payment_list,
    'week_payment' => $week_payment_list,
    'today_work' => $today_work_list,
    'employees' => $employees
];

$today_work_count = count($today_work_list);

$module_properties = $pdo->query("SELECT properties.id, properties.scheme_name, properties.property_code,
    properties.category, properties.price, allottees.property_id AS allottee_id,
    COALESCE(payment_totals.total_paid, 0) AS total_paid
    FROM properties
    LEFT JOIN allottees ON allottees.property_id = properties.id
    LEFT JOIN (SELECT property_id, SUM(amount) AS total_paid FROM property_payments GROUP BY property_id) AS payment_totals
        ON payment_totals.property_id = properties.id
    ORDER BY properties.created_at DESC LIMIT 8")->fetchAll();

foreach ($module_properties as &$item) {
    $item['display_status'] = !$item['allottee_id']
        ? 'Vacant'
        : ((float) $item['total_paid'] >= (float) $item['price'] ? 'Assigned for Registry' : 'Allotted');
}
unset($item);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand">
            <span>🏢</span>
            <span>Property Management - Admin</span>
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
            <div class="portal-sidebar-section admin-menu-current">
                <div class="portal-sidebar-title">Main Menu</div>
                <a href="property_search.php" class="portal-nav-item">🔎 Property Search</a>
                <details class="portal-nav-dropdown" open><summary class="portal-nav-item">⚙️ Configuration</summary><div class="portal-nav-children">
                    <a href="property_search.php" class="portal-nav-item">Property Size Definition</a>
                    <a href="lottery_campaigns.php" class="portal-nav-item">Lottery / E-Auction Campaigns</a>
                    <span class="portal-nav-item coming-soon">Letter Template <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">🏗️ Property Configuration</summary><div class="portal-nav-children">
                    <a href="scheme_definition.php" class="portal-nav-item">Scheme Definition</a>
                    <a href="properties.php" class="portal-nav-item">Property Inventory</a>
                    <a href="enrollment_update.php" class="portal-nav-item">Enrollment Update</a>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">📝 Permission Application</summary><div class="portal-nav-children">
                    <a href="permission_requests.php?type=NOC" class="portal-nav-item">NOC (Mortgage) Process</a>
                    <a href="permission_requests.php?type=Surrender" class="portal-nav-item">Surrender Process</a>
                    <span class="portal-nav-item coming-soon">Possession Process <small>Coming Soon</small></span>
                    <a href="kyc_requests.php" class="portal-nav-item">KYC Process</a>
                    <span class="portal-nav-item coming-soon">Change of Floor <small>Coming Soon</small></span>
                    <a href="mutation_requests.php" class="portal-nav-item">Mutation Internal</a>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">🏛️ Estate Management</summary><div class="portal-nav-children">
                    <a href="property_management.php" class="portal-nav-item">Property Management</a>
                    <span class="portal-nav-item coming-soon">Legacy Property Update <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">💳 Financial Activities</summary><div class="portal-nav-children">
                    <a href="online_payments.php" class="portal-nav-item">Online Payments</a>
                    <span class="portal-nav-item coming-soon">Legacy Property Costing <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">💼 Sales</summary><div class="portal-nav-children">
                    <a href="enrollment_update.php" class="portal-nav-item">Allotment Plan</a>
                    <a href="campaign_applications.php" class="portal-nav-item">Property Application</a>
                    <a href="sale.php#final-costing" class="portal-nav-item">Final Costing</a>
                    <span class="portal-nav-item coming-soon">Agreement <small>Coming Soon</small></span>
                    <a href="registry.php" class="portal-nav-item">Registry</a>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">🔧 Utility</summary><div class="portal-nav-children">
                    <span class="portal-nav-item coming-soon">Apply Schedule (Property) <small>Coming Soon</small></span>
                    <a href="enrollment_update.php" class="portal-nav-item">Allotment Update</a>
                    <span class="portal-nav-item coming-soon">Enrollment Update <small>Coming Soon</small></span>
                </div></details>
            </div>
            <?php if (false): ?><div class="portal-sidebar-section admin-menu-list">
                <div class="portal-sidebar-title">Main Menu</div>
                <a href="admin.php" class="portal-nav-item active">📊 Dashboard</a>
                <a href="property_management.php" class="portal-nav-item">🏠 Property Management</a>
                <a href="properties.php" class="portal-nav-item">🔎 Property Search</a>
                <details class="portal-nav-dropdown" open><summary class="portal-nav-item">⚙️ Configuration</summary><div class="portal-nav-children">
                    <span class="portal-nav-item coming-soon">Rate Note <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Property Size Definition <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Letter Template <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">🏗️ Property Configuration</summary><div class="portal-nav-children">
                    <span class="portal-nav-item coming-soon">Scheme Definition <small>Coming Soon</small></span>
                    <a href="properties.php" class="portal-nav-item">Property Inventory</a>
                    <span class="portal-nav-item coming-soon">Enrollment <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">📝 Permission Application</summary><div class="portal-nav-children">
                    <span class="portal-nav-item coming-soon">Application Type <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Application Document <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Application Workflow <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Application Privilege <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">NOC (Mortgage) Process <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Surrender Process <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Possession Process <small>Coming Soon</small></span>
                    <a href="kyc_requests.php" class="portal-nav-item">KYC Process</a>
                    <a href="mutation_requests.php" class="portal-nav-item">Mutation Process</a>
                    <span class="portal-nav-item coming-soon">Change of Floor <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Excess Amount Refund Process <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Application Request <small>Coming Soon</small></span>
                    <span class="portal-nav-item coming-soon">Time Gap Approval <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">🏛️ Estate Management</summary><div class="portal-nav-children">
                    <span class="portal-nav-item coming-soon">Notice Letter <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Show Cause Notice <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Demand Note <small>Coming Soon</small></span><a href="property_management.php" class="portal-nav-item">Property Management</a><span class="portal-nav-item coming-soon">Mutation Internal <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Cancellation Process <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Legacy Property Update <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">OTS Application <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">💳 Financial Activities</summary><div class="portal-nav-children">
                    <span class="portal-nav-item coming-soon">Direct Receipt <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">General Transaction <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Online Payments <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">TDS Approval <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Legacy Property Costing <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Old Receipt Detail <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">💼 Sales</summary><div class="portal-nav-children">
                    <span class="portal-nav-item coming-soon">Allotment Plan <small>Coming Soon</small></span><a href="campaign_applications.php" class="portal-nav-item">Property Application</a><span class="portal-nav-item coming-soon">Lease Plan <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Excess Land Approval <small>Coming Soon</small></span><a href="sale.php#final-costing" class="portal-nav-item">Final Costing</a><span class="portal-nav-item coming-soon">Agreement <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Registry <small>Coming Soon</small></span>
                </div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">📚 Nazul</summary><div class="portal-nav-children"><span class="portal-nav-item coming-soon">Register 1862 <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Register 1886 <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Register 1907 <small>Coming Soon</small></span></div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">🔧 Utility</summary><div class="portal-nav-children"><span class="portal-nav-item coming-soon">Apply Schedule (Property) <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Allotment Update <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Enrollment Update <small>Coming Soon</small></span></div></details>
                <details class="portal-nav-dropdown"><summary class="portal-nav-item">📈 MIS Reports</summary><div class="portal-nav-children"><span class="portal-nav-item coming-soon">Allotment Summary <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Payment Detail FCFS <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Payment Detail Lottery <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Payment Detail Auction <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Registry Camp <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Nazul Registry <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Revert History <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Over 90% Payment Received <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Lease Pending over 30 Days <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Costing Pending over 21 Days <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Due Detail <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Enrollment API Sync Data Summary <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Draftsman / Tracer Wise Lease Plan Details <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Scheme Summary <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Registry Pending 30 Days <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Agreement Pending over 30 Days <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Possession Pending over 2 Weeks <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Headwise Recovery & Transaction <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Bank NOC Pending over 3 Weeks <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Payment Detail Direct Allotment <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Consolidated Payment Detail <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Employee Scheme Relation <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Nazul Register 1862 <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">OTS Applicant <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Updated Legacy Property Details <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Old Receipt MIS <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Employee Wise Old Receipts <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Consolidated Payment Report <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">KYC Survey <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">KYC Survey Scheme Wise <small>Coming Soon</small></span><span class="portal-nav-item coming-soon">Scheme Wise Recovery Report <small>Coming Soon</small></span></div></details>
            </div><?php endif; ?>
        </aside>

        <main class="portal-main">
            <div class="portal-page-header mb-4">
                <h1 class="portal-page-title">Admin Dashboard</h1>
                <p class="portal-page-subtitle">Central control panel for property operations, allotments, and payments.</p>
            </div>

            <div class="container">
                <div class="row g-3 mb-4">
                    <?php foreach ([
                        ['Total Properties', $total, 'text-primary', 'fa-building', 'properties'],
                        ['Vacant', $vacant, 'text-secondary', 'fa-house', 'vacant'],
                        ['Allotted', $allotted, 'text-info', 'fa-user-check', 'allotted'],
                        ['Assigned for Registry', $registry, 'text-success', 'fa-file-circle-check', 'registry'],
                        ['Payment Received', '₹' . number_format($total_received, 2), 'text-warning', 'fa-indian-rupee-sign', 'week_payment'],
                        ['Property Value', '₹' . number_format($total_value, 2), 'text-dark', 'fa-chart-line', 'properties'],
                        ["Today's Payment", '₹' . number_format($today_payment, 2), 'text-success', 'fa-calendar-day', 'today_payment'],
                        ['7 Days Payment', '₹' . number_format($week_payment, 2), 'text-info', 'fa-calendar-week', 'week_payment'],
                        ["Today's Work", $today_work_count, 'text-primary', 'fa-list-check', 'today_work'],
                        ['Employees', count($employees), 'text-dark', 'fa-users', 'employees']
                    ] as $stat): ?>
                        <div class="col-sm-6 col-xl-4">
                            <button type="button" class="card dashboard-stat-card dashboard-stat-button h-100 w-100 text-start" data-list-key="<?= $stat[4] ?>" data-bs-toggle="modal" data-bs-target="#dashboardListModal">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted mb-2"><?= $stat[0] ?></h6>
                                        <h2 class="mb-0 <?= $stat[2] ?>"><?= $stat[1] ?></h2>
                                    </div>
                                    <i class="fas <?= $stat[3] ?> dashboard-stat-icon <?= $stat[2] ?>"></i>
                                </div>
                                <small class="dashboard-stat-link">Click to view details</small>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="dashboard-shell mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Properties by Category</h5>
                        <a href="properties.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($category_counts as $category): ?>
                            <div class="col-sm-6 col-lg-3">
                                <div class="dashboard-category-stat">
                                    <span><?= htmlspecialchars($category['category']) ?></span>
                                    <strong><?= (int) $category['total'] ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$category_counts): ?><p class="text-muted mb-0">No properties added yet.</p><?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-shell">
                    <h5 class="mb-3">Recent Properties</h5>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Property ID</th>
                                    <th>Scheme</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Received</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($module_properties as $serial => $item): ?>
                                    <tr>
                                    <td><?= $serial + 1 ?></td>
                                        <td><?= htmlspecialchars($item['property_code']) ?></td>
                                        <td><?= htmlspecialchars($item['scheme_name']) ?></td>
                                        <td><?= htmlspecialchars($item['category']) ?></td>
                                        <td><span class="badge <?= $item['display_status'] === 'Assigned for Registry' ? 'bg-success' : ($item['display_status'] === 'Allotted' ? 'bg-primary' : 'bg-secondary') ?>"><?= htmlspecialchars($item['display_status']) ?></span></td>
                                        <td>₹<?= number_format($item['total_paid'], 2) ?></td>
                                        <td>₹<?= number_format($item['price'], 2) ?></td>
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
    document.querySelectorAll('[data-sidebar-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const sidebar = document.getElementById(button.dataset.sidebarTarget);
            sidebar.classList.toggle('sidebar-collapsed');
            button.querySelector('i').className = sidebar.classList.contains('sidebar-collapsed') ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
        });
    });
</script>

<div class="modal fade" id="dashboardListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashboardListTitle">Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 dashboard-popup-table">
                        <thead id="dashboardListHead"></thead>
                        <tbody id="dashboardListBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const dashboardLists = <?= json_encode($dashboard_lists, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const dashboardTitles = {
        properties: 'Property List',
        vacant: 'Vacant Property List',
        allotted: 'Allotted Property List',
        registry: 'Registry-Ready Property List',
        today_payment: "Today's Payment List",
        week_payment: 'Last 7 Days Payment List',
        today_work: "Today's Work List",
        employees: 'Employees List'
    };
    const dashboardColumns = {
        properties: [['serial', 'S.No.'], ['property_code', 'Property ID'], ['scheme_name', 'Scheme'], ['category', 'Category'], ['display_status', 'Status']],
        vacant: [['serial', 'S.No.'], ['property_code', 'Property ID'], ['scheme_name', 'Scheme'], ['category', 'Category'], ['display_status', 'Status']],
        allotted: [['serial', 'S.No.'], ['property_code', 'Property ID'], ['scheme_name', 'Scheme'], ['category', 'Category'], ['display_status', 'Status']],
        registry: [['serial', 'S.No.'], ['property_code', 'Property ID'], ['scheme_name', 'Scheme'], ['category', 'Category'], ['display_status', 'Status']],
        today_payment: [['serial', 'S.No.'], ['payment_date', 'Date'], ['property_code', 'Property ID'], ['scheme_name', 'Scheme'], ['amount', 'Amount'], ['payment_mode', 'Mode']],
        week_payment: [['serial', 'S.No.'], ['payment_date', 'Date'], ['property_code', 'Property ID'], ['scheme_name', 'Scheme'], ['amount', 'Amount'], ['payment_mode', 'Mode']],
        today_work: [['serial', 'S.No.'], ['reference_number', 'Reference'], ['request_type', 'Work'], ['status', 'Status'], ['created_at', 'Time']],
        employees: [['serial', 'S.No.'], ['full_name', 'Name'], ['username', 'Username'], ['email', 'Email'], ['status', 'Status']]
    };

    const escapeHtml = (value) => String(value ?? '-').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    })[character]);

    document.querySelectorAll('.dashboard-stat-button').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.listKey;
            const columns = dashboardColumns[key] || [];
            const rows = dashboardLists[key] || [];
            document.getElementById('dashboardListTitle').textContent = dashboardTitles[key] || 'Details';
            document.getElementById('dashboardListHead').innerHTML = '<tr>' + columns.map((column) => `<th>${column[1]}</th>`).join('') + '</tr>';
            document.getElementById('dashboardListBody').innerHTML = rows.length
                ? rows.map((row, index) => '<tr>' + columns.map((column) => `<td>${escapeHtml(column[0] === 'serial' ? index + 1 : row[column[0]])}</td>`).join('') + '</tr>').join('')
                : `<tr><td colspan="${columns.length}" class="text-center text-muted py-4">No data found.</td></tr>`;
        });
    });
</script>
</body>
</html>


</body>
</html>
