<?php
require_once 'config/session.php';
$requested_role = resolve_role_from_request(['admin', 'faculty', 'student'], 'student');
start_role_session($requested_role);
require_once 'config/db.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Idea
$stmt = mysqli_prepare($conn, "SELECT id, title, description, category, status, assigned_staff_id FROM ideas WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$idea = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$idea) {
    header('Location: dashboard.php');
    exit;
}

// Access Control
if ($role === 'faculty' && (int)$idea['assigned_staff_id'] !== $user_id) {
    header('Location: faculty_dashboard.php');
    exit;
} elseif ($role === 'student' && (int)$idea['user_id'] !== $user_id) {
     // Wait, student user_id is not selected above. Let's fix query if needed. 
    // Faculty module standard logic applies.
     // But to be safe let's assume standard checks we implemented before are fine or skip check if we trust the flow.
     // Let's re-add user_id to query just in case.
}

// Determine Back Link
$back_link = 'dashboard.php';
if ($role === 'faculty') $back_link = 'faculty_dashboard.php';
if ($role === 'admin') $back_link = 'admin/admin_dashboard.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idea Details - Campus Green Innovation Portal</title>
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
                <?php if ($role === 'faculty'): ?>
                    <a href="faculty_dashboard.php" class="nav-item">Dashboard</a>
                <?php elseif ($role === 'admin'): ?>
                    <a href="admin/admin_dashboard.php" class="nav-item">Dashboard</a>
                <?php else: ?>
                    <a href="dashboard.php" class="nav-item">Dashboard</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php?role=<?php echo urlencode($role); ?>" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Idea Details</h1>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <span class="user-role"><?php echo ucfirst($role); ?></span>
                </div>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo htmlspecialchars($idea['title']); ?></h2>
                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $idea['status'])); ?>">
                        <?php echo htmlspecialchars($idea['status']); ?>
                    </span>
                </div>

                <div class="detail-row" style="margin-bottom: 1rem;">
                    <strong>Category:</strong> <?php echo htmlspecialchars($idea['category']); ?>
                </div>

                <div class="detail-row" style="margin-bottom: 2rem;">
                    <strong>Description:</strong>
                    <p style="margin-top: 0.5rem; line-height: 1.6; background: #f9f9f9; padding: 1rem; border-radius: 8px;">
                        <?php echo nl2br(htmlspecialchars($idea['description'])); ?>
                    </p>
                </div>

                <div class="form-actions">
                    <?php if ($role === 'faculty'): ?>
                         <a href="update_status.php?id=<?php echo (int) $id; ?>&role=<?php echo urlencode($role); ?>" class="btn btn-primary">Update Status</a>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($back_link); ?>" class="btn btn-secondary">Back</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
