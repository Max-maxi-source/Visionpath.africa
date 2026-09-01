<?php
// verify.php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$simulated_email = '';
$verify_email = $_SESSION['verify_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';
    $email = trim($_POST['email'] ?? '');
    
    if ($action === 'verify') {
        $code = trim($_POST['code'] ?? '');
        if (empty($email) || empty($code)) {
            $error = "Please enter your email and the 6-digit code.";
        } else {
            // Check if code exists and is valid for this email
            $stmt_check = $conn->prepare("SELECT id FROM verification_codes WHERE email = ? AND code = ?");
            $stmt_check->bind_param("ss", $email, $code);
            $stmt_check->execute();
            $stmt_check->store_result();
            
            if ($stmt_check->num_rows > 0) {
                // Update user to verified
                $stmt_update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
                $stmt_update->bind_param("s", $email);
                if ($stmt_update->execute()) {
                    $success = "Account verified successfully! You can now log in.";
                    
                    // Delete the code so it cannot be used again
                    $stmt_del = $conn->prepare("DELETE FROM verification_codes WHERE email = ?");
                    $stmt_del->bind_param("s", $email);
                    $stmt_del->execute();
                    $stmt_del->close();
                    
                    // Clear session var
                    unset($_SESSION['verify_email']);
                    unset($_SESSION['verify_role']);
                } else {
                    $error = "Failed to verify account. Please try again.";
                }
                $stmt_update->close();
            } else {
                $error = "Invalid or expired verification code.";
            }
            $stmt_check->close();
        }
    } elseif ($action === 'resend') {
        if (empty($email)) {
            $error = "Please enter your email address to resend the code.";
        } else {
            // Check if user exists and is not verified
            $stmt_user = $conn->prepare("SELECT id, name, role FROM users WHERE email = ? AND is_verified = 0");
            $stmt_user->bind_param("s", $email);
            $stmt_user->execute();
            $result = $stmt_user->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // Delete old codes
                $stmt_del = $conn->prepare("DELETE FROM verification_codes WHERE email = ?");
                $stmt_del->bind_param("s", $email);
                $stmt_del->execute();
                $stmt_del->close();
                
                // Generate new 6 digit code
                $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                
                $stmt_code = $conn->prepare("INSERT INTO verification_codes (email, code) VALUES (?, ?)");
                $stmt_code->bind_param("ss", $email, $code);
                
                if ($stmt_code->execute()) {
                    $name = $user['name'];
                    $subject = "VisionPath - Your New Verification Code";
                    $message = "Hello $name,\n\nYour new verification code is: $code\n\nPlease enter this code to activate your account.";
                    $headers = "From: noreply@visionpath.local\r\n";
                    $headers .= "Reply-To: noreply@visionpath.local\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();
                    
                    @mail($email, $subject, $message, $headers);
                    
                    $success = "A new verification code has been sent to your email.";
                    $simulated_email = "Local Testing Notice: Your NEW code is <strong>$code</strong>. Please copy it.";
                    
                    // Update session email just in case they typed it manually
                    $_SESSION['verify_email'] = $email;
                    $verify_email = $email; 
                } else {
                    $error = "Failed to generate a new code. Please try again.";
                }
                $stmt_code->close();
            } else {
                $error = "No pending verification found for this email. You might already be verified, or the email is incorrect.";
            }
            $stmt_user->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px; text-align: center; letter-spacing: 2px; }
        input[type="email"] { text-align: left; letter-spacing: normal; }
        input:focus { border-color: #28a745; outline: none; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; margin-top: 10px; transition: background 0.3s; }
        button:hover { background: #218838; }
        .btn-resend { background: #6c757d; margin-top: 10px; }
        .btn-resend:hover { background: #5a6268; }
        .error { color: #d9534f; margin-bottom: 15px; text-align: center; background: #fdf2f2; padding: 10px; border-radius: 4px; border: 1px solid #d9534f; font-size: 14px; }
        .success { color: #5cb85c; margin-bottom: 15px; text-align: center; background: #f4fdf4; padding: 10px; border-radius: 4px; border: 1px solid #5cb85c; font-size: 14px; }
        .notice { background: #e9ecef; padding: 15px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; border: 1px solid #ced4da; color: #495057; }
        .instructions { text-align: center; color: #666; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        .links { text-align: center; margin-top: 20px; font-size: 14px; }
        .links a { color: #0056b3; text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h2>Verify Account</h2>
    <div class="instructions">Enter the 6-digit verification code that was sent to your email address.</div>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php if ($simulated_email): ?>
            <div class="notice"><?php echo $simulated_email; ?></div>
        <?php endif; ?>
        <?php if (strpos($success, 'Account verified') !== false): ?>
            <a href="login.php" style="display:block; text-align:center; padding:12px; background:#0056b3; color:white; text-decoration:none; border-radius:6px; font-weight:600; margin-top:15px;">Proceed to Login</a>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (strpos($success, 'Account verified') === false): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="verify" id="form_action">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($verify_email); ?>" required placeholder="email@example.com">
            </div>
            
            <div class="form-group">
                <label for="code">6-Digit Code</label>
                <input type="text" id="code" name="code" maxlength="6" pattern="\d{6}" title="Please enter exactly 6 digits" placeholder="123456">
            </div>
            
            <button type="submit" onclick="document.getElementById('form_action').value='verify';">Verify Now</button>
            <button type="submit" class="btn-resend" onclick="document.getElementById('form_action').value='resend'; document.getElementById('code').removeAttribute('required');">Resend Verification Code</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
