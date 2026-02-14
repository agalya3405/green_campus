<?php
session_start();
require_once 'config/db.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$user_name = $_SESSION['user_name'];

// Allowed Roles
if (!in_array($role, ['admin', 'staff'])) {
    header('Location: dashboard.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . ($role === 'admin' ? 'admin/admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

// Fetch Idea (include user_id for points when marking Completed)
$stmt = mysqli_prepare($conn, "SELECT id, title, status, staff_remarks, assigned_staff_id, user_id FROM ideas WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$idea = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$idea) {
    header('Location: ' . ($role === 'admin' ? 'admin/admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

// Strict Check for Staff: Must be assigned to this idea
if ($role === 'staff' && (int)$idea['assigned_staff_id'] !== $user_id) {
    header('Location: staff_dashboard.php?msg=' . urlencode('You are not authorized to update this idea.'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $remarks = trim($_POST['staff_remarks'] ?? '');

    // Validation
    if ($role === 'staff') {
        // Staff can ONLY set "In Progress" or "Completed"
        if (!in_array($status, ['In Progress', 'Completed'])) {
            $error = 'Invalid status. You can only set In Progress or Completed.';
        }
        
        // Enforce strict workflow: Approved → In Progress → Completed
        if ($status === 'In Progress' && $idea['status'] !== 'Approved') {
            $error = 'You can only start work on approved ideas.';
        }
        
        if ($status === 'Completed' && $idea['status'] !== 'In Progress') {
            $error = 'You must start work before marking it as completed.';
        }
        
        // Remarks are REQUIRED when marking as Completed
        if ($status === 'Completed' && empty($remarks)) {
            $error = 'Staff remarks are required when marking task as completed.';
        }
    }

    if (!$error) {
        $was_completed = ($idea['status'] === 'Completed');
        $idea_user_id = isset($idea['user_id']) ? (int) $idea['user_id'] : 0;

        $has_updated_at = (mysqli_fetch_assoc(mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'updated_at'")) !== null);
        $set_updated = $has_updated_at ? ', updated_at = NOW()' : '';
        if ($role === 'staff') {
            $stmt = mysqli_prepare($conn, "UPDATE ideas SET status = ?, staff_remarks = ?$set_updated WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $status, $remarks, $id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE ideas SET status = ?$set_updated WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            
            // +50 points for student when idea is first marked Completed
            if ($status === 'Completed' && !$was_completed && $idea_user_id) {
                // Update user points
                mysqli_query($conn, "UPDATE users SET points = COALESCE(points, 0) + 50 WHERE id = $idea_user_id");
                
                // Record in rewards table
                $reward_stmt = mysqli_prepare($conn, "INSERT INTO rewards (student_id, points, reason) VALUES (?, 50, ?)");
                if ($reward_stmt) {
                    $reason = 'Implementation Completed';
                    mysqli_stmt_bind_param($reward_stmt, "is", $idea_user_id, $reason);
                    mysqli_stmt_execute($reward_stmt);
                    mysqli_stmt_close($reward_stmt);
                }
            }
            
            $redirect = ($role === 'admin') ? 'admin/admin_dashboard.php' : 'staff_dashboard.php';
            header('Location: ' . $redirect . '?success=1&msg=' . urlencode('Status updated successfully.'));
            exit;
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
            if (isset($stmt)) mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Status - Campus Green Innovation Portal</title>
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
                <?php if ($role === 'admin'): ?>
                    <a href="admin/admin_dashboard.php" class="nav-item">Dashboard</a>
                    <a href="admin/admin_dashboard.php" class="nav-item">Manage Ideas</a>
                <?php else: ?>
                    <a href="staff_dashboard.php" class="nav-item active">Dashboard</a>
                    <a href="staff_dashboard.php" class="nav-item">Assigned Ideas</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Update Status</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role"><?php echo ucfirst($role); ?></span>
                    </div>
                </div>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Updating: <?php echo htmlspecialchars($idea['title']); ?></h2>
                </div>
                
                <form method="POST" action="update_status.php?id=<?php echo (int) $id; ?>">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <?php if ($role === 'staff'): ?>
                                <option value="In Progress" <?php echo ($idea['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo ($idea['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <?php else: ?>
                                <option value="Pending" <?php echo ($idea['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo ($idea['status'] === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="In Progress" <?php echo ($idea['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo ($idea['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Rejected" <?php echo ($idea['status'] === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <?php if ($role === 'staff'): ?>
                    <div class="form-group">
                        <label for="staff_remarks">Staff Remarks <?php if ($idea['status'] === 'In Progress'): ?><span style="color: red;">*</span><?php endif; ?></label>
                        <textarea id="staff_remarks" name="staff_remarks" class="form-control" rows="5" placeholder="Enter progress details..." <?php if ($idea['status'] === 'In Progress'): ?>required<?php endif; ?>><?php echo htmlspecialchars($idea['staff_remarks'] ?? ''); ?></textarea>
                        <?php if ($idea['status'] === 'In Progress'): ?>
                            <small class="form-text text-muted">Remarks are required when marking task as completed.</small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Status</button>
                        <a href="<?php echo ($role === 'admin' ? 'admin/admin_dashboard.php' : 'staff_dashboard.php'); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
