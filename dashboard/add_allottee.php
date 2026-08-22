<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin', 'Property Officer']);

$property_id = $_GET['property_id'] ?? $_POST['property_id'] ?? null;

if (!$property_id) {
    die("Invalid property.");
}

$prop_stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$prop_stmt->execute([$property_id]);
$property = $prop_stmt->fetch();

if (!$property) {
    die("Property not found.");
}

// Pehle se allottee hai to edit mode
$allottee_stmt = $pdo->prepare("SELECT * FROM allottees WHERE property_id = ?");
$allottee_stmt->execute([$property_id]);
$allottee = $allottee_stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allottee_name  = trim($_POST['allottee_name'] ?? '');
    $father_name    = trim($_POST['father_name'] ?? '');
    $mobile         = trim($_POST['mobile'] ?? '');
    $aadhar_no      = trim($_POST['aadhar_no'] ?? '');
    $pan_no         = trim($_POST['pan_no'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $allotment_date = $_POST['allotment_date'] ?? null;

    if ($allottee_name === '' || $mobile === '') {
        $error = "Allottee Name aur Mobile bharna zaroori hai.";
    } elseif ($mobile !== '' && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Mobile number 10 digit ka hona chahiye.";
    } elseif ($aadhar_no !== '' && !preg_match('/^[0-9]{12}$/', $aadhar_no)) {
        $error = "Aadhar number 12 digit ka hona chahiye.";
    } else {
        $aadhar_photo = $allottee['aadhar_photo'] ?? null;
        $pan_photo    = $allottee['pan_photo'] ?? null;

        // Aadhar photo upload
        if (!empty($_FILES['aadhar_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['aadhar_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png']) && $_FILES['aadhar_photo']['size'] <= 2 * 1024 * 1024) {
                $aadhar_photo = 'aadhar_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                move_uploaded_file($_FILES['aadhar_photo']['tmp_name'], '../assets/uploads/allottees/' . $aadhar_photo);
            }
        }

        // PAN photo upload
        if (!empty($_FILES['pan_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['pan_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png']) && $_FILES['pan_photo']['size'] <= 2 * 1024 * 1024) {
                $pan_photo = 'pan_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                move_uploaded_file($_FILES['pan_photo']['tmp_name'], '../assets/uploads/allottees/' . $pan_photo);
            }
        }

        if ($allottee) {
            // Update existing
            $upd = $pdo->prepare(
                "UPDATE allottees SET allottee_name=?, father_name=?, mobile=?, aadhar_no=?, pan_no=?,
                 aadhar_photo=?, pan_photo=?, address=?, allotment_date=? WHERE property_id=?"
            );
            $upd->execute([$allottee_name, $father_name, $mobile, $aadhar_no, $pan_no, $aadhar_photo, $pan_photo, $address, $allotment_date, $property_id]);
        } else {
            // Insert new
            $ins = $pdo->prepare(
                "INSERT INTO allottees (property_id, allottee_name, father_name, mobile, aadhar_no, pan_no, aadhar_photo, pan_photo, address, allotment_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->execute([$property_id, $allottee_name, $father_name, $mobile, $aadhar_no, $pan_no, $aadhar_photo, $pan_photo, $address, $allotment_date]);
        }

        // Property status "Allotted" kar do
        $pdo->prepare("UPDATE properties SET status = 'Allotted' WHERE id = ?")->execute([$property_id]);

        header("Location: property_detail.php?id=$property_id&saved=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Allottee Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Allottee Detail - <?= htmlspecialchars($property['property_code']) ?></span>
    <div class="text-white">
        <a href="property_detail.php?id=<?= $property_id ?>" class="text-white">← Back to Property</a>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4" style="max-width: 700px;">

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card p-4">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="property_id" value="<?= $property_id ?>">

            <div class="mb-3">
                <label class="form-label">Allottee Name</label>
                <input type="text" name="allottee_name" class="form-control" value="<?= htmlspecialchars($allottee['allottee_name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Father's Name</label>
                <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($allottee['father_name'] ?? '') ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mobile No.</label>
                    <input type="text" name="mobile" class="form-control" maxlength="10" value="<?= htmlspecialchars($allottee['mobile'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Allotment Date</label>
                    <input type="date" name="allotment_date" class="form-control" value="<?= htmlspecialchars($allottee['allotment_date'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Aadhar No.</label>
                    <input type="text" name="aadhar_no" class="form-control" maxlength="12" value="<?= htmlspecialchars($allottee['aadhar_no'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">PAN No.</label>
                    <input type="text" name="pan_no" class="form-control" value="<?= htmlspecialchars($allottee['pan_no'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($allottee['address'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Aadhar Photo</label>
                    <input type="file" name="aadhar_photo" class="form-control" accept=".jpg,.jpeg,.png">
                    <?php if (!empty($allottee['aadhar_photo'])): ?>
                        <img src="../assets/uploads/allottees/<?= htmlspecialchars($allottee['aadhar_photo']) ?>" class="mt-2" width="120">
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">PAN Photo</label>
                    <input type="file" name="pan_photo" class="form-control" accept=".jpg,.jpeg,.png">
                    <?php if (!empty($allottee['pan_photo'])): ?>
                        <img src="../assets/uploads/allottees/<?= htmlspecialchars($allottee['pan_photo']) ?>" class="mt-2" width="120">
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Save Allottee Detail</button>
        </form>
    </div>
</div>

</body>
</html>
