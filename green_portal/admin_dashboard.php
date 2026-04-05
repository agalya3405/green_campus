<?php
// Admin dashboard entry point (requested name).
require_once 'config/session.php';
start_role_session('admin');
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    if ($_SESSION['role'] === 'faculty') {
        header('Location: faculty_dashboard.php');
    } else {
        header('Location: student_dashboard.php');
    }
    exit;
}
header('Location: admin/admin_dashboard.php');
exit;

