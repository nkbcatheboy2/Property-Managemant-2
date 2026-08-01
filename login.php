<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

// Agar already login hai to seedha dashboard bhej do
if (is_logged_in()) {
    header("Location: " . dashboard_redirect_path($_SESSION['role_name']));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username aur password dono bharna zaroori hai.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT users.*, roles.role_name
             FROM users
             JOIN roles ON users.role_id = roles.id
             WHERE users.username = ?"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            if ($user['status'] !== 'active') {
                $error = "Aapka account inactive hai. Admin se contact karein.";
            } else {
                // Session set karo
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_name'] = $user['role_name'];

                // Login log save karo
                $log = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
                $log->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);

                header("Location: " . dashboard_redirect_path($user['role_name']));
                exit;
            }
        } else {
            $error = "Galat username ya password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Property Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<div class="login-card">
    <h4 class="text-center mb-1">Property Management System</h4>
    <p class="text-center text-muted mb-4">Login to your account</p>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>

</body>
</html>
