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

// Check if the user_id is provided via POST request
if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $role = isset($_POST['role']) ? $_POST['role'] : null;

    // Prepare the SQL query to update user details
    $sql = "UPDATE management_user SET ";
    
    // Dynamically add fields to update (only if they are provided)
    $params = [];
    if ($name) {
        $sql .= "name = ?, ";
        $params[] = $name;
    }
    if ($email) {
        $sql .= "email = ?, ";
        $params[] = $email;
    }
    if ($role) {
        $sql .= "role = ?, ";
        $params[] = $role;
    }

    // Remove trailing comma
    $sql = rtrim($sql, ", ");

    $sql .= " WHERE user_id = ?"; // Add condition for updating the specific user
    $params[] = $user_id;

    // Prepare the SQL query
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters (dynamically binding based on available fields)
        $types = str_repeat("s", count($params) - 1) . "i"; // "s" for string, "i" for integer (user_id)
        $stmt->bind_param($types, ...$params);
        
        // Execute the query
        if ($stmt->execute()) {
            echo json_encode(["message" => "User updated successfully!"]);
        } else {
            echo json_encode(["message" => "Error: " . $stmt->error]);
        }

        $stmt->close();
    }
} else {
    echo json_encode(["message" => "Invalid user ID."]);
}

$conn->close();
?>
