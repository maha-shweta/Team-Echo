<?php
session_start();

// Ensure only HR/Admin can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'hr')) {
    header('Location: login.php'); // Redirect to login page if not logged in or not HR/Admin
    exit;
}

include('../db/db.php'); // Include the database connection file

// Check if feedback ID is provided
if (isset($_GET['id'])) {
    $feedback_id = $_GET['id'];

    // Delete feedback from the database
    $sql = "DELETE FROM feedback WHERE feedback_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $feedback_id); // Bind feedback_id as an integer
        if ($stmt->execute()) {
            echo "Feedback deleted successfully.";
        } else {
            echo "Error deleting feedback.";
        }
        $stmt->close();
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>
