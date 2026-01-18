<?php
session_start();

// Ensure only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

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
    
    if (!empty($tag_name)) {
        $sql = "INSERT INTO tags (tag_name, tag_color) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $tag_name, $tag_color);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Tag added successfully!";
        } else {
            $_SESSION['error'] = "Error adding tag: " . $stmt->error;
        }
        $stmt->close();
        header('Location: manage_tags.php');
        exit;
    }
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
    <title>Manage Tags</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_dashboard.css">
    <style>
        /* Additional styles for tags page */
        .add-tag-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .add-tag-section h2 {
            margin-bottom: 20px;
            color: var(--primary-green-dark);
            font-size: 18px;
            font-weight: 700;
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #495057;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-green-dark);
        }

        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .tag-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .tag-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        .tag-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .tag-badge {
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .tag-stats {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .tag-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 6px;
        }

        .no-tags {
            grid-column: 1/-1;
            text-align: center;
            color: #6c757d;
            padding: 40px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .tags-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
            <div class="welcome-text">🏷️ Tag Management</div>
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
                echo '<div class="message success">✓ ' . htmlspecialchars($_SESSION['success']) . '</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="message error">✗ ' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <!-- Add Tag Section -->
            <div class="add-tag-section">
                <h2>➕ Add New Tag</h2>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tag_name">Tag Name *</label>
                            <input type="text" id="tag_name" name="tag_name" required placeholder="e.g., Urgent, Bug Fix">
                        </div>
                        <div class="form-group">
                            <label for="tag_color">Tag Color *</label>
                            <input type="color" id="tag_color" name="tag_color" value="#667eea" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_tag" class="button btn-primary">Add Tag</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tags Grid -->
            <h2 class="section-title">📋 All Tags (<?php echo $tags_result->num_rows; ?>)</h2>
            <div class="tags-grid">
                <?php if ($tags_result->num_rows > 0): ?>
                    <?php while ($tag = $tags_result->fetch_assoc()): ?>
                        <div class="tag-card">
                            <div class="tag-header">
                                <span class="tag-badge" style="background-color: <?php echo htmlspecialchars($tag['tag_color']); ?>">
                                    <?php echo htmlspecialchars($tag['tag_name']); ?>
                                </span>
                            </div>
                            <div class="tag-stats">
                                📊 Used in <?php echo $tag['usage_count']; ?> feedback(s)
                            </div>
                            <div class="tag-actions">
                                <a href="?delete=<?php echo $tag['tag_id']; ?>" 
                                   class="button btn-sm btn-danger" 
                                   onclick="return confirm('Delete this tag? It will be removed from all feedback.')">
                                    🗑️ Delete
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-tags">No tags found. Add your first tag above!</p>
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