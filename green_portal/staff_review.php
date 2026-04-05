<?php
require_once 'config/session.php';
start_role_session('faculty');
require_once 'config/db.php';

/** Treat both legacy 'Done' and ENUM 'Completed' as finished */
function review_is_done($status) {
    $s = trim((string) ($status ?? ''));
    return $s === 'Done' || $s === 'Completed';
}

// Strict Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: login.php');
    exit;
}

$faculty_id = (int) $_SESSION['user_id'];
$faculty_name = $_SESSION['user_name'];

// ─── Handle POST Actions (then redirect — PRG pattern) ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $idea_id = isset($_POST['idea_id']) ? (int)$_POST['idea_id'] : 0;
    $ok = false;
    $err = '';

    if ($idea_id <= 0) {
        $err = 'Invalid idea.';
    } elseif ($action === 'review1') {
        $remarks = trim($_POST['review1_remarks'] ?? '');
        if ($remarks === '') { $err = 'Remarks are required for Review 1.'; }
        else {
            // Store 'Completed' (ENUM-friendly); WHERE accepts legacy 'Done' too
            $stmt = mysqli_prepare($conn,
                "UPDATE ideas SET review1_status='Completed', review1_remarks=?, progress_percentage=GREATEST(COALESCE(progress_percentage,0), 33), updated_at=NOW()
                 WHERE id=? AND assigned_faculty_id=?
                   AND (review1_status IS NULL OR review1_status='' OR review1_status='Pending' OR review1_status NOT IN ('Done','Completed'))");
            mysqli_stmt_bind_param($stmt, "sii", $remarks, $idea_id, $faculty_id);
            $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
            mysqli_stmt_close($stmt);
            if (!$ok) $err = 'Review 1 could not be saved.';
        }
    } elseif ($action === 'review2') {
        $remarks = trim($_POST['review2_remarks'] ?? '');
        if ($remarks === '') { $err = 'Remarks are required for Review 2.'; }
        else {
            $stmt = mysqli_prepare($conn,
                "UPDATE ideas SET review2_status='Completed', review2_remarks=?, progress_percentage=GREATEST(COALESCE(progress_percentage,0), 66), updated_at=NOW()
                 WHERE id=? AND assigned_faculty_id=?
                   AND review1_status IN ('Done','Completed')
                   AND (review2_status IS NULL OR review2_status='' OR review2_status='Pending' OR review2_status NOT IN ('Done','Completed'))");
            mysqli_stmt_bind_param($stmt, "sii", $remarks, $idea_id, $faculty_id);
            $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
            mysqli_stmt_close($stmt);
            if (!$ok) $err = 'Review 2 could not be saved. Complete Review 1 first.';
        }
    } elseif ($action === 'review3') {
        $remarks = trim($_POST['review3_remarks'] ?? '');
        if ($remarks === '') { $err = 'Remarks are required for Final Review.'; }
        else {
            $stmt = mysqli_prepare($conn,
                "UPDATE ideas SET review3_status='Completed', review3_remarks=?, final_review_status='Completed', final_review_remarks=?,
                        progress_percentage=100, status='Completed', faculty_remarks=?, updated_at=NOW()
                 WHERE id=? AND assigned_faculty_id=?
                   AND review1_status IN ('Done','Completed') AND review2_status IN ('Done','Completed')
                   AND (review3_status IS NULL OR review3_status='' OR review3_status='Pending' OR review3_status NOT IN ('Done','Completed'))");
            mysqli_stmt_bind_param($stmt, "sssii", $remarks, $remarks, $remarks, $idea_id, $faculty_id);
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                mysqli_stmt_close($stmt);
                // +50 points
                $pts = mysqli_prepare($conn, "UPDATE users SET points=COALESCE(points,0)+50 WHERE id=(SELECT user_id FROM ideas WHERE id=?)");
                mysqli_stmt_bind_param($pts, "i", $idea_id);
                mysqli_stmt_execute($pts); mysqli_stmt_close($pts);
                // Log reward
                $log = mysqli_prepare($conn, "INSERT INTO rewards (student_id,points,reason) SELECT user_id,50,'Implementation Completed' FROM ideas WHERE id=?");
                mysqli_stmt_bind_param($log, "i", $idea_id);
                mysqli_stmt_execute($log); mysqli_stmt_close($log);
                $ok = true;
            } else {
                $err = 'Final Review could not be saved. Complete Reviews 1 & 2 first.';
                mysqli_stmt_close($stmt);
            }
        }
    }

    // Redirect back (PRG)
    $redir = 'staff_review.php';
    if ($idea_id > 0) $redir .= '?idea_id=' . $idea_id;
    if ($ok)  $redir .= (strpos($redir,'?')!==false?'&':'?') . 'success=1&msg=' . urlencode('Review saved successfully!');
    if ($err) $redir .= (strpos($redir,'?')!==false?'&':'?') . 'error=' . urlencode($err);
    header('Location: ' . $redir);
    exit;
}

// ─── Read flash messages ─────────────────────────────────────────────
$success_msg = ($_GET['success'] ?? '') === '1' ? ($_GET['msg'] ?? 'Done!') : '';
$error_msg   = $_GET['error'] ?? '';

// ─── DETAIL VIEW (single idea) ──────────────────────────────────────
$detail_mode = isset($_GET['idea_id']) && (int)$_GET['idea_id'] > 0;
$idea = null;

if ($detail_mode) {
    $idea_id = (int)$_GET['idea_id'];
    $sql = "SELECT i.*, COALESCE(p.title, i.title) AS problem_title, u.name AS student_name
            FROM ideas i
            LEFT JOIN users u ON u.id=i.user_id
            LEFT JOIN problems p ON p.id=i.problem_id
            WHERE i.id=? AND i.assigned_faculty_id=?";
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, "ii", $idea_id, $faculty_id);
    mysqli_stmt_execute($st);
    $idea = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    if (!$idea) { header('Location: staff_review.php'); exit; }
}

// ─── LIST VIEW — fetch all assigned ideas ────────────────────────────
$ideas_result = null;
if (!$detail_mode) {
    $sql = "SELECT i.id,
                   COALESCE(p.title, i.title) AS problem_title,
                   u.name AS student_name,
                   i.description AS solution_text,
                   i.review1_status, i.review2_status, i.review3_status,
                   i.progress_percentage, i.status
            FROM ideas i
            LEFT JOIN users u ON u.id=i.user_id
            LEFT JOIN problems p ON p.id=i.problem_id
            WHERE i.assigned_faculty_id=?
            ORDER BY i.updated_at DESC";
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, "i", $faculty_id);
    mysqli_stmt_execute($st);
    $ideas_result = mysqli_stmt_get_result($st);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $detail_mode ? 'Review Idea' : 'Review System'; ?> - Campus Green Innovation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ── Progress Bar ── */
        .progress-bar-wrap {
            background:#E0E0E0; border-radius:10px; overflow:hidden;
            height:22px; min-width:110px;
        }
        .progress-bar-fill {
            height:100%; border-radius:10px; text-align:center;
            color:#fff; font-size:.75rem; font-weight:700; line-height:22px;
            transition:width .4s ease;
        }
        .p-0   { width:0%;   background:#BDBDBD; }
        .p-33  { width:33%;  background:#FF9800; }
        .p-66  { width:66%;  background:#2196F3; }
        .p-100 { width:100%; background:#4CAF50; }

        /* ── Review badges ── */
        .r-badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:.78rem; font-weight:600; }
        .r-done    { background:#E8F5E9; color:#2E7D32; }
        .r-pending { background:#FFF3E0; color:#E65100; }
        .r-locked  { background:#F5F5F5; color:#9E9E9E; }

        /* ── Detail card ── */
        .detail-card { background:#fff; border:1px solid var(--border-color); border-radius:var(--radius); padding:1.5rem; margin-bottom:1.2rem; box-shadow:var(--shadow-sm); }
        .detail-card h3 { margin-bottom:.8rem; font-size:1.1rem; }
        .detail-label { font-weight:600; color:var(--text-muted); font-size:.85rem; text-transform:uppercase; letter-spacing:.4px; margin-bottom:.25rem; }
        .detail-value { font-size:.95rem; line-height:1.55; margin-bottom:1rem; white-space:pre-wrap; }

        /* ── Review steps ── */
        .review-step { border:1px solid var(--border-color); border-radius:var(--radius); margin-bottom:1rem; overflow:hidden; transition:box-shadow .2s; }
        .review-step.active { border-color:var(--primary-color); box-shadow:0 0 0 2px rgba(46,125,50,.15); }
        .review-step.locked { opacity:.55; }
        .step-header {
            display:flex; align-items:center; gap:.7rem; padding:1rem 1.2rem;
            background:#FAFAFA; font-weight:600; font-size:.95rem;
        }
        .step-header .step-icon { font-size:1.2rem; }
        .step-body { padding:1rem 1.2rem; }
        .step-body textarea {
            width:100%; min-height:70px; padding:.55rem .7rem;
            border:1px solid var(--border-color); border-radius:6px;
            font-family:inherit; font-size:.88rem; resize:vertical; margin-bottom:.5rem;
        }
        .step-body textarea:focus { outline:none; border-color:var(--primary-color); box-shadow:0 0 0 2px rgba(46,125,50,.12); }
        .step-remarks { background:#F5F5F5; padding:.7rem 1rem; border-radius:6px; font-size:.88rem; line-height:1.5; white-space:pre-wrap; }

        /* ── Buttons ── */
        .btn-continue { background:#FF9800; color:#fff; padding:.35rem .75rem; border-radius:6px; font-size:.82rem; font-weight:600; text-decoration:none; display:inline-block; }
        .btn-continue:hover { background:#F57C00; }
        .btn-view-done { background:#4CAF50; color:#fff; padding:.35rem .75rem; border-radius:6px; font-size:.82rem; font-weight:600; text-decoration:none; display:inline-block; }
        .btn-view-done:hover { background:#388E3C; }
        .btn-back { display:inline-flex; align-items:center; gap:.4rem; color:var(--text-muted); text-decoration:none; font-weight:500; margin-bottom:1rem; }
        .btn-back:hover { color:var(--text-main); }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header"><span class="brand-name">Green Campus</span></div>
            <nav class="sidebar-nav">
                <a href="faculty_dashboard.php" class="nav-item">Dashboard</a>
                <a href="staff_review.php" class="nav-item active">Review System</a>
                <a href="staff_assigned_tasks.php" class="nav-item">Assigned Tasks</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php?role=faculty" class="nav-item" style="color:#D32F2F;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1 class="page-title"><?php echo $detail_mode ? 'Review Idea' : 'Review System'; ?></h1>
                <div class="user-profile"><div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($faculty_name); ?></span>
                    <span class="user-role">Faculty Member</span>
                </div></div>
            </header>

            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

<?php if ($detail_mode && $idea): // ═══════════ DETAIL VIEW ═══════════ ?>
            <a href="staff_review.php" class="btn-back">← Back to Review List</a>

            <?php
                // Normalize: 'Done' or 'Completed' both unlock next step
                $r1 = review_is_done($idea['review1_status'] ?? '') ? 'Done' : 'Pending';
                $r2 = review_is_done($idea['review2_status'] ?? '') ? 'Done' : 'Pending';
                $r3 = review_is_done($idea['review3_status'] ?? '') ? 'Done' : 'Pending';
                $pct = 0;
                if ($r1==='Done') $pct += 33;
                if ($r2==='Done') $pct += 33;
                if ($r3==='Done') $pct += 34;
                if ($pct>=100)    $pc = 'p-100';
                elseif ($pct>=66) $pc = 'p-66';
                elseif ($pct>=33) $pc = 'p-33';
                else              $pc = 'p-0';
            ?>

            <!-- Idea Details Card -->
            <div class="detail-card">
                <h3>Idea Details</h3>
                <div class="detail-label">Problem Title</div>
                <div class="detail-value"><?php echo htmlspecialchars($idea['problem_title'] ?? 'N/A'); ?></div>

                <div class="detail-label">Student Name</div>
                <div class="detail-value"><?php echo htmlspecialchars($idea['student_name'] ?? 'Unknown'); ?></div>

                <div class="detail-label">Student Solution (Full View)</div>
                <div class="detail-value"><?php echo htmlspecialchars($idea['description'] ?? '—'); ?></div>

                <div class="detail-label">Admin Remarks</div>
                <div class="detail-value"><?php echo htmlspecialchars($idea['admin_remarks'] ?? '—'); ?></div>

                <div style="display:flex; align-items:center; gap:1rem;">
                    <div class="detail-label" style="margin-bottom:0">Progress</div>
                    <div class="progress-bar-wrap" style="flex:1; max-width:300px;">
                        <div class="progress-bar-fill <?php echo $pc; ?>"><?php echo $pct; ?>%</div>
                    </div>
                    <span class="badge badge-<?php echo strtolower(str_replace(' ','',$idea['status'])); ?>">
                        <?php echo htmlspecialchars($idea['status']); ?>
                    </span>
                </div>
            </div>

            <!-- ── Review Step 1 ── -->
            <?php
                $s1_active  = ($r1==='Pending');
                $s1_done    = ($r1==='Done');
                $s1_class   = $s1_done ? '' : ($s1_active ? 'active' : 'locked');
            ?>
            <div class="review-step <?php echo $s1_class; ?>">
                <div class="step-header">
                    <span class="step-icon"><?php echo $s1_done ? '✅' : ($s1_active ? '⏳' : '🔒'); ?></span>
                    Review 1
                    <span class="r-badge <?php echo $s1_done ? 'r-done' : ($s1_active ? 'r-pending' : 'r-locked'); ?>">
                        <?php echo $s1_done ? 'Completed' : ($s1_active ? 'Pending' : 'Locked'); ?>
                    </span>
                </div>
                <?php if ($s1_done): ?>
                    <div class="step-body">
                        <div class="step-remarks"><?php echo htmlspecialchars($idea['review1_remarks'] ?? ''); ?></div>
                    </div>
                <?php elseif ($s1_active): ?>
                    <div class="step-body">
                        <form method="POST">
                            <input type="hidden" name="idea_id" value="<?php echo (int)$idea['id']; ?>">
                            <input type="hidden" name="action" value="review1">
                            <textarea name="review1_remarks" placeholder="Enter Review 1 remarks…" required></textarea>
                            <button type="submit" class="btn btn-sm btn-primary">Submit Review 1</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Review Step 2 ── -->
            <?php
                $s2_active = ($r1==='Done' && $r2==='Pending');
                $s2_done   = ($r2==='Done');
                $s2_locked = (!$s2_active && !$s2_done);
                $s2_class  = $s2_done ? '' : ($s2_active ? 'active' : 'locked');
            ?>
            <div class="review-step <?php echo $s2_class; ?>">
                <div class="step-header">
                    <span class="step-icon"><?php echo $s2_done ? '✅' : ($s2_active ? '⏳' : '🔒'); ?></span>
                    Review 2
                    <span class="r-badge <?php echo $s2_done ? 'r-done' : ($s2_active ? 'r-pending' : 'r-locked'); ?>">
                        <?php echo $s2_done ? 'Completed' : ($s2_active ? 'Pending' : 'Locked'); ?>
                    </span>
                </div>
                <?php if ($s2_done): ?>
                    <div class="step-body">
                        <div class="step-remarks"><?php echo htmlspecialchars($idea['review2_remarks'] ?? ''); ?></div>
                    </div>
                <?php elseif ($s2_active): ?>
                    <div class="step-body">
                        <form method="POST">
                            <input type="hidden" name="idea_id" value="<?php echo (int)$idea['id']; ?>">
                            <input type="hidden" name="action" value="review2">
                            <textarea name="review2_remarks" placeholder="Enter Review 2 remarks…" required></textarea>
                            <button type="submit" class="btn btn-sm btn-primary">Submit Review 2</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Review Step 3 (Final) ── -->
            <?php
                $s3_active = ($r1==='Done' && $r2==='Done' && $r3==='Pending');
                $s3_done   = ($r3==='Done');
                $s3_locked = (!$s3_active && !$s3_done);
                $s3_class  = $s3_done ? '' : ($s3_active ? 'active' : 'locked');
            ?>
            <div class="review-step <?php echo $s3_class; ?>">
                <div class="step-header">
                    <span class="step-icon"><?php echo $s3_done ? '✅' : ($s3_active ? '⏳' : '🔒'); ?></span>
                    Review 3 — Final Review
                    <span class="r-badge <?php echo $s3_done ? 'r-done' : ($s3_active ? 'r-pending' : 'r-locked'); ?>">
                        <?php echo $s3_done ? 'Completed' : ($s3_active ? 'Pending' : 'Locked'); ?>
                    </span>
                </div>
                <?php if ($s3_done): ?>
                    <div class="step-body">
                        <div class="step-remarks"><?php echo htmlspecialchars($idea['review3_remarks'] ?? ''); ?></div>
                        <div style="margin-top:.8rem;">
                            <span class="badge badge-completed" style="font-size:.9rem; padding:.4rem 1rem;">
                                ✓ Idea Successfully Reviewed and Completed
                            </span>
                        </div>
                    </div>
                <?php elseif ($s3_active): ?>
                    <div class="step-body">
                        <form method="POST">
                            <input type="hidden" name="idea_id" value="<?php echo (int)$idea['id']; ?>">
                            <input type="hidden" name="action" value="review3">
                            <textarea name="review3_remarks" placeholder="Enter Final Review remarks…" required></textarea>
                            <button type="submit" class="btn btn-sm btn-success">Submit Final Review</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

<?php else: // ═══════════ LIST VIEW ═══════════ ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Assigned Ideas — 3-Stage Review</h2>
                </div>

                <?php if (!$ideas_result || mysqli_num_rows($ideas_result) === 0): ?>
                    <p class="empty-state">No ideas have been assigned to you yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Problem Title</th>
                                    <th>Student Name</th>
                                    <th>Solution</th>
                                    <th>Review 1</th>
                                    <th>Review 2</th>
                                    <th>Review 3</th>
                                    <th>Progress</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($ideas_result)):
                                    $r1 = review_is_done($row['review1_status'] ?? '') ? 'Done' : 'Pending';
                                    $r2 = review_is_done($row['review2_status'] ?? '') ? 'Done' : 'Pending';
                                    $r3 = review_is_done($row['review3_status'] ?? '') ? 'Done' : 'Pending';
                                    $pct = 0;
                                    if ($r1==='Done') $pct += 33;
                                    if ($r2==='Done') $pct += 33;
                                    if ($r3==='Done') $pct += 34;
                                    if ($pct>=100)    $pc='p-100';
                                    elseif ($pct>=66) $pc='p-66';
                                    elseif ($pct>=33) $pc='p-33';
                                    else              $pc='p-0';
                                    $all_done = ($r3==='Done');
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['problem_title'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name'] ?? 'Unknown'); ?></td>
                                        <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                            title="<?php echo htmlspecialchars($row['solution_text']); ?>">
                                            <?php echo htmlspecialchars($row['solution_text']); ?>
                                        </td>
                                        <td><span class="r-badge <?php echo $r1==='Done'?'r-done':'r-pending'; ?>"><?php echo $r1==='Done'?'✓ Done':'Pending'; ?></span></td>
                                        <td><span class="r-badge <?php echo $r2==='Done'?'r-done':($r1==='Done'?'r-pending':'r-locked'); ?>"><?php echo $r2==='Done'?'✓ Done':($r1==='Done'?'Pending':'🔒 Locked'); ?></span></td>
                                        <td><span class="r-badge <?php echo $r3==='Done'?'r-done':($r2==='Done'?'r-pending':'r-locked'); ?>"><?php echo $r3==='Done'?'✓ Done':($r2==='Done'?'Pending':'🔒 Locked'); ?></span></td>
                                        <td>
                                            <div class="progress-bar-wrap">
                                                <div class="progress-bar-fill <?php echo $pc; ?>"><?php echo $pct; ?>%</div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($all_done): ?>
                                                <a href="staff_review.php?idea_id=<?php echo (int)$row['id']; ?>" class="btn-view-done">View Completed</a>
                                            <?php else: ?>
                                                <a href="staff_review.php?idea_id=<?php echo (int)$row['id']; ?>" class="btn-continue">Continue Review</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
<?php endif; ?>
        </main>
    </div>
</body>
</html>
