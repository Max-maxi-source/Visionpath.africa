<?php
// login.php
session_start();
require_once 'config.php';

$error = '';
$success_message = '';

if (isset($_SESSION['reset_success'])) {
    $success_message = $_SESSION['reset_success'];
    unset($_SESSION['reset_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($identifier) || empty($password)) {
        $error = "Please enter your Admission Number/Email and Password.";
    } else {
        $stmt = $conn->prepare("SELECT id, admission_number, name, email, password_hash, role, is_verified, school_name, assigned_class FROM users WHERE admission_number = ? OR email = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if ($user['is_verified'] == 0) {
                // Not verified
                $_SESSION['verify_email'] = $user['email'];
                $error = "Your account is not verified. <a href='verify.php' style='color:#d9534f; text-decoration:underline;'>Click here to verify</a>.";
            } else {
                if (password_verify($password, $user['password_hash'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['admission_number'] = $user['admission_number'];
                    $_SESSION['school_name'] = $user['school_name'];
                    $_SESSION['assigned_class'] = $user['assigned_class'];
                    
                    if ($user['role'] === 'student') {
                        header("Location: portfolio.php");
                        exit;
                    } elseif ($user['role'] === 'teacher') {
                        header("Location: class_dashboard.php");
                        exit;
                    } elseif ($user['role'] === 'mentor') {
                        header("Location: mentor_dashboard.php");
                        exit;
                    } else {
                        header("Location: index.php");
                        exit;
                    }
                } else {
                    $error = "Invalid credentials.";
                }
            }
        } else {
            $error = "No account found with that identifier.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px; font-family: 'Inter', sans-serif;}
        input:focus { border-color: #0056b3; outline: none; }
        button { width: 100%; padding: 12px; background: #0056b3; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; margin-top: 10px; transition: background 0.3s; }
        button:hover { background: #004494; }
        .error { color: #d9534f; margin-bottom: 15px; text-align: center; background: #fdf2f2; padding: 10px; border-radius: 4px; border: 1px solid #d9534f; font-size: 14px; }
        .links { text-align: center; margin-top: 20px; font-size: 14px; }
        .links a { color: #0056b3; text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h2>Login to VisionPath</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success" style="color:#155724;background:#d4edda;border:1px solid #c3e6cb;padding:10px;border-radius:6px;margin-bottom:15px;text-align:center;">
            <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="identifier">Admission No. (Students) or Email (Teachers / Mentors)</label>
            <input type="text" id="identifier" name="identifier" required placeholder="Enter admission number or email">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>
        
        <button type="submit">Login</button>

        <div class="form-group" style="margin-top:12px; margin-bottom:0;">
            <div style="text-align:right;">
                <a href="forgot_password.php" style="color:#0056b3; text-decoration:none; font-weight:600;">Forgot Password?</a>
            </div>
        </div>
    </form>
    
    <div class="links">
        Don't have an account? <a href="register.php">Register Now</a>
    </div>
</div>

</body>
</html>
