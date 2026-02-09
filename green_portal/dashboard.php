<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header('Location: admin/admin_dashboard.php');
    exit;
}
if ($_SESSION['role'] === 'staff') {
    header('Location: staff_dashboard.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, title, description, category, status, assigned_to, admin_remarks, staff_remarks, created_at, updated_at 
     FROM ideas 
     WHERE user_id = ? 
     ORDER BY created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
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
    <title>Dashboard - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="submit_idea.php">Submit Idea</a>
            <a href="view_ideas.php">View My Ideas</a>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <?php if ($success === '1' && $msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <p class="subtitle">Submit and track your eco-friendly ideas.</p>

        <div class="action-buttons">
            <a href="submit_idea.php" class="btn btn-primary">Submit Idea</a>
            <a href="view_ideas.php" class="btn btn-secondary">View My Ideas</a>
        </div>

        <section class="card">
            <h2>My Submitted Ideas</h2>
            <?php if (mysqli_num_rows($ideas_result) === 0): ?>
                <p class="empty-state">You haven't submitted any ideas yet. <a href="submit_idea.php">Submit your first idea</a>.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Assigned Staff</th>
                                <th>Admin Remarks</th>
                                <th>Staff Remarks</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($ideas_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['assigned_to'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($row['admin_remarks'] ?? '', 0, 40)) . ((isset($row['admin_remarks']) && mb_strlen($row['admin_remarks']) > 40) ? '…' : ''); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($row['staff_remarks'] ?? '', 0, 40)) . ((isset($row['staff_remarks']) && mb_strlen($row['staff_remarks']) > 40) ? '…' : ''); ?></td>
                                    <td>
                                        <?php
                                        $ts = $row['updated_at'] ?? $row['created_at'];
                                        echo $ts ? date('M j, Y', strtotime($ts)) : '—';
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Campus Green Innovation Portal</p>
    </footer>
    <script src="assets/js/script.js"></script>
</body>
</html>
