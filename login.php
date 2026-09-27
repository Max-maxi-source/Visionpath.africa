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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0B2A4A;
            --gold: #C9A24B;
            --cream: #FAF9F6;
            --text: #1A1A1A;
            --border-soft: #E4E1D8;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0; padding: 20px; min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
            background: radial-gradient(circle at top right, rgba(28,169,201,0.08) 0%, transparent 45%),
                        radial-gradient(circle at bottom left, rgba(11,42,74,0.06) 0%, transparent 45%),
                        var(--cream);
        }
        .container {
            background: #fff; padding: 40px 34px; border-radius: 18px;
            box-shadow: 0 20px 60px rgba(11,42,74,0.12);
            width: 100%; max-width: 420px;
            border: 1px solid var(--border-soft);
        }
        .brand { display: flex; flex-direction: column; align-items: center; margin-bottom: 24px; }
        .brand .logo-mark {
            width: 52px; height: 52px; border-radius: 14px; margin-bottom: 12px;
            background: linear-gradient(145deg, var(--navy), #123a63);
            color: var(--gold); font-family: 'Playfair Display', serif; font-weight: 800; font-size: 26px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 18px rgba(11,42,74,0.28);
        }
        .brand .name { font-family: 'Playfair Display', serif; font-weight: 800; color: var(--navy); font-size: 1.3rem; }
        .brand .name span { color: var(--gold); }
        h2 { text-align: center; color: var(--navy); margin: 0 0 24px; font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.5rem; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; color: #4a5568; font-weight: 600; font-size: 0.88rem; }
        input {
            width: 100%; padding: 12px 14px; border: 1.5px solid var(--border-soft); border-radius: 10px;
            font-size: 15px; font-family: 'Inter', sans-serif; transition: border-color 0.2s;
        }
        input:focus { border-color: var(--gold); outline: none; }
        button {
            width: 100%; padding: 14px; background: var(--navy); color: #fff; border: none;
            border-radius: 40px; cursor: pointer; font-size: 16px; font-weight: 700; margin-top: 8px;
            transition: background 0.3s, transform 0.2s;
        }
        button:hover { background: #08213a; transform: translateY(-1px); }
        .error { color: #d9534f; margin-bottom: 18px; text-align: center; background: #fdf2f2; padding: 12px; border-radius: 8px; border: 1px solid #f0b8b8; font-size: 14px; }
        .success { color:#155724; background:#d4edda; border:1px solid #c3e6cb; padding:12px; border-radius:8px; margin-bottom:18px; text-align:center; font-size: 14px; }
        .links { text-align: center; margin-top: 24px; font-size: 14px; color: #4a5568; }
        .links a { color: var(--navy); text-decoration: none; font-weight: 700; }
        .links a:hover { text-decoration: underline; }
        .reassurance { text-align: center; font-size: 0.78rem; color: #8a92a3; margin-top: 14px; }
        .forgot-link { text-align:right; }
        .forgot-link a { color: var(--navy); text-decoration:none; font-weight:600; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="brand">
        <span class="logo-mark">V</span>
        <div class="name">Vision<span>Path</span></div>
    </div>
    <h2>Login to Your Account</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success">
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

        <div class="form-group forgot-link" style="margin-top:14px; margin-bottom:0;">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
    </form>

    <p class="reassurance">Your data is safe with us.</p>

    <div class="links">
        Don't have an account? <a href="register.php">Register Now</a>
    </div>
</div>

</body>
</html>
