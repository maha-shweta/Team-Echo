<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } else {
        // SQL matching your table: password_hash
        $sql = "SELECT password_hash FROM management_user WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_password, $user['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $update = $conn->prepare("UPDATE management_user SET password_hash = ? WHERE user_id = ?");
            $update->bind_param("si", $new_hash, $user_id);
            
            if ($update->execute()) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Database error.";
            }
        } else {
            $error_message = "Current password incorrect.";
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
    <title>Change Password - Anonymous Feedback System</title>
    <link rel="stylesheet" href="change_password.css">
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
                    <h1>Security Settings</h1>
                    <p>Update your login password to keep your account secure.</p>
                </div>

                <!-- Messages -->
                <?php
                if ($success_message) {
                    echo '<div class="message success">✓ ' . htmlspecialchars($success_message) . '</div>';
                }
                if ($error_message) {
                    echo '<div class="message error">✗ ' . htmlspecialchars($error_message) . '</div>';
                }
                ?>

                <!-- Back Button -->
                <a href="profile.php" class="button btn-back">← Back to Profile</a>

                <!-- Info Box -->
                <div class="info-box">
                    <h4>Password Requirements</h4>
                    <ul>
                        <li>Use a strong password with a mix of letters, numbers, and symbols</li>
                        <li>Avoid using common words or personal information</li>
                        <li>Password should be at least 8 characters long</li>
                        <li>Don't reuse passwords from other accounts</li>
                    </ul>
                </div>

                <!-- Change Password Form -->
                <div class="form-card">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Current Password <span class="required">*</span></label>
                            <input type="password" id="current_password" name="current_password" required>
                            <span class="help-text">Enter your current password for verification</span>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password <span class="required">*</span></label>
                            <input type="password" id="new_password" name="new_password" required>
                            <span class="help-text">Choose a strong and unique password</span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <span class="help-text">Re-enter your new password to confirm</span>
                        </div>

                        <button type="submit" class="btn-primary">Update Password</button>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div class="button-group">
                    <a href="profile.php" class="btn-secondary">Back to Profile</a>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>