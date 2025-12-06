<?php
// Include the database connection file
include('../db/db.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; // 'admin', 'hr', or 'user'

    // Check if fields are not empty
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        echo "All fields are required.";
        exit;
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the database
    $sql = "INSERT INTO management_user (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssss", $name, $email, $password_hash, $role);
        if ($stmt->execute()) {
            // Redirect to login page after successful registration
            header("Location: login.php");
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!-- Simple registration form -->
<form method="POST" action="">
    Name: <input type="text" name="name" required><br>
    Email: <input type="email" name="email" required><br>
    Password: <input type="password" name="password" required><br>
    Role: 
    <select name="role">
        <option value="admin">Admin</option>
        <option value="hr">HR</option>
        <option value="user">User</option>
    </select><br>
    <input type="submit" value="Register">
</form>
