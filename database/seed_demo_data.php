<?php
/**
 * Demo data seeder for Campus Green Innovation Portal.
 *
 * Run in browser:
 *   http://localhost/green_portal/database/seed_demo_data.php
 *
 * Or from CLI:
 *   php database/seed_demo_data.php
 */

require_once __DIR__ . '/../config/db.php';

mysqli_set_charset($conn, 'utf8mb4');

function seed_find_user_id(mysqli $conn, string $email): ?int
{
    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $id = null;
    if ($row = mysqli_fetch_assoc($res)) {
        $id = (int) $row['id'];
    }
    mysqli_stmt_close($stmt);
    return $id;
}

function seed_upsert_user(mysqli $conn, string $name, string $email, string $password, string $role, int $points = 0): int
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (name, email, password, role, points)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            password = VALUES(password),
            role = VALUES(role),
            points = VALUES(points)"
    );
    if (!$stmt) {
        die('Failed to prepare user seed statement: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $hash, $role, $points);
    if (!mysqli_stmt_execute($stmt)) {
        die('Failed to seed user ' . htmlspecialchars($email) . ': ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    $id = seed_find_user_id($conn, $email);
    if (!$id) {
        die('Could not determine user id for ' . htmlspecialchars($email));
    }
    return $id;
}

function seed_find_problem_id(mysqli $conn, string $title): ?int
{
    $stmt = mysqli_prepare($conn, 'SELECT id FROM problems WHERE title = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $title);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $id = null;
    if ($row = mysqli_fetch_assoc($res)) {
        $id = (int) $row['id'];
    }
    mysqli_stmt_close($stmt);
    return $id;
}

function seed_insert_idea(mysqli $conn, array $idea): void
{
    $existsStmt = mysqli_prepare($conn, 'SELECT id FROM ideas WHERE user_id = ? AND title = ? LIMIT 1');
    if (!$existsStmt) {
        die('Failed to check existing ideas: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($existsStmt, 'is', $idea['user_id'], $idea['title']);
    mysqli_stmt_execute($existsStmt);
    $existsRes = mysqli_stmt_get_result($existsStmt);
    if ($existsRes && mysqli_fetch_assoc($existsRes)) {
        mysqli_stmt_close($existsStmt);
        return;
    }
    mysqli_stmt_close($existsStmt);

    $hasFaculty = array_key_exists('assigned_faculty_id', $idea) && $idea['assigned_faculty_id'] !== null;
    if ($hasFaculty) {
        $sql = "INSERT INTO ideas (
                    user_id, problem_id, title, description, category, status,
                    assigned_to, assigned_faculty_id, progress_percentage,
                    review1_status, review1_remarks,
                    review2_status, review2_remarks,
                    review3_status, review3_remarks,
                    final_review_status, final_review_remarks,
                    admin_remarks, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $types = 'iisssssissssssssss';
    } else {
        $sql = "INSERT INTO ideas (
                    user_id, problem_id, title, description, category, status,
                    assigned_to, progress_percentage,
                    review1_status, review1_remarks,
                    review2_status, review2_remarks,
                    review3_status, review3_remarks,
                    final_review_status, final_review_remarks,
                    admin_remarks, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $types = 'iisssssisssssssss';
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die('Failed to prepare idea seed statement: ' . mysqli_error($conn));
    }

    if ($hasFaculty) {
        mysqli_stmt_bind_param(
            $stmt,
            $types,
            $idea['user_id'],
            $idea['problem_id'],
            $idea['title'],
            $idea['description'],
            $idea['category'],
            $idea['status'],
            $idea['assigned_to'],
            $idea['assigned_faculty_id'],
            $idea['progress_percentage'],
            $idea['review1_status'],
            $idea['review1_remarks'],
            $idea['review2_status'],
            $idea['review2_remarks'],
            $idea['review3_status'],
            $idea['review3_remarks'],
            $idea['final_review_status'],
            $idea['final_review_remarks'],
            $idea['admin_remarks']
        );
    } else {
        mysqli_stmt_bind_param(
            $stmt,
            $types,
            $idea['user_id'],
            $idea['problem_id'],
            $idea['title'],
            $idea['description'],
            $idea['category'],
            $idea['status'],
            $idea['assigned_to'],
            $idea['progress_percentage'],
            $idea['review1_status'],
            $idea['review1_remarks'],
            $idea['review2_status'],
            $idea['review2_remarks'],
            $idea['review3_status'],
            $idea['review3_remarks'],
            $idea['final_review_status'],
            $idea['final_review_remarks'],
            $idea['admin_remarks']
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        die('Failed to seed idea ' . htmlspecialchars($idea['title']) . ': ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
}

function seed_upsert_reward(mysqli $conn, int $student_id, int $points, string $reason): void
{
    $existsStmt = mysqli_prepare($conn, 'SELECT id FROM rewards WHERE student_id = ? AND points = ? AND reason = ? LIMIT 1');
    if (!$existsStmt) {
        die('Failed to check existing rewards: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($existsStmt, 'iis', $student_id, $points, $reason);
    mysqli_stmt_execute($existsStmt);
    $existsRes = mysqli_stmt_get_result($existsStmt);
    if ($existsRes && mysqli_fetch_assoc($existsRes)) {
        mysqli_stmt_close($existsStmt);
        return;
    }
    mysqli_stmt_close($existsStmt);

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO rewards (student_id, points, reason) VALUES (?, ?, ?)'
    );
    if (!$stmt) {
        die('Failed to prepare reward seed statement: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'iis', $student_id, $points, $reason);
    if (!mysqli_stmt_execute($stmt)) {
        die('Failed to seed reward: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
}

// 1) Users
$admin_id = seed_upsert_user($conn, 'Administrator', 'admin@green.com', 'admin123', 'admin', 0);
$faculty1_id = seed_upsert_user($conn, 'Campus Faculty', 'faculty@college.com', 'faculty123', 'faculty', 0);
$faculty2_id = seed_upsert_user($conn, 'Eco Mentor', 'mentor@college.com', 'mentor123', 'faculty', 0);

$students = [
    ['name' => 'Aisha Sultan', 'email' => 'aisha.sultan@college.com', 'password' => 'student123', 'points' => 220],
    ['name' => 'Rahul Verma', 'email' => 'rahul.verma@college.com', 'password' => 'student123', 'points' => 155],
    ['name' => 'Meera Patel', 'email' => 'meera.patel@college.com', 'password' => 'student123', 'points' => 120],
    ['name' => 'Arjun Nair', 'email' => 'arjun.nair@college.com', 'password' => 'student123', 'points' => 65],
    ['name' => 'Sana Khan', 'email' => 'sana.khan@college.com', 'password' => 'student123', 'points' => 40],
    ['name' => 'Imran Shaikh', 'email' => 'imran.shaikh@college.com', 'password' => 'student123', 'points' => 15],
];

$student_ids = [];
foreach ($students as $student) {
    $student_ids[$student['email']] = seed_upsert_user(
        $conn,
        $student['name'],
        $student['email'],
        $student['password'],
        'student',
        $student['points']
    );
}

$seededIdeas = 0;
$seededRewards = 0;

$problemTitles = [
    'Reduce electricity waste in classrooms',
    'Control plastic usage in canteen',
    'Stop water leakage in washrooms',
    'Implement waste segregation system',
    'Reduce paper usage in office',
];

    $problemIds = [];
    foreach ($problemTitles as $title) {
        $pid = seed_find_problem_id($conn, $title);
        if ($pid) {
            $problemIds[] = $pid;
        }
    }

if (count($problemIds) < 5) {
    die('Could not find the default problem statements. Open the site once or run the setup again so problems are created.');
}

    $ideas = [
        [
            'user_email' => 'aisha.sultan@college.com',
            'problem_id' => $problemIds[0],
            'title' => 'Smart classroom power monitoring',
            'description' => 'Install motion sensors and a simple dashboard to switch off lights and fans automatically in empty classrooms.',
            'category' => 'Energy',
            'status' => 'Completed',
            'assigned_to' => 'Campus Faculty',
            'assigned_faculty_id' => $faculty1_id,
            'progress_percentage' => 100,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Feasible and well scoped.',
            'review2_status' => 'Completed',
            'review2_remarks' => 'Prototype approved for pilot.',
            'review3_status' => 'Completed',
            'review3_remarks' => 'Implementation verified.',
            'final_review_status' => 'Completed',
            'final_review_remarks' => 'Ready for campus rollout.',
            'admin_remarks' => 'Pilot implemented in Block A.',
        ],
        [
            'user_email' => 'rahul.verma@college.com',
            'problem_id' => $problemIds[1],
            'title' => 'Reusable canteen kit program',
            'description' => 'Introduce a reusable plate and cup return system with a small deposit so single-use plastic can be reduced.',
            'category' => 'Waste',
            'status' => 'In Progress',
            'assigned_to' => 'Eco Mentor',
            'assigned_faculty_id' => $faculty2_id,
            'progress_percentage' => 66,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Strong sustainability impact.',
            'review2_status' => 'Completed',
            'review2_remarks' => 'Vendor coordination in progress.',
            'review3_status' => 'Pending',
            'review3_remarks' => '',
            'final_review_status' => 'Pending',
            'final_review_remarks' => '',
            'admin_remarks' => '',
        ],
        [
            'user_email' => 'meera.patel@college.com',
            'problem_id' => $problemIds[2],
            'title' => 'Leak reporting with QR stickers',
            'description' => 'Place QR stickers near taps and flush tanks so students can report leaks immediately to the maintenance team.',
            'category' => 'Water',
            'status' => 'Approved',
            'assigned_to' => 'Campus Faculty',
            'assigned_faculty_id' => $faculty1_id,
            'progress_percentage' => 33,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Low-cost and easy to deploy.',
            'review2_status' => 'Pending',
            'review2_remarks' => '',
            'review3_status' => 'Pending',
            'review3_remarks' => '',
            'final_review_status' => 'Pending',
            'final_review_remarks' => '',
            'admin_remarks' => 'Approved for maintenance review.',
        ],
        [
            'user_email' => 'arjun.nair@college.com',
            'problem_id' => $problemIds[3],
            'title' => 'Colour-coded waste bins and signage',
            'description' => 'Install clearly labelled bins for wet waste, dry waste, and recyclables with poster guidance in each block.',
            'category' => 'Waste',
            'status' => 'Pending',
            'assigned_to' => '',
            'assigned_faculty_id' => null,
            'progress_percentage' => 0,
            'review1_status' => 'Pending',
            'review1_remarks' => '',
            'review2_status' => 'Pending',
            'review2_remarks' => '',
            'review3_status' => 'Pending',
            'review3_remarks' => '',
            'final_review_status' => 'Pending',
            'final_review_remarks' => '',
            'admin_remarks' => '',
        ],
        [
            'user_email' => 'sana.khan@college.com',
            'problem_id' => $problemIds[4],
            'title' => 'Paperless approvals with digital signatures',
            'description' => 'Replace routine paper forms with a simple digital approval workflow for notices, requests, and internal approvals.',
            'category' => 'General',
            'status' => 'Rejected',
            'assigned_to' => 'Eco Mentor',
            'assigned_faculty_id' => $faculty2_id,
            'progress_percentage' => 20,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Good idea but needs rollout plan.',
            'review2_status' => 'Pending',
            'review2_remarks' => '',
            'review3_status' => 'Pending',
            'review3_remarks' => '',
            'final_review_status' => 'Pending',
            'final_review_remarks' => '',
            'admin_remarks' => 'Rejected due to implementation constraints.',
        ],
        [
            'user_email' => 'imran.shaikh@college.com',
            'problem_id' => $problemIds[0],
            'title' => 'Motion sensor lights in unused rooms',
            'description' => 'Use motion sensor lighting in labs, corridors, and store rooms to reduce electricity usage after class hours.',
            'category' => 'Energy',
            'status' => 'Completed',
            'assigned_to' => 'Campus Faculty',
            'assigned_faculty_id' => $faculty1_id,
            'progress_percentage' => 100,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Implementation is practical.',
            'review2_status' => 'Completed',
            'review2_remarks' => 'Energy savings validated.',
            'review3_status' => 'Completed',
            'review3_remarks' => 'Final review passed.',
            'final_review_status' => 'Completed',
            'final_review_remarks' => 'Completed successfully.',
            'admin_remarks' => 'Installed in the library wing.',
        ],
        [
            'user_email' => 'aisha.sultan@college.com',
            'problem_id' => $problemIds[1],
            'title' => 'Reusable dishware deposit system',
            'description' => 'Create a small deposit-based reusable dishware exchange so disposable cups and plates are not needed.',
            'category' => 'Waste',
            'status' => 'In Progress',
            'assigned_to' => 'Eco Mentor',
            'assigned_faculty_id' => $faculty2_id,
            'progress_percentage' => 66,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Good student engagement opportunity.',
            'review2_status' => 'Completed',
            'review2_remarks' => 'Campus vendor discussion ongoing.',
            'review3_status' => 'Pending',
            'review3_remarks' => '',
            'final_review_status' => 'Pending',
            'final_review_remarks' => '',
            'admin_remarks' => '',
        ],
        [
            'user_email' => 'rahul.verma@college.com',
            'problem_id' => $problemIds[2],
            'title' => 'Low-cost valve repair kit',
            'description' => 'Prepare a maintenance kit and checklist for quick repair of leaking taps and flush systems in washrooms.',
            'category' => 'Water',
            'status' => 'Completed',
            'assigned_to' => 'Campus Faculty',
            'assigned_faculty_id' => $faculty1_id,
            'progress_percentage' => 100,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Simple and very practical.',
            'review2_status' => 'Completed',
            'review2_remarks' => 'Facilities team approved.',
            'review3_status' => 'Completed',
            'review3_remarks' => 'Great impact on water use.',
            'final_review_status' => 'Completed',
            'final_review_remarks' => 'Implementation completed.',
            'admin_remarks' => 'Maintenance team adopted the kit.',
        ],
        [
            'user_email' => 'meera.patel@college.com',
            'problem_id' => $problemIds[3],
            'title' => 'Waste segregation ambassadors',
            'description' => 'Appoint student volunteers in each block to guide proper segregation and keep the bin areas tidy.',
            'category' => 'Waste',
            'status' => 'Approved',
            'assigned_to' => 'Eco Mentor',
            'assigned_faculty_id' => $faculty2_id,
            'progress_percentage' => 33,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Strong awareness angle.',
            'review2_status' => 'Pending',
            'review2_remarks' => '',
            'review3_status' => 'Pending',
            'review3_remarks' => '',
            'final_review_status' => 'Pending',
            'final_review_remarks' => '',
            'admin_remarks' => 'Approved for pilot run.',
        ],
        [
            'user_email' => 'arjun.nair@college.com',
            'problem_id' => $problemIds[4],
            'title' => 'Digital notice board and e-signatures',
            'description' => 'Use a digital notice board and electronic signatures for routine office approvals to cut paper use.',
            'category' => 'General',
            'status' => 'Completed',
            'assigned_to' => 'Campus Faculty',
            'assigned_faculty_id' => $faculty1_id,
            'progress_percentage' => 100,
            'review1_status' => 'Completed',
            'review1_remarks' => 'Easy to adopt.',
            'review2_status' => 'Completed',
            'review2_remarks' => 'IT support confirmed.',
            'review3_status' => 'Completed',
            'review3_remarks' => 'Final review passed.',
            'final_review_status' => 'Completed',
            'final_review_remarks' => 'Completed successfully.',
            'admin_remarks' => 'Paper usage reduced in admin office.',
        ],
    ];

foreach ($ideas as $idea) {
    $idea['user_id'] = $student_ids[$idea['user_email']];
    $before = (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS c FROM ideas'))['c'];
    seed_insert_idea($conn, $idea);
    $after = (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS c FROM ideas'))['c'];
    if ($after > $before) {
        $seededIdeas++;
    }
}

    // 3) Rewards
    $rewardRows = [
        ['email' => 'aisha.sultan@college.com', 'points' => 50, 'reason' => 'Implementation Completed'],
        ['email' => 'aisha.sultan@college.com', 'points' => 100, 'reason' => 'Marked as Best Idea of Month'],
        ['email' => 'rahul.verma@college.com', 'points' => 50, 'reason' => 'Implementation Completed'],
        ['email' => 'meera.patel@college.com', 'points' => 20, 'reason' => 'Approved Idea Bonus'],
        ['email' => 'imran.shaikh@college.com', 'points' => 50, 'reason' => 'Implementation Completed'],
        ['email' => 'arjun.nair@college.com', 'points' => 50, 'reason' => 'Implementation Completed'],
    ];

foreach ($rewardRows as $reward) {
    $before = (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS c FROM rewards'))['c'];
    seed_upsert_reward($conn, $student_ids[$reward['email']], $reward['points'], $reward['reason']);
    $after = (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS c FROM rewards'))['c'];
    if ($after > $before) {
        $seededRewards++;
    }
}

// 4) Output summary
$u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"));
$i = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM ideas"));
$r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM rewards"));
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM problems"));

$message = [
    'Seed complete.',
    'Users: ' . (int) ($u['c'] ?? 0),
    'Problems: ' . (int) ($p['c'] ?? 0),
    'Ideas: ' . (int) ($i['c'] ?? 0),
    'Rewards: ' . (int) ($r['c'] ?? 0),
    'New ideas inserted this run: ' . $seededIdeas,
    'New rewards inserted this run: ' . $seededRewards,
];

if (php_sapi_name() === 'cli') {
    echo implode(PHP_EOL, $message) . PHP_EOL;
} else {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Demo Data Seeded</title>';
    echo '<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 16px;line-height:1.6} .box{background:#f6f9f6;border:1px solid #d7e7d7;border-radius:12px;padding:20px}</style>';
    echo '</head><body><div class="box">';
    echo '<h1>Demo data seeded</h1>';
    echo '<ul>';
    foreach ($message as $line) {
        echo '<li>' . htmlspecialchars($line) . '</li>';
    }
    echo '</ul>';
    echo '<p>Open <a href="../login.php">Login</a> or refresh the dashboards to see the data.</p>';
    echo '</div></body></html>';
}
