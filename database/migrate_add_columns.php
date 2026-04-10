<?php
/**
 * One-time migration: add admin_remarks and updated_at to ideas table if missing.
 * Open in browser once: http://localhost:8080/database/migrate_add_columns.php
 */
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/html; charset=utf-8');

$done = [];
$errors = [];

$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'admin_remarks'");
if (!$r || mysqli_num_rows($r) === 0) {
    if (mysqli_query($conn, "ALTER TABLE ideas ADD COLUMN admin_remarks TEXT NULL")) {
        $done[] = 'Added column admin_remarks';
    } else {
        $errors[] = 'admin_remarks: ' . mysqli_error($conn);
    }
} else {
    $done[] = 'Column admin_remarks already exists';
}

$r = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'updated_at'");
if (!$r || mysqli_num_rows($r) === 0) {
    if (mysqli_query($conn, "ALTER TABLE ideas ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP")) {
        $done[] = 'Added column updated_at';
    } else {
        $errors[] = 'updated_at: ' . mysqli_error($conn);
    }
} else {
    $done[] = 'Column updated_at already exists';
}

echo '<h2>Migration result</h2>';
if (!empty($done)) {
    echo '<ul>';
    foreach ($done as $d) echo '<li>' . htmlspecialchars($d) . '</li>';
    echo '</ul>';
}
if (!empty($errors)) {
    echo '<p style="color:red;">Errors:</p><ul>';
    foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>';
    echo '</ul>';
}
echo '<p><a href="../login.php">Go to Login</a></p>';
