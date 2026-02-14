<?php
session_start();
require_once 'config/db.php';

// Strict Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}

$staff_id = (int) $_SESSION['user_id'];
$staff_name = $_SESSION['user_name'];

// Fetch ideas assigned to this staff member, including problem title and student name
$sql = "SELECT i.id,
               i.status,
               i.description,
               i.admin_remarks,
               i.staff_remarks,
               p.title AS problem_title,
               u.name AS student_name
        FROM ideas i
        LEFT JOIN problems p ON i.problem_id = p.id
        LEFT JOIN users u ON i.user_id = u.id
        WHERE i.assigned_staff_id = ?
        ORDER BY i.updated_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $staff_id);
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
    <title>Staff Dashboard - Campus Green Innovation Portal</title>
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
                <a href="staff_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="staff_dashboard.php" class="nav-item">My Assigned Tasks</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Staff Dashboard</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($staff_name); ?></span>
                        <span class="user-role">Staff Member</span>
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
                                    <th>Student Solution</th>
                                    <th>Admin Remarks</th>
                                    <th>Current Status</th>
                                    <th>Staff Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($ideas_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['problem_title'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name'] ?? 'Unknown'); ?></td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['description']); ?>">
                                            <?php echo htmlspecialchars($row['description']); ?>
                                        </td>
                                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($row['admin_remarks'] ?? '—'); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($row['staff_remarks'] ?? '—'); ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'Approved'): ?>
                                                <form method="POST" action="update_status.php?id=<?php echo (int) $row['id']; ?>" style="display: inline;">
                                                    <input type="hidden" name="status" value="In Progress">
                                                    <input type="hidden" name="staff_remarks" value="Work started">
                                                    <button type="submit" class="btn btn-sm btn-primary">Start Work</button>
                                                </form>
                                            <?php elseif ($row['status'] === 'In Progress'): ?>
                                                <a href="update_status.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-success">Mark Completed</a>
                                            <?php elseif ($row['status'] === 'Completed'): ?>
                                                <span class="badge badge-completed">Completed</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
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
