<?php
// Include the database connection file
include('../db/db.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the raw POST data (JSON)
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the required fields are present
    if (!isset($data['category_id'], $data['feedback_text'])) {
        echo json_encode(["message" => "Category ID and Feedback are required."]);
        exit;
    }

    // Get the data from the JSON
    $category_id = $data['category_id']; // The category of the feedback
    $feedback_text = $data['feedback_text']; // The feedback content
    $is_anonymous = isset($data['is_anonymous']) ? $data['is_anonymous'] : 1; // Anonymous flag (default to 1)

    // Prepare the SQL query to insert feedback into the database
    $sql = "INSERT INTO feedback (category_id, feedback_text, is_anonymous, submitted_at) 
            VALUES (?, ?, ?, NOW())";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iss", $category_id, $feedback_text, $is_anonymous); // Bind parameters
        if ($stmt->execute()) {
            echo json_encode(["message" => "Feedback submitted successfully!"]);
        } else {
            echo json_encode(["message" => "Error: " . $stmt->error]);
        }
        $stmt->close();
    }
}

$conn->close();
?>
