<?php
require_once 'config/session.php';
start_role_session('student');
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Build SELECT so it works even if admin_remarks/updated_at are missing
$cols = [];
$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $cols[] = $row['Field'];
    }
}
$sel = "i.id, i.title, i.description, i.category, i.status, i.assigned_to, i.created_at, s.name AS assigned_staff_name, p.title AS problem_title";
if (in_array('staff_remarks', $cols)) $sel .= ", i.staff_remarks";
if (in_array('admin_remarks', $cols)) $sel .= ", i.admin_remarks";
if (in_array('updated_at', $cols)) $sel .= ", i.updated_at";

$stmt = mysqli_prepare(
    $conn,
    "SELECT $sel
     FROM ideas i
     LEFT JOIN users s ON i.assigned_staff_id = s.id
     LEFT JOIN problems p ON i.problem_id = p.id
     WHERE i.user_id = ?
     ORDER BY i.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$ideas_result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ideas - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="submit_idea.php" class="nav-item">Submit Idea</a>
                <a href="view_ideas.php" class="nav-item active">My Ideas</a>
                <a href="leaderboard.php?role=student" class="nav-item">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php?role=student" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">My Solutions</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role">Student</span>
                    </div>
                </div>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Submitted Solutions</h2>
                </div>

                <?php if (mysqli_num_rows($ideas_result) === 0): ?>
                    <p class="empty-state">You haven't submitted any solutions yet. <a href="student_problems.php" style="color: var(--primary-color);">View problems and submit your first solution</a>.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Problem</th>
                                    <th>My Solution (summary)</th>
                                    <th>Status</th>
                                    <th>Assigned Faculty</th>
                                    <th>Remarks</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($ideas_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['problem_title'] ?? $row['title']); ?></td>
                                        <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['description']); ?>">
                                            <?php echo htmlspecialchars($row['description']); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $staff_name = $row['assigned_staff_name'] ?? $row['assigned_to'];
                                            echo $staff_name ? htmlspecialchars($staff_name) : '—'; 
                                            ?>
                                        </td>
                                        <td style="max-width: 250px;">
                                            <?php 
                                            if ($row['status'] === 'Rejected') {
                                                echo '<span style="color: var(--status-rejected-text); font-size: 0.9em;">' . htmlspecialchars($row['admin_remarks'] ?? '') . '</span>';
                                            } elseif (!empty($row['staff_remarks'])) {
                                                echo '<span style="font-size: 0.9em;">' . htmlspecialchars($row['staff_remarks']) . '</span>';
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $ts = $row['updated_at'] ?? $row['created_at'];
                                            echo $ts ? date('M d, Y', strtotime($ts)) : '—';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="view_idea.php?id=<?php echo (int) $row['id']; ?>&role=student" class="btn btn-sm btn-secondary">Details</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
