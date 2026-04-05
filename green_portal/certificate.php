<?php
require_once 'config/session.php';
$requested_role = resolve_role_from_request(['admin', 'faculty', 'student'], 'guest');
if ($requested_role === 'guest') {
    start_guest_session();
} else {
    start_role_session($requested_role);
}
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Fetch idea with student name (user_id = submitter). Certificate only for completed ideas.
$stmt = mysqli_prepare($conn, "SELECT i.id, i.title, i.updated_at, i.status, i.user_id FROM ideas i WHERE i.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$idea = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$idea || $idea['status'] !== 'Completed') {
    header('Location: dashboard.php?msg=' . urlencode('Certificate available only for implemented ideas.'));
    exit;
}
// Students may only view certificate for their own idea
if (isset($_SESSION['role']) && $_SESSION['role'] === 'student' && isset($_SESSION['user_id']) && (int)$idea['user_id'] !== (int)$_SESSION['user_id']) {
    header('Location: dashboard.php?msg=' . urlencode('Access denied.'));
    exit;
}

$user_id = (int) $idea['user_id'];
$ustmt = mysqli_prepare($conn, "SELECT name FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($ustmt, "i", $user_id);
mysqli_stmt_execute($ustmt);
$ures = mysqli_stmt_get_result($ustmt);
$student_name = 'Student';
if ($u = mysqli_fetch_assoc($ures)) {
    $student_name = $u['name'];
}
mysqli_stmt_close($ustmt);

$impl_date = !empty($idea['updated_at']) ? date('F j, Y', strtotime($idea['updated_at'])) : date('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - Green Campus</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Georgia, serif; margin: 0; padding: 2rem; background: #f5f5f5; }
        .certificate { max-width: 700px; margin: 0 auto; background: #fff; border: 3px solid #2E7D32; padding: 3rem; text-align: center; }
        .certificate h1 { color: #2E7D32; font-size: 1.8rem; margin-bottom: 2rem; border-bottom: 2px solid #2E7D32; padding-bottom: 0.5rem; }
        .certificate .name { font-size: 1.6rem; font-weight: bold; margin: 1.5rem 0; color: #1B5E20; }
        .certificate .title { font-size: 1.2rem; margin: 1rem 0; }
        .certificate .date { margin-top: 2rem; font-style: italic; color: #555; }
        .certificate .message { font-size: 1.3rem; margin: 2rem 0; color: #2E7D32; font-weight: bold; }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <p class="no-print" style="text-align: center;">
        <a href="dashboard.php" style="color: #2E7D32;">&larr; Back to Dashboard</a> &nbsp;|&nbsp;
        <button onclick="window.print();" style="background: #2E7D32; color: white; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px;">Print / Save as PDF</button>
    </p>
    <div class="certificate">
        <h1>Campus Green Innovation Portal</h1>
        <p class="message">Certified Green Campus Contributor</p>
        <p class="name"><?php echo htmlspecialchars($student_name); ?></p>
        <p class="title">Idea: <?php echo htmlspecialchars($idea['title']); ?></p>
        <p class="date">Implemented on <?php echo htmlspecialchars($impl_date); ?></p>
    </div>
</body>
</html>
