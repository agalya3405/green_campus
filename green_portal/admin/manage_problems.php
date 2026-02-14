<?php
session_start();
require_once '../config/db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_name = $_SESSION['user_name'];
$success = $_GET['success'] ?? '';
$msg = $_GET['msg'] ?? '';

// Handle create / update / delete (STRUCTURAL UPDATE STEP 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        if ($title !== '' && $description !== '') {
            $stmt = mysqli_prepare($conn, "INSERT INTO problems (title, description, category) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $title, $description, $category);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: manage_problems.php?success=1&msg=" . urlencode('Problem created.'));
            exit;
        }
        $msg = 'Title and description are required.';
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        if ($id > 0 && $title !== '' && $description !== '') {
            $stmt = mysqli_prepare($conn, "UPDATE problems SET title = ?, description = ?, category = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $title, $description, $category, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: manage_problems.php?success=1&msg=" . urlencode('Problem updated.'));
            exit;
        }
        $msg = 'Failed to update problem.';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM problems WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: manage_problems.php?success=1&msg=" . urlencode('Problem deleted.'));
            exit;
        }
        $msg = 'Failed to delete problem.';
    }
}

// Editing state
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_problem = null;
if ($edit_id > 0) {
    $er = mysqli_query($conn, "SELECT * FROM problems WHERE id = " . $edit_id);
    $edit_problem = $er ? mysqli_fetch_assoc($er) : null;
}

// Fetch all problems for listing
$problems_res = mysqli_query($conn, "SELECT * FROM problems ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Problem Statements - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <a href="manage_ideas.php" class="nav-item">Manage Ideas</a>
                <a href="approved_ideas.php" class="nav-item">Approved Ideas</a>
                <a href="manage_staff.php" class="nav-item">Manage Staff</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="manage_problems.php" class="nav-item active">Manage Problem Statements</a>
                <a href="../leaderboard.php" class="nav-item">Leaderboard</a>
                <a href="../hall_of_fame.php" class="nav-item">Hall of Fame</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Manage Problem Statements</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
            </header>

            <?php if ($success === '1' && $msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
            <?php elseif ($msg): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo $edit_problem ? 'Edit Problem' : 'Create Problem'; ?></h2>
                </div>
                <form method="POST" action="manage_problems.php" style="padding: 1.5rem;">
                    <input type="hidden" name="action" value="<?php echo $edit_problem ? 'update' : 'create'; ?>">
                    <?php if ($edit_problem): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$edit_problem['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="title">Problem Title</label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo htmlspecialchars($edit_problem['title'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-control">
                            <?php
                            $categories = ['Energy', 'Waste Management', 'Water Conservation', 'Digital Transformation', 'Awareness', 'Other'];
                            $current_cat = $edit_problem['category'] ?? '';
                            foreach ($categories as $cat):
                            ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($current_cat === $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($edit_problem['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_problem ? 'Update Problem' : 'Create Problem'; ?>
                        </button>
                        <?php if ($edit_problem): ?>
                            <a href="manage_problems.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Problem Statements</h2>
                </div>
                <?php if (!$problems_res || mysqli_num_rows($problems_res) === 0): ?>
                    <p class="empty-state">No problems defined yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($problems_res)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['created_at']))); ?></td>
                                        <td>
                                            <a href="manage_problems.php?edit=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                            <form method="POST" action="manage_problems.php" style="display: inline;" onsubmit="return confirm('Delete this problem? This will also remove any linked solutions.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
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
</body>
</html>

