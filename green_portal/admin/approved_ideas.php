<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_name = $_SESSION['user_name'];

// Fetch only Approved ideas (submitter = users via user_id, staff = users via assigned_staff_id)
$query = "SELECT i.*,
          u.name AS student_name,
          s.name AS staff_name
          FROM ideas i
          LEFT JOIN users u ON i.user_id = u.id
          LEFT JOIN users s ON i.assigned_staff_id = s.id
          WHERE i.status = 'Approved'
          ORDER BY i.created_at DESC";
$result = mysqli_query($conn, $query);
if (!$result) die("SQL Error: " . mysqli_error($conn));

// Staff list with workload for modal (ordered by assigned_count ASC for smart selection)
$staff_query = "SELECT u.id, u.name,
                COUNT(i.id) AS assigned_count,
                SUM(CASE WHEN i.status = 'Completed' THEN 1 ELSE 0 END) AS completed_count
                FROM users u
                LEFT JOIN ideas i ON u.id = i.assigned_staff_id
                WHERE u.role = 'staff'
                GROUP BY u.id
                ORDER BY assigned_count ASC";
$staff_result = mysqli_query($conn, $staff_query);
if (!$staff_result) die("SQL Error: " . mysqli_error($conn));
$staff_list = [];
$min_count = null;
while ($row = mysqli_fetch_assoc($staff_result)) {
    $row['assigned_count'] = (int) $row['assigned_count'];
    $row['completed_count'] = (int) $row['completed_count'];
    if ($min_count === null) $min_count = $row['assigned_count'];
    $staff_list[] = $row;
}

$success = $_GET['success'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Ideas - Admin</title>
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
            margin: 5% auto;
            padding: 2rem;
            border: 1px solid #888;
            width: 90%;
            max-width: 640px;
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
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        .staff-row-available { background-color: #e8f5e9; }
        .staff-row-busy { }
        .staff-row-heavy { background-color: #ffebee; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="manage_ideas.php" class="nav-item">Manage Ideas</a>
                <a href="approved_ideas.php" class="nav-item active">Approved Ideas</a>
                <a href="manage_staff.php" class="nav-item">Manage Staff</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="../leaderboard.php" class="nav-item">Leaderboard</a>
                <a href="../hall_of_fame.php" class="nav-item">Hall of Fame</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Approved Ideas</h1>
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
                    <h2 class="card-title">Approved Ideas – Assign or Reassign Staff</h2>
                </div>

                <?php if (!$result || mysqli_num_rows($result) === 0): ?>
                    <p class="empty-state">No approved ideas yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Idea Title</th>
                                    <th>Description</th>
                                    <th>Current Assigned Staff</th>
                                    <th>Admin Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['student_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['description']); ?>"><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo !empty($row['assigned_staff_id']) ? htmlspecialchars($row['staff_name'] ?? '-') : '-'; ?></td>
                                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($row['admin_remarks'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (empty($row['assigned_staff_id'])): ?>
                                                <button type="button" onclick="openAssignModal(<?php echo (int) $row['id']; ?>)" class="btn btn-sm btn-approve" style="background-color: var(--status-approved-text); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Assign Staff</button>
                                            <?php else: ?>
                                                <button type="button" onclick="openAssignModal(<?php echo (int) $row['id']; ?>)" class="btn btn-sm" style="background-color: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Reassign Staff</button>
                                            <?php endif; ?>
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

    <!-- Assign Staff Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignModal()">&times;</span>
            <h2 style="margin-bottom: 1rem; color: var(--primary-color);">Assign Staff to Idea</h2>
            <form id="assignForm" method="POST" action="assign_staff.php">
                <input type="hidden" id="assign_idea_id" name="idea_id" value="">
                <p style="margin-bottom: 1rem; color: #555;">Select a staff member. Availability is based on current workload.</p>
                <?php if (empty($staff_list)): ?>
                    <p class="empty-state">No staff members found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>Staff Name</th>
                                    <th>Total Assigned Ideas</th>
                                    <th>Completed Ideas</th>
                                    <th>Availability Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff_list as $staff): ?>
                                    <?php
                                    $available = $staff['assigned_count'] == 0;
                                    $heavy = $staff['assigned_count'] >= 3;
                                    $lowest = ($min_count !== null && $staff['assigned_count'] == $min_count);
                                    $row_class = $heavy ? 'staff-row-heavy' : ($lowest ? 'staff-row-available' : 'staff-row-busy');
                                    $status_text = $available ? 'Available' : 'Busy';
                                    ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td>
                                            <input type="radio" name="staff_id" value="<?php echo (int) $staff['id']; ?>" id="staff_<?php echo $staff['id']; ?>" required>
                                        </td>
                                        <td><label for="staff_<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?></label></td>
                                        <td><?php echo $staff['assigned_count']; ?></td>
                                        <td><?php echo $staff['completed_count']; ?></td>
                                        <td><?php echo $heavy ? 'Heavy workload' : $status_text; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-actions" style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">Assign Selected Staff</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal(ideaId) {
            document.getElementById('assign_idea_id').value = ideaId;
            document.getElementById('assignModal').style.display = 'block';
        }
        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target === document.getElementById('assignModal')) {
                closeAssignModal();
            }
        };
    </script>
</body>
</html>
