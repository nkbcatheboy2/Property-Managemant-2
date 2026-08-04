<?php
require_once 'config/db.php';

$full_name = 'System Admin';
$username  = 'admin';
$email     = 'admin@example.com';
$plain_password = 'admin123';
$role_id   = 1; 


$check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$check->execute([$username]);

if ($check->rowCount() > 0) {
    die("Admin user pehle se maujood hai. Yeh file ab delete kar dein.");
}

$hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    "INSERT INTO users (full_name, username, email, password, role_id, status)
     VALUES (?, ?, ?, ?, ?, 'active')"
);
$stmt->execute([$full_name, $username, $email, $hashed_password, $role_id]);

echo "Admin user successfully create ho gaya!<br>";
echo "Username: admin<br>";
echo "Password: admin123<br><br>";
echo "<strong>IMPORTANT:</strong> Ab is file (create_admin.php) ko delete kar dein.";
