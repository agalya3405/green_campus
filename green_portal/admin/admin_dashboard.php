<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$result = mysqli_query(
    $conn,
    "SELECT 
        i.id,
        i.title,
        i.category,
        i.status,
        i.assigned_to,
        i.admin_remarks,
        i.staff_remarks,
        i.created_at,
        i.updated_at,
        u.name AS submitter,
        s.name AS assigned_staff_name
     FROM ideas i
     JOIN users u ON i.user_id = u.id
     LEFT JOIN users s ON i.assigned_staff_id = s.id
     ORDER BY i.created_at DESC"
);

$success = $_GET['success'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="active">Admin Dashboard</a>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</span>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <?php if ($success === '1' && $msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <h1>Admin Dashboard</h1>
        <p class="subtitle">Review, approve, and assign submitted ideas.</p>

        <section class="card">
            <h2>All Submitted Ideas</h2>
            <?php if (!$result || mysqli_num_rows($result) === 0): ?>
                <p class="empty-state">No ideas submitted yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Assigned Staff</th>
                                <th>Admin Remarks</th>
                                <th>Staff Remarks</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo (int) $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo htmlspecialchars($row['submitter']); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['assigned_staff_name'] ?? $row['assigned_to'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($row['admin_remarks'] ?? '', 0, 40)) . ((isset($row['admin_remarks']) && mb_strlen($row['admin_remarks']) > 40) ? '…' : ''); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($row['staff_remarks'] ?? '', 0, 40)) . ((isset($row['staff_remarks']) && mb_strlen($row['staff_remarks']) > 40) ? '…' : ''); ?></td>
                                    <td>
                                        <?php
                                        $ts = $row['updated_at'] ?? $row['created_at'];
                                        echo $ts ? date('M j, Y', strtotime($ts)) : '—';
                                        ?>
                                    </td>
                                    <td class="actions-cell">
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <a href="approve_idea.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                        <?php endif; ?>
                                        <a href="../assign_idea.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-secondary">Assign</a>
                                        <a href="../update_status.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-outline">Update Status</a>
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
    <script src="../assets/js/script.js"></script>
</body>
</html>
