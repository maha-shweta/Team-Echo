<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get comment ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid comment ID.";
    header('Location: ../user/user_dashboard.php');
    exit;
}

$comment_id = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'];
$is_hr_admin = in_array($_SESSION['role'], ['hr', 'admin']);

// Fetch comment details
$comment_sql = "SELECT fc.*, f.feedback_id 
                FROM feedback_comments fc
                JOIN feedback f ON fc.feedback_id = f.feedback_id
                WHERE fc.comment_id = ?";
$stmt = $conn->prepare($comment_sql);
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$comment_result = $stmt->get_result();

if ($comment_result->num_rows == 0) {
    $_SESSION['error'] = "Comment not found.";
    header('Location: ../user/user_dashboard.php');
    exit;
}

$comment = $comment_result->fetch_assoc();
$feedback_id = $comment['feedback_id'];

// Check permissions
$is_owner = ($comment['user_id'] == $current_user_id);

if (!$is_hr_admin && !$is_owner) {
    $_SESSION['error'] = "You don't have permission to edit this comment.";
    header("Location: view_feedback.php?id=" . $feedback_id);
    exit;
}

// Calculate time elapsed since comment creation (in seconds)
$created_time = strtotime($comment['created_at']);
$current_time = time();
$time_elapsed = $current_time - $created_time;
$time_limit = 15 * 60; // 15 minutes in seconds

// Check if regular user is within time limit (HR/Admin have no time restriction)
if (!$is_hr_admin && $is_owner && $time_elapsed > $time_limit) {
    $_SESSION['error'] = "You can only edit comments within 15 minutes of posting.";
    header("Location: view_feedback.php?id=" . $feedback_id);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_text = trim($_POST['comment_text']);
    
    if (!empty($comment_text)) {
        $update_sql = "UPDATE feedback_comments SET comment_text = ?, edited_at = NOW() WHERE comment_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $comment_text, $comment_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Comment updated successfully!";
            header("Location: view_feedback.php?id=" . $feedback_id);
            exit;
        } else {
            $_SESSION['error'] = "Error updating comment.";
        }
        $update_stmt->close();
    } else {
        $_SESSION['error'] = "Comment text cannot be empty.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Comment</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header-card h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-card p {
            color: #6c757d;
            font-size: 15px;
        }
        
        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
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
            padding: 14px 32px;
            font-size: 16px;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .form-card {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .warning-box .icon {
            font-size: 24px;
        }
        
        .warning-box p {
            color: #856404;
            font-size: 14px;
            margin: 0;
        }
        
        .submit-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header-card,
            .form-card {
                padding: 20px;
            }
            
            .header-card h1 {
                font-size: 22px;
            }
            
            .submit-section {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-card">
        <h1>✏️ Edit Comment</h1>
        <p>Make changes to your comment</p>
    </div>
    
    <?php
    if (isset($_SESSION['error'])) {
        echo '<div class="message error">❌ ' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    ?>
    
    <a href="view_feedback.php?id=<?php echo $feedback_id; ?>" class="button btn-back">← Back to Feedback</a>
    
    <?php if (!$is_hr_admin && $is_owner): ?>
    <div class="warning-box">
        <span class="icon">⏰</span>
        <p><strong>Time Limit:</strong> You can only edit comments within 15 minutes of posting. Time remaining: <?php echo max(0, floor(($time_limit - $time_elapsed) / 60)); ?> minutes.</p>
    </div>
    <?php endif; ?>
    
    <div class="form-card">
        <form method="POST" action="">
            <div class="form-group">
                <label for="comment_text">Comment Text</label>
                <textarea id="comment_text" name="comment_text" required><?php echo htmlspecialchars($comment['comment_text']); ?></textarea>
            </div>
            
            <div class="submit-section">
                <button type="submit" class="button btn-primary">💾 Save Changes</button>
                <a href="view_feedback.php?id=<?php echo $feedback_id; ?>" class="button btn-danger">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>
