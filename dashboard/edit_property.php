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
    die("Property nahi mili.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['property_title'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $area_size   = trim($_POST['area_size'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $category    = $_POST['category'] ?? '';
    $status      = $_POST['status'] ?? '';
    $description = trim($_POST['description'] ?? '');

    $allowed_categories = ['Lottery', 'Auction', 'FCFS', 'Direct Allotment'];
    $allowed_status = ['Available', 'Pending', 'Sold', 'Allotted'];

    if ($title === '' || $location === '' || $price === ''
        || !in_array($category, $allowed_categories)
        || !in_array($status, $allowed_status)) {
        $error = "Sabhi zaroori fields sahi se bharein.";
    } else {
        $image_name = $property['image']; // purani image by default

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
             SET property_title=?, location=?, area_size=?, price=?, category=?, status=?, description=?, image=?
             WHERE id=?"
        );
        $update->execute([$title, $location, $area_size, $price, $category, $status, $description, $image_name, $id]);

        $success = "Property update ho gayi.";

        // Refresh data
        $stmt->execute([$id]);
        $property = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <label class="form-label">Property Title</label>
                <input type="text" name="property_title" class="form-control" value="<?= htmlspecialchars($property['property_title']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($property['location']) ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Area Size</label>
                    <input type="text" name="area_size" class="form-control" value="<?= htmlspecialchars($property['area_size']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($property['price']) ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <?php foreach (['Lottery','Auction','FCFS','Direct Allotment'] as $c): ?>
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
