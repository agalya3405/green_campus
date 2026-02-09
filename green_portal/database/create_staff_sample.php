<?php
/**
 * One-time script: Create sample staff user and assign sample ideas.
 * Run from browser: http://localhost/green_portal/database/create_staff_sample.php
 */

require_once __DIR__ . '/../config/db.php';

$staff_email = 'staff@college.com';
$staff_password = 'staff123';
$staff_name = 'Campus Staff';

$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'assigned_staff_id'");
if (!$r || mysqli_num_rows($r) === 0) {
    die('Run staff_module_migration.sql first (add assigned_staff_id and staff_remarks to ideas table).');
}

$hash = password_hash($staff_password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'staff') ON DUPLICATE KEY UPDATE password = ?, name = ?, role = 'staff'");
mysqli_stmt_bind_param($stmt, "sssss", $staff_name, $staff_email, $hash, $hash, $staff_name);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$staff_id = null;
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $staff_email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) {
    $staff_id = (int) $row['id'];
}
mysqli_stmt_close($stmt);

if (!$staff_id) {
    die('Could not create or find staff user.');
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
    $stmt = mysqli_prepare($conn, "INSERT INTO ideas (user_id, title, description, category, status, assigned_to, assigned_staff_id) VALUES (?, 'Solar lights in corridors', 'Install solar-powered lights in campus corridors to save energy.', 'Energy', 'Approved', ?, ?)");
    mysqli_stmt_bind_param($stmt, "isi", $sid, $staff_name, $staff_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "INSERT INTO ideas (user_id, title, description, category, status, assigned_to, assigned_staff_id) VALUES (?, 'Rainwater harvesting', 'Collect rainwater for gardening and wash areas.', 'Water', 'Approved', ?, ?)");
    mysqli_stmt_bind_param($stmt, "isi", $sid, $staff_name, $staff_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    $stmt = mysqli_prepare($conn, "UPDATE ideas SET assigned_staff_id = ?, assigned_to = ?, status = IF(status = 'Pending', 'Approved', status) WHERE assigned_staff_id IS NULL LIMIT 3");
    mysqli_stmt_bind_param($stmt, "is", $staff_id, $staff_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

echo "Done. Sample staff user: <strong>" . htmlspecialchars($staff_email) . "</strong> / <strong>" . htmlspecialchars($staff_password) . "</strong> (Role: staff). Log in and you will be redirected to Staff Dashboard.";
if (php_sapi_name() !== 'cli') {
    echo '<br><a href="../login.php">Go to Login</a>';
}
