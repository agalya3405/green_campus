<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } elseif ($_SESSION['role'] === 'staff') {
        header('Location: staff_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                mysqli_stmt_close($stmt);
                if ($row['role'] === 'admin') {
                    header('Location: admin/admin_dashboard.php');
                } elseif ($row['role'] === 'staff') {
                    header('Location: staff_dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            }
        }
        $error = 'Invalid email or password.';
        if (isset($stmt)) mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="login.php" class="active">Login</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <main class="container auth-container">
        <div class="auth-card">
            <h1>Login</h1>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <p class="auth-footer">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Campus Green Innovation Portal</p>
    </footer>
</body>
</html>
