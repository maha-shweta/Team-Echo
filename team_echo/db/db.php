<?php
$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'team_echo';

$conn = new mysqli($host, $username, $password, $database);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If the connection is successful, print this message (for testing purposes)
echo "Connected successfully to the database!";
?>
