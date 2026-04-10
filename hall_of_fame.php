<?php
require_once 'config/session.php';
$requested_role = resolve_role_from_request(['admin', 'faculty', 'student'], 'guest');
if ($requested_role === 'guest') {
    start_guest_session();
} else {
    start_role_session($requested_role);
}
require_once 'config/db.php';

$logged_in = isset($_SESSION['user_id']);
$user_name = $logged_in ? $_SESSION['user_name'] : '';
$role = $_SESSION['role'] ?? '';

// Implemented ideas with student name and faculty remarks (ideas table has title, not idea_title; student = users via user_id)
$query = "SELECT i.id, i.title, i.description, i.updated_at,
          u.name AS student_name,
          i.staff_remarks
          FROM ideas i
          LEFT JOIN users u ON i.user_id = u.id
          WHERE i.status = 'Completed'
          ORDER BY i.updated_at DESC";
$result = mysqli_query($conn, $query);
if (!$result) die("SQL Error: " . mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall of Fame - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <?php if ($logged_in): ?>
                    <?php if ($role === 'admin'): ?>
                        <a href="admin/admin_dashboard.php" class="nav-item">Dashboard</a>
                        <a href="admin/manage_ideas.php" class="nav-item">Manage Ideas</a>
                    <?php elseif ($role === 'faculty'): ?>
                        <a href="faculty_dashboard.php" class="nav-item">Dashboard</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="nav-item">Dashboard</a>
                        <a href="submit_idea.php" class="nav-item">Submit Idea</a>
                        <a href="view_ideas.php" class="nav-item">My Ideas</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="index.php" class="nav-item">Home</a>
                    <a href="login.php" class="nav-item">Login</a>
                <?php endif; ?>
                <a href="leaderboard.php" class="nav-item">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <?php if ($logged_in): ?>
                    <a href="logout.php?role=<?php echo urlencode($role); ?>" class="nav-item" style="color: #D32F2F;">Logout</a>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Hall of Fame</h1>
                <?php if ($logged_in): ?>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
                </div>
                <?php endif; ?>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Implemented Ideas</h2>
                </div>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <p class="empty-state">No implemented ideas yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Idea Title</th>
                                    <th>Description</th>
                                    <th>Student Name</th>
                                    <th>Faculty Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td style="max-width: 300px;"><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name'] ?? '-'); ?></td>
                                        <td style="max-width: 250px;"><?php echo htmlspecialchars($row['staff_remarks'] ?? '-'); ?></td>
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
