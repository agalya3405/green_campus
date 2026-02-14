<?php
// Student dashboard entry point (requested name).
// The actual student dashboard UI lives in dashboard.php.
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
if ($_SESSION['role'] === 'staff') {
    header('Location: staff_dashboard.php');
    exit;
}
header('Location: dashboard.php');
exit;

