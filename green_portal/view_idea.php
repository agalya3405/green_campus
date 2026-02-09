<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . ($_SESSION['role'] === 'staff' ? 'staff_dashboard.php' : ($_SESSION['role'] === 'admin' ? 'admin/admin_dashboard.php' : 'dashboard.php')));
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        i.id,
        i.title,
        i.description,
        i.category,
        i.status,
        i.assigned_to,
        i.admin_remarks,
        i.staff_remarks,
        i.created_at,
        i.updated_at,
        i.user_id,
        i.assigned_staff_id
     FROM ideas i
     WHERE i.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$idea = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$idea) {
    header('Location: ' . ($role === 'staff' ? 'staff_dashboard.php' : ($role === 'admin' ? 'admin/admin_dashboard.php' : 'dashboard.php')));
    exit;
}

// Staff can only view ideas assigned to them
if ($role === 'staff' && (int) $idea['assigned_staff_id'] !== $user_id) {
    header('Location: staff_dashboard.php');
    exit;
}

$back_url = $role === 'staff' ? 'staff_dashboard.php' : ($role === 'admin' ? 'admin/admin_dashboard.php' : 'view_ideas.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Idea - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <?php if ($role === 'admin'): ?>
                <a href="admin/admin_dashboard.php">Admin Dashboard</a>
            <?php elseif ($role === 'staff'): ?>
                <a href="staff_dashboard.php" class="active">Staff Dashboard</a>
            <?php else: ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="view_ideas.php">View My Ideas</a>
            <?php endif; ?>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>Idea Details</h1>
        <p class="subtitle">View only. Staff cannot edit title or description.</p>

        <div class="card">
            <table class="data-table detail-table">
                <tr>
                    <th>Title</th>
                    <td><?php echo htmlspecialchars($idea['title']); ?></td>
                </tr>
                <tr>
                    <th>Category</th>
                    <td><?php echo htmlspecialchars($idea['category']); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $idea['status'])); ?>"><?php echo htmlspecialchars($idea['status']); ?></span></td>
                </tr>
                <tr>
                    <th>Assigned Staff</th>
                    <td><?php echo htmlspecialchars($idea['assigned_to'] ?? '—'); ?></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td><?php echo nl2br(htmlspecialchars($idea['description'])); ?></td>
                </tr>
                <?php if (!empty($idea['admin_remarks'])): ?>
                <tr>
                    <th>Admin Remarks</th>
                    <td><?php echo nl2br(htmlspecialchars($idea['admin_remarks'])); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($idea['staff_remarks'])): ?>
                <tr>
                    <th>Staff Remarks</th>
                    <td><?php echo nl2br(htmlspecialchars($idea['staff_remarks'])); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Submitted</th>
                    <td><?php echo date('M j, Y', strtotime($idea['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Last Updated</th>
                    <td>
                        <?php
                        $ts = $idea['updated_at'] ?? $idea['created_at'];
                        echo $ts ? date('M j, Y', strtotime($ts)) : '—';
                        ?>
                    </td>
                </tr>
            </table>
            <div class="form-actions" style="margin-top: 1rem;">
                <?php if ($role === 'staff' && (int) $idea['assigned_staff_id'] === $user_id): ?>
                    <a href="update_status.php?id=<?php echo (int) $id; ?>" class="btn btn-primary">Update Status</a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Campus Green Innovation Portal</p>
    </footer>
    <script src="assets/js/script.js"></script>
</body>
</html>
