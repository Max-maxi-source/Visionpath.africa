<?php
// register.php (updated with Mentor registration)
session_start();
require_once 'config.php';
require_once 'send_mail.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'student';
    $name = trim($_POST['name'] ?? ''); // used for student/teacher; for mentor we use full_name
    $full_name = trim($_POST['full_name'] ?? $name);
    $school_name = trim($_POST['school_name'] ?? '');
    $assigned_class = trim($_POST['assigned_class'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $admission_number = trim($_POST['admission_number'] ?? '');
    // Mentor specific fields
    $institution_company = trim($_POST['institution_company'] ?? '');
    $job_title = trim($_POST['job_title'] ?? '');
    $cbc_pathway_interest = trim($_POST['cbc_pathway_interest'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $mentorship_capacity = intval($_POST['mentorship_capacity'] ?? 0);
    $professional_bio = trim($_POST['professional_bio'] ?? '');

    // Basic validation common to all roles
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Name, Email and Password are required.";
    } else {
        // Role‑specific validation
        if ($role === 'student') {
            if (empty($school_name) || empty($assigned_class) || empty($admission_number)) {
                $error = "All student fields are required.";
            }
        } elseif ($role === 'teacher') {
            if (empty($school_name) || empty($assigned_class)) {
                $error = "All teacher fields are required.";
            }
        } elseif ($role === 'mentor') {
            // Corporate email domain check
            $public_domains = ['gmail.com','yahoo.com','hotmail.com','outlook.com','live.com','aol.com'];
            $email_domain = strtolower(substr(strrchr($email, "@"), 1));
            if (in_array($email_domain, $public_domains)) {
                $error = "Mentor must register with an official corporate or institutional email address.";
            }
            if (empty($institution_company) || empty($job_title) || empty($cbc_pathway_interest) || empty($linkedin_url) || $mentorship_capacity <= 0) {
                $error = "All mentor fields are required and capacity must be > 0.";
            }
        }
    }

    if (empty($error)) {
        // Check for duplicate email
        $stmt_dup = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_dup->bind_param("s", $email);
        $stmt_dup->execute();
        $stmt_dup->store_result();
        if ($stmt_dup->num_rows > 0) {
            $error = "An account with this email already exists.";
        }
        $stmt_dup->close();
    }

    if (empty($error)) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $is_verified = 0; // pending verification
        // Insert into users table – note that admission_number, school_name, assigned_class may be NULL for mentors
        $stmt_ins = $conn->prepare("INSERT INTO users (admission_number, name, school_name, assigned_class, email, password_hash, role, is_verified) VALUES (?,?,?,?,?,?,?,?)");
        // Normalize values per role
        $admission_val = ($role === 'student') ? $admission_number : null;
        $school_val = ($role === 'student' || $role === 'teacher')
            ? $school_name
            : (($role === 'mentor') ? ($institution_company !== '' ? $institution_company : 'Independent Mentor') : null);
        $class_val = ($role === 'student' || $role === 'teacher')
            ? $assigned_class
            : (($role === 'mentor') ? 'Mentor' : null);
        $stmt_ins->bind_param("sssssssi", $admission_val, $full_name, $school_val, $class_val, $email, $password_hash, $role, $is_verified);
        if ($stmt_ins->execute()) {
            $user_id = $stmt_ins->insert_id;
            // If mentor, add to mentor_profiles
            if ($role === 'mentor') {
                $stmt_mentor = $conn->prepare("INSERT INTO mentor_profiles (user_id, full_name, institution_company, job_title, cbc_pathway_interest, linkedin_url, mentorship_capacity, professional_bio, is_verified_mentor, is_approved) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $mentor_approval = 0;
                $stmt_mentor->bind_param("isssssisii", $user_id, $full_name, $institution_company, $job_title, $cbc_pathway_interest, $linkedin_url, $mentorship_capacity, $professional_bio, $mentor_approval, $mentor_approval);
                $stmt_mentor->execute();
                $stmt_mentor->close();
            }
            // Store only a hash; the raw code is delivered by email.
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $code_hash = password_hash($code, PASSWORD_DEFAULT);
            $expires_at = date('Y-m-d H:i:s', time() + 900);
            $stmt_code = $conn->prepare("INSERT INTO verification_codes (email, code_hash, expires_at) VALUES (?, ?, ?)");
            $stmt_code->bind_param("sss", $email, $code_hash, $expires_at);
            $stmt_code->execute();
            $stmt_code->close();

            try {
                send_verification_email($email, $full_name, $code);
            } catch (Throwable $mail_error) {
                error_log('Verification email failed: ' . $mail_error->getMessage());
                $stmt_cleanup = $conn->prepare("DELETE FROM verification_codes WHERE email = ?");
                $stmt_cleanup->bind_param("s", $email);
                $stmt_cleanup->execute();
                $stmt_cleanup->close();
                $error = "Registration could not send a verification email. Please try again.";
            }

            if (empty($error)) {
                $_SESSION['verify_email'] = $email;
                $_SESSION['verify_role'] = $role;
                header('Location: verify_code.php');
                exit;
            }
        } else {
            $error = "Database error during registration. Please try again.";
        }
        $stmt_ins->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {font-family:'Inter',sans-serif;background:#f4f7f6;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;padding:20px;box-sizing:border-box;}
        .container{background:#fff;padding:30px;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,0.05);max-width:500px;width:100%;}
        h2{text-align:center;color:#333;margin-top:0;margin-bottom:25px;}
        .role-selector{display:flex;gap:10px;margin-bottom:25px;}
        .role-btn{flex:1;padding:12px;text-align:center;border:2px solid #e0e0e0;border-radius:6px;cursor:pointer;font-weight:600;color:#666;transition:all .3s;}
        .role-btn.active{border-color:#0056b3;background:#f0f7ff;color:#0056b3;}
        .role-btn input{display:none;}
        .form-group{margin-bottom:15px;}
        label{display:block;margin-bottom:5px;color:#555;font-weight:600;font-size:14px;}
        input,select,textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;box-sizing:border-box;font-size:15px;font-family:'Inter',sans-serif;}
        input:focus,select:focus,textarea:focus{border-color:#0056b3;outline:none;}
        button{width:100%;padding:12px;background:#0056b3;color:white;border:none;border-radius:6px;cursor:pointer;font-size:16px;font-weight:600;margin-top:10px;transition:bg .3s;}
        button:hover{background:#004494;}
        .error,.success{margin-bottom:15px;padding:10px;border-radius:4px;text-align:center;font-size:14px;}
        .error{color:#d9534f;background:#fdf2f2;border:1px solid #d9534f;}
        .success{color:#5cb85c;background:#f4fdf4;border:1px solid #5cb85c;}
        .notice{background:#e9ecef;padding:15px;border-radius:4px;margin-bottom:15px;font-size:14px;text-align:center;border:1px solid #ced4da;color:#495057;}
        .links{text-align:center;margin-top:20px;font-size:14px;}
        .links a{color:#0056b3;text-decoration:none;font-weight:600;}
        .links a:hover{ text-decoration:underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>Create an Account</h2>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <a href="verify_code.php" style="display:block;text-align:center;padding:12px;background:#28a745;color:white;text-decoration:none;border-radius:6px;font-weight:600;margin-top:15px;">Proceed to Verification</a>
    <?php else: ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="role-selector">
                <label class="role-btn active" id="btn_student"><input type="radio" name="role" value="student" checked onchange="toggleForm()"> Student</label>
                <label class="role-btn" id="btn_teacher"><input type="radio" name="role" value="teacher" onchange="toggleForm()"> Teacher</label>
                <label class="role-btn" id="btn_mentor"><input type="radio" name="role" value="mentor" onchange="toggleForm()"> Mentor</label>
            </div>
            <!-- Common fields -->
            <div class="form-group"><label for="full_name">Full Name</label><input type="text" id="full_name" name="full_name" required placeholder="John Doe"></div>
            <div class="form-group" id="group_email"><label for="email">Email Address</label><input type="email" id="email" name="email" required placeholder="email@company.com"></div>
            <div class="form-group" id="group_password"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
            <!-- Student fields -->
            <div id="student_fields">
                <div class="form-group"><label for="admission_number">Admission Number</label><input type="text" id="admission_number" name="admission_number" placeholder="e.g. 10456"></div>
                <div class="form-group"><label for="school_name">School Name</label><input type="text" id="school_name" name="school_name" placeholder="e.g. Nairobi High"></div>
                <div class="form-group"><label for="assigned_class">Assigned Class / Grade</label><input type="text" id="assigned_class" name="assigned_class" placeholder="e.g. Grade 7 East"></div>
            </div>
            <!-- Teacher fields -->
            <div id="teacher_fields" style="display:none;">
                <div class="form-group"><label for="school_name_t">School Name</label><input type="text" id="school_name_t" name="school_name" placeholder="e.g. Nairobi High"></div>
                <div class="form-group"><label for="assigned_class_t">Assigned Class / Grade</label><input type="text" id="assigned_class_t" name="assigned_class" placeholder="e.g. Grade 7 East"></div>
            </div>
            <!-- Mentor fields -->
            <div id="mentor_fields" style="display:none;">
                <div class="form-group"><label for="institution_company">Institution / Company</label><input type="text" id="institution_company" name="institution_company" placeholder="e.g. Microsoft" required></div>
                <div class="form-group"><label for="job_title">Job Title</label><input type="text" id="job_title" name="job_title" placeholder="Software Engineer" required></div>
                <div class="form-group"><label for="cbc_pathway_interest">CBC Pathway Interest</label>
                    <select id="cbc_pathway_interest" name="cbc_pathway_interest" required>
                        <option value="">Select Pathway</option>
                        <option value="STEM">STEM</option>
                        <option value="Social Sciences">Social Sciences</option>
                        <option value="Arts & Sports Science">Arts & Sports Science</option>
                    </select>
                </div>
                <div class="form-group"><label for="linkedin_url">LinkedIn URL</label><input type="url" id="linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/in/username" required></div>
                <div class="form-group"><label for="mentorship_capacity">Mentorship Capacity (max projects)</label><input type="number" id="mentorship_capacity" name="mentorship_capacity" min="1" value="5" required></div>
                <div class="form-group"><label for="professional_bio">Professional Bio</label><textarea id="professional_bio" name="professional_bio" rows="4" placeholder="Brief summary of experience" required></textarea></div>
            </div>
            <button type="submit" id="submit_btn">Register as Student</button>
        </form>
    <?php endif; ?>
    <div class="links">Already have an account? <a href="login.php">Login here</a></div>
</div>
<script>
function toggleForm(){
    const role = document.querySelector('input[name="role"]:checked').value;
    const btnStudent=document.getElementById('btn_student');
    const btnTeacher=document.getElementById('btn_teacher');
    const btnMentor=document.getElementById('btn_mentor');
    // Reset all
    btnStudent.classList.remove('active');
    btnTeacher.classList.remove('active');
    btnMentor.classList.remove('active');
    document.getElementById('student_fields').style.display='none';
    document.getElementById('teacher_fields').style.display='none';
    document.getElementById('mentor_fields').style.display='none';
    // Show appropriate
    if(role==='student'){
        btnStudent.classList.add('active');
        document.getElementById('student_fields').style.display='block';
        document.getElementById('submit_btn').innerText='Register as Student';
    } else if(role==='teacher'){
        btnTeacher.classList.add('active');
        document.getElementById('teacher_fields').style.display='block';
        document.getElementById('submit_btn').innerText='Register as Teacher';
    } else if(role==='mentor'){
        btnMentor.classList.add('active');
        document.getElementById('mentor_fields').style.display='block';
        document.getElementById('submit_btn').innerText='Register as Mentor';
    }
}
// Initialize
toggleForm();
</script>
</body>
</html>
