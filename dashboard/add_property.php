<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin', 'Property Officer']);

$error = '';
$success = '';

$all_categories = ['Lottery', 'Auction', 'FCFS', 'Direct Allotment'];
$allowed_categories = get_user_allowed_categories();
$campaigns = $pdo->query("SELECT id, lottery_name, scheme_name FROM lottery_campaigns WHERE status IN ('Draft','Published') ORDER BY start_date DESC")->fetchAll();

if (empty($allowed_categories)) {
    die("You do not have permission to add properties. Please contact an administrator.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheme_name   = trim($_POST['scheme_name'] ?? '');
    $property_no   = trim($_POST['property_no'] ?? '');
    $property_code = trim($_POST['property_code'] ?? '');
    $campaign_id   = (int) ($_POST['campaign_id'] ?? 0) ?: null;
    $scheme_address = trim($_POST['scheme_address'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $area_size     = trim($_POST['area_size'] ?? '');
    $property_type = $_POST['property_type'] ?? '';
    $allotment_date = trim($_POST['allotment_date'] ?? '') ?: null;
    $price         = trim($_POST['price'] ?? '');
    $category      = $_POST['category'] ?? '';
    $description   = trim($_POST['description'] ?? '');

    if ($property_code === '') {
        do {
            $property_code = (string) random_int(10000, 99999);
            $check = $pdo->prepare("SELECT id FROM properties WHERE property_code = ?");
            $check->execute([$property_code]);
        } while ($check->fetch());
    }

    if ($scheme_name === '' || $scheme_address === '' || $property_no === '' || $address === ''
        || $area_size === '' || $price === '' || !in_array($property_type, ['Residential', 'Commercial', 'Shop', 'Office', 'Plot', 'Flat'], true)
        || !in_array($category, $allowed_categories, true)) {
        $error = "Please complete all required fields and select an allowed category.";
    } else {
        $check = $pdo->prepare("SELECT id FROM properties WHERE property_code = ?");
        $check->execute([$property_code]);

        if ($check->rowCount() > 0) {
            $error = "This Property ID already exists. Please use a different ID.";
        } else {
            $image_name = null;

            if (!empty($_FILES['image']['name'])) {
                $allowed_ext = ['jpg', 'jpeg', 'png'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed_ext) && $_FILES['image']['size'] <= 2 * 1024 * 1024) {
                    $image_name = 'prop_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], '../assets/uploads/properties/' . $image_name);
                } else {
                    $error = "Images must be JPG or PNG files smaller than 2 MB.";
                }
            }

            if ($error === '') {
                $stmt = $pdo->prepare(
                    "INSERT INTO properties (scheme_name, scheme_address, property_no, property_code, address, area_size, property_type, allotment_date, price, category, description, image, added_by, campaign_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $scheme_name, $scheme_address, $property_no, $property_code, $address, $area_size,
                    $property_type, $allotment_date, $price, $category, $description, $image_name, $_SESSION['user_id'], $campaign_id
                ]);

                $success = "Property added successfully.";
                header("refresh:2;url=properties.php");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="portal-wrapper">
    <header class="portal-header">
        <div class="portal-brand">
            <span>➕</span>
            <span>Add New Property</span>
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
                <div class="portal-sidebar-title">Navigation</div>
                <a href="<?= $_SESSION['role_name'] === 'Admin' ? 'admin.php' : 'officer.php' ?>" class="portal-nav-item">📊 Dashboard</a>
                <a href="properties.php" class="portal-nav-item">📋 Property List</a>
                <a href="add_property.php" class="portal-nav-item active">➕ Add Property</a>
                <a href="import_properties.php" class="portal-nav-item">📥 Import</a>
            </div>
        </aside>

        <main class="portal-main">
            <div class="portal-page-header mb-4">
                <h1 class="portal-page-title">Add New Property</h1>
                <p class="portal-page-subtitle">Create and register a new property in the system.</p>
            </div>

            <div class="container" style="max-width: 700px;">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="dashboard-shell card">
                    <form method="POST" enctype="multipart/form-data" class="p-4">

                        <div class="mb-3">
                            <label class="form-label">Scheme Name</label>
                            <input type="text" name="scheme_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Scheme Address</label>
                            <textarea name="scheme_address" class="form-control" rows="2" required placeholder="Scheme / project ka full address"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Property No.</label>
                                <input type="text" name="property_no" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Property ID (auto-generated)</label>
                                <input type="text" name="property_code" class="form-control" placeholder="Leave blank to generate a 5-digit ID">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Lottery Campaign (optional)</label>
                            <select name="campaign_id" class="form-select">
                                <option value="">-- Link to Campaign --</option>
                                <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?= $campaign['id'] ?>"><?= htmlspecialchars($campaign['lottery_name'] . ' - ' . $campaign['scheme_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Property Address</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Area Size (e.g. 1200 sq ft)</label>
                                <input type="text" name="area_size" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (INR)</label>
                                <input type="number" step="0.01" name="price" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Property Type</label>
                                <select name="property_type" class="form-select" required>
                                    <option value="">-- Select Type --</option>
                                    <?php foreach (['Residential', 'Commercial', 'Shop', 'Office', 'Plot', 'Flat'] as $type): ?><option value="<?= $type ?>"><?= $type ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Allotment Date (optional)</label>
                                <input type="date" name="allotment_date" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($all_categories as $c): ?>
                                    <?php if (in_array($c, $allowed_categories)): ?>
                                        <option value="<?= $c ?>"><?= $c ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Only categories assigned to your account are available.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Property Image (optional)</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Save Property</button>
                            <a href="properties.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
