<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin', 'Property Officer']);

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    die("Invalid property.");
}

$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->execute([$id]);
$property = $stmt->fetch();

if (!$property) {
    die("Property not found.");
}

$error = '';
$success = '';
$categories = ['Lottery', 'Auction', 'FCFS', 'Direct Allotment'];
$campaigns = $pdo->query("SELECT id, lottery_name, scheme_name FROM lottery_campaigns WHERE status IN ('Draft','Published') ORDER BY start_date DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheme_name   = trim($_POST['scheme_name'] ?? '');
    $property_no   = trim($_POST['property_no'] ?? '');
    $property_code = trim($_POST['property_code'] ?? '');
    $scheme_address = trim($_POST['scheme_address'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $area_size     = trim($_POST['area_size'] ?? '');
    $property_type = $_POST['property_type'] ?? '';
    $allotment_date = trim($_POST['allotment_date'] ?? '') ?: null;
    $price         = trim($_POST['price'] ?? '');
    $category      = $_POST['category'] ?? '';
    $status        = $_POST['status'] ?? '';
    $description   = trim($_POST['description'] ?? '');
    $campaign_id   = (int) ($_POST['campaign_id'] ?? 0) ?: null;

    $allowed_status = ['Available', 'Pending', 'Sold', 'Allotted'];

    if ($scheme_name === '' || $scheme_address === '' || $property_no === '' || $property_code === '' || $address === ''
        || $area_size === '' || $price === '' || !in_array($property_type, ['Residential', 'Commercial', 'Shop', 'Office', 'Plot', 'Flat'], true)
        || !in_array($category, $categories, true) || !in_array($status, $allowed_status, true)) {
        $error = "Sabhi zaroori fields sahi se bharein.";
    } else {
        // Property ID doosre kisi property se clash to nahi kar raha
        $check = $pdo->prepare("SELECT id FROM properties WHERE property_code = ? AND id != ?");
        $check->execute([$property_code, $id]);

        if ($check->rowCount() > 0) {
            $error = "Ye Property ID kisi aur property me pehle se use ho rahi hai.";
        } else {
            $image_name = $property['image'];

            if (!empty($_FILES['image']['name'])) {
                $allowed_ext = ['jpg', 'jpeg', 'png'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed_ext) && $_FILES['image']['size'] <= 2 * 1024 * 1024) {
                    $image_name = 'prop_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], '../assets/uploads/properties/' . $image_name);
                }
            }

            $update = $pdo->prepare(
                "UPDATE properties
                 SET scheme_name=?, scheme_address=?, property_no=?, property_code=?, address=?, area_size=?, property_type=?, allotment_date=?, price=?, category=?, status=?, description=?, image=?, campaign_id=?
                 WHERE id=?"
            );
            $update->execute([
                $scheme_name, $scheme_address, $property_no, $property_code, $address, $area_size,
                $property_type, $allotment_date, $price, $category, $status, $description, $image_name, $campaign_id, $id
            ]);

            $success = "Property update ho gayi.";

            $stmt->execute([$id]);
            $property = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Edit Property</span>
    <div class="text-white">
        <a href="properties.php" class="text-white">← Back to List</a>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4" style="max-width: 700px;">

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card p-4">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $property['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Scheme Name</label>
                <input type="text" name="scheme_name" class="form-control" value="<?= htmlspecialchars($property['scheme_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Scheme Address</label>
                <textarea name="scheme_address" class="form-control" rows="2" required><?= htmlspecialchars($property['scheme_address'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Property No.</label>
                    <input type="text" name="property_no" class="form-control" value="<?= htmlspecialchars($property['property_no']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Property ID</label>
                    <input type="text" name="property_code" class="form-control" value="<?= htmlspecialchars($property['property_code']) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Lottery Campaign (optional)</label>
                <select name="campaign_id" class="form-select">
                    <option value="">-- No Campaign --</option>
                    <?php foreach ($campaigns as $campaign): ?>
                        <option value="<?= $campaign['id'] ?>" <?= (int) ($property['campaign_id'] ?? 0) === (int) $campaign['id'] ? 'selected' : '' ?>><?= htmlspecialchars($campaign['lottery_name'] . ' - ' . $campaign['scheme_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Property Address</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($property['address']) ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Area Size</label>
                    <input type="text" name="area_size" class="form-control" value="<?= htmlspecialchars($property['area_size']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($property['price']) ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Property Type</label>
                    <select name="property_type" class="form-select" required>
                        <?php foreach (['Residential', 'Commercial', 'Shop', 'Office', 'Plot', 'Flat'] as $type): ?><option value="<?= $type ?>" <?= ($property['property_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Allotment Date (optional)</label>
                    <input type="date" name="allotment_date" class="form-control" value="<?= htmlspecialchars($property['allotment_date'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c ?>" <?= $property['category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <?php foreach (['Available','Pending','Sold','Allotted'] as $s): ?>
                            <option value="<?= $s ?>" <?= $property['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($property['description']) ?></textarea>
            </div>

            <?php if ($property['image']): ?>
                <div class="mb-3">
                    <label class="form-label d-block">Current Image</label>
                    <img src="../assets/uploads/properties/<?= htmlspecialchars($property['image']) ?>" width="150">
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Change Image (optional)</label>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
            </div>

            <button type="submit" class="btn btn-primary w-100">Update Property</button>
        </form>
    </div>
</div>

</body>
</html>
