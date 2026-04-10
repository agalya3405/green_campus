<?php
/**
 * One-time script: Create sample faculty user and assign sample ideas.
 * Run from browser: http://localhost/green_portal/database/create_faculty_sample.php
 */

require_once __DIR__ . '/../config/db.php';

$faculty_email = 'faculty@college.com';
$faculty_password = 'faculty123';
$faculty_name = 'Campus Faculty';

$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'assigned_faculty_id'");
if (!$r || mysqli_num_rows($r) === 0) {
    die('Run faculty_module_migration.sql first (add assigned_faculty_id and faculty_remarks to ideas table).');
}

$hash = password_hash($faculty_password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'faculty') ON DUPLICATE KEY UPDATE password = ?, name = ?, role = 'faculty'");
mysqli_stmt_bind_param($stmt, "sssss", $faculty_name, $faculty_email, $hash, $hash, $faculty_name);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$faculty_id = null;
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $faculty_email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) {
    $faculty_id = (int) $row['id'];
}
mysqli_stmt_close($stmt);

if (!$faculty_id) {
    die('Could not create or find faculty user.');
}

$count = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM ideas"))['c'];
if ($count === 0) {
    $submitter_hash = password_hash('student1', PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO users (name, email, password, role) VALUES ('Sample Student', 'student@college.com', ?, 'student')");
    mysqli_stmt_bind_param($stmt, "s", $submitter_hash);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $sid = 1;
    $r = mysqli_query($conn, "SELECT id FROM users WHERE email = 'student@college.com' LIMIT 1");
    if ($row = mysqli_fetch_assoc($r)) {
        $sid = (int) $row['id'];
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO ideas (user_id, title, description, category, status, assigned_to, assigned_faculty_id) VALUES (?, 'Solar lights in corridors', 'Install solar-powered lights in campus corridors to save energy.', 'Energy', 'Approved', ?, ?)");
    mysqli_stmt_bind_param($stmt, "isi", $sid, $faculty_name, $faculty_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "INSERT INTO ideas (user_id, title, description, category, status, assigned_to, assigned_faculty_id) VALUES (?, 'Rainwater harvesting', 'Collect rainwater for gardening and wash areas.', 'Water', 'Approved', ?, ?)");
    mysqli_stmt_bind_param($stmt, "isi", $sid, $faculty_name, $faculty_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    $stmt = mysqli_prepare($conn, "UPDATE ideas SET assigned_faculty_id = ?, assigned_to = ?, status = IF(status = 'Pending', 'Approved', status) WHERE assigned_faculty_id IS NULL LIMIT 3");
    mysqli_stmt_bind_param($stmt, "is", $faculty_id, $faculty_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

echo "Done. Sample faculty user: <strong>" . htmlspecialchars($faculty_email) . "</strong> / <strong>" . htmlspecialchars($faculty_password) . "</strong> (Role: faculty). Log in and you will be redirected to Faculty Dashboard.";
if (php_sapi_name() !== 'cli') {
    echo '<br><a href="../login.php">Go to Login</a>';
}
