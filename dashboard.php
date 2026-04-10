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

$user_id = (int) $_SESSION['user_id'];

// Build SELECT list so it works even if admin_remarks/updated_at columns don't exist yet
$cols = [];
$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $cols[] = $row['Field'];
    }
}
$sel = "i.id, i.title, i.description, i.category, i.status, i.assigned_to, i.created_at, s.name AS assigned_staff_name";
if (in_array('staff_remarks', $cols)) $sel .= ", i.staff_remarks";
if (in_array('admin_remarks', $cols)) $sel .= ", i.admin_remarks";
if (in_array('updated_at', $cols)) $sel .= ", i.updated_at";

$stmt = mysqli_prepare(
    $conn,
    "SELECT $sel FROM ideas i LEFT JOIN users s ON i.assigned_staff_id = s.id WHERE i.user_id = ? ORDER BY i.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$ideas_result = mysqli_stmt_get_result($stmt);

$success = $_GET['success'] ?? '';
$msg = $_GET['msg'] ?? '';

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
                        
                        $staff_name = $row['assigned_staff_name'] ?? $row['assigned_to'];
                        $staff_display = $staff_name ? '<span class="staff-badge">' . htmlspecialchars($staff_name) . '</span>' : '—';
                        echo '<td>' . $staff_display . '</td>';
                        
                        $badge_class = 'badge-' . strtolower(str_replace(' ', '', $row['status']));
                        echo '<td><span class="badge ' . $badge_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                        
                        $remarks = '';
                        if ($row['status'] === 'Rejected' && !empty($row['admin_remarks'])) {
                            $remarks = '<span style="color: var(--status-rejected-text);">' . htmlspecialchars($row['admin_remarks']) . '</span>';
                        } elseif (!empty($row['staff_remarks'])) {
                            $remarks = htmlspecialchars($row['staff_remarks']);
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
        </main>
    </div>
</body>
</html>
