<?php
// Include the database connection file
include('../db/db.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Fetch all feedback data from the database
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name, f.is_anonymous
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id";

// Execute the query
$result = $conn->query($sql);

// Check if there are any feedback records
if ($result->num_rows > 0) {
    $feedbacks = array(); // Create an empty array to store feedback data

    // Fetch each row and store it in the $feedbacks array
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }

    // Return the feedbacks as a JSON response
    echo json_encode($feedbacks);
} else {
    // If no feedback is found, return an empty message
    echo json_encode(["message" => "No feedback found."]);
}

// Close the database connection
$conn->close();
?>
