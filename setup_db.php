<?php
// setup_db.php
$db_host = 'localhost';
$db_user = 'root'; // default for WampServer
$db_pass = ''; // default for WampServer
$db_name = 'visionpath_db';
$db_port = 3306;

$conn = new mysqli($db_host, $db_user, $db_pass, '', $db_port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

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

    if ($conn->errno) {
        die("Error creating database: " . $conn->error);
    }

    if (!$conn->select_db($db_name)) {
        die("Database was created but could not be selected: " . $conn->error);
    }

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
