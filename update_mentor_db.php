<?php
// update_mentor_db.php – run once in browser to apply mentor schema for VisionPath
require_once 'config.php';

// Prevent fatal errors when the base VisionPath schema has not been created yet.
$existing_users = $conn->query("SHOW TABLES LIKE 'users'");
$existing_projects = $conn->query("SHOW TABLES LIKE 'projects'");

if (!$existing_users || $existing_users->num_rows === 0) {
    echo "<div style='font-family:sans-serif;text-align:center;margin-top:50px;color:#d9534f;'>";
    echo "<h2>Base database missing.</h2>";
    echo "<p>Please run <strong>setup_db.php</strong> first, then try this page again.</p>";
    echo "<p><a href='setup_db.php' style='color:#0056b3;font-weight:bold;'>Open setup_db.php</a></p>";
    echo "</div>";
    $conn->close();
    exit;
}

if (!$existing_projects || $existing_projects->num_rows === 0) {
    echo "<div style='font-family:sans-serif;text-align:center;margin-top:50px;color:#d9534f;'>";
    echo "<h2>Projects table missing.</h2>";
    echo "<p>Please run <strong>setup_db.php</strong> first, then try this page again.</p>";
    echo "<p><a href='setup_db.php' style='color:#0056b3;font-weight:bold;'>Open setup_db.php</a></p>";
    echo "</div>";
    $conn->close();
    exit;
}

$sql_file = file_get_contents(__DIR__ . '/db_mentor_update.sql');
if (!$sql_file) {
    die("Could not read db_mentor_update.sql file.");
}

try {
    if ($conn->multi_query($sql_file)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        echo "<div style='font-family:sans-serif;text-align:center;margin-top:50px;'>";
        echo "<h2 style='color:#28a745;'>✅ Mentor tables created successfully!</h2>";
        echo "<p>You can now <a href='register.php' style='color:#0056b3;font-weight:bold;'>Register a Mentor</a>.</p>";
        echo "</div>";
    } else {
        echo "<div style='font-family:sans-serif;text-align:center;margin-top:50px;color:#d9534f;'>";
        echo "<h2>Error applying schema:</h2><p>" . htmlspecialchars($conn->error) . "</p>";
        echo "</div>";
    }
} catch (mysqli_sql_exception $e) {
    echo "<div style='font-family:sans-serif;text-align:center;margin-top:50px;color:#d9534f;'>";
    echo "<h2>Mentor schema could not be applied.</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please make sure <strong>setup_db.php</strong> has been run successfully first.</p>";
    echo "<p><a href='setup_db.php' style='color:#0056b3;font-weight:bold;'>Run setup_db.php</a></p>";
    echo "</div>";
}

$conn->close();
?>
