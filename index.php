<?php
require_once 'config/session.php';
$role = resolve_role_from_request(['admin', 'faculty', 'student'], 'guest');
if ($role !== 'guest') {
    start_role_session($role);
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: admin_dashboard.php');
        } elseif ($_SESSION['role'] === 'faculty') {
            header('Location: faculty_dashboard.php');
        } else {
            header('Location: student_dashboard.php');
        }
        exit;
    }
}
start_guest_session();
header('Location: login.php');
exit;
