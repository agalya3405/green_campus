<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$allowed_roles = ['admin', 'staff'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: dashboard.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$staff_redirect = 'staff_dashboard.php';
$admin_redirect = 'admin/admin_dashboard.php';
if ($id <= 0) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? $admin_redirect : $staff_redirect));
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = mysqli_prepare($conn, "SELECT id, title, status, staff_remarks, assigned_staff_id FROM ideas WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$idea = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$idea) {
    header('Location: ' . ($role === 'admin' ? $admin_redirect : $staff_redirect));
    exit;
}

// Staff can only update ideas assigned to them
if ($role === 'staff' && (int) $idea['assigned_staff_id'] !== $user_id) {
    header('Location: staff_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $remarks = trim($_POST['staff_remarks'] ?? '');

    if ($role === 'staff') {
        $allowed = ['In Progress', 'Completed'];
        if (!in_array($status, $allowed)) {
            $error = 'Invalid status. Staff can only set In Progress or Completed.';
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE ideas SET status = ?, staff_remarks = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $status, $remarks, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: staff_dashboard.php?success=1&msg=' . urlencode('Status and remarks updated successfully.'));
                exit;
            }
            $error = 'Failed to update. Please try again.';
            if (isset($stmt)) mysqli_stmt_close($stmt);
        }
    } else {
        $allowed = ['Pending', 'Approved', 'In Progress', 'Completed'];
        if (!in_array($status, $allowed)) {
            $error = 'Invalid status.';
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE ideas SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: admin/admin_dashboard.php?success=1&msg=' . urlencode('Status updated successfully.'));
                exit;
            }
            $error = 'Failed to update status.';
            if (isset($stmt)) mysqli_stmt_close($stmt);
        }
    }
}

$back_url = $role === 'admin' ? $admin_redirect : $staff_redirect;
$is_staff = ($role === 'staff');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <?php if ($role === 'admin'): ?>
                <a href="admin/admin_dashboard.php">Admin Dashboard</a>
            <?php else: ?>
                <a href="staff_dashboard.php" class="active">Staff Dashboard</a>
            <?php endif; ?>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_name']); ?><?php echo $role === 'staff' ? ' (Staff)' : ''; ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>Update Idea Status</h1>
        <p class="subtitle"><?php echo $is_staff ? 'Set status to In Progress or Completed and add remarks.' : 'Change the status of this idea.'; ?></p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <p><strong>Idea:</strong> <?php echo htmlspecialchars($idea['title']); ?></p>
            <form method="POST" action="update_status.php?id=<?php echo (int) $id; ?>">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <?php if ($is_staff): ?>
                            <option value="In Progress" <?php echo ($idea['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo ($idea['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <?php else: ?>
                            <option value="Pending" <?php echo ($idea['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo ($idea['status'] === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="In Progress" <?php echo ($idea['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo ($idea['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if ($is_staff): ?>
                <div class="form-group">
                    <label for="staff_remarks">Remarks / Feedback</label>
                    <textarea id="staff_remarks" name="staff_remarks" rows="4" placeholder="Add your remarks or progress feedback..."><?php echo htmlspecialchars($idea['staff_remarks'] ?? ''); ?></textarea>
                </div>
                <?php endif; ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo $is_staff ? 'Update Status & Remarks' : 'Update Status'; ?></button>
                    <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Campus Green Innovation Portal</p>
    </footer>
    <script src="assets/js/script.js"></script>
</body>
</html>
