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
    $_SESSION['error'] = "You don't have permission to delete this comment.";
    header("Location: view_feedback.php?id=" . $feedback_id);
    exit;
}

// Handle deletion confirmation
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $delete_sql = "DELETE FROM feedback_comments WHERE comment_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $comment_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Comment deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting comment.";
    }
    $delete_stmt->close();
    
    header("Location: view_feedback.php?id=" . $feedback_id);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Comment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
        }
        
        .confirm-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .confirm-card h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .confirm-card p {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .comment-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .comment-preview .label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .comment-preview .text {
            color: #333;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .warning-box p {
            color: #856404;
            font-size: 14px;
            margin: 0;
            text-align: left;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .button {
            padding: 14px 32px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .confirm-card {
                padding: 25px;
            }
            
            .confirm-card h1 {
                font-size: 22px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="confirm-card">
        <div class="icon">🗑️</div>
        <h1>Delete Comment?</h1>
        <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
        
        <div class="comment-preview">
            <div class="label">Comment to be deleted:</div>
            <div class="text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></div>
        </div>
        
        <div class="button-group">
            <a href="?id=<?php echo $comment_id; ?>&confirm=yes" class="button btn-danger">🗑️ Yes, Delete Comment</a>
            <a href="view_feedback.php?id=<?php echo $feedback_id; ?>" class="button btn-secondary">❌ Cancel</a>
        </div>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>