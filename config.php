<?php
// config.php
$db_host = 'localhost';
$db_user = 'root'; // default WampServer user
$db_pass = '';     // default WampServer password
$db_name = 'visionpath_db';

// Enable error reporting for MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    // Display a generic error to the user without exposing sensitive details
    exit('<h3>Database connection failed. Please ensure the MySQL server is running and the database is created.</h3>');
}
?>
