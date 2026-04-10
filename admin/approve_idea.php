<?php
require_once '../config/session.php';
start_role_session('admin');
require_once '../config/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit();
}

$idea_id = isset($_POST['idea_id']) ? (int) $_POST['idea_id'] : 0;
$staff_id = isset($_POST['staff_id']) ? (int) $_POST['staff_id'] : 0;
$remarks = trim($_POST['admin_remarks'] ?? '');

if ($idea_id <= 0 || $staff_id <= 0 || $remarks === '') {
    header("Location: admin_dashboard.php");
    exit();
}

// Approve workflow: status, assigned_staff_id, admin_remarks, updated_at. Also set assigned_to for display.
$staff_name = null;
$st = mysqli_prepare($conn, "SELECT id, name FROM users WHERE id = ? AND role = 'faculty' LIMIT 1");
mysqli_stmt_bind_param($st, "i", $staff_id);
mysqli_stmt_execute($st);
$sr = mysqli_stmt_get_result($st);
if ($row = mysqli_fetch_assoc($sr)) {
    $staff_name = $row['name'];
}
mysqli_stmt_close($st);

if (!$staff_name) {
    header("Location: admin_dashboard.php");
    exit();
}

$sql = "UPDATE ideas
SET status = 'Approved',
    assigned_staff_id = ?,
    assigned_to = ?,
    admin_remarks = ?,
    updated_at = CURRENT_TIMESTAMP
WHERE id = ? AND status = 'Pending'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "issi", $staff_id, $staff_name, $remarks, $idea_id);
if (!mysqli_stmt_execute($stmt)) {
    die("SQL Error: " . mysqli_error($conn));
}
mysqli_stmt_close($stmt);

// +20 points for student when idea is approved (idea submitter = user_id)
$idea_user = null;
$uq = mysqli_prepare($conn, "SELECT user_id FROM ideas WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($uq, "i", $idea_id);
mysqli_stmt_execute($uq);
$uqr = mysqli_stmt_get_result($uq);
if ($ur = mysqli_fetch_assoc($uqr)) {
    $idea_user = (int) $ur['user_id'];
}
mysqli_stmt_close($uq);
if ($idea_user) {
    mysqli_query($conn, "UPDATE users SET points = COALESCE(points, 0) + 20 WHERE id = $idea_user");
}

header("Location: admin_dashboard.php");
exit();
