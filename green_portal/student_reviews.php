<?php
require_once 'config/session.php';
start_role_session('student');
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Fetch ideas with review details
$sql = "SELECT id, title, progress_percentage, 
               review1_status, review1_remarks, 
               review2_status, review2_remarks, 
               final_review_status, final_review_remarks 
        FROM ideas 
        WHERE user_id = ? 
        ORDER BY updated_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .review-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .review-step {
            padding: 1.25rem;
            border-left: 4px solid #ddd;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }
        .review-step.completed {
            border-left-color: #2E7D32;
        }
        .review-step h4 {
            margin-top: 0;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .remarks-box {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: white;
            border: 1px solid #eee;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #555;
            min-height: 50px;
        }
        .progress-bar-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 10px;
            margin: 10px 0;
            height: 20px;
        }
        .progress-bar {
            height: 100%;
            background-color: #2E7D32;
            border-radius: 10px;
            text-align: center;
            color: white;
            font-size: 12px;
            line-height: 20px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="student_problems.php" class="nav-item">View Problems</a>
                <a href="submit_idea.php" class="nav-item">Submit Solution</a>
                <a href="view_ideas.php" class="nav-item">My Solutions</a>
                <a href="student_reviews.php" class="nav-item active">Reviews</a>
                <a href="leaderboard.php?role=student" class="nav-item">Leaderboard</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php?role=student" class="nav-item" style="color: #D32F2F;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title">Project Reviews</h1>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </header>

            <?php if (mysqli_num_rows($result) === 0): ?>
                <div class="card">
                    <p class="empty-state">No ideas submitted yet. Submit an idea to see review status.</p>
                </div>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-header">
                            <h2 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h2>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 1.5rem;">
                                <strong>Overall Progress: <?php echo $row['progress_percentage']; ?>%</strong>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?php echo $row['progress_percentage']; ?>%;">
                                        <?php echo $row['progress_percentage']; ?>%
                                    </div>
                                </div>
                            </div>

                            <div class="review-grid">
                                <!-- Review 1 -->
                                <div class="review-step <?php echo $row['review1_status'] === 'Completed' ? 'completed' : ''; ?>">
                                    <h4>
                                        Review 1 (30%)
                                        <span class="badge badge-<?php echo strtolower($row['review1_status']); ?>">
                                            <?php echo htmlspecialchars($row['review1_status']); ?>
                                        </span>
                                    </h4>
                                    <div class="remarks-box">
                                        <?php echo !empty($row['review1_remarks']) ? nl2br(htmlspecialchars($row['review1_remarks'])) : 'No remarks yet.'; ?>
                                    </div>
                                </div>

                                <!-- Review 2 -->
                                <div class="review-step <?php echo $row['review2_status'] === 'Completed' ? 'completed' : ''; ?>">
                                    <h4>
                                        Review 2 (60%)
                                        <span class="badge badge-<?php echo strtolower($row['review2_status']); ?>">
                                            <?php echo htmlspecialchars($row['review2_status']); ?>
                                        </span>
                                    </h4>
                                    <div class="remarks-box">
                                        <?php echo !empty($row['review2_remarks']) ? nl2br(htmlspecialchars($row['review2_remarks'])) : 'No remarks yet.'; ?>
                                    </div>
                                </div>

                                <!-- Final Review -->
                                <div class="review-step <?php echo $row['final_review_status'] === 'Completed' ? 'completed' : ''; ?>">
                                    <h4>
                                        Final Review (100%)
                                        <span class="badge badge-<?php echo strtolower($row['final_review_status']); ?>">
                                            <?php echo htmlspecialchars($row['final_review_status']); ?>
                                        </span>
                                    </h4>
                                    <div class="remarks-box">
                                        <?php echo !empty($row['final_review_remarks']) ? nl2br(htmlspecialchars($row['final_review_remarks'])) : 'No remarks yet.'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
