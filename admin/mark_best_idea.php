<?php
require_once '../config/session.php';
start_role_session('admin');
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_ideas.php");
    exit();
}

$idea_id = isset($_POST['idea_id']) ? (int) $_POST['idea_id'] : 0;
if ($idea_id <= 0) {
    header("Location: manage_ideas.php?success=0&msg=" . urlencode("Invalid idea."));
    exit();
}

// Idea must exist and be Completed
$chk = mysqli_prepare($conn, "SELECT id, user_id FROM ideas WHERE id = ? AND status = 'Completed' LIMIT 1");
mysqli_stmt_bind_param($chk, "i", $idea_id);
mysqli_stmt_execute($chk);
$chk_res = mysqli_stmt_get_result($chk);
$idea_row = mysqli_fetch_assoc($chk_res);
mysqli_stmt_close($chk);

if (!$idea_row) {
    header("Location: manage_ideas.php?success=0&msg=" . urlencode("Idea not found or not completed."));
    exit();
}

$student_user_id = (int) $idea_row['user_id'];

// Set reward tag; award +100 points only the first time idea is marked as Best
$has_tag = mysqli_fetch_assoc(mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'reward_tag'"));
$already_tagged = false;
if ($has_tag) {
    $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT reward_tag FROM ideas WHERE id = $idea_id"));
    $already_tagged = !empty(trim($cur['reward_tag'] ?? ''));
    mysqli_query($conn, "UPDATE ideas SET reward_tag = 'Best Idea of Month' WHERE id = $idea_id");
}
if ($student_user_id && !$already_tagged) {
    mysqli_query($conn, "UPDATE users SET points = COALESCE(points, 0) + 100 WHERE id = $student_user_id");
}

header("Location: manage_ideas.php?success=1&msg=" . urlencode("Marked as Best Idea of Month. Student awarded 100 points."));
exit();
