<?php
session_start();
require_once 'config.php';
require_once 'send_mail.php';

$error = '';
$success = '';
$verify_email = $_SESSION['verify_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';
    $email = trim($_POST['email'] ?? $verify_email);
    $verify_email = $email;

    if ($action === 'verify') {
        $code = trim($_POST['code'] ?? '');
        $stmt = $conn->prepare("SELECT id, code_hash FROM verification_codes WHERE email = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record || !preg_match('/^\d{6}$/', $code) || !password_verify($code, $record['code_hash'])) {
            $error = 'Invalid or expired verification code.';
        } else {
            $stmt = $conn->prepare('UPDATE users SET is_verified = 1 WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare('DELETE FROM verification_codes WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->close();
            unset($_SESSION['verify_email'], $_SESSION['verify_role']);
            $success = 'Account verified successfully. You can now log in.';
        }
    } elseif ($action === 'resend') {
        $stmt = $conn->prepare('SELECT name FROM users WHERE email = ? AND is_verified = 0');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$user) {
            $error = 'No pending verification found for this email.';
        } else {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = password_hash($code, PASSWORD_DEFAULT);
            $expires_at = date('Y-m-d H:i:s', time() + 900);
            $stmt = $conn->prepare('DELETE FROM verification_codes WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare('INSERT INTO verification_codes (email, code_hash, expires_at) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $email, $hash, $expires_at);
            $stmt->execute();
            $stmt->close();
            try {
                send_verification_email($email, $user['name'], $code);
                $success = 'A new verification code has been sent to your email.';
            } catch (Throwable $mail_error) {
                error_log('Verification resend failed: ' . $mail_error->getMessage());
                $error = 'Could not send a verification email. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verify Account - VisionPath Africa</title></head>
<body><main><h1>Verify your VisionPath Africa account</h1>
<?php if ($error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<?php if ($success): ?><p><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
<?php if (!$success || strpos($success, 'verified') === false): ?>
<form method="post"><input type="hidden" name="action" value="verify"><label>Email <input type="email" name="email" value="<?php echo htmlspecialchars($verify_email); ?>" required></label><label>6-digit code <input type="text" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" required></label><button type="submit">Verify account</button></form>
<form method="post"><input type="hidden" name="action" value="resend"><input type="hidden" name="email" value="<?php echo htmlspecialchars($verify_email); ?>"><button type="submit">Resend code</button></form>
<?php else: ?><a href="login.php">Continue to login</a><?php endif; ?></main></body></html>