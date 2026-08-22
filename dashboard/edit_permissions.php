<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin']);

$categories = ['Lottery', 'Auction', 'FCFS', 'Direct Allotment'];
$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    die("Invalid user.");
}

$stmt = $pdo->prepare("SELECT users.*, roles.role_name FROM users JOIN roles ON users.role_id = roles.id WHERE users.id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['categories'] ?? [];
    $new_status = $_POST['status'] ?? 'active';

    // Purani permissions hata ke naya set save karo
    $del = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
    $del->execute([$id]);

    foreach ($selected as $cat) {
        if (in_array($cat, $categories)) {
            $ins = $pdo->prepare("INSERT INTO user_permissions (user_id, category) VALUES (?, ?)");
            $ins->execute([$id, $cat]);
        }
    }

    $upd = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $upd->execute([$new_status, $id]);

    $success = "Access update ho gaya.";
}

$current_perms = $pdo->prepare("SELECT category FROM user_permissions WHERE user_id = ?");
$current_perms->execute([$id]);
$current_perms = $current_perms->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Edit User Access</span>
    <div class="text-white">
        <a href="manage_users.php" class="text-white">← Back to Users</a>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4" style="max-width: 600px;">

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card p-4">
        <h5><?= htmlspecialchars($user['full_name']) ?> <span class="text-muted">(<?= htmlspecialchars($user['role_name']) ?>)</span></h5>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">

            <div class="mb-3 mt-3">
                <label class="form-label d-block">Category Access</label>
                <?php foreach ($categories as $cat): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $cat ?>"
                            id="cat_<?= $cat ?>" <?= in_array($cat, $current_perms) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="cat_<?= $cat ?>"><?= $cat ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Account Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Update Access</button>
        </form>
    </div>
</div>

</body>
</html>
