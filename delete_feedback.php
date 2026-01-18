<?php
session_start();

// Ensure only HR/Admin can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'hr')) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Check if feedback ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid feedback ID.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/admin_dashboard.php'));
    exit;
}

$feedback_id = intval($_GET['id']);

// Delete feedback from the database
$sql = "DELETE FROM feedback WHERE feedback_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $feedback_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Feedback deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting feedback: " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "Database error: " . $conn->error;
}

$conn->close();

// Redirect back to the referring page
$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../admin/admin_dashboard.php';
header('Location: ' . $redirect_url);
exit;
?>