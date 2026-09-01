<?php
// update_db.php
require_once 'config.php';

$sql_file = file_get_contents(__DIR__ . '/db_update.sql');
if (!$sql_file) {
    die("Could not read db_update.sql file.");
}

if ($conn->multi_query($sql_file)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #28a745;'>Database updated successfully!</h2>";
    echo "<p>You can now go to the <a href='register.php' style='color: #0056b3; font-weight: bold;'>Registration Page</a>.</p>";
    echo "</div>";
} else {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px; color: #d9534f;'>";
    echo "<h2>Error updating database:</h2><p>" . $conn->error . "</p>";
    echo "</div>";
}
$conn->close();
?>
