<?php
header('Content-Type: application/json');  // Set the response type to JSON

// Include the database connection file
include('../db/db.php');

// Fetch all users from the database
$sql = "SELECT user_id, name, email, role FROM management_user";  // Modify the query based on your table
$result = $conn->query($sql);

// Check if there are users
if ($result->num_rows > 0) {
    $users = array();  // Create an empty array to hold user data

    // Fetch all rows and store them in the $users array
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    // Return the users as a JSON response
    echo json_encode($users);
} else {
    // If no users found, return an empty message
    echo json_encode(["message" => "No users found."]);
}

// Close the database connection
$conn->close();
?>
