<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login();

$allowed_categories = get_user_allowed_categories();

if (empty($allowed_categories)) {
    die("You do not have permission to view any property category. Please contact an administrator.");
}

// Filters
$category_filter = $_GET['category'] ?? '';
$status_filter    = $_GET['status'] ?? '';
$search           = trim($_GET['search'] ?? '');

// User sirf apni allowed categories hi dekh sakta hai
$placeholders = implode(',', array_fill(0, count($allowed_categories), '?'));
$sql = "SELECT properties.*, users.full_name AS added_by_name,
           allottees.allottee_name,
               COALESCE(payment_totals.total_paid, 0) AS total_paid
        FROM properties
        JOIN users ON properties.added_by = users.id
        LEFT JOIN allottees ON allottees.property_id = properties.id
        LEFT JOIN (SELECT property_id, SUM(amount) AS total_paid FROM property_payments GROUP BY property_id) AS payment_totals
            ON payment_totals.property_id = properties.id
        WHERE properties.category IN ($placeholders)";
$params = $allowed_categories;

if ($category_filter !== '' && in_array($category_filter, $allowed_categories)) {
    $sql .= " AND properties.category = ?";
    $params[] = $category_filter;
}
if ($status_filter !== '') {
    // The visible status is calculated from allottee and payment data below.
}
if ($search !== '') {
    $sql .= " AND (properties.scheme_name LIKE ? OR properties.property_no LIKE ? OR properties.property_code LIKE ? OR properties.address LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

$sql .= " ORDER BY properties.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

$properties = array_values(array_filter($properties, function ($property) use ($status_filter) {
    $display_status = !$property['allottee_name']
        ? 'Vacant'
        : ((float) $property['total_paid'] >= (float) $property['price']
            ? 'Assigned for Registry'
            : 'Allotted');
    return $status_filter === '' || $status_filter === $display_status;
}));

foreach ($properties as &$property) {
    $property['display_status'] = !$property['allottee_name']
        ? 'Vacant'
        : ((float) $property['total_paid'] >= (float) $property['price']
            ? 'Assigned for Registry'
            : 'Allotted');
}
unset($property);

$can_manage = in_array($_SESSION['role_name'], ['Admin', 'Property Officer']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand">
            <span>🟢</span>
            <span>Property Directory</span>
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
                <div class="portal-sidebar-title">Main</div>
                <a href="admin.php" class="portal-nav-item">📊 Dashboard</a>
                <a href="properties.php" class="portal-nav-item active">📋 All Properties</a>
                <?php if ($can_manage): ?>
                    <a href="add_property.php" class="portal-nav-item">➕ Add Property</a>
                    <a href="import_properties.php" class="portal-nav-item">📥 Import</a>
                <?php endif; ?>
            </div>

            <div class="portal-sidebar-section">
                <div class="portal-sidebar-title">Modules</div>
                <a href="module_lottery.php" class="portal-nav-item">?? Lottery</a>
                <a href="module_auction.php" class="portal-nav-item">?? Auction</a>
                <a href="module_fcfs.php" class="portal-nav-item">?? FCFS</a>
                <a href="module_hrms.php" class="portal-nav-item">?? HRMS</a>
            </div>
        </aside>

        <main class="portal-main">
            <div class="portal-page-header mb-4">
                <h1 class="portal-page-title">Property Directory</h1>
                <p class="portal-page-subtitle">Browse, filter, and manage properties with ease.</p>
            </div>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Property deleted successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="container">
                <div class="dashboard-shell mb-3">
                    <form method="GET" class="row g-2">
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($allowed_categories as $c): ?>
                                    <option value="<?= $c ?>" <?= $category_filter === $c ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <?php foreach (['Vacant','Allotted','Assigned for Registry'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search scheme, property no, ID, address"
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <div class="dashboard-shell">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Scheme</th>
                                    <th>Property No.</th>
                                    <th>Property ID</th>
                                    <th>Address</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Allotted To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($properties) === 0): ?>
                                    <tr><td colspan="10" class="text-center text-muted">No properties found.</td></tr>
                                <?php endif; ?>

                                <?php foreach ($properties as $serial => $p): ?>
                                    <tr>
                                    <td><?= $serial + 1 ?></td>
                                        <td><?= htmlspecialchars($p['scheme_name']) ?></td>
                                        <td><?= htmlspecialchars($p['property_no']) ?></td>
                                        <td><?= htmlspecialchars($p['property_code']) ?></td>
                                        <td><?= htmlspecialchars($p['address']) ?></td>
                                        <td>INR <?= number_format($p['price'], 2) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['category']) ?></span></td>
                                        <td><span class="badge <?= $p['display_status'] === 'Assigned for Registry' ? 'bg-success' : ($p['display_status'] === 'Allotted' ? 'bg-primary' : 'bg-secondary') ?>"><?= htmlspecialchars($p['display_status']) ?></span></td>
                                        <td>
                                            <?php if ($p['allottee_name']): ?>
                                                <?= htmlspecialchars($p['allottee_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">�</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="property_detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                            <?php if ($can_manage): ?>
                                                <a href="edit_property.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="delete_property.php?id=<?= $p['id'] ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Kya aap sach me delete karna chahte hain?');">Delete</a>
                                            <?php endif; ?>
                                        </td>
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
</body>
</html>
