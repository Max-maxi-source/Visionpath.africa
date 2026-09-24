<?php
// setup_db.php
$db_host = 'localhost';
$db_user = 'root'; // default for WampServer
$db_pass = ''; // default for WampServer

$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql_file = file_get_contents(__DIR__ . '/database.sql');
if (!$sql_file) {
    die("Could not read database.sql file.");
}

if ($conn->multi_query($sql_file)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #28a745;'>Database and tables created successfully!</h2>";
    echo "<p>You can now go to the <a href='register.php' style='color: #0056b3; font-weight: bold;'>Registration Page</a>.</p>";
    echo "</div>";
} else {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px; color: #d9534f;'>";
    echo "<h2>Error creating database:</h2><p>" . $conn->error . "</p>";
    echo "</div>";
}

$conn->close();
?>
