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

    // Prepare the SQL query to mark the feedback as resolved
    $sql = "UPDATE feedback SET is_resolved = 1, resolved_by = ?, resolved_at = NOW() WHERE feedback_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters: resolved_by (user who resolves), feedback_id
        $stmt->bind_param("ii", $_SESSION['user_id'], $feedback_id);

        // Execute the query
        if ($stmt->execute()) {
            echo json_encode(["message" => "Feedback marked as resolved successfully."]);
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
