<?php
// forgot_password.php
session_start();
require_once 'config.php';

$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$error = '';
$success = '';
$local_code = '';
$step = $_POST['step'] ?? 'request';
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'request') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $user_stmt = $conn->prepare('SELECT name FROM users WHERE email = ? LIMIT 1');
            $user_stmt->bind_param('s', $email);
            $user_stmt->execute();
            $user = $user_stmt->get_result()->fetch_assoc();
            $user_stmt->close();

            if ($user) {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $code_hash = hash('sha256', $code);

                $delete_stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
                $delete_stmt->bind_param('s', $email);
                $delete_stmt->execute();
                $delete_stmt->close();

                $reset_stmt = $conn->prepare('INSERT INTO password_resets (email, code_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))');
                $reset_stmt->bind_param('ss', $email, $code_hash);
                $reset_stmt->execute();
                $reset_stmt->close();

                $safe_name = $user['name'];
                $subject = 'VisionPath - Password Reset Code';
                $message = "Hello $safe_name,\n\nYour VisionPath password reset code is: $code\n\nThis code expires in 15 minutes. If you did not request a password reset, you can ignore this email.\n\nVisionPath";
                $headers = "From: noreply@visionpath.local\r\nReply-To: noreply@visionpath.local\r\nX-Mailer: PHP/" . phpversion();
                if (!@mail($email, $subject, $message, $headers)) {
                    if (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'], true)) {
                        $local_code = $code;
                        $success = 'Email delivery is not configured in this local WampServer environment. Use the development code below.';
                        $step = 'reset';
                    } else {
                        $error = 'The reset email could not be sent. Please check the server email configuration and try again.';
                    }
                } else {
                    $success = 'A password reset code has been sent to your email.';
                    $step = 'reset';
                }
            } else {
                $success = 'If an account exists for that email, a password reset code has been sent.';
                $step = 'reset';
            }
        }
    } elseif ($step === 'reset') {
        $code = trim($_POST['code'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $code)) {
            $error = 'Enter the email address and six-digit reset code.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Your new password must be at least 8 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'The passwords do not match.';
        } else {
            $reset_stmt = $conn->prepare('SELECT id, code_hash FROM password_resets WHERE email = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
            $reset_stmt->bind_param('s', $email);
            $reset_stmt->execute();
            $reset = $reset_stmt->get_result()->fetch_assoc();
            $reset_stmt->close();

            if (!$reset || !hash_equals($reset['code_hash'], hash('sha256', $code))) {
                $error = 'The reset code is invalid or has expired.';
            } else {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE email = ? LIMIT 1');
                $update_stmt->bind_param('ss', $password_hash, $email);
                $update_stmt->execute();
                $update_stmt->close();

                $delete_stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
                $delete_stmt->bind_param('s', $email);
                $delete_stmt->execute();
                $delete_stmt->close();

                $success = 'Your password has been changed successfully. You can now log in.';
                $step = 'complete';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        p { color: #666; font-size: 14px; line-height: 1.5; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px; font-family: 'Inter', sans-serif; }
        button { width: 100%; padding: 12px; background: #0056b3; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; margin-top: 10px; }
        button:hover { background: #004494; }
        .error, .success { margin-bottom: 15px; padding: 10px; border-radius: 4px; text-align: center; font-size: 14px; }
        .error { color: #d9534f; background: #fdf2f2; border: 1px solid #d9534f; }
        .success { color: #287a3d; background: #f1fbf3; border: 1px solid #5cb85c; }
        .development-code { color: #856404; background: #fff8df; border: 1px solid #e0b84c; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 15px; font-size: 14px; }
        .development-code strong { display: block; font-size: 22px; letter-spacing: 2px; margin-top: 5px; }
        .links { text-align: center; margin-top: 20px; font-size: 14px; }
        .links a { color: #0056b3; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <h2>Reset Your Password</h2>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($local_code): ?><div class="development-code">Local development reset code<strong><?php echo htmlspecialchars($local_code); ?></strong></div><?php endif; ?>

    <?php if ($step === 'complete'): ?>
        <div class="links"><a href="login.php">Return to Login</a></div>
    <?php elseif ($step === 'reset'): ?>
        <p>Enter the six-digit code sent to your email and choose a new password.</p>
        <form method="POST" action="">
            <input type="hidden" name="step" value="reset">
            <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></div>
            <div class="form-group"><label for="code">Reset Code</label><input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required></div>
            <div class="form-group"><label for="new_password">New Password</label><input type="password" id="new_password" name="new_password" minlength="8" required></div>
            <div class="form-group"><label for="confirm_password">Confirm New Password</label><input type="password" id="confirm_password" name="confirm_password" minlength="8" required></div>
            <button type="submit">Change Password</button>
        </form>
    <?php else: ?>
        <p>Enter your account email and we will send you a password reset code.</p>
        <form method="POST" action="">
            <input type="hidden" name="step" value="request">
            <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" required></div>
            <button type="submit">Send Reset Code</button>
        </form>
    <?php endif; ?>
    <div class="links"><a href="login.php">Back to Login</a></div>
</div>
</body>
</html>
