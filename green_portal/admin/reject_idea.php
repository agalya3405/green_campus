<?php
session_start();
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
$remarks = trim($_POST['admin_remarks'] ?? '');

if ($idea_id <= 0 || $remarks === '') {
    header("Location: admin_dashboard.php");
    exit();
}

$sql = "UPDATE ideas
SET status = 'Rejected',
    assigned_staff_id = NULL,
    admin_remarks = ?,
    updated_at = CURRENT_TIMESTAMP
WHERE id = ? AND status = 'Pending'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $remarks, $idea_id);
if (!mysqli_stmt_execute($stmt)) {
    die("SQL Error: " . mysqli_error($conn));
}
mysqli_stmt_close($stmt);

header("Location: admin_dashboard.php");
exit();
