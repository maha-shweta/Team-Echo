<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get username safely
$username = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = trim($_POST['category_name']);

    if (empty($category_name)) {
        $error_message = "Category name is required.";
    } elseif (strlen($category_name) < 3) {
        $error_message = "Category name must be at least 3 characters.";
    } else {
        // Check if category already exists
        $check_sql = "SELECT category_id FROM category WHERE category_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $category_name);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = "This category already exists.";
        } else {
            $sql = "INSERT INTO category (category_name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $category_name);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Category added successfully!";
                header('Location: manage_category.php');
                exit;
            } else {
                $error_message = "Database error occurred.";
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
    <title>Add Category - Anonymous Feedback System</title>
    <link rel="stylesheet" href="add_category.css">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="../admin/admin_dashboard.php" class="nav-item">Dashboard</a>
            <a href="../admin/manage_users.php" class="nav-item">Manage Users</a>
            <a href="manage_category.php" class="nav-item active">Manage Categories</a>
            <a href="../admin/manage_tags.php" class="nav-item">Manage Tags</a>
            <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
            <a href="../feedback/export_feedback.php" class="nav-item">Export Feedback</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Welcome, <?php echo htmlspecialchars($username); ?></div>
            <div class="header-buttons">
                <a href="../user/profile.php" class="btn-header">Profile</a>
                <a href="../user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <!-- Back Button -->
            <a href="manage_category.php" class="button btn-back">← Back to Categories</a>

            <!-- Header Card -->
            <div class="header-card">
                <h1>Add New Category</h1>
                <p>Create a new category to organize user feedback efficiently</p>
            </div>

            <!-- Error Message -->
            <?php if (isset($error_message)): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="info-box">
                <h4>Category Guidelines</h4>
                <ul>
                    <li>Use clear, descriptive names that users will understand</li>
                    <li>Category names must be at least 3 characters long</li>
                    <li>Avoid creating duplicate categories</li>
                    <li>Consider using broad categories that can encompass multiple topics</li>
                </ul>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="category_name">Category Name <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="category_name"
                            name="category_name" 
                            placeholder="e.g., Campus Facilities, Academic Support, Student Wellness" 
                            maxlength="100" 
                            oninput="document.getElementById('count').innerText = this.value.length"
                            required
                        >
                        <span class="help-text">
                            <span id="count">0</span> / 100 characters
                        </span>
                    </div>

                    <button type="submit" class="btn-primary">Add Category</button>
                </form>
            </div>

            <!-- Suggestions Box -->
            <div class="info-box" style="border-left-color: #32b25e;">
                <h4>Category Suggestions</h4>
                <ul>
                    <li>Academic Support - Course content, teaching methods, academic resources</li>
                    <li>Campus Facilities - Buildings, classrooms, library, labs</li>
                    <li>Student Wellness - Mental health, counseling, health services</li>
                    <li>IT Infrastructure - Technology, software, internet connectivity</li>
                    <li>Student Services - Administration, registration, financial aid</li>
                    <li>Campus Life - Events, clubs, activities, dining</li>
                </ul>
            </div>
        </main>
    </div>
</div>

</body>
</html>