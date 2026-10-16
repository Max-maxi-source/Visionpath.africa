<?php
// session.php – small DB-backed session wrapper
require_once __DIR__ . '/config.php';

define('SESSION_COOKIE', 'visionpath_sess');
define('SESSION_TTL', 60 * 60 * 24 * 7); // 1 week

function start_db_session(): void {
    $token = $_COOKIE[SESSION_COOKIE] ?? '';
    if ($token && preg_match('/^[a-f0-9]{64}$/', $token)) {
        $stmt = $GLOBALS['conn']->prepare(
            "SELECT user_id, role, data, expires_at FROM sessions WHERE id = ? AND expires_at > NOW()"
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role']    = $row['role'];
            $_SESSION['data']    = json_decode($row['data'] ?? '{}', true);
            return;
        }
    }
    $_SESSION = [];
}

function create_db_session(int $user_id, string $role, array $extra = []): string {
    $token = hash('sha256', random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + SESSION_TTL);
    $data = json_encode($extra);
    
    $stmt = $GLOBALS['conn']->prepare(
        "INSERT INTO sessions (id, user_id, role, data, expires_at) VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param('sisss', $token, $user_id, $role, $data, $expires);
    $stmt->execute();
    $stmt->close();

    return sprintf(
        "%s=%s; Path=/; HttpOnly; SameSite=Lax; Max-Age=%d; Secure",
        SESSION_COOKIE,
        $token,
        SESSION_TTL
    );
}

function destroy_db_session(): void {
    $token = $_COOKIE[SESSION_COOKIE] ?? '';
    if ($token) {
        $stmt = $GLOBALS['conn']->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
        setcookie(SESSION_COOKIE, '', time() - 3600, '/', '', true, true);
    }
    $_SESSION = [];
}
?>
