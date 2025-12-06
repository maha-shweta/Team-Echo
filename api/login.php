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
    if (!isset($data['email'], $data['password'])) {
        echo json_encode(["message" => "Email and password are required."]);
        exit;
    }

    // Get the data from the JSON
    $email = $data['email'];
    $password = $data['password'];

    // Fetch user data from the database
    $sql = "SELECT * FROM management_user WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);  // Bind the email parameter
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if email exists
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $row['password_hash'])) {
                // Password is correct, send a success response
                echo json_encode([
                    "message" => "Login successful!",
                    "user_id" => $row['user_id'],
                    "role" => $row['role'],
                    "name" => $row['name']
                ]);
            } else {
                // Invalid password
                echo json_encode(["message" => "Invalid password."]);
            }
        } else {
            // Email not found
            echo json_encode(["message" => "No user found with this email."]);
        }

        $stmt->close();
    }
}

$conn->close();
?>
