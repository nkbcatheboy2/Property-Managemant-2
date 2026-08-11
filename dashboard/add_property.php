<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin', 'Property Officer']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['property_title'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $area_size   = trim($_POST['area_size'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $category    = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');

    $allowed_categories = ['Lottery', 'Auction', 'FCFS', 'Direct Allotment'];

    if ($title === '' || $location === '' || $price === '' || !in_array($category, $allowed_categories)) {
        $error = "Zaroori fields (Title, Location, Price, Category) bharna hoga.";
    } else {
        $image_name = null;

        
        if (!empty($_FILES['image']['name'])) {
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_ext)) {
                $error = "Sirf JPG/PNG image allowed hai.";
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) { // 2MB limit
                $error = "Image size 2MB se kam honi chahiye.";
            } else {
                $image_name = 'prop_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_path = '../assets/uploads/properties/' . $image_name;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                "INSERT INTO properties (property_title, location, area_size, price, category, description, image, added_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $title, $location, $area_size, $price, $category, $description, $image_name, $_SESSION['user_id']
            ]);

            $success = "Property successfully add ho gayi!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Add Property</span>
    <div class="text-white">
        Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>
        | <a href="properties.php" class="text-white">Property List</a>
        | <a href="<?= $_SESSION['role_name'] === 'Admin' ? 'admin.php' : 'officer.php' ?>" class="text-white">Dashboard</a>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4" style="max-width: 700px;">

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card p-4">
        <h5 class="mb-3">New Property Details</h5>
        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label">Property Title</label>
                <input type="text" name="property_title" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Area Size (e.g. 1200 sq ft)</label>
                    <input type="text" name="area_size" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select" required>
                    <option value="">-- Select Category --</option>
                    <option value="Lottery">Lottery</option>
                    <option value="Auction">Auction</option>
                    <option value="FCFS">FCFS</option>
                    <option value="Direct Allotment">Direct Allotment</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Property Image (optional)</label>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
            </div>

            <button type="submit" class="btn btn-primary w-100">Save Property</button>
        </form>
    </div>
</div>

</body>
</html>
