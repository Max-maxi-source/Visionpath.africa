<?php
// portfolio.php
session_start();
require_once 'config.php';

// Access control: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$viewing_student_id = null;
$viewing_student_name = '';
$viewing_student_admission = '';
$is_teacher = ($_SESSION['role'] === 'teacher');

if ($is_teacher) {
    // Teacher viewing a student's portfolio
    $student_id_param = $_GET['student_id'] ?? '';
    if (empty($student_id_param)) {
        die("Student ID required. <a href='class_dashboard.php'>Back to Dashboard</a>");
    }
    
    // Validate student belongs to teacher's class
    $stmt_check = $conn->prepare("SELECT id, name, admission_number FROM users WHERE id = ? AND role = 'student' AND school_name = ? AND assigned_class = ?");
    $stmt_check->bind_param("iss", $student_id_param, $_SESSION['school_name'], $_SESSION['assigned_class']);
    $stmt_check->execute();
    $res = $stmt_check->get_result();
    
    if ($student = $res->fetch_assoc()) {
        $viewing_student_id = $student['id'];
        $viewing_student_name = $student['name'];
        $viewing_student_admission = $student['admission_number'];
    } else {
        die("Access denied or student not found in your class.");
    }
    $stmt_check->close();
} else if ($_SESSION['role'] === 'student') {
    // Student viewing their own
    $viewing_student_id = $_SESSION['user_id'];
    $viewing_student_name = $_SESSION['name'];
    $viewing_student_admission = $_SESSION['admission_number'];
} else {
    die("Access denied.");
}

// Fetch projects
$stmt = $conn->prepare("
    SELECT p.project_title, p.project_description, p.cbc_strand, p.project_documentation, p.created_at, u.name AS teacher_name 
    FROM projects p
    JOIN users u ON p.uploaded_by_teacher_id = u.id
    WHERE p.student_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $viewing_student_id);
$stmt->execute();
$projects_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding: 0; color: #333; }
        .navbar { background: #28a745; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar.teacher { background: #0056b3; }
        .navbar h1 { margin: 0; font-size: 20px; }
        .navbar .user-info { font-size: 14px; }
        .navbar a { color: #fff; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .navbar a:hover { text-decoration: underline; }
        
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .header-section { margin-bottom: 30px; }
        .header-section h2 { margin: 0 0 10px 0; color: #2c3e50; font-size: 28px; }
        .header-section p { margin: 0; color: #7f8c8d; font-size: 16px; }
        
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .project-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 5px solid <?php echo $is_teacher ? '#0056b3' : '#28a745'; ?>; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .project-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .project-card h3 { margin: 0 0 10px 0; font-size: 18px; color: #333; }
        .strand-badge { display: inline-block; background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-bottom: 15px; align-self: flex-start; }
        .project-desc { font-size: 14px; color: #555; line-height: 1.5; margin-bottom: 15px; flex-grow: 1; }
        .project-meta { font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 10px; display: flex; justify-content: space-between; align-items: center; }
        
        .btn-doc { background: #f8f9fa; border: 1px solid #ddd; color: #333; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600; transition: all 0.2s; }
        .btn-doc:hover { background: #e9ecef; }
        
        .empty-state { text-align: center; padding: 50px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .empty-state h3 { color: #7f8c8d; margin-bottom: 10px; }
        .empty-state p { color: #95a5a6; }
    </style>
</head>
<body>

<div class="navbar <?php echo $is_teacher ? 'teacher' : ''; ?>">
    <h1>VisionPath</h1>
    <div class="user-info">
        <?php if ($is_teacher): ?>
            <a href="class_dashboard.php">← Back to Dashboard</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="header-section">
        <h2><?php echo $is_teacher ? htmlspecialchars($viewing_student_name) . "'s Portfolio" : "My CBC Portfolio"; ?></h2>
        <p>Admission No: <?php echo htmlspecialchars($viewing_student_admission); ?></p>
    </div>
    
    <?php if ($projects_result->num_rows > 0): ?>
        <div class="projects-grid">
            <?php while ($project = $projects_result->fetch_assoc()): ?>
                <div class="project-card">
                    <span class="strand-badge"><?php echo htmlspecialchars($project['cbc_strand']); ?></span>
                    <h3><?php echo htmlspecialchars($project['project_title']); ?></h3>
                    <div class="project-desc">
                        <?php echo nl2br(htmlspecialchars($project['project_description'])); ?>
                    </div>
                    
                    <?php if (!empty($project['project_documentation'])): ?>
                        <div style="margin-bottom: 15px;">
                            <a href="<?php echo htmlspecialchars($project['project_documentation']); ?>" target="_blank" class="btn-doc">📄 View Documentation</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="project-meta">
                        <span>Teacher: <?php echo htmlspecialchars($project['teacher_name']); ?></span>
                        <span><?php echo date('M d, Y', strtotime($project['created_at'])); ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No Projects Yet</h3>
            <p>No projects have been uploaded to this portfolio yet.</p>
        </div>
    <?php endif; ?>
    
</div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
