<?php
// Start the session to check if the user is logged in
session_start();

// Include the database connection file
include('../db/db.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Ensure the user is logged in and has the role of HR or Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'hr' && $_SESSION['role'] != 'admin')) {
    echo json_encode(["message" => "You do not have permission to perform this action."]);
    exit;
}

// Check if the feedback_id is provided via GET request
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $feedback_id = $_GET['id'];

    // Prepare the SQL query to delete the feedback
    $sql = "DELETE FROM feedback WHERE feedback_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameter: feedback_id
        $stmt->bind_param("i", $feedback_id);

        // Execute the query
        if ($stmt->execute()) {
            echo json_encode(["message" => "Feedback deleted successfully."]);
        } else {
            echo json_encode(["message" => "Error: " . $stmt->error]);
        }

        $stmt->close();
    }
} else {
    echo json_encode(["message" => "Invalid feedback ID."]);
}

$conn->close();
?>
