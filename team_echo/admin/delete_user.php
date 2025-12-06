<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

include('../db/db.php');  // Include database connection file

// Check if the user_id is provided
if (!isset($_GET['id'])) {
    echo "User ID is required.";
    exit;
}

// Get the user ID from the URL
$user_id = $_GET['id'];

// Delete the user from the database
$sql = "DELETE FROM management_user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo "User deleted successfully!";
} else {
    echo "Error: " . $stmt->error;
}

// Close the connection
$stmt->close();
$conn->close();

// Redirect back to the user management page
header("Location: manage_users.php");
exit;
?>
