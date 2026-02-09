<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}

$staff_id = (int) $_SESSION['user_id'];

// Check if Staff module columns exist (run staff_module_migration.sql if not)
$has_staff_columns = false;
$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'assigned_staff_id'");
if ($r && mysqli_num_rows($r) > 0) {
    $r2 = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'staff_remarks'");
    $has_staff_columns = ($r2 && mysqli_num_rows($r2) > 0);
}
$ideas_result = false;
if ($has_staff_columns) {
    $stmt = mysqli_prepare($conn, "SELECT i.id, i.title, i.description, i.category, i.status, i.staff_remarks, i.created_at FROM ideas i WHERE i.assigned_staff_id = ? ORDER BY i.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $staff_id);
    mysqli_stmt_execute($stmt);
    $ideas_result = mysqli_stmt_get_result($stmt);
}

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
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <a href="staff_dashboard.php" class="active">Staff Dashboard</a>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (Staff)</span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <?php if ($success === '1' && $msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <h1>Staff Dashboard</h1>
        <p class="subtitle">Ideas assigned to you. Update status and add remarks.</p>

        <?php if (!$has_staff_columns): ?>
            <div class="alert alert-error">
                <strong>Database update required.</strong> Run the Staff module migration in phpMyAdmin so this page works:<br>
                1. Open <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a> → SQL tab.<br>
                2. Copy the contents of <code>green_portal/database/staff_module_migration.sql</code> and paste there, then click Go.
            </div>
        <?php endif; ?>

        <section class="card">
            <h2>Assigned Ideas</h2>
            <?php if (!$has_staff_columns): ?>
                <p class="empty-state">Add the database columns (see message above) to see assigned ideas here.</p>
            <?php elseif (!$ideas_result || mysqli_num_rows($ideas_result) === 0): ?>
                <p class="empty-state">No ideas assigned to you yet. Admin will assign ideas from the Admin Dashboard.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Idea Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>View Details</th>
                                <th>Update Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($ideas_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td><a href="view_idea.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-outline">View Details</a></td>
                                    <td><a href="update_status.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-primary">Update Status</a></td>
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
