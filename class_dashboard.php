<?php
// class_dashboard.php
session_start();
require_once 'config.php';

// Access control: only teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_school = $_SESSION['school_name'];
$teacher_class = $_SESSION['assigned_class'];
$teacher_name = $_SESSION['name'];

// Fetch students
$stmt = $conn->prepare("SELECT id, name, admission_number FROM users WHERE role = 'student' AND school_name = ? AND assigned_class = ? ORDER BY name ASC");
$stmt->bind_param("ss", $teacher_school, $teacher_class);
$stmt->execute();
$students_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Dashboard - VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding: 0; color: #333; }
        .navbar { background: #0056b3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar h1 { margin: 0; font-size: 20px; }
        .navbar .user-info { font-size: 14px; }
        .navbar a { color: #fff; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .navbar a:hover { text-decoration: underline; }
        
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .header-section { margin-bottom: 30px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #0056b3; }
        .header-section h2 { margin: 0 0 5px 0; color: #2c3e50; font-size: 24px; }
        .header-section p { margin: 0; color: #555; font-size: 16px; font-weight: 500; }
        
        .student-list { background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; }
        tr:hover { background: #fdfdfd; }
        
        .btn-view { background: #17a2b8; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; margin-right: 5px; display: inline-block; transition: background 0.2s; }
        .btn-view:hover { background: #138496; }
        
        .btn-add { background: #28a745; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-block; transition: background 0.2s; }
        .btn-add:hover { background: #218838; }
        
        .empty-state { text-align: center; padding: 50px; }
        .empty-state h3 { color: #7f8c8d; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>VisionPath - Teacher Portal</h1>
    <div class="user-info">
        Welcome, <?php echo htmlspecialchars($teacher_name); ?> | <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="header-section">
        <h2>Class Roster</h2>
        <p>School: <?php echo htmlspecialchars($teacher_school); ?> | Class: <?php echo htmlspecialchars($teacher_class); ?></p>
    </div>
    
    <div class="student-list">
        <?php if ($students_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Admission No.</th>
                        <th>Student Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 600; color: #555;"><?php echo htmlspecialchars($student['admission_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td>
                                <a href="portfolio.php?student_id=<?php echo $student['id']; ?>" class="btn-view">View Portfolio</a>
                                <a href="add_project.php?student_id=<?php echo $student['id']; ?>" class="btn-add">+ Add Project</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h3>No students found</h3>
                <p>There are no registered students in <?php echo htmlspecialchars($teacher_class); ?> yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
</div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
