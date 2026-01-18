<?php
session_start();

// Ensure only HR/Admin can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'hr')) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get feedback ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid feedback ID.";
    header('Location: ' . ($_SESSION['role'] == 'admin' ? '../admin/admin_dashboard.php' : '../hr/hr_dashboard.php'));
    exit;
}

$feedback_id = intval($_GET['id']);
$current_user_role = $_SESSION['role'];

// Fetch feedback details
$feedback_sql = "SELECT f.*, c.category_name 
                 FROM feedback f 
                 JOIN category c ON f.category_id = c.category_id 
                 WHERE f.feedback_id = ?";
$stmt = $conn->prepare($feedback_sql);
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$feedback_result = $stmt->get_result();

if ($feedback_result->num_rows == 0) {
    $_SESSION['error'] = "Feedback not found.";
    header('Location: ' . ($_SESSION['role'] == 'admin' ? '../admin/admin_dashboard.php' : '../hr/hr_dashboard.php'));
    exit;
}

$feedback = $feedback_result->fetch_assoc();
$old_priority = $feedback['priority'];

// Handle priority update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_priority'])) {
    $priority = $_POST['priority'];
    $update_sql = "UPDATE feedback SET priority = ? WHERE feedback_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $priority, $feedback_id);
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Priority updated successfully!";
        $feedback['priority'] = $priority;
    }
    $update_stmt->close();
}

// Handle tag assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tag'])) {
    $tag_id = intval($_POST['tag_id']);
    $user_id = $_SESSION['user_id'];
    
    $check_tag_sql = "SELECT * FROM feedback_tags WHERE feedback_id = ? AND tag_id = ?";
    $check_tag_stmt = $conn->prepare($check_tag_sql);
    $check_tag_stmt->bind_param("ii", $feedback_id, $tag_id);
    $check_tag_stmt->execute();
    $check_tag_result = $check_tag_stmt->get_result();
    
    if ($check_tag_result->num_rows > 0) {
        $_SESSION['error'] = "This tag is already assigned to the feedback.";
    } else {
        $tag_sql = "INSERT INTO feedback_tags (feedback_id, tag_id, added_by) VALUES (?, ?, ?)";
        $tag_stmt = $conn->prepare($tag_sql);
        $tag_stmt->bind_param("iii", $feedback_id, $tag_id, $user_id);
        if ($tag_stmt->execute()) {
            $_SESSION['success'] = "Tag added successfully!";
        } else {
            $_SESSION['error'] = "An error occurred while adding the tag.";
        }
        $tag_stmt->close();
    }
    $check_tag_stmt->close();
    header('Location: manage_feedback_tags.php?id=' . $feedback_id);
    exit;
}

// Handle tag removal
if (isset($_GET['remove_tag']) && is_numeric($_GET['remove_tag'])) {
    $tag_id = intval($_GET['remove_tag']);
    $remove_sql = "DELETE FROM feedback_tags WHERE feedback_id = ? AND tag_id = ?";
    $remove_stmt = $conn->prepare($remove_sql);
    $remove_stmt->bind_param("ii", $feedback_id, $tag_id);
    $remove_stmt->execute();
    $remove_stmt->close();
    
    $_SESSION['success'] = "Tag removed successfully!";
    header('Location: manage_feedback_tags.php?id=' . $feedback_id);
    exit;
}

// Fetch all tags
$all_tags_sql = "SELECT * FROM tags ORDER BY tag_name";
$all_tags_result = $conn->query($all_tags_sql);

// Fetch assigned tags
$assigned_tags_sql = "SELECT t.*, u.name as added_by_name, ft.added_at 
                      FROM feedback_tags ft
                      JOIN tags t ON ft.tag_id = t.tag_id
                      LEFT JOIN management_user u ON ft.added_by = u.user_id
                      WHERE ft.feedback_id = ?
                      ORDER BY ft.added_at DESC";
$assigned_stmt = $conn->prepare($assigned_tags_sql);
$assigned_stmt->bind_param("i", $feedback_id);
$assigned_stmt->execute();
$assigned_tags_result = $assigned_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Priority & Tags - Anonymous Feedback System</title>
    <link rel="stylesheet" href="manage_feedback_tags.css">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <?php if ($current_user_role == 'admin'): ?>
                <a href="../admin/admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="../admin/manage_users.php" class="nav-item">Manage Users</a>
                <a href="../admin/manage_categories.php" class="nav-item">Categories</a>
                <a href="../admin/manage_tags.php" class="nav-item">Tags</a>
                <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
                <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
                <a href="../user/profile.php" class="nav-item">My Profile</a>
            <?php elseif ($current_user_role == 'hr'): ?>
                <a href="../hr/hr_dashboard.php" class="nav-item">Dashboard</a>
                <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
                <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
                <a href="../feedback/export_feedback.php" class="nav-item">Export Feedback</a>
                <a href="../user/profile.php" class="nav-item">My Profile</a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Manage Feedback #<?php echo $feedback_id; ?></div>
            <div class="header-buttons">
                <a href="../user/profile.php" class="btn-header">Profile</a>
                <a href="../user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
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
            
            <!-- Feedback Information -->
            <div class="card">
                <h2>Feedback Details</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Category</span>
                        <span class="info-value"><?php echo htmlspecialchars($feedback['category_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Submitted</span>
                        <span class="info-value"><?php echo date('M d, Y H:i', strtotime($feedback['submitted_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="badge <?php echo $feedback['is_resolved'] ? 'badge-resolved' : 'badge-unresolved'; ?>">
                                <?php echo $feedback['is_resolved'] ? 'Resolved' : 'Unresolved'; ?>
                            </span>
                        </span>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <span class="info-label">Feedback Content</span>
                    <div class="feedback-text">
                        <?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Priority Management -->
            <div class="card">
                <h2>Priority Management</h2>
                
                <div class="priority-display">
                    <span class="priority-label">Current Priority:</span>
                    <span class="priority-badge priority-<?php echo $feedback['priority'] ?? 'medium'; ?>">
                        <?php echo strtoupper($feedback['priority'] ?? 'medium'); ?>
                    </span>
                </div>
                
                <form method="POST" action="" class="priority-form">
                    <div class="form-group">
                        <label for="priority">Change Priority Level</label>
                        <select id="priority" name="priority" required>
                            <option value="low" <?php echo ($feedback['priority'] == 'low') ? 'selected' : ''; ?>>Low - Minor issue, can wait</option>
                            <option value="medium" <?php echo (($feedback['priority'] ?? 'medium') == 'medium') ? 'selected' : ''; ?>>Medium - Normal priority</option>
                            <option value="high" <?php echo ($feedback['priority'] == 'high') ? 'selected' : ''; ?>>High - Important, needs attention</option>
                            <option value="critical" <?php echo ($feedback['priority'] == 'critical') ? 'selected' : ''; ?>>Critical - Urgent, immediate action</option>
                        </select>
                    </div>
                    <button type="submit" name="update_priority" class="button btn-primary">Update Priority</button>
                </form>
            </div>
            
            <!-- Tags Management -->
            <div class="card">
                <div class="tags-header">
                    <h2>Tags Management</h2>
                    <span class="tags-count"><?php echo $assigned_tags_result->num_rows; ?> Tag(s)</span>
                </div>
                
                <!-- Assigned Tags Display -->
                <div class="tags-container">
                    <?php if ($assigned_tags_result->num_rows > 0): ?>
                        <?php 
                        $assigned_tags_result->data_seek(0);
                        while ($tag = $assigned_tags_result->fetch_assoc()): 
                        ?>
                            <div class="tag-item" style="background-color: <?php echo htmlspecialchars($tag['tag_color']); ?>">
                                <span><?php echo htmlspecialchars($tag['tag_name']); ?></span>
                                <a href="?id=<?php echo $feedback_id; ?>&remove_tag=<?php echo $tag['tag_id']; ?>" 
                                   class="tag-remove" 
                                   onclick="return confirm('Remove this tag?')"
                                   title="Remove tag">×</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-tags">No tags assigned yet. Add tags below to categorize this feedback.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Add New Tag -->
                <div class="add-tag-section">
                    <h3>Add New Tag</h3>
                    <form method="POST" action="" class="add-tag-form">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <select name="tag_id" required>
                                <option value="">Select a tag to add...</option>
                                <?php 
                                $all_tags_result->data_seek(0);
                                while ($tag = $all_tags_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $tag['tag_id']; ?>">
                                        <?php echo htmlspecialchars($tag['tag_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_tag" class="button btn-primary">Add Tag</button>
                    </form>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="view_feedback.php?id=<?php echo $feedback_id; ?>" class="button btn-primary">View Full Feedback</a>
                <a href="<?php echo $_SESSION['role'] == 'admin' ? '../admin/admin_dashboard.php' : '../hr/hr_dashboard.php'; ?>" class="button btn-secondary">Back to Dashboard</a>
            </div>
        </main>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>