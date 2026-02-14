<?php
session_start();
require_once 'config/db.php';

// Students only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
        header('Location: staff_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit;
}

$error = '';
$user_name = $_SESSION['user_name'];
$user_id = (int) $_SESSION['user_id'];

// Fetch all problems for dropdown
$problems = [];
$pRes = mysqli_query($conn, "SELECT id, title FROM problems ORDER BY created_at ASC");
if ($pRes) {
    while ($row = mysqli_fetch_assoc($pRes)) {
        $problems[] = $row;
    }
}

// Pre-select problem from query string if provided
$selected_problem_id = isset($_GET['problem_id']) ? (int) $_GET['problem_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $problem_id = (int) ($_POST['problem_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($problem_id <= 0 || $description === '') {
        $error = 'Please select a problem and enter your solution.';
    } else {
        // Load problem title to generate a technical title for the idea record
        $pstmt = mysqli_prepare($conn, "SELECT title FROM problems WHERE id = ?");
        mysqli_stmt_bind_param($pstmt, "i", $problem_id);
        mysqli_stmt_execute($pstmt);
        $pres = mysqli_stmt_get_result($pstmt);
        $problem = mysqli_fetch_assoc($pres);
        mysqli_stmt_close($pstmt);

        if (!$problem) {
            $error = 'Selected problem is not available.';
        } else {
            $auto_title = 'Solution for: ' . $problem['title'];
            $category = 'General';
            $status = 'Pending';

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO ideas (user_id, problem_id, title, description, category, status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "iissss", $user_id, $problem_id, $auto_title, $description, $category, $status);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                // +5 points for submitting a solution (logic unchanged)
                mysqli_query($conn, "UPDATE users SET points = COALESCE(points, 0) + 5 WHERE id = " . (int) $user_id);
                header('Location: dashboard.php?success=1&msg=' . urlencode('Solution submitted successfully!'));
                exit;
            }
            $error = 'Failed to submit solution. Please try again.';
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
    <title>Submit Solution - Campus Green Innovation Portal</title>
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
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="student_problems.php" class="nav-item">View Problems</a>
                <a href="submit_idea.php" class="nav-item active">Submit Solution</a>
                <a href="view_ideas.php" class="nav-item">My Solutions</a>
                <a href="leaderboard.php" class="nav-item">Leaderboard</a>
                <a href="hall_of_fame.php" class="nav-item">Hall of Fame</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Submit Solution</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role">Student</span>
                    </div>
                </div>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                 <div class="card-header">
                    <h2 class="card-title">Submit your solution for a campus problem</h2>
                </div>
                <form method="POST" action="submit_idea.php">
                    <div class="form-group">
                        <label for="problem_id">Select Problem Statement</label>
                        <select id="problem_id" name="problem_id" class="form-control" required>
                            <option value="">-- Choose a problem --</option>
                            <?php foreach ($problems as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>" <?php echo ($selected_problem_id === (int)$p['id'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($p['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Your Solution</label>
                        <textarea id="description" name="description" class="form-control" rows="6" required placeholder="Describe your solution in detail..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-actions" style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">Submit Solution</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
