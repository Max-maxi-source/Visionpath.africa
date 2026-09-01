<?php
// mentor_feedback.php
// Handles feedback & referee submission via POST then redirects back
session_start();
require_once 'config.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit;
}

$mentor_user_id = $_SESSION['user_id'];

$stmt_approval = $conn->prepare("SELECT is_approved FROM mentor_profiles WHERE user_id = ?");
$stmt_approval->bind_param("i", $mentor_user_id);
$stmt_approval->execute();
$approval_result = $stmt_approval->get_result();
$approval_row = $approval_result->fetch_assoc();
$stmt_approval->close();

if (!$approval_row || empty($approval_row['is_approved'])) {
    header("Location: mentor_dashboard.php?fb_error=3");
    exit;
}

$project_id     = intval($_POST['project_id'] ?? 0);
$feedback_text  = trim($_POST['feedback_text'] ?? '');
$is_referee     = isset($_POST['is_referee']) ? 1 : 0;

if ($project_id <= 0 || empty($feedback_text)) {
    // Redirect back with a simple error flag
    header("Location: mentor_dashboard.php?fb_error=1");
    exit;
}

// Upsert: update if exists, insert if not
$stmt_dup = $conn->prepare(
    "SELECT id FROM project_feedback WHERE project_id = ? AND mentor_id = ?"
);
$stmt_dup->bind_param("ii", $project_id, $mentor_user_id);
$stmt_dup->execute();
$stmt_dup->store_result();
$exists = $stmt_dup->num_rows > 0;
$stmt_dup->close();

if ($exists) {
    $stmt = $conn->prepare(
        "UPDATE project_feedback
         SET feedback_text = ?, is_referee = ?
         WHERE project_id = ? AND mentor_id = ?"
    );
    $stmt->bind_param("siii", $feedback_text, $is_referee, $project_id, $mentor_user_id);
} else {
    $stmt = $conn->prepare(
        "INSERT INTO project_feedback (project_id, mentor_id, feedback_text, is_referee)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("iisi", $project_id, $mentor_user_id, $feedback_text, $is_referee);
}

if ($stmt->execute()) {
    header("Location: mentor_dashboard.php?fb_success=1");
} else {
    header("Location: mentor_dashboard.php?fb_error=2");
}
$stmt->close();
$conn->close();
exit;
?>
