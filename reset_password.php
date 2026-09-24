<?php
session_start();
require_once 'config.php';

$error = '';
$info = '';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($token === '' || $email === '') {
        $error = 'The reset link is invalid or missing required information.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $check_stmt = $conn->prepare('SELECT id, email, token, expires_at FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW() LIMIT 1');
        $check_stmt->bind_param('ss', $email, $token);
        $check_stmt->execute();
        $token_result = $check_stmt->get_result();

        if ($token_result->num_rows === 0) {
            $error = 'This password reset link is invalid or has expired.';
        } else {
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            $update_stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE email = ? LIMIT 1');
            $update_stmt->bind_param('ss', $password_hash, $email);
            if ($update_stmt->execute()) {
                $delete_stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
                $delete_stmt->bind_param('s', $email);
                $delete_stmt->execute();
                $delete_stmt->close();

                $_SESSION['reset_success'] = 'Password updated successfully. Please log in.';
                header('Location: login.php');
                exit;
            } else {
                $error = 'Unable to update your password. Please try again.';
            }

            $update_stmt->close();
        }

        $check_stmt->close();
    }
}

if ($token === '' || $email === '') {
    $error = 'Password reset token is missing. Please request a new reset link.';
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
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 420px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
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
        <h2>Reset Password</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($info): ?>
            <div class="success"><?php echo htmlspecialchars($info, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($token !== '' && $email !== '' && !$error): ?>
            <form method="POST" action="reset_password.php?token=<?php echo urlencode($token); ?>&email=<?php echo urlencode($email); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                </div>

                <button type="submit">Update Password</button>
            </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
