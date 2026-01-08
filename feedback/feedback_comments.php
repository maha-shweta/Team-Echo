<?php 
session_start();

// Ensure only HR/Admin/User can access
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');
include('../db/notification_helper.php');

// Get feedback ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid feedback ID.";
    header('Location: ' . ($_SESSION['role'] == 'admin' ? '../admin/admin_dashboard.php' : ($_SESSION['role'] == 'hr' ? '../hr/hr_dashboard.php' : '../user/user_dashboard.php')));
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
    header('Location: ' . ($_SESSION['role'] == 'admin' ? '../admin/admin_dashboard.php' : ($_SESSION['role'] == 'hr' ? '../hr/hr_dashboard.php' : '../user/user_dashboard.php')));
    exit;
}

$feedback = $feedback_result->fetch_assoc();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $comment_text = trim($_POST['comment_text']);
    $is_internal = ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'hr') && isset($_POST['is_internal']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    if (!empty($comment_text)) {
        $insert_sql = "INSERT INTO feedback_comments (feedback_id, user_id, comment_text, is_internal) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iisi", $feedback_id, $user_id, $comment_text, $is_internal);
        
        if ($insert_stmt->execute()) {
            // Get commenter details for notification
            $commenter_sql = "SELECT name, role FROM management_user WHERE user_id = ?";
            $commenter_stmt = $conn->prepare($commenter_sql);
            $commenter_stmt->bind_param("i", $user_id);
            $commenter_stmt->execute();
            $commenter_result = $commenter_stmt->get_result();
            $commenter = $commenter_result->fetch_assoc();
            $commenter_stmt->close();
            
            // Fetch feedback owner
            $owner_sql = "SELECT user_id FROM feedback WHERE feedback_id = ?";
            $owner_stmt = $conn->prepare($owner_sql);
            $owner_stmt->bind_param("i", $feedback_id);
            $owner_stmt->execute();
            $owner_result = $owner_stmt->get_result();
            $owner_row = $owner_result->fetch_assoc();
            $owner_stmt->close();
            
            // Notify feedback owner (don't notify if commenting on own feedback)
            if ($owner_row && $owner_row['user_id'] != $user_id) {
                notifyFeedbackOwnerNewComment($conn, $feedback_id, $commenter['name'], $commenter['role']);
            }
            
            $_SESSION['success'] = "Comment added successfully!";
        } else {
            $_SESSION['error'] = "Error adding comment: " . $insert_stmt->error;
        }
        $insert_stmt->close();
        header('Location: feedback_comments.php?id=' . $feedback_id);
        exit;
    }
}

// Handle comment deletion
if (isset($_GET['delete_comment']) && is_numeric($_GET['delete_comment'])) {
    $comment_id = intval($_GET['delete_comment']);
    
    // Check if user owns the comment or is admin
    $check_sql = "SELECT user_id FROM feedback_comments WHERE comment_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $comment_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $comment_owner = $check_result->fetch_assoc()['user_id'];
        
        if ($comment_owner == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {
            $delete_sql = "DELETE FROM feedback_comments WHERE comment_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $comment_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            $_SESSION['success'] = "Comment deleted successfully!";
        } else {
            $_SESSION['error'] = "You don't have permission to delete this comment.";
        }
    }
    $check_stmt->close();
    header('Location: feedback_comments.php?id=' . $feedback_id);
    exit;
}

// Fetch comments
if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'hr') {
    // HR and Admin see all comments (internal and public) with real names
    $comments_sql = "SELECT c.*, u.name as commenter_name, u.role as commenter_role
                     FROM feedback_comments c
                     JOIN management_user u ON c.user_id = u.user_id
                     WHERE c.feedback_id = ?
                     ORDER BY c.created_at DESC";
} else {
    // Regular users see only public comments
    $comments_sql = "SELECT c.*, u.name as commenter_name, u.role as commenter_role
                     FROM feedback_comments c
                     JOIN management_user u ON c.user_id = u.user_id
                     WHERE c.feedback_id = ? AND c.is_internal = 0
                     ORDER BY c.created_at DESC";
}

$comments_stmt = $conn->prepare($comments_sql);
$comments_stmt->bind_param("i", $feedback_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Comments</title>
    <link rel="stylesheet" href="feedback_comments.css">
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header-card">
        <h1>💬 Feedback Comments</h1>
        <p>Discussion and notes for Feedback #<?php echo $feedback_id; ?></p>
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
    <a href="<?php 
        if ($_SESSION['role'] == 'admin') {
            echo '../admin/admin_dashboard.php';
        } elseif ($_SESSION['role'] == 'hr') {
            echo '../hr/hr_dashboard.php';
        } else {
            echo '../user/user_dashboard.php';
        }
    ?>" class="button btn-back">← Back to Dashboard</a>
    
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
        </div>
    </div>
    
    <!-- Add Comment Form -->
    <div class="card">
        <h2>➕ Add Comment</h2>
        <form method="POST" action="" class="comment-form">
            <div class="form-group">
                <label for="comment_text">Your Comment *</label>
                <textarea id="comment_text" name="comment_text" required placeholder="Write your comment here..."></textarea>
            </div>
            
            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'hr'): ?>
            <div class="checkbox-group">
                <input type="checkbox" id="is_internal" name="is_internal" value="1">
                <label for="is_internal">🔒 Make this an internal note (only visible to HR and Admin)</label>
            </div>
            <div class="internal-note">
                💡 Internal notes are only visible to HR and Admin staff. Public comments can be seen by all users.
            </div>
            <?php endif; ?>
            
            <button type="submit" name="add_comment" class="button btn-primary" style="margin-top: 15px;">💬 Post Comment</button>
        </form>
    </div>
    
    <!-- Comments List -->
    <div class="card">
        <div class="comments-header">
            <h2>💭 Comments & Discussion</h2>
            <span class="comments-count"><?php echo $comments_result->num_rows; ?> Comment(s)</span>
        </div>
        
        <?php if ($comments_result->num_rows > 0): ?>
            <?php 
            $comment_number = 1;
            $is_viewer_hr_admin = ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'hr');
            
            while ($comment = $comments_result->fetch_assoc()): 
                $is_hr_admin_comment = in_array($comment['commenter_role'], ['hr', 'admin']);
                $is_own_comment = ($comment['user_id'] == $_SESSION['user_id']);
                
                // Determine display name based on commenter role
                if ($is_hr_admin_comment) {
                    // Show real names for HR/Admin comments (visible to everyone)
                    $display_name = htmlspecialchars($comment['commenter_name']);
                    $display_role = ucfirst($comment['commenter_role']);
                } else {
                    // Regular users are always anonymized
                    if ($is_own_comment) {
                        $display_name = "You";
                        $display_role = "Your Comment";
                    } else {
                        $display_name = "Anonymous User #" . $comment_number;
                        $display_role = "User";
                        $comment_number++; // Only increment for anonymous users
                    }
                }
            ?>
                <div class="comment-item <?php echo $comment['is_internal'] ? 'internal' : ''; ?>">
                    <div class="comment-header">
                        <div class="commenter-info">
                            <div class="commenter-avatar">
                                <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                            </div>
                            <div class="commenter-details">
                                <span class="commenter-name"><?php echo $display_name; ?></span>
                                <span class="commenter-role"><?php echo $display_role; ?></span>
                            </div>
                        </div>
                        <div class="comment-meta">
                            <span class="comment-date"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></span>
                            <?php if ($comment['is_internal']): ?>
                                <span class="internal-badge">🔒 Internal</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="comment-text">
                        <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                    </div>
                    <?php if ($is_own_comment || $_SESSION['role'] == 'admin'): ?>
                        <div class="comment-actions">
                            <a href="?id=<?php echo $feedback_id; ?>&delete_comment=<?php echo $comment['comment_id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this comment?')">
                                🗑️ Delete
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-comments">
                <div class="icon">💬</div>
                <p>No comments yet. Be the first to comment!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>
