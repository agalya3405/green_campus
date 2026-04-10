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

// Fetch faculty stats
$query = "SELECT s.id, s.name, 
           COUNT(i.id) AS assigned_count, 
           SUM(CASE WHEN i.status='Completed' THEN 1 ELSE 0 END) AS completed_count 
    FROM users s 
    LEFT JOIN ideas i ON s.id = i.assigned_faculty_id 
    WHERE s.role = 'faculty' 
    GROUP BY s.id
    ORDER BY s.name";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-portal">
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
                <a href="manage_faculty.php" class="nav-item active">Manage Faculty</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="../leaderboard.php" class="nav-item">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php?role=admin" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Manage Faculty</h1>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Faculty Overview</h2>
                </div>
                
                <?php if (!$result || mysqli_num_rows($result) === 0): ?>
                    <p class="empty-state">No faculty members found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Faculty Name</th>
                                    <th>Total Assigned Ideas</th>
                                    <th>Completed Ideas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td>
                                            <span class="badge" style="background: #E3F2FD; color: #1565C0;">
                                                <?php echo (int) $row['assigned_count']; ?> Assigned
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: #E8F5E9; color: #2E7D32;">
                                                <?php echo (int) $row['completed_count']; ?> Completed
                                            </span>
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
