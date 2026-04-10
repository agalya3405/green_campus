<?php
require_once 'config/session.php';
start_role_session('admin');
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: admin/admin_dashboard.php');
    exit;
}

$error = '';
$idea = null;
$has_staff_id = false;
$staff_list = [];

// Check if ideas table has assigned_faculty_id (Faculty module)
$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'assigned_staff_id'");
if ($r && mysqli_num_rows($r) > 0) {
    $has_staff_id = true;
}
if ($has_staff_id) {
    $r = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role = 'faculty' ORDER BY name");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $staff_list[] = $row;
        }
    }
}

$stmt = mysqli_prepare($conn, "SELECT id, title, status, assigned_to, " . ($has_staff_id ? "assigned_staff_id" : "NULL AS assigned_staff_id") . " FROM ideas WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $idea = $row;
}
mysqli_stmt_close($stmt);

if (!$idea) {
    header('Location: admin/admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($has_staff_id && !empty($staff_list)) {
        $assigned_staff_id = isset($_POST['assigned_staff_id']) ? (int) $_POST['assigned_staff_id'] : 0;
        $staff_name = '';
        foreach ($staff_list as $s) {
            if ((int) $s['id'] === $assigned_staff_id) {
                $staff_name = $s['name'];
                break;
            }
        }
        if ($assigned_staff_id > 0 && $staff_name !== '') {
            $stmt = mysqli_prepare($conn, "UPDATE ideas SET assigned_staff_id = ?, assigned_to = ?, status = IF(status = 'Pending', 'Approved', status) WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "isi", $assigned_staff_id, $staff_name, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: admin/admin_dashboard.php?success=1&msg=' . urlencode('Idea assigned to faculty successfully.'));
                exit;
            }
            if (isset($stmt)) mysqli_stmt_close($stmt);
        }
        $error = 'Please select a faculty member.';
    } else {
        $assigned_to = trim($_POST['assigned_to'] ?? '');
        if (empty($assigned_to)) {
            $error = 'Please enter faculty name to assign.';
        } else {
            if ($has_staff_id) {
                $stmt = mysqli_prepare($conn, "UPDATE ideas SET assigned_to = ?, status = IF(status = 'Pending', 'Approved', status) WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $assigned_to, $id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE ideas SET assigned_to = ?, status = IF(status = 'Pending', 'Approved', status) WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $assigned_to, $id);
            }
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: admin/admin_dashboard.php?success=1&msg=' . urlencode('Idea assigned successfully.'));
                exit;
            }
            $error = 'Failed to assign. Please try again.';
            if (isset($stmt)) mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Idea - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <a href="admin/admin_dashboard.php">Admin Dashboard</a>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</span>
            <a href="logout.php?role=admin">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>Assign Idea</h1>
        <p class="subtitle">Assign idea to a faculty member. Faculty will see it on their dashboard.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <p><strong>Idea:</strong> <?php echo htmlspecialchars($idea['title']); ?></p>
            <form method="POST" action="assign_idea.php?id=<?php echo (int) $id; ?>">
                <?php if ($has_staff_id && !empty($staff_list)): ?>
                <div class="form-group">
                    <label for="assigned_staff_id">Assign to (Faculty)</label>
                    <select id="assigned_staff_id" name="assigned_staff_id" required>
                        <option value="">— Select faculty —</option>
                        <?php foreach ($staff_list as $s): ?>
                            <option value="<?php echo (int) $s['id']; ?>" <?php echo (isset($idea['assigned_staff_id']) && (int) $idea['assigned_staff_id'] === (int) $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?> (<?php echo htmlspecialchars($s['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label for="assigned_to">Assign to (Faculty name)</label>
                    <input type="text" id="assigned_to" name="assigned_to" required placeholder="Enter faculty name" value="<?php echo htmlspecialchars($idea['assigned_to'] ?? $_POST['assigned_to'] ?? ''); ?>">
                </div>
                <?php endif; ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Assign</button>
                    <a href="admin/admin_dashboard.php" class="btn btn-secondary">Cancel</a>
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
