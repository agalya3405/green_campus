<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

// Load idea and submitter for context
$stmt = mysqli_prepare($conn, "SELECT i.id, i.title, i.status, u.name AS student_name FROM ideas i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$idea = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$idea) {
    header('Location: admin_dashboard.php?success=0&msg=' . urlencode('Idea not found.'));
    exit;
}

// Load staff list
$staff_list = [];
$r = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role = 'staff' ORDER BY name");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $staff_list[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assigned_staff_id = isset($_POST['assigned_staff_id']) ? (int) $_POST['assigned_staff_id'] : 0;
    $admin_remarks = trim($_POST['admin_remarks'] ?? '');

    $staff_name = '';
    foreach ($staff_list as $s) {
        if ((int) $s['id'] === $assigned_staff_id) {
            $staff_name = $s['name'];
            break;
        }
    }

    if ($assigned_staff_id <= 0 || $staff_name === '') {
        $error = 'Please select a staff member.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE ideas 
             SET status = 'Approved',
                 assigned_staff_id = ?,
                 assigned_to = ?,
                 admin_remarks = ?
             WHERE id = ? AND status = 'Pending'"
        );
        mysqli_stmt_bind_param($stmt, "issi", $assigned_staff_id, $staff_name, $admin_remarks, $id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($conn);
        mysqli_stmt_close($stmt);

        if ($affected > 0) {
            header('Location: admin_dashboard.php?success=1&msg=' . urlencode('Idea approved and assigned to staff successfully.'));
            exit;
        }
        $error = 'Idea not found or already processed.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve & Assign Idea - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <a href="admin_dashboard.php">Admin Dashboard</a>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</span>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>Approve & Assign Idea</h1>
        <p class="subtitle">Approve the idea and assign it to a staff member with your remarks.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <p><strong>Student:</strong> <?php echo htmlspecialchars($idea['student_name']); ?></p>
            <p><strong>Idea Title:</strong> <?php echo htmlspecialchars($idea['title']); ?></p>
            <p><strong>Current Status:</strong> <?php echo htmlspecialchars($idea['status']); ?></p>

            <form method="POST" action="approve_idea.php?id=<?php echo (int) $id; ?>">
                <div class="form-group">
                    <label for="assigned_staff_id">Assign to Staff</label>
                    <select id="assigned_staff_id" name="assigned_staff_id" required>
                        <option value="">— Select staff —</option>
                        <?php foreach ($staff_list as $s): ?>
                            <option value="<?php echo (int) $s['id']; ?>">
                                <?php echo htmlspecialchars($s['name']); ?> (<?php echo htmlspecialchars($s['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="admin_remarks">Admin Remarks</label>
                    <textarea id="admin_remarks" name="admin_remarks" rows="4" placeholder="Add your approval remarks..."><?php echo htmlspecialchars($_POST['admin_remarks'] ?? ''); ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Approve & Assign</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Campus Green Innovation Portal</p>
    </footer>
    <script src="../assets/js/script.js"></script>
</body>
</html>
