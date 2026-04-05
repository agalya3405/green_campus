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

function fd_review_done($s) {
    $s = trim((string) ($s ?? ''));
    return $s === 'Done' || $s === 'Completed';
}

// Fetch ideas assigned to this faculty member
$sql = "SELECT i.id,
               i.status,
               i.description,
               i.progress_percentage,
               i.review1_status,
               i.review2_status,
               i.review3_status,
               i.admin_remarks,
               i.faculty_remarks,
               COALESCE(p.title, i.title) AS problem_title,
               u.name AS student_name
        FROM ideas i
        LEFT JOIN problems p ON i.problem_id = p.id
        LEFT JOIN users u ON i.user_id = u.id
        WHERE i.assigned_faculty_id = ?
        ORDER BY i.updated_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $faculty_id);
mysqli_stmt_execute($stmt);
$ideas_result = mysqli_stmt_get_result($stmt);

$success = $_GET['success'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .progress-bar-wrap {
            background: #E0E0E0; border-radius: 10px; overflow: hidden;
            height: 22px; min-width: 100px;
        }
        .progress-bar-fill {
            height: 100%; border-radius: 10px; text-align: center;
            color: #fff; font-size: 0.75rem; font-weight: 700;
            line-height: 22px; transition: width 0.4s ease;
        }
        .progress-0   { width: 0%;   background: #BDBDBD; }
        .progress-33  { width: 33%;  background: #FF9800; }
        .progress-66  { width: 66%;  background: #2196F3; }
        .progress-100 { width: 100%; background: #4CAF50; }
        .btn-info {
            background: #1976D2; color: #fff; padding: 0.35rem 0.7rem;
            border-radius: 6px; font-size: 0.82rem; font-weight: 600;
            text-decoration: none; display: inline-block; transition: background 0.2s;
        }
        .btn-info:hover { background: #1565C0; }
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
                <a href="faculty_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="staff_review.php" class="nav-item">Review System</a>
                <a href="staff_assigned_tasks.php" class="nav-item">Assigned Tasks</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php?role=faculty" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Faculty Dashboard</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($faculty_name); ?></span>
                        <span class="user-role">Faculty Member</span>
                    </div>
                </div>
            </header>

            <?php if ($success === '1' && $msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Assigned Solutions</h2>
                </div>

                <?php if (!$ideas_result || mysqli_num_rows($ideas_result) === 0): ?>
                    <p class="empty-state">No ideas assigned to you yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Problem Title</th>
                                    <th>Student Name</th>
                                    <th>Solution</th>
                                    <th>Admin Remarks</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>View Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($ideas_result)):
                                    $r1 = fd_review_done($row['review1_status'] ?? '') ? 'Done' : 'Pending';
                                    $r2 = fd_review_done($row['review2_status'] ?? '') ? 'Done' : 'Pending';
                                    $r3 = fd_review_done($row['review3_status'] ?? '') ? 'Done' : 'Pending';
                                    $pct = 0;
                                    if ($r1 === 'Done') $pct += 33;
                                    if ($r2 === 'Done') $pct += 33;
                                    if ($r3 === 'Done') $pct += 34;
                                    if ($pct >= 100)     $pct_class = 'progress-100';
                                    elseif ($pct >= 66)  $pct_class = 'progress-66';
                                    elseif ($pct >= 33)  $pct_class = 'progress-33';
                                    else                 $pct_class = 'progress-0';
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['problem_title'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name'] ?? 'Unknown'); ?></td>
                                        <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                            title="<?php echo htmlspecialchars($row['description']); ?>">
                                            <?php echo htmlspecialchars($row['description']); ?>
                                        </td>
                                        <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <?php echo htmlspecialchars($row['admin_remarks'] ?? '—'); ?>
                                        </td>
                                        <td>
                                            <div class="progress-bar-wrap">
                                                <div class="progress-bar-fill <?php echo $pct_class; ?>">
                                                    <?php echo $pct; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="staff_review.php?idea_id=<?php echo (int)$row['id']; ?>" class="btn-info btn-sm">
                                                View Details
                                            </a>
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
