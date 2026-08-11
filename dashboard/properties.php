<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login(); 


$category_filter = $_GET['category'] ?? '';
$status_filter    = $_GET['status'] ?? '';

$sql = "SELECT properties.*, users.full_name AS added_by_name
        FROM properties
        JOIN users ON properties.added_by = users.id
        WHERE 1=1";
$params = [];

if ($category_filter !== '') {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}
if ($status_filter !== '') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

$can_manage = in_array($_SESSION['role_name'], ['Admin', 'Property Officer']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Property List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Property List</span>
    <div class="text-white">
        Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>
        <?php if ($can_manage): ?>
            | <a href="add_property.php" class="text-white">+ Add Property</a>
        <?php endif; ?>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4">

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Property delete ho gayi.</div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach (['Lottery','Auction','FCFS','Direct Allotment'] as $c): ?>
                    <option value="<?= $c ?>" <?= $category_filter === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <?php foreach (['Available','Pending','Sold','Allotted'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead class="table-dark">
                <tr>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Added By</th>
                    <?php if ($can_manage): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($properties) === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted">Koi property nahi mili.</td></tr>
                <?php endif; ?>

                <?php foreach ($properties as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['property_title']) ?></td>
                        <td><?= htmlspecialchars($p['location']) ?></td>
                        <td>₹<?= number_format($p['price'], 2) ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['category']) ?></span></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($p['status']) ?></span></td>
                        <td><?= htmlspecialchars($p['added_by_name']) ?></td>
                        <?php if ($can_manage): ?>
                        <td>
                            <a href="edit_property.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete_property.php?id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Kya aap sach me delete karna chahte hain?');">Delete</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
