<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM management_user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $_SESSION['error'] = "User not found.";
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // Validate inputs
    if (empty($name) || empty($email)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
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
            $update_sql = "UPDATE management_user SET name = ?, email = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $name, $email, $user_id);

            if ($update_stmt->execute()) {
                $_SESSION['name'] = $name; // Update session
                $success_message = "Profile updated successfully!";
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $error_message = "Error updating profile: " . $update_stmt->error;
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
    <title>My Profile - Anonymous Feedback System</title>
    <link rel="stylesheet" href="profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="../admin/admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="../admin/manage_users.php" class="nav-item">Manage Users</a>
                <a href="../admin/manage_categories.php" class="nav-item">Categories</a>
                <a href="../admin/manage_tags.php" class="nav-item">Tags</a>
                <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
                <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
            <?php elseif ($_SESSION['role'] == 'hr'): ?>
                <a href="../hr/hr_dashboard.php" class="nav-item">Dashboard</a>
                <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
                <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
            <?php else: ?>
                <a href="user_dashboard.php" class="nav-item">Dashboard</a>
                <a href="../feedback/submit_feedback.php" class="nav-item">Submit Feedback</a>
                <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
                <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
            <?php endif; ?>
            <a href="profile.php" class="nav-item active">My Profile</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="header-buttons">
                <a href="profile.php" class="btn-header">Profile</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <div class="container">
                <!-- Header -->
                <div class="header-card">
                    <h1>My Profile</h1>
                    <p>Manage your account information and update your personal details.</p>
                </div>

                <!-- Messages -->
                <?php
                if (isset($success_message)) {
                    echo '<div class="message success">✓ ' . htmlspecialchars($success_message) . '</div>';
                }
                if (isset($error_message)) {
                    echo '<div class="message error">✗ ' . htmlspecialchars($error_message) . '</div>';
                }
                ?>

                <!-- Back Button -->
                <?php
                // Dynamic back link based on role
                if ($_SESSION['role'] == 'admin') {
                    echo '<a href="../admin/admin_dashboard.php" class="button btn-back">← Back to Dashboard</a>';
                } elseif ($_SESSION['role'] == 'hr') {
                    echo '<a href="../hr/hr_dashboard.php" class="button btn-back">← Back to Dashboard</a>';
                } else {
                    echo '<a href="user_dashboard.php" class="button btn-back">← Back to Dashboard</a>';
                }
                ?>

                <!-- Account Info Box -->
                <div class="info-box">
                    <h4>Account Information</h4>
                    <div class="info-item">
                        <span>User ID:</span>
                        <span>#<?php echo htmlspecialchars($user['user_id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span>Role:</span>
                        <span class="badge"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span>Member Since:</span>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="form-card">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            <span class="help-text">Enter your full name as you'd like it to appear</span>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <span class="help-text">This email will be used for account-related communications</span>
                        </div>

                        <button type="submit" class="btn-primary">Update Profile</button>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div class="button-group">
                    <a href="change_password.php" class="btn-warning">Change Password</a>
                    <?php
                    // Dynamic back link based on role
                    if ($_SESSION['role'] == 'admin') {
                        echo '<a href="../admin/admin_dashboard.php" class="btn-secondary">Back to Dashboard</a>';
                    } elseif ($_SESSION['role'] == 'hr') {
                        echo '<a href="../hr/hr_dashboard.php" class="btn-secondary">Back to Dashboard</a>';
                    } else {
                        echo '<a href="user_dashboard.php" class="btn-secondary">Back to Dashboard</a>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>