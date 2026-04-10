<?php
require_once '../config/session.php';
start_role_session('admin');
require_once '../config/db.php';

// Role protection (mandatory)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Debug safety (temporarily enable)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_name = $_SESSION['user_name'];

// Report counts: real-time from database (no manual counters)
$r = mysqli_query($conn, "SELECT COUNT(*) FROM ideas");
if (!$r) die("SQL Error: " . mysqli_error($conn));
$total = (int) mysqli_fetch_row($r)[0];

$r = mysqli_query($conn, "SELECT COUNT(*) FROM ideas WHERE status='Pending'");
if (!$r) die("SQL Error: " . mysqli_error($conn));
$pending = (int) mysqli_fetch_row($r)[0];

$r = mysqli_query($conn, "SELECT COUNT(*) FROM ideas WHERE status='Approved'");
if (!$r) die("SQL Error: " . mysqli_error($conn));
$approved = (int) mysqli_fetch_row($r)[0];

$r = mysqli_query($conn, "SELECT COUNT(*) FROM ideas WHERE status='Rejected'");
if (!$r) die("SQL Error: " . mysqli_error($conn));
$rejected = (int) mysqli_fetch_row($r)[0];

$r = mysqli_query($conn, "SELECT COUNT(*) FROM ideas WHERE status='Completed'");
if (!$r) die("SQL Error: " . mysqli_error($conn));
$completed = (int) mysqli_fetch_row($r)[0];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="brand-name">Green Campus</span>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="manage_ideas.php" class="nav-item">Manage Ideas</a>
                <a href="admin_manage_problems.php" class="nav-item">Manage Problem Statements</a>
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
                <h1 class="page-title">Admin Dashboard</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
            </header>

            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card" style="text-align: center; padding: 2rem;">
                    <h3 style="font-size: 1rem; color: #666; margin-bottom: 0.5rem;">Total Ideas</h3>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin: 0;"><?php echo $total; ?></p>
                </div>
                <div class="card" style="text-align: center; padding: 2rem;">
                    <h3 style="font-size: 1rem; color: #666; margin-bottom: 0.5rem;">Pending</h3>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--status-pending-text); margin: 0;"><?php echo $pending; ?></p>
                </div>
                <div class="card" style="text-align: center; padding: 2rem;">
                    <h3 style="font-size: 1rem; color: #666; margin-bottom: 0.5rem;">Approved</h3>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--status-approved-bg); margin: 0;"><?php echo $approved; ?></p>
                </div>
                <div class="card" style="text-align: center; padding: 2rem;">
                    <h3 style="font-size: 1rem; color: #666; margin-bottom: 0.5rem;">Rejected</h3>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--status-rejected-text); margin: 0;"><?php echo $rejected; ?></p>
                </div>
                <div class="card" style="text-align: center; padding: 2rem;">
                    <h3 style="font-size: 1rem; color: #666; margin-bottom: 0.5rem;">Completed</h3>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--status-completed-text); margin: 0;"><?php echo $completed; ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div style="padding: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="manage_ideas.php?filter=pending" class="btn btn-primary">Review Pending Ideas</a>
                    <a href="manage_faculty.php" class="btn btn-secondary">Manage Faculty</a>
                    <a href="../leaderboard.php?role=admin" class="btn btn-secondary">View Leaderboard</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
