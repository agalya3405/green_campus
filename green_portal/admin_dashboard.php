<?php
// Admin dashboard entry point (requested name).
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    if ($_SESSION['role'] === 'staff') {
        header('Location: staff_dashboard.php');
    } else {
        header('Location: student_dashboard.php');
    }
    exit;
}
header('Location: admin/admin_dashboard.php');
exit;

