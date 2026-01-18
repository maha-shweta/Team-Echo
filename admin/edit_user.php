<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Check if the user_id is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header('Location: manage_users.php');
    exit;
}

$user_id = intval($_GET['id']);

// Fetch user details from the database based on the ID
$sql = "SELECT * FROM management_user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $_SESSION['error'] = "User not found.";
    header('Location: manage_users.php');
    exit;
}

// Handle the form submission to update user details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($role)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (!in_array($role, ['admin', 'hr', 'user'])) {
        $error_message = "Invalid role selected.";
    } else {
        // Check if email already exists (except for current user)
        $check_sql = "SELECT user_id FROM management_user WHERE email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Email already exists for another user.";
        } else {
            // Update user data
            $update_sql = "UPDATE management_user SET name = ?, email = ?, role = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssi", $name, $email, $role, $user_id);

            if ($update_stmt->execute()) {
                $_SESSION['success'] = "User updated successfully!";
                header('Location: manage_users.php');
                exit;
            } else {
                $error_message = "Error updating user: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Anonymous Feedback System</title>
    <link rel="stylesheet" href="edit_user.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
            <a href="manage_users.php" class="nav-item active">Manage Users</a>
            <a href="../category/manage_category.php" class="nav-item">Manage Categories</a>
            <a href="manage_tags.php" class="nav-item">Manage Tags</a>
            <a href="analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
            <a href="../feedback/export_feedback.php" class="nav-item">Export Feedback</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Welcome, Admin <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="header-buttons">
                <a href="../user/profile.php" class="btn-header">Profile</a>
                <a href="../user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>Edit User</h1>
                    <p>Update user information and role settings</p>
                </div>

                <!-- Back Button -->
                <a href="manage_users.php" class="button btn-back">← Back to Manage Users</a>

                <!-- Messages -->
                <?php if (isset($error_message)): ?>
                    <div class="message error">✗ <?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <!-- User Info Box -->
                <div class="info-box">
                    <h4>User Information</h4>
                    <div class="info-item">
                        <span>User ID:</span>
                        <span>#<?php echo htmlspecialchars($user['user_id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span>Current Role:</span>
                        <span class="badge"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span>Member Since:</span>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>

                <!-- Edit User Form -->
                <div class="form-card">
                    <h3>Update User Details</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            <span class="help-text">Enter the user's full name</span>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <span class="help-text">This email will be used for login and notifications</span>
                        </div>

                        <div class="form-group">
                            <label for="role">User Role <span class="required">*</span></label>
                            <select id="role" name="role" required>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="hr" <?php echo $user['role'] == 'hr' ? 'selected' : ''; ?>>HR</option>
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                            </select>
                            <span class="help-text">Select the appropriate role for this user</span>
                        </div>

                        <button type="submit" class="btn-primary">Update User</button>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div class="button-group">
                    <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" 
                       class="btn-danger"
                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                        Delete User
                    </a>
                    <a href="manage_users.php" class="btn-secondary">Cancel & Go Back</a>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>