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
$old_priority = $feedback['priority']; // Store old priority

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
    
    // Check if the tag is already assigned to the feedback
    $check_tag_sql = "SELECT * FROM feedback_tags WHERE feedback_id = ? AND tag_id = ?";
    $check_tag_stmt = $conn->prepare($check_tag_sql);
    $check_tag_stmt->bind_param("ii", $feedback_id, $tag_id);
    $check_tag_stmt->execute();
    $check_tag_result = $check_tag_stmt->get_result();
    
    if ($check_tag_result->num_rows > 0) {
        $_SESSION['error'] = "This tag is already assigned to the feedback.";
    } else {
        // If the tag is not already assigned, insert it
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
    <title>Manage Priority & Tags</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* Header Card */
        .header-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header-card h1 {
            font-size: 26px;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-card p {
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
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
        .button {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-back {
            background: white;
            color: #667eea;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        /* Card Sections */
        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Feedback Info Box */
        .info-box {
            background: linear-gradient(135deg, #e7f3ff 0%, #f0e7ff 100%);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 120px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-unresolved {
            background: #fff3cd;
            color: #856404;
        }
        
        /* Priority Section */
        .priority-display {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .priority-display span {
            font-weight: 600;
            color: #495057;
        }
        
        .priority-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .priority-low { 
            background: #d4edda; 
            color: #155724; 
        }
        
        .priority-medium { 
            background: #fff3cd; 
            color: #856404; 
        }
        
        .priority-high { 
            background: #f8d7da; 
            color: #721c24; 
        }
        
        .priority-critical { 
            background: #721c24; 
            color: white; 
        }
        
        .priority-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: white;
        }
        
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Tags Section */
        .tags-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .tags-count {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
            min-height: 50px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .tag-item {
            padding: 10px 18px;
            border-radius: 25px;
            color: white;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: transform 0.2s ease;
        }
        
        .tag-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .tag-remove {
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        .tag-remove:hover {
            background: rgba(255,255,255,0.6);
            transform: rotate(90deg);
        }
        
        .no-tags {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 20px;
            width: 100%;
        }
        
        .add-tag-section {
            border-top: 2px dashed #e1e8ed;
            padding-top: 20px;
        }
        
        .add-tag-section h3 {
            font-size: 16px;
            color: #495057;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .add-tag-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
        }
        
        .add-tag-form select {
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .add-tag-form select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header-card {
                padding: 20px;
            }
            
            .header-card h1 {
                font-size: 22px;
            }
            
            .card {
                padding: 20px;
            }
            
            .priority-form,
            .add-tag-form {
                grid-template-columns: 1fr;
            }
            
            .info-item {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header-card">
        <h1>⚙️ Manage Feedback #<?php echo $feedback_id; ?></h1>
        <p>Set priority level and assign tags to organize feedback</p>
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
    <a href="<?php echo $_SESSION['role'] == 'admin' ? '../admin/admin_dashboard.php' : '../hr/hr_dashboard.php'; ?>" class="button btn-back">← Back to Dashboard</a>
    
    <!-- Feedback Information -->
    <div class="card">
        <h2>📋 Feedback Details</h2>
        <div class="info-box">
            <div class="info-item">
                <span class="info-label">📁 Category:</span>
                <span class="info-value"><?php echo htmlspecialchars($feedback['category_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">💬 Feedback:</span>
                <span class="info-value"><?php echo htmlspecialchars($feedback['feedback_text']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">📅 Submitted:</span>
                <span class="info-value"><?php echo date('M d, Y H:i', strtotime($feedback['submitted_at'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">🔔 Status:</span>
                <span class="info-value">
                    <span class="status-badge <?php echo $feedback['is_resolved'] ? 'status-resolved' : 'status-unresolved'; ?>">
                        <?php echo $feedback['is_resolved'] ? '✅ Resolved' : '⏳ Unresolved'; ?>
                    </span>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Priority Management -->
    <div class="card">
        <h2>⚡ Priority Management</h2>
        
        <div class="priority-display">
            <span>Current Priority:</span>
            <span class="priority-badge priority-<?php echo $feedback['priority'] ?? 'medium'; ?>">
                <?php 
                $priority_icons = [
                    'low' => '🟢',
                    'medium' => '🟡',
                    'high' => '🟠',
                    'critical' => '🔴'
                ];
                echo $priority_icons[$feedback['priority'] ?? 'medium'] . ' ' . strtoupper($feedback['priority'] ?? 'medium'); 
                ?>
            </span>
        </div>
        
        <form method="POST" action="" class="priority-form">
            <div class="form-group">
                <label for="priority">Change Priority Level:</label>
                <select id="priority" name="priority" required>
                    <option value="low" <?php echo ($feedback['priority'] == 'low') ? 'selected' : ''; ?>>🟢 Low - Minor issue, can wait</option>
                    <option value="medium" <?php echo (($feedback['priority'] ?? 'medium') == 'medium') ? 'selected' : ''; ?>>🟡 Medium - Normal priority</option>
                    <option value="high" <?php echo ($feedback['priority'] == 'high') ? 'selected' : ''; ?>>🟠 High - Important, needs attention</option>
                    <option value="critical" <?php echo ($feedback['priority'] == 'critical') ? 'selected' : ''; ?>>🔴 Critical - Urgent, immediate action</option>
                </select>
            </div>
            <button type="submit" name="update_priority" class="button btn-primary">💾 Update Priority</button>
        </form>
    </div>
    
    <!-- Tags Management -->
    <div class="card">
        <div class="tags-header">
            <h2>🏷️ Tags Management</h2>
            <span class="tags-count"><?php echo $assigned_tags_result->num_rows; ?> Tag(s)</span>
        </div>
        
        <!-- Assigned Tags Display -->
        <div class="tags-container">
            <?php if ($assigned_tags_result->num_rows > 0): ?>
                <?php 
                $assigned_tags_result->data_seek(0); // Reset pointer
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
                <span class="no-tags">No tags assigned yet. Add tags below to categorize this feedback.</span>
            <?php endif; ?>
        </div>
        
        <!-- Add New Tag -->
        <div class="add-tag-section">
            <h3>➕ Add New Tag</h3>
            <form method="POST" action="" class="add-tag-form">
                <select name="tag_id" required>
                    <option value="">Select a tag to add...</option>
                    <?php 
                    $all_tags_result->data_seek(0); // Reset pointer
                    while ($tag = $all_tags_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $tag['tag_id']; ?>">
                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="add_tag" class="button btn-primary">Add Tag</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>
