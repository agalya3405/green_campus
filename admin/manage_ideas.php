<?php
// Prevent Caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../config/session.php';
start_role_session('admin');
require_once '../config/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_name = $_SESSION['user_name'];

// Filter: pending = only Pending ideas; else all ideas
$filter = isset($_GET['filter']) && $_GET['filter'] == 'pending' ? 'pending' : 'all';

if ($filter === 'pending') {
    $query = "SELECT i.*,
              u.name AS student_name,
              s.name AS staff_name,
              p.title AS problem_title
              FROM ideas i
              LEFT JOIN users u ON i.user_id = u.id
              LEFT JOIN users s ON i.assigned_staff_id = s.id
              LEFT JOIN problems p ON i.problem_id = p.id
              WHERE i.status = 'Pending'
              ORDER BY i.created_at DESC";
} else {
    $query = "SELECT i.*,
              u.name AS student_name,
              s.name AS staff_name,
              p.title AS problem_title
              FROM ideas i
              LEFT JOIN users u ON i.user_id = u.id
              LEFT JOIN users s ON i.assigned_staff_id = s.id
              LEFT JOIN problems p ON i.problem_id = p.id
              ORDER BY i.created_at DESC";
}

$result = mysqli_query($conn, $query);
if (!$result) die("SQL Error: " . mysqli_error($conn));

// Staff dropdown: SELECT id, name FROM users WHERE role='staff'
$staff_res = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'faculty' ORDER BY name");
if (!$staff_res) die("SQL Error: " . mysqli_error($conn));
$staff_members = [];
while ($s = mysqli_fetch_assoc($staff_res)) {
    $staff_members[] = $s;
}

$success = $_GET['success'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ideas - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; 
            padding: 2rem;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="manage_ideas.php" class="nav-item active">Manage Ideas</a>
                <a href="approved_ideas.php" class="nav-item">Approved Ideas</a>
                <a href="manage_faculty.php" class="nav-item">Manage Faculty</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="../leaderboard.php?role=admin" class="nav-item">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php?role=admin" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Manage Ideas</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
            </header>

            <?php if ($success === '1' && $msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <?php if ($success !== '1' && $msg): ?>
                <div class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px;"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Submitted Ideas</h2>
                </div>
                
                <?php if (!$result || mysqli_num_rows($result) === 0): ?>
                    <p class="empty-state">No ideas submitted yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Problem Title</th>
                                    <th>Student Solution</th>
                                    <th>Status</th>
                                    <th>Assigned Faculty</th>
                                    <th>Admin Remarks</th>
                                    <th>Faculty Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <?php 
                                        $status_clean = strtolower(trim($row['status']));
                                        $is_pending = ($status_clean === 'pending');
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['student_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['problem_title'] ?? $row['title']); ?></td>
                                        <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['description']); ?>">
                                            <?php echo htmlspecialchars($row['description']); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo !empty($row['assigned_staff_id']) ? htmlspecialchars($row['staff_name'] ?? '-') : '-'; ?>
                                        </td>
                                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($row['admin_remarks'] ?? '-'); ?>
                                        </td>
                                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($row['staff_remarks'] ?? '-'); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                <?php if ($is_pending): ?>
                                                    <button onclick="openApproveModal(<?php echo $row['id']; ?>)" class="btn btn-sm btn-approve" style="background-color: var(--primary-color); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">✓ Approve</button>
                                                    <button onclick="openRejectModal(<?php echo $row['id']; ?>)" class="btn btn-sm btn-reject" style="background-color: var(--status-rejected-text); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Reject</button>
                                                <?php else: ?>
                                                    <a href="../view_idea.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-view" style="background-color: #6c757d; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; display: inline-block;">View</a>
                                                    <?php if (strtolower(trim($row['status'] ?? '')) === 'completed'): ?>
                                                        <form method="POST" action="mark_best_idea.php" style="display: inline;">
                                                            <input type="hidden" name="idea_id" value="<?php echo (int) $row['id']; ?>">
                                                            <button type="submit" class="btn btn-sm" style="background-color: #1B5E20; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Mark as Best Idea</button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeApproveModal()">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Approve Idea</h2>
            <form id="approveForm" method="POST" action="approve_idea.php">
                <input type="hidden" id="approve_idea_id" name="idea_id">
                <div class="form-group">
                    <label for="staff_id">Assign Faculty (Required)</label>
                    <select id="staff_id" name="staff_id" class="form-control" required>
                        <option value="">-- Select Faculty --</option>
                        <?php foreach ($staff_members as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="approve_remarks">Admin Remarks (Required)</label>
                    <textarea id="approve_remarks" name="admin_remarks" class="form-control" rows="4" required placeholder="Enter approval notes..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRejectModal()">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--status-rejected-text);">Reject Idea</h2>
            <form id="rejectForm" method="POST" action="reject_idea.php">
                <input type="hidden" id="reject_idea_id" name="idea_id">
                <div class="form-group">
                    <label for="reject_remarks">Reason for Rejection (Required)</label>
                    <textarea id="reject_remarks" name="admin_remarks" class="form-control" rows="4" required placeholder="Enter rejection reason..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger btn-block" style="background-color: var(--status-rejected-text); border: none;">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApproveModal(id) {
            document.getElementById('approve_idea_id').value = id;
            document.getElementById('approveModal').style.display = "block";
        }
        function closeApproveModal() {
            document.getElementById('approveModal').style.display = "none";
        }

        function openRejectModal(id) {
            document.getElementById('reject_idea_id').value = id;
            document.getElementById('rejectModal').style.display = "block";
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('approveModal')) {
                closeApproveModal();
            }
            if (event.target == document.getElementById('rejectModal')) {
                closeRejectModal();
            }
        }
    </script>
</body>
</html>
