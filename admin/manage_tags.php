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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        /* Navigation */
        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .button {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        /* Add Tag Form */
        .add-tag-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        .add-tag-section h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 15px;
            align-items: end;
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
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #6f42c1;
        }
        
        .btn-primary {
            background: #6f42c1;
            color: white;
            padding: 10px 24px;
        }
        
        .btn-primary:hover {
            background: #5a32a3;
        }
        
        /* Tags Grid */
        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .tag-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
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
        }
        
        .tag-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
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

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>🏷️ Tag Management</h1>
        <p>Create and manage feedback tags</p>
    </div>
    
    <!-- Messages -->
    <?php
    if (isset($_SESSION['success'])) {
        echo '<div class="message success">✅ ' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="message error">❌ ' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    ?>
    
    <!-- Navigation -->
    <div class="nav-buttons">
        <a href="admin_dashboard.php" class="button btn-secondary">← Back to Dashboard</a>
    </div>
    
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
    <h2 style="margin-bottom: 20px; color: #333;">📋 All Tags (<?php echo $tags_result->num_rows; ?>)</h2>
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
            <p style="grid-column: 1/-1; text-align: center; color: #6c757d;">No tags found. Add your first tag above!</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>