<?php
session_start(); // Start the session

// Include the database connection file
include('../db/db.php');

// Check if the login form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        echo "Email and password are required.";
        exit;
    }

    // Fetch the user from the database based on the provided email
    $sql = "SELECT * FROM management_user WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email); // Bind the email parameter
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables for logged-in user
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                // Redirect based on role
                if ($user['role'] == 'admin') {
                    // Redirect to Admin Dashboard
                    header('Location: ../admin/admin_dashboard.php');
                    exit;
                } elseif ($user['role'] == 'hr') {
                    // Redirect to HR Dashboard (you can create this page)
                    header('Location: ../hr/hr_dashboard.php');
                    exit;
                } else {
                    // Redirect to User Dashboard (you can create this page)
                    header('Location: ../user/user_dashboard.php');
                    exit;
                }
            } else {
                echo "Invalid password.";
            }
        } else {
            echo "No user found with that email.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!-- Login Form -->
<h2>Login</h2>
<form method="POST" action="">
    Email: <input type="email" name="email" required><br>
    Password: <input type="password" name="password" required><br>
    <input type="submit" value="Login">
</form>
