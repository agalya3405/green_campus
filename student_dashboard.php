<?php
// Student dashboard entry point (requested name).
// The actual student dashboard UI lives in dashboard.php.
require_once 'config/session.php';
start_role_session('student');
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
if ($_SESSION['role'] === 'faculty') {
    header('Location: faculty_dashboard.php');
    exit;
}
header('Location: dashboard.php');
exit;

