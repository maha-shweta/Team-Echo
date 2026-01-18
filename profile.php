<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

// Fetch user details from management_user table
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

    if (empty($name) || empty($email)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check if email already exists (excluding current user)
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
                $_SESSION['name'] = $name; 
                $success_message = "Profile updated successfully!";
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $error_message = "Error updating profile.";
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
    <title>My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="profile.css">
</head>
<body>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">Feedback System</div>
        <nav class="sidebar-nav">
            <a href="user_dashboard.php" class="nav-link">Dashboard</a>
            <a href="../feedback/submit_feedback.php" class="nav-link">Submit Feedback</a>
            <a href="analytics_dashboard.php" class="nav-link">Analytics</a>
            <a href="ai_analytics_dashboard.php" class="nav-link">Team-Echo AI</a>
            <a href="profile.php" class="nav-link active">My Profile</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-welcome">Welcome, <?php echo htmlspecialchars($user['name']); ?></div>
            <div class="topbar-actions">
                <a href="profile.php" class="topbar-btn">Profile</a>
                <a href="logout.php" class="topbar-btn">Logout</a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            <div class="profile-card">
                <div class="header">
                    <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <h2>Profile Details</h2>
                    <span class="role-badge"><?php echo htmlspecialchars($user['role']); ?></span>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">✨ <?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">⚠️ <?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <button type="submit" class="btn-save">Save Changes</button>
                </form>

                <div class="footer">
                    <a href="change_password.php" class="link">🔒 Security</a>
                    <?php
                        $dash = "user_dashboard.php";
                        if($user['role'] == 'admin') $dash = "../admin/admin_dashboard.php";
                        elseif($user['role'] == 'hr') $dash = "../hr/hr_dashboard.php";
                    ?>
                    <a href="<?php echo $dash; ?>" class="btn-back">← Back to Dashboard</a>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>