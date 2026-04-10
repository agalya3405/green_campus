<?php
require_once '../config/session.php';
start_role_session('admin');
require_once '../config/db.php';

// Strict Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_name = $_SESSION['user_name'];

// Fetch Stats
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'in_progress' => 0,
    'completed' => 0
];

$res = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM ideas GROUP BY status");
while ($row = mysqli_fetch_assoc($res)) {
    $status = $row['status'];
    $count = (int)$row['count'];
    $stats['total'] += $count;
    
    if ($status === 'Pending') $stats['pending'] = $count;
    if ($status === 'Approved') $stats['approved'] = $count;
    if ($status === 'Rejected') $stats['rejected'] = $count;
    if ($status === 'In Progress') $stats['in_progress'] = $count;
    if ($status === 'Completed') $stats['completed'] = $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="manage_ideas.php" class="nav-item">Manage Ideas</a>
                <a href="approved_ideas.php" class="nav-item">Approved Ideas</a>
                <a href="manage_faculty.php" class="nav-item">Manage Faculty</a>
                <a href="reports.php" class="nav-item active">Reports</a>
                <a href="../leaderboard.php" class="nav-item">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php?role=admin" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">System Reports</h1>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Idea Statistics</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Submitted Ideas</td>
                                <td><strong><?php echo $stats['total']; ?></strong></td>
                            </tr>
                            <tr>
                                <td>Pending Approval</td>
                                <td><span class="badge badge-pending"><?php echo $stats['pending']; ?></span></td>
                            </tr>
                            <tr>
                                <td>Approved</td>
                                <td><span class="badge badge-approved"><?php echo $stats['approved']; ?></span></td>
                            </tr>
                            <tr>
                                <td>Rejected</td>
                                <td><span class="badge badge-rejected"><?php echo $stats['rejected']; ?></span></td>
                            </tr>
                            <tr>
                                <td>Work In Progress</td>
                                <td><span class="badge" style="background:#E3F2FD; color:#1976D2;"><?php echo $stats['in_progress']; ?></span></td>
                            </tr>
                             <tr>
                                <td>Completed</td>
                                <td><span class="badge" style="background:#E8F5E9; color:#2E7D32;"><?php echo $stats['completed']; ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
