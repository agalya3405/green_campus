<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } elseif ($_SESSION['role'] === 'staff') {
        header('Location: staff_dashboard.php');
    } else {
        header('Location: student_dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $login_as = $_POST['role'] ?? '';

    $allowed_roles = ['student', 'staff', 'admin'];
    if (!in_array($login_as, $allowed_roles, true)) {
        $login_as = '';
    }

    if (empty($email) || empty($password) || empty($login_as)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if ($row['role'] !== $login_as) {
                $error = 'Invalid role selected for this account.';
            } elseif (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['role'] = $row['role'];

                mysqli_stmt_close($stmt);

                if ($row['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } elseif ($row['role'] === 'staff') {
                    header('Location: staff_dashboard.php');
                } else {
                    header('Location: student_dashboard.php');
                }
                exit;
            }
        }
        if (!$error) {
            $error = 'Invalid email or password.';
        }
        if (isset($stmt) && $stmt) mysqli_stmt_close($stmt);
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="auth-layout">
    <div class="auth-card">
        <div class="auth-logo">🌿</div>
        <h1 class="auth-title">Green Campus<br>Innovation Portal</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registration successful. Please login.</div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="role">Login As</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="">— Select role —</option>
                    <option value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="staff" <?php echo (($_POST['role'] ?? '') === 'staff') ? 'selected' : ''; ?>>Staff</option>
                    <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="student@example.com">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
        
        <p style="margin-top: 1.5rem; color: #757575; font-size: 0.9rem;">
            New to the portal? <a href="register.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">Create an account</a>
        </p>
    </div>
</body>
</html>
