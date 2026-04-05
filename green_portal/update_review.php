<?php
require_once 'config/session.php';
start_role_session('faculty');
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: login.php');
    exit;
}

$faculty_id = (int) $_SESSION['user_id'];
$idea_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function ur_review_done($s) {
    $s = trim((string) ($s ?? ''));
    return $s === 'Done' || $s === 'Completed';
}
$type = $_GET['type'] ?? '';

if ($idea_id <= 0 || !in_array($type, ['1', '2', 'final'])) {
    header('Location: faculty_dashboard.php');
    exit;
}

// Fetch idea details for validation
$stmt = mysqli_prepare($conn, "SELECT id, title, progress_percentage, review1_status, review2_status, final_review_status, assigned_faculty_id FROM ideas WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $idea_id);
mysqli_stmt_execute($stmt);
$idea = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$idea || (int)$idea['assigned_faculty_id'] !== $faculty_id) {
    header('Location: faculty_dashboard.php?msg=Unauthorized');
    exit;
}

$error = '';
$success = '';

// Validation Logic
$p = (int)$idea['progress_percentage'];
if ($type === '1') {
    if ($p < 30) $error = "Review 1 requires at least 30% progress.";
    if (ur_review_done($idea['review1_status'] ?? '')) $error = "Review 1 is already completed.";
} elseif ($type === '2') {
    if ($p < 60) $error = "Review 2 requires at least 60% progress.";
    if (!ur_review_done($idea['review1_status'] ?? '')) $error = "Review 1 must be completed first.";
    if (ur_review_done($idea['review2_status'] ?? '')) $error = "Review 2 is already completed.";
} elseif ($type === 'final') {
    if ($p < 100) $error = "Final Review requires 100% progress.";
    if (!ur_review_done($idea['review2_status'] ?? '')) $error = "Review 2 must be completed first.";
    if (ur_review_done($idea['final_review_status'] ?? '')) $error = "Final Review is already completed.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $remarks = trim($_POST['remarks'] ?? '');
    
    $status_col = "review{$type}_status";
    $remarks_col = "review{$type}_remarks";
    if ($type === 'final') {
        $status_col = "final_review_status";
        $remarks_col = "final_review_remarks";
    }
    
    $upd_sql = "UPDATE ideas SET $status_col = 'Completed', $remarks_col = ? WHERE id = ?";
    $upd_stmt = mysqli_prepare($conn, $upd_sql);
    mysqli_stmt_bind_param($upd_stmt, "si", $remarks, $idea_id);
    
    if (mysqli_stmt_execute($upd_stmt)) {
        header("Location: faculty_dashboard.php?success=1&msg=Review " . ($type === 'final' ? 'Final' : $type) . " completed successfully.");
        exit;
    } else {
        $error = "Database error: " . mysqli_error($conn);
    }
}

$type_label = ($type === 'final') ? 'Final Review' : "Review $type";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Review - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header"><span class="brand-name">Green Campus</span></div>
            <nav class="sidebar-nav">
                <a href="faculty_dashboard.php" class="nav-item">Dashboard</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Submit <?php echo $type_label; ?></h1>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <div class="card"><a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a></div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><?php echo htmlspecialchars($idea['title']); ?></h3></div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <p><strong>Current Progress:</strong> <?php echo $p; ?>%</p>
                        <form method="POST">
                            <div class="form-group">
                                <label for="remarks">Review Remarks (Optional)</label>
                                <textarea name="remarks" id="remarks" class="form-control" rows="5" placeholder="Enter feedback for the student..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Complete Review</button>
                                <a href="faculty_dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
