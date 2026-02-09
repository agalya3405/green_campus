<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $user_id = (int) $_SESSION['user_id'];

    $allowed_categories = ['Waste', 'Energy', 'Water', 'Greenery'];
    if (!in_array($category, $allowed_categories)) {
        $category = 'Waste';
    }

    if (empty($title) || empty($description)) {
        $error = 'Please fill in title and description.';
    } else {
        $status = 'Pending';
        $stmt = mysqli_prepare($conn, "INSERT INTO ideas (user_id, title, description, category, status) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $title, $description, $category, $status);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header('Location: dashboard.php?success=1&msg=' . urlencode('Idea submitted successfully!'));
            exit;
        }
        $error = 'Failed to submit idea. Please try again.';
        if (isset($stmt)) mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Idea - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="submit_idea.php" class="active">Submit Idea</a>
            <a href="view_ideas.php">View My Ideas</a>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>Submit an Idea</h1>
        <p class="subtitle">Share your eco-friendly innovation with the campus.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="submit_idea.php" class="idea-form">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required placeholder="Short title for your idea" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required placeholder="Describe your idea in detail"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="Waste" <?php echo (($_POST['category'] ?? '') === 'Waste') ? 'selected' : ''; ?>>Waste</option>
                        <option value="Energy" <?php echo (($_POST['category'] ?? '') === 'Energy') ? 'selected' : ''; ?>>Energy</option>
                        <option value="Water" <?php echo (($_POST['category'] ?? '') === 'Water') ? 'selected' : ''; ?>>Water</option>
                        <option value="Greenery" <?php echo (($_POST['category'] ?? '') === 'Greenery') ? 'selected' : ''; ?>>Greenery</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Idea</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
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
