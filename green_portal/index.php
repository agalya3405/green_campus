<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } elseif ($_SESSION['role'] === 'staff') {
        header('Location: staff_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}
header('Location: login.php');
exit;
