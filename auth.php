<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function is_logged_in() {
    return isset($_SESSION['user_id']);
}


function require_login() {
    if (!is_logged_in()) {
        header("Location: /property-management/login.php");
        exit;
    }
}


function require_role($allowed_roles = []) {
    require_login();
    if (!in_array($_SESSION['role_name'], $allowed_roles)) {
        die("Access Denied: Aapke paas is page ko dekhne ki permission nahi hai.");
    }
}


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
