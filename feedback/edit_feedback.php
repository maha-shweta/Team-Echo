<?php
session_start();

// Ensure only logged-in users can access this page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Check if feedback table has user_id column
$columns_check = $conn->query("SHOW COLUMNS FROM feedback LIKE 'user_id'");
$has_user_id = ($columns_check->num_rows > 0);

if (!$has_user_id) {
    $_SESSION['error'] = "Feedback editing is not available. Database structure needs to be updated.";
    header('Location: ../user/user_dashboard.php');
    exit;
}

// Get feedback ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid feedback ID.";
    header('Location: ../user/user_dashboard.php');
    exit;
}

$feedback_id = intval($_GET['id']);

// Fetch feedback details and verify ownership
$feedback_sql = "SELECT f.*, c.category_name 
                 FROM feedback f 
                 JOIN category c ON f.category_id = c.category_id 
                 WHERE f.feedback_id = ? AND f.user_id = ?";
$stmt = $conn->prepare($feedback_sql);
$stmt->bind_param("ii", $feedback_id, $_SESSION['user_id']);
$stmt->execute();
$feedback_result = $stmt->get_result();

if ($feedback_result->num_rows == 0) {
    $_SESSION['error'] = "Feedback not found or you don't have permission to edit it.";
    header('Location: ../user/user_dashboard.php');
    exit;
}

$feedback = $feedback_result->fetch_assoc();

// Check if feedback is already resolved
if ($feedback['is_resolved']) {
    $_SESSION['error'] = "Cannot edit resolved feedback.";
    header('Location: ../user/user_dashboard.php');
    exit;
}

// Fetch all categories
$categories_sql = "SELECT * FROM category ORDER BY category_name";
$categories_result = $conn->query($categories_sql);

// Fetch all available tags
$tags_sql = "SELECT * FROM tags ORDER BY tag_name";
$tags_result = $conn->query($tags_sql);

// Fetch currently assigned tags
$assigned_tags_sql = "SELECT tag_id FROM feedback_tags WHERE feedback_id = ?";
$assigned_stmt = $conn->prepare($assigned_tags_sql);
$assigned_stmt->bind_param("i", $feedback_id);
$assigned_stmt->execute();
$assigned_result = $assigned_stmt->get_result();

$assigned_tag_ids = [];
while ($row = $assigned_result->fetch_assoc()) {
    $assigned_tag_ids[] = $row['tag_id'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = intval($_POST['category_id']);
    $feedback_text = trim($_POST['feedback_text']);
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    
    // Validate inputs
    if (empty($category_id) || empty($feedback_text)) {
        $_SESSION['error'] = "Please fill in all required fields.";
    } else {
        // Update feedback
        $update_sql = "UPDATE feedback SET category_id = ?, feedback_text = ? WHERE feedback_id = ? AND user_id = ? AND is_resolved = 0";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("isii", $category_id, $feedback_text, $feedback_id, $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            // Delete existing user-added tags (keep HR/Admin tags)
            $delete_tags_sql = "DELETE FROM feedback_tags WHERE feedback_id = ? AND added_by = ?";
            $delete_stmt = $conn->prepare($delete_tags_sql);
            $delete_stmt->bind_param("ii", $feedback_id, $_SESSION['user_id']);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Insert new user-selected tags
            if (!empty($selected_tags)) {
                $tag_check_stmt = $conn->prepare("SELECT 1 FROM feedback_tags WHERE feedback_id = ? AND tag_id = ?");
                $tag_check_stmt->bind_param("ii", $feedback_id, $tag_id);

                $tag_stmt = $conn->prepare("INSERT INTO feedback_tags (feedback_id, tag_id, added_by) VALUES (?, ?, ?)");
                foreach ($selected_tags as $tag_id) {
                    $tag_id = intval($tag_id);

                    // Check if tag already exists
                    $tag_check_stmt->execute();
                    $tag_check_result = $tag_check_stmt->get_result();

                    if ($tag_check_result->num_rows == 0) {
                        // If tag doesn't exist, insert it
                        $tag_stmt->bind_param("iii", $feedback_id, $tag_id, $_SESSION['user_id']);
                        $tag_stmt->execute();
                    }
                }
                $tag_stmt->close();
                $tag_check_stmt->close();
            }
            
            // ==== AI AUTO-TAGGING HOOK ====
            // TODO: Your teammate's AI module can be re-triggered here
            // to update tags based on the edited feedback text
            
            $_SESSION['success'] = "Feedback updated successfully!";
            header('Location: ../user/user_dashboard.php?view=my');
            exit;
        } else {
            $_SESSION['error'] = "Error updating feedback. Please try again.";
        }
        $update_stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Feedback - Anonymous Feedback System</title>
    <link rel="stylesheet" href="edit_feedback.css">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="../user/user_dashboard.php" class="nav-item">Dashboard</a>
            <a href="../feedback/submit_feedback.php" class="nav-item">Submit Feedback</a>
            <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
            <a href="../user/profile.php" class="nav-item">My Profile</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Edit Feedback</div>
            <div class="header-buttons">
                <a href="../user/profile.php" class="btn-header">Profile</a>
                <a href="../user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <!-- Header -->
            <div class="header-card">
                <h1>Edit Your Feedback</h1>
                <p>Update your feedback before it's reviewed. Once resolved by HR/Admin, feedback cannot be edited.</p>
            </div>
            
            <!-- Messages -->
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            
            <!-- Back Button -->
            <a href="../user/user_dashboard.php?view=my" class="button btn-back">Back to My Feedback</a>
            
            <!-- Warning Box -->
            <div class="warning-box">
                <span class="icon">⚠</span>
                <p><strong>Note:</strong> Editing your feedback will remove AI-generated tags. Our system will re-analyze your updated feedback and apply new tags automatically.</p>
            </div>
            
            <!-- Current Info -->
            <div class="form-card">
                <div class="info-section">
                    <h3>Current Feedback Information</h3>
                    <div class="info-row">
                        <span class="info-label">Feedback ID:</span>
                        <span class="info-value">#<?php echo htmlspecialchars($feedback['feedback_id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Submitted:</span>
                        <span class="info-value"><?php echo date('M d, Y H:i', strtotime($feedback['submitted_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="badge badge-unresolved">Unresolved</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Priority:</span>
                        <span class="info-value"><?php echo ucfirst($feedback['priority'] ?? 'medium'); ?> (Set by HR/Admin)</span>
                    </div>
                </div>
            </div>
            
            <!-- Edit Form -->
            <div class="form-card">
                <form method="POST" action="">
                    <!-- Category Selection -->
                    <div class="form-group">
                        <label for="category_id">Category <span class="required">*</span></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Select a category --</option>
                            <?php
                            while ($cat = $categories_result->fetch_assoc()) {
                                $selected = ($cat['category_id'] == $feedback['category_id']) ? 'selected' : '';
                                echo "<option value='" . $cat['category_id'] . "' $selected>" . htmlspecialchars($cat['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                        <span class="help-text">Current: <?php echo htmlspecialchars($feedback['category_name']); ?></span>
                    </div>
                    
                    <!-- Feedback Text -->
                    <div class="form-group">
                        <label for="feedback_text">Your Feedback <span class="required">*</span></label>
                        <textarea id="feedback_text" name="feedback_text" required><?php echo htmlspecialchars($feedback['feedback_text']); ?></textarea>
                        <span class="help-text">Update your feedback text as needed</span>
                    </div>
                    
                    <!-- Tags Selection -->
                    <div class="tags-section">
                        <h3>Update Tags (Optional)</h3>
                        <span class="help-text" style="display: block; margin-bottom: 15px;">Select tags that describe your feedback. Deselect tags you want to remove.</span>
                        
                        <div class="tags-grid">
                            <?php
                            $tags_result->data_seek(0);
                            while ($tag = $tags_result->fetch_assoc()):
                                $is_checked = in_array($tag['tag_id'], $assigned_tag_ids) ? 'checked' : '';
                            ?>
                                <div class="tag-checkbox">
                                    <input type="checkbox" 
                                           id="tag_<?php echo $tag['tag_id']; ?>" 
                                           name="tags[]" 
                                           value="<?php echo $tag['tag_id']; ?>"
                                           <?php echo $is_checked; ?>>
                                    <label for="tag_<?php echo $tag['tag_id']; ?>" class="tag-label">
                                        <span class="tag-color-dot" style="background-color: <?php echo htmlspecialchars($tag['tag_color']); ?>"></span>
                                        <span><?php echo htmlspecialchars($tag['tag_name']); ?></span>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="submit-section">
                        <button type="submit" class="button btn-primary">Save Changes</button>
                        <a href="../user/user_dashboard.php?view=my" class="button btn-danger">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>
