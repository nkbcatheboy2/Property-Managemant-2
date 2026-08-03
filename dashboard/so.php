<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['SO']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SO Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Property Management - SO Panel</span>
    <div class="text-white">
        Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>
        (<?= htmlspecialchars($_SESSION['role_name']) ?>)
        | <a href="properties.php" class="text-white">Property List</a>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <h4>SO Dashboard</h4>
    <p class="text-muted">Yahan SO ke assigned kaam se related properties/tasks dikhengi.</p>
</div>

</body>
</html>
