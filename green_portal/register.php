<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    $allowed_roles = ['student', 'staff', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        $role = 'student';
    }

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Email already registered.';
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed, $role);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: login.php?registered=1');
                exit;
            }
            $error = 'Registration failed. Please try again.';
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
    <title>Register - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Campus Green Innovation Portal</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php" class="active">Register</a>
        </div>
    </nav>

    <main class="container auth-container">
        <div class="auth-card">
            <h1>Register</h1>
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Registration successful. Please login.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="staff" <?php echo (($_POST['role'] ?? '') === 'staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            <p class="auth-footer">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Campus Green Innovation Portal</p>
    </footer>
</body>
</html>
