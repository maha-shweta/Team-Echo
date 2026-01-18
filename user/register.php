<?php
session_start();
include('../db/db.php');

// Check if user is already logged in and is admin
$is_admin_adding = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    $is_admin_adding = true;
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate fields
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
    } else {
        // Check if email already exists
        $check_sql = "SELECT user_id FROM management_user WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = "Email already exists. Please use a different email.";
            $check_stmt->close();
        } else {
            $check_stmt->close();

            // Hash the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user into the database
            $sql = "INSERT INTO management_user (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssss", $name, $email, $password_hash, $role);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "User registered successfully!";
                    $stmt->close();
                    
                    // Redirect based on who is registering
                    if ($is_admin_adding) {
                        header("Location: ../admin/manage_users.php");
                    } else {
                        header("Location: login.php");
                    }
                    exit;
                } else {
                    $_SESSION['error'] = "Error: " . $stmt->error;
                    $stmt->close();
                }
            } else {
                $_SESSION['error'] = "Database error. Please try again.";
            }
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
    <title>Register - Anonymous Feedback System</title>
    <link rel="stylesheet" href="register.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Left Section: Registration Form -->
        <div class="left-section">
            <div class="form-wrapper">
                <h1 class="title-text">Create New Account</h1>
                <p class="sub-text">Register a new user for the feedback system</p>

                <!-- Messages -->
                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="message error">✗ ' . htmlspecialchars($_SESSION['error']) . '</div>';
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    echo '<div class="message success">✓ ' . htmlspecialchars($_SESSION['success']) . '</div>';
                    unset($_SESSION['success']);
                }
                ?>

                <!-- Registration Form -->
                <form method="POST" action="" id="registerForm">
                    <!-- Name Field -->
                    <div class="input-group">
                        <label for="name">Full Name</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="input-white" 
                               placeholder="Enter full name" 
                               required>
                    </div>

                    <!-- Email Field -->
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="input-blue" 
                               placeholder="Enter email address" 
                               required>
                    </div>

                    <!-- Password Field -->
                    <div class="input-group password-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="input-blue" 
                                   placeholder="Enter password (min 8 characters)" 
                                   required
                                   minlength="8"
                                   oninput="updateCharCount()">
                            <button type="button" class="show-btn" onclick="togglePassword()">Show</button>
                        </div>
                        <div class="char-count" id="charCount">0/8 characters</div>
                    </div>

                    <!-- Role Field -->
                    <div class="input-group">
                        <label for="role">User Role</label>
                        <select id="role" name="role" class="input-white" required>
                            <option value="">-- Select Role --</option>
                            <option value="user">User</option>
                            <option value="hr">HR</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">Register User</button>
                </form>

                <!-- Footer Links -->
                <div class="form-footer">
                    <?php if ($is_admin_adding): ?>
                        <a href="../admin/manage_users.php">← Back to Manage Users</a>
                    <?php else: ?>
                        Already have an account? <a href="login.php">Login here</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Section: Welcome Content -->
        <div class="right-section">
            <div class="content-wrapper">
                <h1>Join Our Feedback System</h1>
                <p>Register to access the anonymous feedback management platform. Help improve our community by sharing and managing valuable feedback.</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const showBtn = document.querySelector('.show-btn');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                showBtn.textContent = 'Hide';
            } else {
                passwordInput.type = 'password';
                showBtn.textContent = 'Show';
            }
        }

        // Update character count
        function updateCharCount() {
            const passwordInput = document.getElementById('password');
            const charCount = document.getElementById('charCount');
            const length = passwordInput.value.length;
            charCount.textContent = length + '/8 characters';
            
            if (length >= 8) {
                charCount.style.color = '#28a745';
            } else {
                charCount.style.color = '#999';
            }
        }
    </script>
</body>
</html>