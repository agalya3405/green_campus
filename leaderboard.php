<?php
require_once 'config/session.php';
$requested_role = resolve_role_from_request(['admin', 'faculty', 'student'], 'guest');
if ($requested_role === 'guest') {
    start_guest_session();
} else {
    start_role_session($requested_role);
}
require_once 'config/db.php';

// Optional: allow public or require login. Spec says "Show top students" - allow access for all roles and guests for gamification.
$logged_in = isset($_SESSION['user_id']);
$user_name = $logged_in ? $_SESSION['user_name'] : '';
$role = $_SESSION['role'] ?? '';

// Leaderboard: students by points. ideas.user_id = submitter (project uses user_id not student_id)
$cols = [];
$r = mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $cols[] = $row['Field'];
    }
}
$has_points = in_array('points', $cols);
$points_sel = $has_points ? 'COALESCE(u.points, 0) AS points' : '0 AS points';
$order_by = $has_points ? 'points DESC' : 'submitted DESC';

$query = "SELECT u.id, u.name, $points_sel,
          COUNT(i.id) AS submitted,
          SUM(CASE WHEN i.status = 'Completed' THEN 1 ELSE 0 END) AS implemented
          FROM users u
          LEFT JOIN ideas i ON u.id = i.user_id
          WHERE u.role = 'student'
          GROUP BY u.id, u.name" . ($has_points ? ", u.points" : "") . "
          ORDER BY $order_by, u.name ASC";
$result = mysqli_query($conn, $query);
if (!$result) die("SQL Error: " . mysqli_error($conn));

$rows = [];
$rank = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $rank++;
    $row['rank'] = $rank;
    $row['points'] = isset($row['points']) ? (int) $row['points'] : 0;
    $row['submitted'] = (int) $row['submitted'];
    $row['implemented'] = (int) $row['implemented'];
    $rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Campus Green Innovation Portal</title>
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
                <a href="leaderboard.php" class="nav-item active">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <?php if ($logged_in): ?>
                    <a href="logout.php?role=<?php echo urlencode($role); ?>" class="nav-item" style="color: #D32F2F;">Logout</a>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Student Leaderboard</h1>
                <?php if ($logged_in): ?>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
                </div>
                <?php endif; ?>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Top Contributors by Points</h2>
                </div>
                <?php if (empty($rows)): ?>
                    <p class="empty-state">No students yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student Name</th>
                                    <th>Points</th>
                                    <th>Ideas Submitted</th>
                                    <th>Ideas Implemented</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><strong><?php echo (int) $row['rank']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo (int) $row['points']; ?></td>
                                        <td><?php echo (int) $row['submitted']; ?></td>
                                        <td><?php echo (int) $row['implemented']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
