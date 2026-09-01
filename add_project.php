<?php
// add_project.php
session_start();
require_once 'config.php';

// Access control: only teachers allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Access Denied. Only teachers can access this page. <a href='login.php'>Login here</a>");
}

$error = '';
$success = '';
$preselected_student_id = $_GET['student_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $project_title = trim($_POST['project_title'] ?? '');
    $project_description = trim($_POST['project_description'] ?? '');
    $cbc_strand = trim($_POST['cbc_strand'] ?? '');
    $doc_path = null;
    
    if (empty($student_id) || empty($project_title) || empty($project_description) || empty($cbc_strand)) {
        $error = "All fields except documentation are required.";
    } else {
        // Validate student exists and is in the same school/class
        $stmt_check = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'student' AND school_name = ? AND assigned_class = ?");
        $stmt_check->bind_param("iss", $student_id, $_SESSION['school_name'], $_SESSION['assigned_class']);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($student = $result->fetch_assoc()) {
            
            // Handle file upload
            if (isset($_FILES['project_documentation']) && $_FILES['project_documentation']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['project_documentation']['tmp_name'];
                $fileName = $_FILES['project_documentation']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedfileExtensions = array('pdf', 'png', 'jpg', 'jpeg');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    // new file name
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = __DIR__ . '/uploads/';
                    $dest_path = $uploadFileDir . $newFileName;
                    
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $doc_path = 'uploads/' . $newFileName;
                    } else {
                        $error = "There was an error moving the uploaded file.";
                    }
                } else {
                    $error = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
                }
            }

            if (empty($error)) {
                // Insert project
                $stmt_insert = $conn->prepare("INSERT INTO projects (student_id, uploaded_by_teacher_id, project_title, project_description, cbc_strand, project_documentation) VALUES (?, ?, ?, ?, ?, ?)");
                $teacher_id = $_SESSION['user_id'];
                $stmt_insert->bind_param("iissss", $student_id, $teacher_id, $project_title, $project_description, $cbc_strand, $doc_path);
                
                if ($stmt_insert->execute()) {
                    $success = "Project added successfully for student {$student['name']}!";
                    
                    // Send notification email to parent
                    $parent_email = $student['email'];
                    $student_name = $student['name'];
                    
                    $subject = "VisionPath - New Project Added for $student_name";
                    $message = "Hello Parent,\n\nA new CBC project/achievement titled '$project_title' under the strand '$cbc_strand' has been added to $student_name's portfolio by their teacher.\n\nLog in to VisionPath to view the full portfolio and any attached documentation.";
                    $headers = "From: noreply@visionpath.local\r\n";
                    $headers .= "Reply-To: noreply@visionpath.local\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();
                    
                    @mail($parent_email, $subject, $message, $headers);
                    
                    // Clear the form fields after successful submission
                    $_POST = array();
                } else {
                    $error = "Failed to add project to the database.";
                }
                $stmt_insert->close();
            }
        } else {
            $error = "Invalid student selected. Ensure the student is in your class.";
        }
        $stmt_check->close();
    }
}

// Fetch all students for this teacher to populate the dropdown
$stmt_students = $conn->prepare("SELECT id, name, admission_number FROM users WHERE role = 'student' AND school_name = ? AND assigned_class = ? ORDER BY name ASC");
$stmt_students->bind_param("ss", $_SESSION['school_name'], $_SESSION['assigned_class']);
$stmt_students->execute();
$students_list = $stmt_students->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add CBC Project - VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding: 0; }
        .navbar { background: #0056b3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar h1 { margin: 0; font-size: 20px; }
        .navbar a { color: #fff; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .navbar a:hover { text-decoration: underline; }
        
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 600px; margin: 40px auto; box-sizing: border-box; }
        h2 { color: #333; margin-top: 0; border-bottom: 2px solid #f4f7f6; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: 600; font-size: 14px; }
        input[type="text"], select, textarea, input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 15px; font-family: inherit; }
        input:focus, select:focus, textarea:focus { border-color: #0056b3; outline: none; }
        textarea { resize: vertical; min-height: 100px; }
        button { width: 100%; padding: 12px; background: #0056b3; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.3s; }
        button:hover { background: #004494; }
        .error { color: #d9534f; margin-bottom: 15px; background: #fdf2f2; padding: 10px; border-radius: 4px; border: 1px solid #d9534f; font-size: 14px; }
        .success { color: #5cb85c; margin-bottom: 15px; background: #f4fdf4; padding: 10px; border-radius: 4px; border: 1px solid #5cb85c; font-size: 14px; }
        .file-hint { font-size: 12px; color: #777; margin-top: 5px; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>VisionPath - Teacher Portal</h1>
    <div class="user-info">
        <a href="class_dashboard.php">← Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h2>Upload Student Project</h2>
    <p style="color:#666; font-size:14px; margin-bottom:20px;">Submit a new CBC achievement to a student's portfolio. Parents will be notified automatically.</p>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="student_id">Select Student</label>
            <select id="student_id" name="student_id" required>
                <option value="">-- Choose a student --</option>
                <?php while ($s = $students_list->fetch_assoc()): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($preselected_student_id == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['name']) . ' (' . htmlspecialchars($s['admission_number']) . ')'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="project_title">Project Title / Achievement</label>
            <input type="text" id="project_title" name="project_title" value="<?php echo htmlspecialchars($_POST['project_title'] ?? ''); ?>" required placeholder="e.g. Science Fair Model">
        </div>
        
        <div class="form-group">
            <label for="cbc_strand">CBC Strand</label>
            <select id="cbc_strand" name="cbc_strand" required>
                <option value="">-- Select Strand --</option>
                <option value="Languages">Languages</option>
                <option value="Mathematics">Mathematics</option>
                <option value="Environmental Activities">Environmental Activities</option>
                <option value="Science and Technology">Science and Technology</option>
                <option value="Creative Arts">Creative Arts</option>
                <option value="Physical and Health Education">Physical and Health Education</option>
                <option value="Other">Other / Extracurricular</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="project_description">Detailed Description & Assessment</label>
            <textarea id="project_description" name="project_description" required placeholder="Describe what the student accomplished..."><?php echo htmlspecialchars($_POST['project_description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="project_documentation">Upload Documentation (Optional)</label>
            <input type="file" id="project_documentation" name="project_documentation" accept=".pdf,.png,.jpg,.jpeg">
            <div class="file-hint">Accepted formats: PDF, PNG, JPG (Max 5MB)</div>
        </div>
        
        <button type="submit">Submit Project & Notify Parent</button>
    </form>
</div>

</body>
</html>
