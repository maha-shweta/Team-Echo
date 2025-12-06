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
    if (!isset($data['name'], $data['email'], $data['password'], $data['role'])) {
        echo json_encode(["message" => "All fields are required."]);
        exit;
    }

    // Get the data from the JSON
    $name = $data['name'];
    $email = $data['email'];
    $password = $data['password'];
    $role = $data['role']; // 'admin', 'hr', or 'user'

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the database
    $sql = "INSERT INTO management_user (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssss", $name, $email, $password_hash, $role);
        if ($stmt->execute()) {
            echo json_encode(["message" => "User registered successfully!"]);
        } else {
            echo json_encode(["message" => "Error: " . $stmt->error]);
        }
        $stmt->close();
    }
}

$conn->close();
?>
