<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin', 'Property Officer']);

$id = $_GET['id'] ?? null;

if ($id) {
    
    $stmt = $pdo->prepare("SELECT image FROM properties WHERE id = ?");
    $stmt->execute([$id]);
    $property = $stmt->fetch();

    if ($property) {
        $delete = $pdo->prepare("DELETE FROM properties WHERE id = ?");
        $delete->execute([$id]);

        if ($property['image'] && file_exists('../assets/uploads/properties/' . $property['image'])) {
            unlink('../assets/uploads/properties/' . $property['image']);
        }
    }
}

header("Location: properties.php?deleted=1");
exit;
