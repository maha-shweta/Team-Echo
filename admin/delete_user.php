<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Check if the user_id is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header('Location: manage_users.php');
    exit;
}

$user_id = intval($_GET['id']);

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account!";
    header('Location: manage_users.php');
    exit;
}

// Delete the user from the database
$sql = "DELETE FROM management_user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "User deleted successfully!";
    } else {
        $_SESSION['error'] = "User not found or already deleted.";
    }
} else {
    $_SESSION['error'] = "Error deleting user: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to the user management page
header("Location: manage_users.php");
exit;
?>
