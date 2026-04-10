<?php
require_once 'config/session.php';
start_role_session('faculty');
require_once 'config/db.php';

// Strict Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: login.php');
    exit;
}

$faculty_id = (int) $_SESSION['user_id'];
$faculty_name = $_SESSION['user_name'];

// Fetch assigned ideas — basic overview only
$sql = "SELECT
            i.id,
            COALESCE(p.title, i.title) AS problem_title,
            u.name                     AS student_name,
            i.status,
            i.progress_percentage
        FROM ideas i
        LEFT JOIN users u    ON u.id = i.user_id
        LEFT JOIN problems p ON p.id = i.problem_id
        WHERE i.assigned_faculty_id = ?
        ORDER BY i.updated_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $faculty_id);
mysqli_stmt_execute($stmt);
$ideas_result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Tasks - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .progress-bar-wrap {
            background: #E0E0E0;
            border-radius: 10px;
            overflow: hidden;
            height: 22px;
            min-width: 100px;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
            text-align: center;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 22px;
            transition: width 0.4s ease;
        }
        .progress-0   { width: 0%;   background: #BDBDBD; }
        .progress-33  { width: 33%;  background: #FF9800; }
        .progress-66  { width: 66%;  background: #2196F3; }
        .progress-100 { width: 100%; background: #4CAF50; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <a href="faculty_dashboard.php" class="nav-item">Dashboard</a>
                <a href="staff_review.php" class="nav-item">Review System</a>
                <a href="staff_assigned_tasks.php" class="nav-item active">Assigned Tasks</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php?role=faculty" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Assigned Tasks</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($faculty_name); ?></span>
                        <span class="user-role">Faculty Member</span>
                    </div>
                </div>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Task Overview</h2>
                </div>

                <?php if (!$ideas_result || mysqli_num_rows($ideas_result) === 0): ?>
                    <p class="empty-state">No tasks assigned to you yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Problem Title</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Progress %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($ideas_result)):
                                    $pct = (int)($row['progress_percentage'] ?? 0);
                                    if ($pct >= 100)     $pct_class = 'progress-100';
                                    elseif ($pct >= 66)  $pct_class = 'progress-66';
                                    elseif ($pct >= 33)  $pct_class = 'progress-33';
                                    else                 $pct_class = 'progress-0';
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['problem_title'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name'] ?? 'Unknown'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress-bar-wrap">
                                                <div class="progress-bar-fill <?php echo $pct_class; ?>">
                                                    <?php echo $pct; ?>%
                                                </div>
                                            </div>
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
