<?php
require_once 'config/session.php';
start_role_session('student');
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header('Location: admin/admin_dashboard.php');
    exit;
}
if ($_SESSION['role'] === 'faculty') {
    header('Location: faculty_dashboard.php');
    exit;
}

// Handle Progress Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $idea_id = (int)$_POST['idea_id'];
    $new_progress = (int)$_POST['progress_percentage'];
    
    // Fetch current progress
    $check_stmt = mysqli_prepare($conn, "SELECT progress_percentage FROM ideas WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $idea_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_res = mysqli_stmt_get_result($check_stmt);
    $current_idea = mysqli_fetch_assoc($check_res);
    
    if ($current_idea) {
        if ($new_progress < 0 || $new_progress > 100) {
            $error = "Progress must be between 0 and 100.";
        } elseif ($new_progress < $current_idea['progress_percentage']) {
            $error = "Progress percentage cannot be reduced.";
        } else {
            $upd_stmt = mysqli_prepare($conn, "UPDATE ideas SET progress_percentage = ? WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($upd_stmt, "iii", $new_progress, $idea_id, $user_id);
            if (mysqli_stmt_execute($upd_stmt)) {
                header("Location: dashboard.php?success=1&msg=Progress updated successfully");
                exit;
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

$user_id = (int) $_SESSION['user_id'];

// Build SELECT list so it works even if admin_remarks/updated_at columns don't exist yet
$cols = [];
$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $cols[] = $row['Field'];
    }
}
$sel = "i.id, i.title, i.description, i.category, i.status, i.assigned_to, i.created_at, s.name AS assigned_faculty_name";
if (in_array('faculty_remarks', $cols)) $sel .= ", i.faculty_remarks";
if (in_array('admin_remarks', $cols)) $sel .= ", i.admin_remarks";
if (in_array('updated_at', $cols)) $sel .= ", i.updated_at";

$stmt = mysqli_prepare(
    $conn,
    "SELECT $sel FROM ideas i LEFT JOIN users s ON i.assigned_faculty_id = s.id WHERE i.user_id = ? ORDER BY i.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$ideas_result = mysqli_stmt_get_result($stmt);

$success = $_GET['success'] ?? '';
$msg = $_GET['msg'] ?? '';
$error = $error ?? '';

// Group ideas
$ideas_by_status = [
    'Pending' => [],
    'Approved' => [],
    'Rejected' => [],
    'Completed' => [],
    'In Progress' => []
];

while ($row = mysqli_fetch_assoc($ideas_result)) {
    $status = $row['status'];
    if (isset($ideas_by_status[$status])) {
        $ideas_by_status[$status][] = $row;
    } else {
        $ideas_by_status['Pending'][] = $row;
    }
}

// Points & rank (if users.points exists)
$user_points = 0;
$user_rank = 0;
$ucols = [];
$ur = mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($ur) {
    while ($urow = mysqli_fetch_assoc($ur)) {
        $ucols[] = $urow['Field'];
    }
}
if (in_array('points', $ucols)) {
    $pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(points, 0) AS points FROM users WHERE id = $user_id"));
    if ($pr) {
        $user_points = (int) $pr['points'];
    }
    $rank_res = mysqli_query($conn, "SELECT COUNT(*) + 1 AS r FROM users WHERE role = 'student' AND COALESCE(points, 0) > $user_points");
    if ($rank_res && $rr = mysqli_fetch_row($rank_res)) {
        $user_rank = (int) $rr[0];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-icon">🌿</span>
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">Dashboard</a>
                <a href="student_problems.php" class="nav-item">View Problems</a>
                <a href="submit_idea.php" class="nav-item">Submit Solution</a>
                <a href="view_ideas.php" class="nav-item">My Solutions</a>
                <a href="student_reviews.php" class="nav-item">Reviews</a>
                <a href="leaderboard.php?role=student" class="nav-item">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php?role=student" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Student Dashboard</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <span class="user-role">Student</span>
                    </div>
                </div>
            </header>

            <?php if ($success === '1' && $msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div class="card" style="text-align: center; padding: 1.25rem;">
                    <h3 style="font-size: 0.9rem; color: #666; margin-bottom: 0.25rem;">My Points</h3>
                    <p style="font-size: 2rem; font-weight: 700; color: var(--primary-color); margin: 0;"><?php echo $user_points; ?></p>
                </div>
                <div class="card" style="text-align: center; padding: 1.25rem;">
                    <h3 style="font-size: 0.9rem; color: #666; margin-bottom: 0.25rem;">My Rank</h3>
                    <p style="font-size: 2rem; font-weight: 700; color: var(--primary-color); margin: 0;">#<?php echo $user_rank ?: '—'; ?></p>
                </div>
                <div class="card" style="display: flex; align-items: center; justify-content: center; padding: 1.25rem;">
                    <a href="leaderboard.php?role=student" class="btn btn-primary">View Leaderboard</a>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <div class="card" style="background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%); color: white;">
                    <h2 style="color: white; margin-bottom: 0.5rem;">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                    <p style="opacity: 0.9; margin-bottom: 1.5rem;">Ready to make our campus greener? Submit a new eco-friendly idea today.</p>
                    <a href="submit_idea.php" class="btn btn-secondary" style="background: white; color: #2E7D32; border: none;">Submit New Idea</a>
                </div>
            </div>

            <?php
            function render_section($title, $ideas) {
                $is_completed = ($title === 'Completed');
                echo '<div class="card">';
                echo '<div class="card-header"><h3 class="card-title">' . htmlspecialchars($title) . ' Ideas</h3></div>';
                
                if (empty($ideas)) {
                    echo '<p class="empty-state">No ' . htmlspecialchars(strtolower($title)) . ' ideas found.</p>';
                } else {
                    echo '<div class="table-responsive"><table class="table"><thead><tr>';
                    echo '<th>Idea Title</th><th>Assigned To</th><th>Status</th><th>Remarks</th>';
                    if ($is_completed) {
                        echo '<th>Certificate</th>';
                    }
                    echo '</tr></thead><tbody>';
                    
                    foreach ($ideas as $row) {
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($row['title']) . '</strong></td>';
                        
                        $faculty_name = $row['assigned_faculty_name'] ?? $row['assigned_to'];
                        $faculty_display = $faculty_name ? '<span class="faculty-badge">' . htmlspecialchars($faculty_name) . '</span>' : '—';
                        echo '<td>' . $faculty_display . '</td>';
                        
                        $badge_class = 'badge-' . strtolower(str_replace(' ', '', $row['status']));
                        echo '<td><span class="badge ' . $badge_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                        
                        $remarks = '';
                        if ($row['status'] === 'Rejected' && !empty($row['admin_remarks'])) {
                            $remarks = '<span style="color: var(--status-rejected-text);">' . htmlspecialchars($row['admin_remarks']) . '</span>';
                        } elseif (!empty($row['faculty_remarks'])) {
                            $remarks = htmlspecialchars($row['faculty_remarks']);
                        } else {
                            $remarks = '<span style="color: var(--text-muted);">—</span>';
                        }
                        echo '<td>' . $remarks . '</td>';
                        if ($is_completed) {
                            $idea_id = (int)($row['id'] ?? 0);
                            echo '<td><a href="certificate.php?id=' . $idea_id . '&role=student" class="btn btn-sm btn-secondary" target="_blank">Download Certificate</a></td>';
                        }
                        echo '</tr>';
                    }
                    echo '</tbody></table></div>';
                }
                echo '</div>';
            }

            render_section('Pending', $ideas_by_status['Pending']);
            render_section('Approved', $ideas_by_status['Approved']);
            render_section('In Progress', $ideas_by_status['In Progress']);
            render_section('Rejected', $ideas_by_status['Rejected']);
            render_section('Completed', $ideas_by_status['Completed']);
            ?>

            <!-- Update Progress Section -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3 class="card-title">Update Project Progress</h3>
                </div>
                <?php
                // Re-fetch ideas to get current progress for the form
                mysqli_stmt_execute($stmt);
                $ideas_for_form = mysqli_stmt_get_result($stmt);
                $has_in_progress = false;
                ?>
                <div class="card-body" style="padding: 1.5rem;">
                    <form method="POST" action="dashboard.php" class="progress-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
                            <div class="form-group" style="margin: 0;">
                                <label for="idea_id">Select Idea</label>
                                <select name="idea_id" id="idea_id" class="form-control" required>
                                    <option value="">-- Choose Idea --</option>
                                    <?php while ($idea = mysqli_fetch_assoc($ideas_for_form)): ?>
                                        <?php if ($idea['status'] === 'In Progress' || $idea['status'] === 'Approved'): ?>
                                            <?php 
                                            $has_in_progress = true; 
                                            // Need to fetch current progress_percentage for display
                                            $pid = (int)$idea['id'];
                                            $p_res = mysqli_query($conn, "SELECT progress_percentage FROM ideas WHERE id = $pid");
                                            $p_row = mysqli_fetch_assoc($p_res);
                                            $curr_p = $p_row['progress_percentage'] ?? 0;
                                            ?>
                                            <option value="<?php echo $idea['id']; ?>"><?php echo htmlspecialchars($idea['title']); ?> (Current: <?php echo $curr_p; ?>%)</option>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="progress_percentage">New Progress %</label>
                                <input type="number" name="progress_percentage" id="progress_percentage" class="form-control" min="0" max="100" required>
                            </div>
                            <button type="submit" name="update_progress" class="btn btn-primary">Update</button>
                        </div>
                        <?php if (!$has_in_progress): ?>
                            <p class="text-muted" style="margin-top: 1rem;">No active ideas to update progress for.</p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
