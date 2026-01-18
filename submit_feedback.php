<?php
session_start();

// Ensure only logged-in users can access this page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Fetch all categories
$categories_sql = "SELECT * FROM category ORDER BY category_name";
$categories_result = $conn->query($categories_sql);

// Fetch all available tags
$tags_sql = "SELECT * FROM tags ORDER BY tag_name";
$tags_result = $conn->query($tags_sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = intval($_POST['category_id']);
    $feedback_text = trim($_POST['feedback_text']);
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

    // Validate inputs
    if (empty($category_id) || empty($feedback_text)) {
        $_SESSION['error'] = "Please fill in all required fields.";
    } else {
        // Check if feedback table has user_id column
        $columns_check = $conn->query("SHOW COLUMNS FROM feedback LIKE 'user_id'");
        $has_user_id = ($columns_check->num_rows > 0);

        // Insert feedback
        if ($has_user_id) {
            $insert_sql = "INSERT INTO feedback (user_id, category_id, feedback_text, is_anonymous, submitted_at, priority) 
                          VALUES (?, ?, ?, ?, NOW(), 'medium')";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iisi", $_SESSION['user_id'], $category_id, $feedback_text, $is_anonymous);
        } else {
            $insert_sql = "INSERT INTO feedback (category_id, feedback_text, is_anonymous, submitted_at, priority) 
                          VALUES (?, ?, ?, NOW(), 'medium')";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("isi", $category_id, $feedback_text, $is_anonymous);
        }

        if ($stmt->execute()) {
            $feedback_id = $stmt->insert_id;

            // Insert user-selected tags
            if (!empty($selected_tags)) {
                $tag_stmt = $conn->prepare("INSERT INTO feedback_tags (feedback_id, tag_id, added_by) VALUES (?, ?, ?)");
                foreach ($selected_tags as $tag_id) {
                    $tag_id = intval($tag_id);
                    $tag_stmt->bind_param("iii", $feedback_id, $tag_id, $_SESSION['user_id']);
                    $tag_stmt->execute();
                }
                $tag_stmt->close();
            }

            // File upload handling
            if (isset($_FILES['attachments']) && count($_FILES['attachments']['name']) > 0) {
                $upload_dir = 'uploads/feedback_attachments/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                    $file_name = $_FILES['attachments']['name'][$i];
                    $file_tmp_name = $_FILES['attachments']['tmp_name'][$i];
                    $file_size = $_FILES['attachments']['size'][$i];
                    $file_error = $_FILES['attachments']['error'][$i];

                    if ($file_error === UPLOAD_ERR_OK) {
                        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        $file_new_name = uniqid('', true) . '.' . $file_ext;
                        $file_path = $upload_dir . $file_new_name;

                        if (move_uploaded_file($file_tmp_name, $file_path)) {
                            // Insert attachment info into database, including file size
                            $attachment_sql = "INSERT INTO feedback_attachments (feedback_id, file_name, file_path, file_size, uploaded_by) 
                                               VALUES (?, ?, ?, ?, ?)";
                            $attachment_stmt = $conn->prepare($attachment_sql);
                            $attachment_stmt->bind_param("issii", $feedback_id, $file_name, $file_path, $file_size, $_SESSION['user_id']);
                            $attachment_stmt->execute();
                            $attachment_stmt->close();
                        }
                    }
                }
            }

            $_SESSION['success'] = "Feedback submitted successfully! Thank you for your input.";
            header('Location: ../user/user_dashboard.php');
            exit;
        } else {
            $_SESSION['error'] = "Error submitting feedback. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - Anonymous Feedback System</title>
    <link rel="stylesheet" href="submit_feedback.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="../user/user_dashboard.php" class="nav-item">Dashboard</a>
            <a href="submit_feedback.php" class="nav-item active">Submit Feedback</a>
            <a href="../user/analytics_dashboard.php" class="nav-item">Analytics</a>
             <a href="../user/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
            <a href="../user/profile.php" class="nav-item">My Profile</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="header-buttons">
                <a href="../user/profile.php" class="btn-header">Profile</a>
                <a href="../user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <div class="container">
                <!-- Header -->
                <div class="header-card">
                    <h1>Submit Anonymous Feedback</h1>
                    <p>Your feedback helps us improve! Share your thoughts, suggestions, or concerns anonymously. We value your input and take every submission seriously.</p>
                </div>

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

                <!-- Back Button -->
                <a href="../user/user_dashboard.php" class="button btn-back">← Back to Dashboard</a>

                <!-- Info Box -->
                <div class="info-box">
                    <h4>How Tagging Works</h4>
                    <ul>
                        <li><strong>Step 1:</strong> You select relevant tags that describe your feedback</li>
                        <li><strong>Step 2:</strong> Our AI will analyze your feedback and add additional tags automatically</li>
                        <li><strong>Step 3:</strong> HR/Admin can review and refine tags if needed</li>
                    </ul>
                </div>

                <!-- Feedback Form -->
                <div class="form-card">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Category Selection -->
                        <div class="form-group">
                            <label for="category_id">Category <span class="required">*</span></label>
                            <select id="category_id" name="category_id" required>
                                <option value="">-- Select a category --</option>
                                <?php
                                while ($cat = $categories_result->fetch_assoc()) {
                                    echo "<option value='" . $cat['category_id'] . "'>" . htmlspecialchars($cat['category_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <span class="help-text">Choose the category that best fits your feedback</span>
                        </div>

                        <!-- Feedback Text -->
                        <div class="form-group">
                            <label for="feedback_text">Your Feedback <span class="required">*</span></label>
                            <textarea id="feedback_text" name="feedback_text" required placeholder="Share your thoughts, suggestions, or concerns..."></textarea>
                            <span class="help-text">Be specific and constructive. Your feedback will be reviewed carefully.</span>
                        </div>

                        <!-- Tags Selection -->
                        <div class="tags-section">
                            <h3>Select Relevant Tags (Optional)</h3>
                            <span class="help-text">Choose tags that best describe your feedback. The AI will analyze and add more tags automatically after submission.</span>

                            <div class="tags-grid">
                                <?php
                                $tags_result->data_seek(0);
                                while ($tag = $tags_result->fetch_assoc()): 
                                ?>
                                    <div class="tag-checkbox">
                                        <input type="checkbox" 
                                               id="tag_<?php echo $tag['tag_id']; ?>" 
                                               name="tags[]" 
                                               value="<?php echo $tag['tag_id']; ?>">
                                        <label for="tag_<?php echo $tag['tag_id']; ?>" class="tag-label">
                                            <span class="tag-color-dot" style="background-color: <?php echo htmlspecialchars($tag['tag_color']); ?>"></span>
                                            <span><?php echo htmlspecialchars($tag['tag_name']); ?></span>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="form-group file-upload-group">
                            <label for="attachments">Attach Files (Optional)</label>
                            <input type="file" name="attachments[]" id="attachments" multiple accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx">
                            <span class="help-text">You can attach images or documents related to your feedback</span>
                        </div>

                        <!-- Anonymous Option -->
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_anonymous" name="is_anonymous" checked>
                            <label for="is_anonymous">
                                <strong>Submit anonymously</strong> (Your identity will be kept confidential)
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="submit-section">
                            <button type="submit" class="button btn-primary">Submit Feedback</button>
                            <span class="help-text">Your submission will be reviewed within 24-48 hours</span>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>
<?php
$conn->close();
?>