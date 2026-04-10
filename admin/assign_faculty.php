<?php
require_once '../config/session.php';
start_role_session('admin');
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: approved_ideas.php");
    exit();
}

$idea_id = isset($_POST['idea_id']) ? (int) $_POST['idea_id'] : 0;
$faculty_id = isset($_POST['faculty_id']) ? (int) $_POST['faculty_id'] : 0;

if ($idea_id <= 0 || $faculty_id <= 0) {
    header("Location: approved_ideas.php?success=0&msg=" . urlencode("Invalid idea or faculty."));
    exit();
}

// Ensure idea exists and is Approved
$chk = mysqli_prepare($conn, "SELECT id FROM ideas WHERE id = ? AND status = 'Approved' LIMIT 1");
mysqli_stmt_bind_param($chk, "i", $idea_id);
mysqli_stmt_execute($chk);
$chk_res = mysqli_stmt_get_result($chk);
if (!mysqli_fetch_assoc($chk_res)) {
    mysqli_stmt_close($chk);
    header("Location: approved_ideas.php?success=0&msg=" . urlencode("Idea not found or not approved."));
    exit();
}
mysqli_stmt_close($chk);

// Ensure faculty exists and is faculty role
$faculty_name = null;
$st = mysqli_prepare($conn, "SELECT id, name FROM users WHERE id = ? AND role = 'faculty' LIMIT 1");
mysqli_stmt_bind_param($st, "i", $faculty_id);
mysqli_stmt_execute($st);
$sr = mysqli_stmt_get_result($st);
if ($row = mysqli_fetch_assoc($sr)) {
    $faculty_name = $row['name'];
}
mysqli_stmt_close($st);

if (!$faculty_name) {
    header("Location: approved_ideas.php?success=0&msg=" . urlencode("Invalid faculty."));
    exit();
}

// Update idea: assigned_faculty_id and assigned_to for display consistency
$sql = "UPDATE ideas
        SET assigned_faculty_id = ?,
            assigned_to = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND status = 'Approved'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "isi", $faculty_id, $faculty_name, $idea_id);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: approved_ideas.php?success=0&msg=" . urlencode("Update failed."));
    exit();
}
mysqli_stmt_close($stmt);

header("Location: approved_ideas.php?success=1&msg=" . urlencode("Faculty assigned successfully."));
exit();
