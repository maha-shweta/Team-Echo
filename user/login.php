<?php
session_start(); // Start the session

// Include the database connection file
include('../db/db.php');

$error_message = '';
$success_message = '';

// Check if the login form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } else {
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
                        // Redirect to HR Dashboard
                        header('Location: ../hr/hr_dashboard.php');
                        exit;
                    } else {
                        // Redirect to User Dashboard
                        header('Location: ../user/user_dashboard.php');
                        exit;
                    }
                } else {
                    $error_message = "Invalid password. Please try again.";
                }
            } else {
                $error_message = "No user found with that email.";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Feedback Management System</title>
    <link rel="stylesheet" href="login.css"> <!-- Include the template's CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"> <!-- Google Fonts -->
</head>
<body>
    <div class="container">
        <!-- Left Section -->
        <div class="left-section">
            <div class="login-wrapper">
                <h2 class="welcome-text">Welcome Back</h2>
                <p class="sub-text">Sign in to access your feedback dashboard</p>

                <!-- Display error/success messages -->
                <?php if (!empty($error_message)): ?>
                    <div class="message error">
                        ❌ <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="message success">
                        ✅ <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="">
                    <div class="input-group email-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="mehenaz@gmail.com" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="input-group password-group">
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required placeholder="Password">
                            <span class="show-btn">Show</span>
                        </div>
                        <div class="char-count">0/20</div>
                    </div>

                    <button type="submit" class="login-btn">Login</button>

                    <!-- Footer Links -->
                    <div class="form-footer">
                        Don't have an account? <a href="registration.html">Create Account</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <div class="content-wrapper">
                <h1>Effortless Campus Feedback Starts with Better Insights</h1>
                <p>"Log in to manage feedback, analyze data trends, and empower your institution."</p>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const showBtn = document.querySelector(".show-btn");

        showBtn.addEventListener("click", () => {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                showBtn.textContent = "Hide";
            } else {
                passwordInput.type = "password";
                showBtn.textContent = "Show";
            }
        });
    </script>
</body>
</html>
