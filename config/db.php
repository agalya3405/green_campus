<?php
/**
 * Database connection using mysqli
 * Campus Green Innovation Portal
 *
 * For InfinityFree: set $host, $user, $pass, $dbname, $port here or keep a
 * separate copy on the server only (do not commit real passwords to Git).
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'green_innovation';
$port = 3307;

// PHP 8.1+ mysqli may throw mysqli_sql_exception on query errors; that becomes HTTP 500 on shared hosts
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

// --- STRUCTURAL UPDATE: Problems + problem_id on ideas, auto-seeding ---
// 1) Ensure problems table exists
$createProblemsSql = "
CREATE TABLE IF NOT EXISTS problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $createProblemsSql);

// 2) Ensure ideas.problem_id column exists (project uses user_id as student id)
$checkProblemCol = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE 'problem_id'");
if ($checkProblemCol && mysqli_num_rows($checkProblemCol) === 0) {
    @mysqli_query($conn, "ALTER TABLE ideas ADD COLUMN problem_id INT NULL AFTER user_id");
}

// 2b) Ensure all required columns exist on ideas table
$requiredIdeasCols = [
    'assigned_faculty_id' => 'INT DEFAULT NULL',
    'faculty_remarks'     => 'TEXT DEFAULT NULL',
    'admin_remarks'       => 'TEXT DEFAULT NULL',
    'progress_percentage' => 'INT DEFAULT 0',
    'review1_status'      => "VARCHAR(20) DEFAULT 'Pending'",
    'review1_remarks'     => 'TEXT',
    'review2_status'      => "VARCHAR(20) DEFAULT 'Pending'",
    'review2_remarks'     => 'TEXT',
    'final_review_status' => "VARCHAR(20) DEFAULT 'Pending'",
    'final_review_remarks'=> 'TEXT',
    'review3_status'      => "VARCHAR(20) DEFAULT 'Pending'",
    'review3_remarks'     => 'TEXT',
];
foreach ($requiredIdeasCols as $colName => $colDef) {
    $chk = mysqli_query($conn, "SHOW COLUMNS FROM ideas LIKE '$colName'");
    if ($chk && mysqli_num_rows($chk) === 0) {
        @mysqli_query($conn, "ALTER TABLE ideas ADD COLUMN $colName $colDef");
    }
}

// 2c) Ensure users.points column exists for reward system
$chkPts = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'points'");
if ($chkPts && mysqli_num_rows($chkPts) === 0) {
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN points INT DEFAULT 0");
}

// 2d) Google Sign-In (OAuth) — stable Google account id
$chkG = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'google_sub'");
if ($chkG && mysqli_num_rows($chkG) === 0) {
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN google_sub VARCHAR(255) NULL DEFAULT NULL");
    @mysqli_query($conn, "CREATE UNIQUE INDEX idx_users_google_sub ON users (google_sub)");
}

// 3) Seed default problems if table is empty
$resProblemsCount = mysqli_query($conn, "SELECT COUNT(*) AS c FROM problems");
if ($resProblemsCount) {
    $rowCount = mysqli_fetch_assoc($resProblemsCount);
    $count = (int)($rowCount['c'] ?? 0);
    if ($count === 0) {
        $defaults = [
            [
                'title' => 'Reduce electricity waste in classrooms',
                'description' => "Many classrooms keep lights, fans and projectors ON even when they are empty.\nPropose a sustainable monitoring or automation solution to reduce electricity waste."
            ],
            [
                'title' => 'Control plastic usage in canteen',
                'description' => "Single-use plastic plates, cups and spoons are heavily used in the canteen.\nSuggest eco-friendly alternatives and a practical implementation strategy."
            ],
            [
                'title' => 'Stop water leakage in washrooms',
                'description' => "Leaking taps and flush systems cause continuous water wastage in campus washrooms.\nRecommend low-cost monitoring or repair mechanisms to reduce leakage."
            ],
            [
                'title' => 'Implement waste segregation system',
                'description' => "Waste is often mixed without proper separation of recyclable and non-recyclable items.\nDesign a clear, practical waste segregation system for the campus."
            ],
            [
                'title' => 'Reduce paper usage in office',
                'description' => "Administrative departments rely heavily on printed documents and forms.\nPropose digital alternatives and awareness strategies to minimise paper usage."
            ],
        ];
        $stmt = mysqli_prepare($conn, "INSERT INTO problems (title, description) VALUES (?, ?)");
        if ($stmt) {
            foreach ($defaults as $p) {
                mysqli_stmt_bind_param($stmt, "ss", $p['title'], $p['description']);
                mysqli_stmt_execute($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// 4) Ensure rewards table exists for tracking student rewards
// No FOREIGN KEY: shared hosts often use MyISAM for users/ideas; FK to MyISAM fails (errno 150).
$createRewardsSql = "
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    points INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_rewards_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $createRewardsSql);
