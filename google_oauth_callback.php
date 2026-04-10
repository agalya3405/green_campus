<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/google_oauth.php';

start_guest_session();

if (!google_oauth_configured()) {
    header('Location: login.php?error=' . urlencode('Google sign-in is not configured.'));
    exit;
}

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';

if ($error !== '') {
    header('Location: login.php?error=' . urlencode('Google sign-in was cancelled or failed.'));
    exit;
}

if ($code === '' || $state === '' || !hash_equals($_SESSION['google_oauth_state'] ?? '', $state)) {
    header('Location: login.php?error=' . urlencode('Invalid sign-in session. Please try again.'));
    exit;
}

unset($_SESSION['google_oauth_state']);

$tokenJson = google_oauth_exchange_code($code);
if (!$tokenJson || empty($tokenJson['access_token'])) {
    header('Location: login.php?error=' . urlencode('Could not verify with Google. Try again.'));
    exit;
}

$info = google_oauth_userinfo($tokenJson['access_token']);
if (!$info || empty($info['sub']) || empty($info['email'])) {
    header('Location: login.php?error=' . urlencode('Could not read your Google profile.'));
    exit;
}

if (empty($info['email_verified'])) {
    header('Location: login.php?error=' . urlencode('Please use a verified Google email.'));
    exit;
}

$googleSub = $info['sub'];
$email = $info['email'];
$name = trim($info['name'] ?? '') ?: explode('@', $email)[0];

$stmt = mysqli_prepare($conn, 'SELECT id, name, email, password, role, google_sub FROM users WHERE google_sub = ? OR email = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ss', $googleSub, $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if ($row) {
    if (empty($row['google_sub'])) {
        $u = mysqli_prepare($conn, 'UPDATE users SET google_sub = ? WHERE id = ?');
        mysqli_stmt_bind_param($u, 'si', $googleSub, $row['id']);
        mysqli_stmt_execute($u);
        mysqli_stmt_close($u);
    }
    $userId = (int) $row['id'];
    $userName = $row['name'];
    $userEmail = $row['email'];
    $userRole = $row['role'];
} else {
    // New accounts via Google are always students (faculty/admin use normal registration)
    $newRole = 'student';
    $dummyPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $ins = mysqli_prepare($conn, 'INSERT INTO users (name, email, password, role, google_sub) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($ins, 'sssss', $name, $email, $dummyPass, $newRole, $googleSub);
    if (!mysqli_stmt_execute($ins)) {
        mysqli_stmt_close($ins);
        header('Location: login.php?error=' . urlencode('Could not create account. This email may already be registered with a password — use Sign In below.'));
        exit;
    }
    $userId = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($ins);
    $userName = $name;
    $userEmail = $email;
    $userRole = $newRole;
}

switch_to_role_session($userRole);
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $userName;
$_SESSION['user_email'] = $userEmail;
$_SESSION['role'] = $userRole;

if ($userRole === 'admin') {
    header('Location: admin_dashboard.php');
} elseif ($userRole === 'faculty') {
    header('Location: faculty_dashboard.php');
} else {
    header('Location: student_dashboard.php');
}
exit;
