<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin']);

$error = '';
$success = '';

$categories = ['Lottery', 'Auction', 'FCFS', 'Direct Allotment'];

// Roles list (dropdown ke liye)
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $email     = $email === '' ? null : strtolower($email);
    $password  = $_POST['password'] ?? '';
    $role_id   = $_POST['role_id'] ?? '';
    $selected_categories = $_POST['categories'] ?? [];

    if ($full_name === '' || $username === '' || $password === '' || $role_id === '') {
        $error = "Sabhi zaroori fields (Name, Username, Password, Role) bharein.";
    } else {
        // Username already exists?
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);

        if ($check->rowCount() > 0) {
            $error = "Ye username pehle se maujood hai.";
        } elseif ($email !== null) {
            $email_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $email_check->execute([$email]);

            if ($email_check->fetch()) {
                $error = "This email is already registered. Please use another email address.";
            }
        }

        if ($error === '') {
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, username, email, password, role_id, status)
                     VALUES (?, ?, ?, ?, ?, 'active')"
                );
                $stmt->execute([$full_name, $username, $email, $hashed, $role_id]);
                $new_user_id = $pdo->lastInsertId();

                // Category permissions save karo
                foreach ($selected_categories as $cat) {
                    if (in_array($cat, $categories)) {
                        $p = $pdo->prepare("INSERT INTO user_permissions (user_id, category) VALUES (?, ?)");
                        $p->execute([$new_user_id, $cat]);
                    }
                }

                $success = "User successfully create ho gaya!";
            } catch (PDOException $exception) {
                if ((int) $exception->errorInfo[1] === 1062) {
                    $error = "Username ya email pehle se registered hai. Doosra value use karein.";
                } else {
                    $error = "The user could not be created. Please try again.";
                }
            }
        }
    }
}

// Sabhi users ki list (permissions ke saath)
$users = $pdo->query(
    "SELECT users.*, roles.role_name
     FROM users JOIN roles ON users.role_id = roles.id
     ORDER BY users.id DESC"
)->fetchAll();

// Har user ki permissions nikal lo
$permissions_map = [];
$perm_rows = $pdo->query("SELECT user_id, category FROM user_permissions")->fetchAll();
foreach ($perm_rows as $row) {
    $permissions_map[$row['user_id']][] = $row['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Manage Users</span>
    <div class="text-white">
        <a href="admin.php" class="text-white">Dashboard</a>
        | <a href="properties.php" class="text-white">Property List</a>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4">

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card p-4 mb-4">
        <h5 class="mb-3">Create New User</h5>
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email (optional)</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <option value="">-- Select Role --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Category Access (is user ko kaunsi property category dikhegi)</label>
                <?php foreach ($categories as $cat): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $cat ?>" id="cat_<?= $cat ?>">
                        <label class="form-check-label" for="cat_<?= $cat ?>"><?= $cat ?></label>
                    </div>
                <?php endforeach; ?>
                <div class="form-text">Category access is not required for administrators; administrators can view all categories.</div>
            </div>

            <button type="submit" class="btn btn-primary">Create User</button>
        </form>
    </div>

    <div class="card p-4">
        <h5 class="mb-3">All Users</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>S.No.</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Category Access</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $serial => $u): ?>
                        <tr>
                            <td><?= $serial + 1 ?></td>
                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['role_name']) ?></td>
                            <td>
                                <?php if ($u['role_name'] === 'Admin'): ?>
                                    <span class="badge bg-dark">All Categories</span>
                                <?php else: ?>
                                    <?php foreach (($permissions_map[$u['id']] ?? []) as $cat): ?>
                                        <span class="badge bg-info text-dark"><?= $cat ?></span>
                                    <?php endforeach; ?>
                                    <?php if (empty($permissions_map[$u['id']])): ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['role_name'] !== 'Admin'): ?>
                                    <a href="edit_permissions.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Edit Access</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
