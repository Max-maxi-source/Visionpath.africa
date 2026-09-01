<?php
// mentor_dashboard.php
session_start();
require_once 'config.php';

// Access control: only logged-in mentors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit;
}

$mentor_user_id = $_SESSION['user_id'];

// Fetch mentor profile
$stmt_profile = $conn->prepare(
    "SELECT mp.full_name, mp.institution_company, mp.job_title, mp.cbc_pathway_interest,
            mp.mentorship_capacity,
            COALESCE(mp.is_approved, mp.is_verified_mentor, 0) AS is_approved,
            COALESCE(mp.is_verified_mentor, mp.is_approved, 0) AS is_verified_mentor,
            mp.linkedin_url
     FROM mentor_profiles mp WHERE mp.user_id = ?"
);
$stmt_profile->bind_param("i", $mentor_user_id);
$stmt_profile->execute();
$profile = $stmt_profile->get_result()->fetch_assoc();
$stmt_profile->close();

if (!$profile) {
    die("<div style='font-family:sans-serif;text-align:center;margin-top:60px;color:#d9534f;'><h2>Mentor profile not found.</h2><p><a href='logout.php'>Logout</a></p></div>");
}

$is_approved = !empty($profile['is_approved']);

$pathway = $profile['cbc_pathway_interest'];

// CBC Strand → Pathway mapping
$pathway_strands = [
    'STEM'                  => ['Mathematics', 'Science and Technology'],
    'Social Sciences'       => ['Languages', 'Environmental Activities'],
    'Arts & Sports Science' => ['Creative Arts', 'Physical and Health Education'],
];
$strands = $pathway_strands[$pathway] ?? [];
$placeholder_list = implode(',', array_fill(0, count($strands), '?'));
$types = str_repeat('s', count($strands));

// Read feedback result from Post-Redirect-Get query params
$fb_success = '';
$fb_error = '';
if (isset($_GET['fb_success'])) {
    $fb_success = "Your feedback has been submitted successfully!";
} elseif (isset($_GET['fb_error'])) {
    $fb_error = "Feedback could not be saved. Please ensure the text field is not empty.";
}

// Fetch projects matching mentor's pathway strands
$projects = [];
if (!empty($strands)) {
    $stmt_proj = $conn->prepare(
        "SELECT p.id, p.project_title, p.project_description, p.cbc_strand,
                p.project_documentation, p.created_at,
                u.name AS student_name, u.school_name, u.assigned_class,
                pf.feedback_text AS my_feedback,
                pf.is_referee AS my_referee
         FROM projects p
         JOIN users u ON u.id = p.student_id
         LEFT JOIN project_feedback pf ON pf.project_id = p.id AND pf.mentor_id = ?
         WHERE p.cbc_strand IN ($placeholder_list)
         ORDER BY p.created_at DESC"
    );
    $bind_types = "i" . $types;
    $bind_values = array_merge([$mentor_user_id], $strands);
    $stmt_proj->bind_param($bind_types, ...$bind_values);
    $stmt_proj->execute();
    $projects_result = $stmt_proj->get_result();
    while ($row = $projects_result->fetch_assoc()) {
        $student_school = strtolower(trim((string)($row['school_name'] ?? '')));
        $mentor_school = strtolower(trim((string)($profile['institution_company'] ?? '')));
        $project_score = 0;
        $score_reasons = [];

        // Step 1: hard pathway filter already applied in SQL

        // Step 2: priority scoring system (100 points max)
        if (empty($row['my_feedback'])) {
            $project_score += 35;
            $score_reasons[] = 'Unmentored priority';
        }

        if ($student_school !== '' && $mentor_school !== '') {
            $local_match = ($student_school === $mentor_school)
                || (strpos($student_school, $mentor_school) !== false)
                || (strpos($mentor_school, $student_school) !== false);

            if ($local_match) {
                $project_score += 30;
                $score_reasons[] = 'Local proximity';
                $project_score += 25;
                $score_reasons[] = 'Alumni / alma mater match';
            } else {
                $project_score += 10;
                $score_reasons[] = 'Regional match signal';
            }
        }

        if (!empty($row['project_documentation']) || strlen((string)($row['project_description'] ?? '')) > 180) {
            $project_score += 10;
            $score_reasons[] = 'Active project quality';
        }

        // Step 3: status penalty
        if (!empty($row['my_feedback'])) {
            $project_score -= 50;
            $score_reasons[] = 'Already mentored';
        }

        $project_score = max(0, min($project_score, 100));

        $projects[] = [
            'id' => $row['id'],
            'project_title' => $row['project_title'],
            'project_description' => $row['project_description'],
            'cbc_strand' => $row['cbc_strand'],
            'project_documentation' => $row['project_documentation'],
            'created_at' => $row['created_at'],
            'student_name' => $row['student_name'],
            'school_name' => $row['school_name'],
            'assigned_class' => $row['assigned_class'],
            'my_feedback' => $row['my_feedback'],
            'my_referee' => $row['my_referee'],
            'priority_score' => $project_score,
            'priority_reasons' => $score_reasons,
        ];
    }
    $stmt_proj->close();

    usort($projects, function ($a, $b) {
        if ($a['priority_score'] === $b['priority_score']) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        }
        return $b['priority_score'] <=> $a['priority_score'];
    });

    if (!$is_approved && count($projects) > 3) {
        $projects = array_slice($projects, 0, 3);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard – VisionPath</title>
    <meta name="description" content="VisionPath Mentor Dashboard – view and provide feedback on CBC student projects.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5b21b6;
            --primary-light: #7c3aed;
            --primary-bg: #f5f3ff;
            --accent: #10b981;
            --accent-bg: #ecfdf5;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white; padding: 16px 30px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 10px rgba(91,33,182,0.3);
            position: sticky; top: 0; z-index: 100;
        }
        .navbar-brand { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
        .navbar-brand span { opacity: 0.7; font-weight: 400; }
        .navbar-right { display: flex; align-items: center; gap: 20px; font-size: 14px; }
        .navbar-right a { color: rgba(255,255,255,0.85); text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .navbar-right a:hover { color: white; }
        .badge-pathway {
            background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600;
        }

        /* Layout */
        .page-wrapper { max-width: 1100px; margin: 0 auto; padding: 30px 20px; }

        /* Profile Card */
        .profile-card {
            background: var(--card-bg); border-radius: 12px;
            padding: 24px 28px; margin-bottom: 28px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            display: flex; align-items: flex-start; gap: 24px;
            border-left: 5px solid var(--primary);
        }
        .profile-avatar {
            width: 64px; height: 64px; background: var(--primary-bg);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 26px; font-weight: 700; color: var(--primary); flex-shrink: 0;
        }
        .profile-info h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .profile-info p { color: var(--muted); font-size: 14px; margin-bottom: 8px; }
        .profile-tags { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag {
            font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px;
        }
        .tag-purple { background: var(--primary-bg); color: var(--primary); }
        .tag-green  { background: var(--accent-bg); color: #065f46; }
        .tag-warning { background: #fffbeb; color: #92400e; }

        .unverified-banner {
            background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;
            padding: 14px 18px; margin-bottom: 24px; font-size: 14px; color: #78350f;
            display: flex; align-items: center; gap: 10px;
        }

        /* Section Header */
        .section-header { margin-bottom: 20px; }
        .section-header h3 { font-size: 18px; font-weight: 700; }
        .section-header p { font-size: 14px; color: var(--muted); margin-top: 4px; }

        /* Projects Grid */
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .project-card {
            background: var(--card-bg); border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            overflow: hidden; display: flex; flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .project-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .project-card-header { padding: 18px 20px 12px; border-bottom: 1px solid var(--border); }
        .strand-badge {
            display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; padding: 3px 9px; border-radius: 4px; margin-bottom: 10px;
            background: var(--primary-bg); color: var(--primary);
        }
        .project-title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .student-meta { font-size: 13px; color: var(--muted); }
        .project-card-body { padding: 14px 20px; flex-grow: 1; }
        .project-desc { font-size: 14px; color: var(--muted); line-height: 1.6; }
        .doc-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 13px; font-weight: 600; color: var(--primary); text-decoration: none; }
        .doc-link:hover { text-decoration: underline; }
        .project-card-footer { padding: 14px 20px; border-top: 1px solid var(--border); background: #fafafa; }

        /* Feedback Form */
        .feedback-form details { }
        .feedback-form summary {
            cursor: pointer; font-size: 14px; font-weight: 600;
            color: var(--primary); user-select: none; list-style: none;
            display: flex; align-items: center; gap: 6px;
        }
        .feedback-form summary::-webkit-details-marker { display: none; }
        .feedback-form summary::before { content: '▶'; font-size: 10px; transition: transform 0.2s; }
        .feedback-form details[open] summary::before { transform: rotate(90deg); }
        .feedback-form textarea {
            width: 100%; margin-top: 10px; padding: 10px;
            border: 1px solid var(--border); border-radius: 6px; resize: vertical;
            font-family: inherit; font-size: 14px; min-height: 80px;
        }
        .feedback-form textarea:focus { outline: none; border-color: var(--primary); }
        .referee-check { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 13px; color: var(--muted); }
        .referee-check input { width: auto; }
        .btn-submit-fb {
            margin-top: 10px; padding: 8px 18px;
            background: var(--primary); color: white; border: none;
            border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600;
            transition: background 0.2s;
        }
        .btn-submit-fb:hover { background: var(--primary-light); }
        .my-feedback-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 12px; font-weight: 600; color: #065f46;
            background: var(--accent-bg); padding: 3px 9px; border-radius: 20px; margin-top: 8px;
        }

        /* Empty State */
        .empty-state {
            text-align: center; padding: 60px 20px; background: var(--card-bg);
            border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        .empty-state h3 { font-size: 18px; color: var(--muted); margin-bottom: 8px; }
        .empty-state p { font-size: 14px; color: #94a3b8; }

        /* Alert banners */
        .alert { padding: 12px 18px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
        .alert-success { background: var(--accent-bg); color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

        @media(max-width:600px) {
            .profile-card { flex-direction: column; }
            .projects-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">VisionPath <span>/ Mentor Portal</span></div>
    <div class="navbar-right">
        <span class="badge-pathway">📌 <?php echo htmlspecialchars($pathway); ?></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-wrapper">

    <!-- Profile Card -->
    <div class="profile-card">
        <div class="profile-avatar"><?php echo strtoupper(substr($profile['full_name'], 0, 1)); ?></div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($profile['full_name']); ?></h2>
            <p><?php echo htmlspecialchars($profile['job_title']); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($profile['institution_company']); ?></p>
            <div class="profile-tags">
                <span class="tag tag-purple">📚 <?php echo htmlspecialchars($pathway); ?></span>
                <span class="tag tag-green">🎯 Capacity: <?php echo intval($profile['mentorship_capacity']); ?> projects</span>
                <?php if (!empty($profile['linkedin_url'])): ?>
                    <a href="<?php echo htmlspecialchars($profile['linkedin_url']); ?>" target="_blank" class="tag tag-warning" style="text-decoration:none;">🔗 LinkedIn</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Admin Verification Notice -->
    <?php if (!$is_approved): ?>
        <div class="unverified-banner">
            ⚠️ <strong>Preview Mode:</strong>&nbsp; You can view a few student projects while your account is pending verification. Full access and feedback tools unlock after admin approval.
        </div>
    <?php endif; ?>

    <!-- Feedback Alerts -->
    <?php if ($fb_success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($fb_success); ?></div>
    <?php endif; ?>
    <?php if ($fb_error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($fb_error); ?></div>
    <?php endif; ?>

    <!-- Project Section -->
    <div class="section-header">
        <h3>Student Projects – <?php echo htmlspecialchars($pathway); ?> Pathway</h3>
        <p>
            <?php if ($is_approved): ?>
                Showing projects matching: <strong><?php echo implode(', ', $strands); ?></strong>
            <?php else: ?>
                Previewing a limited set of student projects until admin verification is complete.
            <?php endif; ?>
        </p>
    </div>

    <?php if (count($projects) > 0): ?>
        <div class="projects-grid">
            <?php foreach ($projects as $proj): ?>
                <div class="project-card">
                    <div class="project-card-header">
                        <span class="strand-badge"><?php echo htmlspecialchars($proj['cbc_strand']); ?></span>
                        <div class="project-title"><?php echo htmlspecialchars($proj['project_title']); ?></div>
                        <div class="student-meta">
                            👤 <?php echo htmlspecialchars($proj['student_name']); ?> &nbsp;·&nbsp;
                            🏫 <?php echo htmlspecialchars($proj['school_name']); ?> &nbsp;·&nbsp;
                            <?php echo htmlspecialchars($proj['assigned_class']); ?>
                        </div>                        <div style="margin-top:10px; font-size:12px; font-weight:700; color: var(--primary);">
                            Priority score: <?php echo (int)$proj['priority_score']; ?>/100
                        </div>                    </div>

                    <div class="project-card-body">
                        <p class="project-desc"><?php echo nl2br(htmlspecialchars($proj['project_description'])); ?></p>

                        <?php if (!empty($proj['project_documentation'])): ?>
                            <a href="<?php echo htmlspecialchars($proj['project_documentation']); ?>" target="_blank" class="doc-link">
                                📄 View Documentation
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($proj['my_feedback'])): ?>
                            <div class="my-feedback-badge">
                                ✅ You left feedback
                                <?php if ($proj['my_referee']): ?> &nbsp;· 🎖️ Referee<?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($proj['priority_reasons'])): ?>
                            <div style="margin-top:10px; font-size:11px; color: var(--muted); line-height:1.6;">
                                <?php echo htmlspecialchars(implode(' • ', $proj['priority_reasons'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="project-card-footer">
                        <div class="feedback-form">
                            <?php if ($is_approved): ?>
                                <details <?php echo !empty($proj['my_feedback']) ? 'open' : ''; ?>>
                                    <summary>
                                        <?php echo !empty($proj['my_feedback']) ? 'Edit Your Feedback' : 'Leave Feedback'; ?>
                                    </summary>
                                    <form method="POST" action="mentor_feedback.php">
                                        <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">
                                        <textarea name="feedback_text" placeholder="Write your professional feedback on this project..."><?php echo htmlspecialchars($proj['my_feedback'] ?? ''); ?></textarea>
                                        <div class="referee-check">
                                            <input type="checkbox" name="is_referee" id="ref_<?php echo $proj['id']; ?>" value="1" <?php echo $proj['my_referee'] ? 'checked' : ''; ?>>
                                            <label for="ref_<?php echo $proj['id']; ?>">Opt in as a verified referee for this student</label>
                                        </div>
                                        <button type="submit" class="btn-submit-fb">Submit Feedback</button>
                                    </form>
                                </details>
                            <?php else: ?>
                                <p style="font-size: 13px; color: var(--muted); line-height: 1.5; margin-top: 6px;">
                                    Full feedback and referee actions will be enabled after admin verification.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <h3>No projects yet</h3>
            <p>No student projects in the <strong><?php echo htmlspecialchars($pathway); ?></strong> pathway have been uploaded yet.<br>Check back later as teachers add more CBC documentation.</p>
        </div>
    <?php endif; ?>

</div><!-- /page-wrapper -->

</body>
</html>
<?php $conn->close(); ?>
