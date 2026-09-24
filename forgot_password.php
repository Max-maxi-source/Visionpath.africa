<?php
session_start();
require_once 'config.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check_stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            $error = 'No account was found with that email address.';
        } else {
            $token = bin2hex(random_bytes(32));

            $insert_stmt = $conn->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
            $insert_stmt->bind_param('ss', $email, $token);

            if ($insert_stmt->execute()) {
                $reset_url = 'http://localhost/visionpath/reset_password.php?token=' . urlencode($token) . '&email=' . urlencode($email);
                $message = 'Password reset link created successfully. Demo mode: <a href="' . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . '</a>';
            } else {
                $error = 'Unable to create a password reset link. Please try again.';
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 420px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        p { color: #555; font-size: 14px; line-height: 1.6; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        button { width: 100%; padding: 12px; background: #0056b3; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; }
        button:hover { background: #004494; }
        .error, .success { margin-bottom: 15px; padding: 12px; border-radius: 6px; font-size: 14px; }
        .error { background: #fdf2f2; color: #d9534f; border: 1px solid #d9534f; }
        .success { background: #f4fdf4; color: #267d3f; border: 1px solid #7dbb7d; }
        .links { text-align: center; margin-top: 20px; font-size: 14px; }
        .links a { color: #0056b3; text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <p>Enter the email associated with your VisionPath account to receive a secure reset link.</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit">Send Reset Link</button>
        </form>

        <div class="links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
