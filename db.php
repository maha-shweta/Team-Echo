<?php
$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'team_echo_new';

$conn = new mysqli($host, $username, $password, $database);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 for proper character handling
$conn->set_charset("utf8mb4");
?>
