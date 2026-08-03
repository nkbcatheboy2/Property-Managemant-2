<?php
// ============================================
// Authentication Helper Functions
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check karo user login hai ya nahi
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Agar login nahi hai to login page pe bhej do
function require_login() {
    if (!is_logged_in()) {
        header("Location: /property-management/login.php");
        exit;
    }
}

// Specific role hi is page ko access kar sakta hai
function require_role($allowed_roles = []) {
    require_login();
    if (!in_array($_SESSION['role_name'], $allowed_roles)) {
        die("Access Denied: Aapke paas is page ko dekhne ki permission nahi hai.");
    }
}

// Role ke hisaab se sahi dashboard ka path do
function dashboard_redirect_path($role_name) {
    switch ($role_name) {
        case 'Admin':
            return '/property-management/dashboard/admin.php';
        case 'Property Officer':
            return '/property-management/dashboard/officer.php';
        case 'LDA':
            return '/property-management/dashboard/lda.php';
        case 'UDC':
            return '/property-management/dashboard/udc.php';
        case 'SO':
            return '/property-management/dashboard/so.php';
        default:
            return '/property-management/login.php';
    }
}
