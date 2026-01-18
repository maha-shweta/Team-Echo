<?php
session_start();

// Ensure only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get username safely
$username = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// Handle tag deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $tag_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM tags WHERE tag_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $tag_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Tag deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting tag.";
    }
    $stmt->close();
    header('Location: manage_tags.php');
    exit;
}

// Handle add new tag
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tag'])) {
    $tag_name = trim($_POST['tag_name']);
    $tag_color = trim($_POST['tag_color']);
    
    if (empty($tag_name)) {
        $_SESSION['error'] = "Tag name is required.";
    } else {
        // Check if tag already exists
        $check_sql = "SELECT tag_id FROM tags WHERE tag_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $tag_name);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $_SESSION['error'] = "This tag already exists.";
        } else {
            $sql = "INSERT INTO tags (tag_name, tag_color) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $tag_name, $tag_color);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Tag added successfully!";
            } else {
                $_SESSION['error'] = "Error adding tag: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    header('Location: manage_tags.php');
    exit;
}

// Fetch all tags
$tags_sql = "SELECT t.*, 
             (SELECT COUNT(*) FROM feedback_tags WHERE tag_id = t.tag_id) as usage_count
             FROM tags t
             ORDER BY t.tag_name";
$tags_result = $conn->query($tags_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tags - Anonymous Feedback System</title>
    <link rel="stylesheet" href="manage_tags.css">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
            <a href="manage_users.php" class="nav-item">Manage Users</a>
            <a href="../category/manage_category.php" class="nav-item">Manage Categories</a>
            <a href="manage_tags.php" class="nav-item active">Manage Tags</a>
            <a href="analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
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
            <!-- Page Header -->
            <div class="page-header">
                <h1>Tag Management</h1>
                <p>Create and manage feedback tags to organize and categorize submissions</p>
            </div>

            <!-- Messages -->
            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="message success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <!-- Back Button -->
            <a href="admin_dashboard.php" class="button btn-back">← Back to Dashboard</a>

            <!-- Add Tag Section -->
            <div class="add-tag-section">
                <h2>Add New Tag</h2>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tag_name">Tag Name <span class="required">*</span></label>
                            <input type="text" id="tag_name" name="tag_name" required placeholder="e.g., Urgent, Bug Fix, Feature Request">
                        </div>
                        <div class="form-group">
                            <label for="tag_color">Tag Color <span class="required">*</span></label>
                            <input type="color" id="tag_color" name="tag_color" value="#667eea" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_tag" class="button btn-primary">Add Tag</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tags Grid -->
            <div class="table-container">
                <div class="table-header">
                    <h2>All Tags</h2>
                    <span class="results-count"><?php echo $tags_result->num_rows; ?> tags found</span>
                </div>

                <?php if ($tags_result->num_rows > 0): ?>
                    <div class="tags-grid">
                        <?php while ($tag = $tags_result->fetch_assoc()): ?>
                            <div class="tag-card">
                                <div class="tag-header">
                                    <span class="tag-badge" style="background-color: <?php echo htmlspecialchars($tag['tag_color']); ?>">
                                        <?php echo htmlspecialchars($tag['tag_name']); ?>
                                    </span>
                                </div>
                                <div class="tag-stats">
                                    Used in <strong><?php echo $tag['usage_count']; ?></strong> feedback(s)
                                </div>
                                <div class="tag-actions">
                                    <a href="?delete=<?php echo $tag['tag_id']; ?>" 
                                       class="action-link delete-link" 
                                       onclick="return confirm('Delete this tag? It will be removed from all feedback.')">
                                        Delete
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <h3>No tags found</h3>
                        <p>Add your first tag above to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>