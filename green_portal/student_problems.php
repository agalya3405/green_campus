<?php
require_once 'config/session.php';
start_role_session('student');
require_once 'config/db.php';

// Students only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'faculty') {
        header('Location: faculty_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit;
}

$user_name = $_SESSION['user_name'];

$problems_res = mysqli_query($conn, "SELECT id, title, description, created_at FROM problems ORDER BY created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problem Statements - Campus Green Innovation Portal</title>
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
                <a href="student_problems.php" class="nav-item active">View Problems</a>
                <a href="submit_idea.php" class="nav-item">Submit Solution</a>
                <a href="view_ideas.php" class="nav-item">My Solutions</a>
                <a href="leaderboard.php?role=student" class="nav-item">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php?role=student" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Problem Statements</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role">Student</span>
                    </div>
                </div>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Active Campus Challenges</h2>
                </div>
                <div style="padding: 1.5rem;">
                    <?php if (!$problems_res || mysqli_num_rows($problems_res) === 0): ?>
                        <p class="empty-state">No problem statements are currently available.</p>
                    <?php else: ?>
                        <div class="cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
                            <?php while ($row = mysqli_fetch_assoc($problems_res)): ?>
                                <div class="card" style="border: 1px solid #e0e0e0;">
                                    <div class="card-header">
                                        <h3 class="card-title" style="font-size: 1.05rem;">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </h3>
                                    </div>
                                    <div style="padding: 1rem;">
                                        <p style="font-size: 0.95rem; line-height: 1.5;">
                                            <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                                        </p>
                                        <div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
                                            <a href="submit_idea.php?problem_id=<?php echo (int)$row['id']; ?>" class="btn btn-primary">
                                                Submit Solution
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

