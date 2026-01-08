<?php
session_start();
include('../db/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in first.";
    header('Location: ../user/login.php');
    exit;
}

$feedback_id = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote_type'])) {
    $vote_type = $_POST['vote_type'];

    $check_vote_sql = "SELECT * FROM feedback_votes WHERE feedback_id = ? AND user_id = ?";
    $check_vote_stmt = $conn->prepare($check_vote_sql);
    $check_vote_stmt->bind_param("ii", $feedback_id, $current_user_id);
    $check_vote_stmt->execute();
    $check_vote_result = $check_vote_stmt->get_result();

    if ($check_vote_result->num_rows > 0) {
        $existing_vote = $check_vote_result->fetch_assoc();
        if ($existing_vote['vote_type'] == $vote_type) {
            $_SESSION['error'] = "You already voted this way.";
        } else {
            $update_vote_sql = "UPDATE feedback_votes SET vote_type = ? WHERE feedback_id = ? AND user_id = ?";
            $update_vote_stmt = $conn->prepare($update_vote_sql);
            $update_vote_stmt->bind_param("sii", $vote_type, $feedback_id, $current_user_id);
            $update_vote_stmt->execute();
            $_SESSION['success'] = "Your vote has been updated!";
        }
    } else {
        $insert_vote_sql = "INSERT INTO feedback_votes (feedback_id, user_id, vote_type) VALUES (?, ?, ?)";
        $insert_vote_stmt = $conn->prepare($insert_vote_sql);
        $insert_vote_stmt->bind_param("iis", $feedback_id, $current_user_id, $vote_type);
        $insert_vote_stmt->execute();
        $_SESSION['success'] = "Your vote has been submitted!";
    }

    header('Location: view_feedback.php?id=' . $feedback_id);
    exit;
}

// Handle comment/reply submission (UPDATED WITH REPLY SUPPORT)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $comment_text = trim($_POST['comment_text']);
    $is_internal = isset($_POST['is_internal']) ? 1 : 0;
    $parent_comment_id = isset($_POST['parent_comment_id']) && is_numeric($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
    
    // Only HR/Admin can make internal comments
    if ($is_internal && !in_array($current_user_role, ['hr', 'admin'])) {
        $is_internal = 0;
    }
    
    // Verify parent comment exists if replying
    if ($parent_comment_id !== null) {
        $check_parent_sql = "SELECT comment_id, feedback_id FROM feedback_comments WHERE comment_id = ?";
        $check_parent_stmt = $conn->prepare($check_parent_sql);
        $check_parent_stmt->bind_param("i", $parent_comment_id);
        $check_parent_stmt->execute();
        $check_parent_result = $check_parent_stmt->get_result();
        
        if ($check_parent_result->num_rows == 0) {
            $_SESSION['error'] = "Parent comment not found.";
            header("Location: view_feedback.php?id=" . $feedback_id);
            exit;
        }
        
        $parent = $check_parent_result->fetch_assoc();
        if ($parent['feedback_id'] != $feedback_id) {
            $_SESSION['error'] = "Invalid comment reference.";
            header("Location: view_feedback.php?id=" . $feedback_id);
            exit;
        }
    }
    
    if (!empty($comment_text)) {
        $insert_comment = "INSERT INTO feedback_comments (feedback_id, user_id, comment_text, is_internal, parent_comment_id) VALUES (?, ?, ?, ?, ?)";
        $comment_stmt = $conn->prepare($insert_comment);
        $comment_stmt->bind_param("iisii", $feedback_id, $current_user_id, $comment_text, $is_internal, $parent_comment_id);
        
        if ($comment_stmt->execute()) {
            $_SESSION['success'] = $parent_comment_id ? "Reply posted successfully!" : "Comment added successfully!";
            header("Location: view_feedback.php?id=" . $feedback_id);
            exit;
        }
        $comment_stmt->close();
    }
}

// Fetch feedback details with vote counts
$feedback_sql = "SELECT f.*, c.category_name,
                (SELECT COUNT(*) FROM feedback_votes WHERE feedback_id = f.feedback_id AND vote_type = 'upvote') AS upvotes,
                (SELECT COUNT(*) FROM feedback_votes WHERE feedback_id = f.feedback_id AND vote_type = 'downvote') AS downvotes
                 FROM feedback f
                 JOIN category c ON f.category_id = c.category_id
                 WHERE f.feedback_id = ?";
$stmt = $conn->prepare($feedback_sql);
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$feedback_result = $stmt->get_result();

if ($feedback_result->num_rows == 0) {
    $_SESSION['error'] = "Feedback not found.";
    header('Location: ../user/user_dashboard.php');
    exit;
}

$feedback = $feedback_result->fetch_assoc();

// Fetch attachments
$attachments_sql = "SELECT * FROM feedback_attachments WHERE feedback_id = ?";
$attachments_stmt = $conn->prepare($attachments_sql);
$attachments_stmt->bind_param("i", $feedback_id);
$attachments_stmt->execute();
$attachments_result = $attachments_stmt->get_result();

// Check if current user has voted
$user_vote_sql = "SELECT vote_type FROM feedback_votes WHERE feedback_id = ? AND user_id = ?";
$user_vote_stmt = $conn->prepare($user_vote_sql);
$user_vote_stmt->bind_param("ii", $feedback_id, $current_user_id);
$user_vote_stmt->execute();
$user_vote_result = $user_vote_stmt->get_result();
$user_vote = $user_vote_result->num_rows > 0 ? $user_vote_result->fetch_assoc()['vote_type'] : null;

// Check permissions
$is_owner = isset($feedback['user_id']) && ($feedback['user_id'] == $current_user_id);
$is_hr_admin = in_array($current_user_role, ['hr', 'admin']);

// Fetch comments WITH THREADING SUPPORT
if ($is_hr_admin) {
    $comments_sql = "SELECT fc.*, u.name AS commenter_name, u.role AS commenter_role
                     FROM feedback_comments fc
                     JOIN management_user u ON fc.user_id = u.user_id
                     WHERE fc.feedback_id = ?
                     ORDER BY fc.created_at ASC";
} else {
    $comments_sql = "SELECT fc.*, u.name AS commenter_name, u.role AS commenter_role
                     FROM feedback_comments fc
                     JOIN management_user u ON fc.user_id = u.user_id
                     WHERE fc.feedback_id = ? AND fc.is_internal = 0
                     ORDER BY fc.created_at ASC";
}

$comments_stmt = $conn->prepare($comments_sql);
$comments_stmt->bind_param("i", $feedback_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

// Build threaded comment structure
$all_comments = [];
$comments_by_id = [];

while ($comment = $comments_result->fetch_assoc()) {
    $comment['replies'] = [];
    $comments_by_id[$comment['comment_id']] = $comment;
    $all_comments[] = $comment;
}

// Organize into tree structure
$root_comments = [];
foreach ($all_comments as $comment) {
    if ($comment['parent_comment_id'] === null) {
        $root_comments[] = &$comments_by_id[$comment['comment_id']];
    } else {
        if (isset($comments_by_id[$comment['parent_comment_id']])) {
            $comments_by_id[$comment['parent_comment_id']]['replies'][] = &$comments_by_id[$comment['comment_id']];
        }
    }
}

// Fetch tags
$tags_sql = "SELECT t.tag_name, t.tag_color
             FROM feedback_tags ft
             JOIN tags t ON ft.tag_id = t.tag_id
             WHERE ft.feedback_id = ?";
$tags_stmt = $conn->prepare($tags_sql);
$tags_stmt->bind_param("i", $feedback_id);
$tags_stmt->execute();
$tags_result = $tags_stmt->get_result();

// Function to display a comment and its replies recursively
function displayComment($comment, $current_user_id, $is_hr_admin, $current_user_role, $feedback_id, $depth = 0) {
    static $comment_number = 1;
    
    $is_comment_owner = ($comment['user_id'] == $current_user_id);
    $can_edit_delete = ($is_hr_admin || $is_comment_owner);
    $is_hr_admin_comment = in_array($comment['commenter_role'], ['hr', 'admin']);
    
    // Check if within 15-minute edit window for regular users
    $can_edit_time = true;
    if ($is_comment_owner && !$is_hr_admin) {
        $created_time = strtotime($comment['created_at']);
        $current_time = time();
        $time_elapsed = $current_time - $created_time;
        $can_edit_time = ($time_elapsed <= 900); // 15 minutes
    }
    
    // Determine display name
    if ($is_hr_admin_comment) {
        $display_name = htmlspecialchars($comment['commenter_name']);
        $display_role = ucfirst($comment['commenter_role']);
    } else {
        if ($is_comment_owner) {
            $display_name = "You";
            $display_role = "Your Comment";
        } else {
            $display_name = "Anonymous User #" . $comment_number;
            $display_role = "User";
            $comment_number++;
        }
    }
    
    $margin_left = $depth > 0 ? '40px' : '0';
    $reply_class = $depth > 0 ? 'comment-reply' : '';
    ?>
    
    <div class="comment <?php echo $reply_class; ?> <?php echo isset($comment['is_internal']) && $comment['is_internal'] ? 'internal' : ''; ?>" style="margin-left: <?php echo $margin_left; ?>;">
        <div class="comment-header">
            <div class="comment-author">
                <div class="avatar">
                    <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                </div>
                <div class="comment-author-info">
                    <span class="comment-author-name"><?php echo $display_name; ?></span>
                    <span class="comment-role"><?php echo $display_role; ?></span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if (isset($comment['is_internal']) && $comment['is_internal']): ?>
                    <span class="internal-badge">Internal</span>
                <?php endif; ?>
                <span class="comment-time"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></span>
            </div>
        </div>
        <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></div>
        
        <div class="comment-footer">
            <div>
                <?php if (isset($comment['edited_at']) && $comment['edited_at']): ?>
                    <span class="edited-indicator">Edited on <?php echo date('M d, Y H:i', strtotime($comment['edited_at'])); ?></span>
                <?php endif; ?>
            </div>
            <div class="comment-actions">
                <!-- Reply Button -->
                <a href="javascript:void(0);" class="comment-action-link" onclick="toggleReplyForm(<?php echo $comment['comment_id']; ?>)">
                    💬 Reply
                </a>
                
                <?php if ($can_edit_delete && $can_edit_time): ?>
                <a href="edit_comment.php?id=<?php echo $comment['comment_id']; ?>" class="comment-action-link">
                    ✏️ Edit
                </a>
                <?php endif; ?>
                
                <?php if ($can_edit_delete): ?>
                <a href="delete_comment.php?id=<?php echo $comment['comment_id']; ?>" class="comment-action-link delete" onclick="return confirm('Are you sure you want to delete this comment and all its replies?');">
                    🗑️ Delete
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reply Form (Hidden by default) -->
        <div id="reply-form-<?php echo $comment['comment_id']; ?>" class="reply-form" style="display: none;">
            <form method="POST" action="">
                <input type="hidden" name="parent_comment_id" value="<?php echo $comment['comment_id']; ?>">
                <div class="form-group" style="margin-bottom: 10px;">
                    <textarea name="comment_text" required placeholder="Write your reply..." style="min-height: 80px;"></textarea>
                </div>
                <?php if ($is_hr_admin): ?>
                <div class="checkbox-group" style="margin-bottom: 10px;">
                    <input type="checkbox" id="is_internal_reply_<?php echo $comment['comment_id']; ?>" name="is_internal">
                    <label for="is_internal_reply_<?php echo $comment['comment_id']; ?>">
                        <strong>Internal Reply</strong> (Only visible to HR and Admin)
                    </label>
                </div>
                <?php endif; ?>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="add_comment" class="button btn-primary" style="padding: 8px 16px; font-size: 13px;">Post Reply</button>
                    <button type="button" class="button" style="padding: 8px 16px; font-size: 13px; background: #6c757d; color: white;" onclick="toggleReplyForm(<?php echo $comment['comment_id']; ?>)">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    // Recursively display replies
    if (!empty($comment['replies'])) {
        foreach ($comment['replies'] as $reply) {
            displayComment($reply, $current_user_id, $is_hr_admin, $current_user_role, $feedback_id, $depth + 1);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Details</title>
    <link rel="stylesheet" href="view_feedback.css">
    <script>
        function toggleReplyForm(commentId) {
            const form = document.getElementById('reply-form-' + commentId);
            if (form.style.display === 'none' || form.style.display === '') {
                // Hide all other reply forms first
                document.querySelectorAll('.reply-form').forEach(f => f.style.display = 'none');
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <?php if ($is_hr_admin): ?>
                <a href="<?php echo $current_user_role == 'admin' ? '../admin/admin_dashboard.php' : '../hr/hr_dashboard.php'; ?>" class="nav-item">Dashboard</a>
                <?php if ($current_user_role == 'admin'): ?>
                    <a href="../admin/manage_users.php" class="nav-item">Manage Users</a>
                <?php endif; ?>
                <a href="../category/manage_category.php" class="nav-item">Manage Categories</a>
                <a href="../admin/manage_tags.php" class="nav-item">Manage Tags</a>
                <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
                <a href="export_feedback.php" class="nav-item">Export Feedback</a>
            <?php else: ?>
                <a href="../user/user_dashboard.php" class="nav-item">Dashboard</a>
                <a href="../user/submit_feedback.php" class="nav-item">Submit Feedback</a>
                <a href="../user/my_feedback.php" class="nav-item">My Feedback</a>
            <?php endif; ?>
            <a href="view_feedback.php?id=<?php echo $feedback_id; ?>" class="nav-item active">View Feedback</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Feedback #<?php echo $feedback_id; ?> Details</div>
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

            <!-- Attachments Section -->
            <?php if ($attachments_result->num_rows > 0): ?>
            <div class="attachments-card">
                <h2>Attachments (<?php echo $attachments_result->num_rows; ?>)</h2>
                <div class="attachment-grid">
                    <?php 
                    while ($attachment = $attachments_result->fetch_assoc()): 
                        $file_ext = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                        $file_type_display = strtoupper($file_ext);
                    ?>
                        <div class="attachment-item">
                            <div class="attachment-icon"><?php echo $file_type_display; ?></div>
                            <div class="attachment-info">
                                <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="attachment-name" title="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                    <?php echo htmlspecialchars($attachment['file_name']); ?></span>
                                </a>
                                <div class="attachment-meta">
                                    <span class="attachment-size"><?php echo round($attachment['file_size'] / 1024, 2); ?> KB</span>
                                    <span>•</span>
                                    <span><?php echo strtoupper($file_ext); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Feedback Information Card -->
            <div class="card">
                <h2>Feedback Information</h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Feedback ID</span>
                        <span class="info-value">#<?php echo htmlspecialchars($feedback['feedback_id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Category</span>
                        <span class="info-value"><?php echo htmlspecialchars($feedback['category_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <?php if ($feedback['is_resolved']): ?>
                                <span class="badge badge-resolved">Resolved</span>
                            <?php else: ?>
                                <span class="badge badge-unresolved">Unresolved</span>
                            <?php endif; ?>
                            <?php if ($is_owner): ?>
                                <span class="badge badge-owner">You</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Priority</span>
                        <span class="info-value">
                            <span class="priority-badge priority-<?php echo $feedback['priority'] ?? 'medium'; ?>">
                                <?php echo strtoupper($feedback['priority'] ?? 'medium'); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Submitted</span>
                        <span class="info-value"><?php echo date('M d, Y H:i', strtotime($feedback['submitted_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tags</span>
                        <span class="info-value">
                            <?php 
                            if ($tags_result->num_rows > 0) {
                                while ($tag = $tags_result->fetch_assoc()) {
                                    echo '<span class="tag-item" style="background-color: ' . htmlspecialchars($tag['tag_color']) . '">' . htmlspecialchars($tag['tag_name']) . '</span>';
                                }
                            } else {
                                echo '<span style="color: #6c757d; font-size: 13px;">No tags</span>';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <span class="info-label">Feedback Content</span>
                    <div class="feedback-text">
                        <?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?>
                    </div>
                </div>
                
                <!-- Voting Section -->
                <div class="voting-section">
                    <div class="voting-header">Vote on this feedback</div>
                    <form method="POST" action="">
                        <div class="vote-buttons">
                            <button type="submit" name="vote_type" value="upvote" class="btn-vote btn-upvote <?php echo $user_vote === 'upvote' ? 'active' : ''; ?>">
                                ↑ Upvote
                            </button>
                            <button type="submit" name="vote_type" value="downvote" class="btn-vote btn-downvote <?php echo $user_vote === 'downvote' ? 'active' : ''; ?>">
                                ↓ Downvote
                            </button>
                        </div>
                    </form>
                    
                    <div class="vote-stats">
                        <div class="vote-stat-item">
                            <div class="vote-count">
                                <span class="vote-count-number"><?php echo $feedback['upvotes']; ?></span>
                                <span class="vote-count-label">Upvotes</span>
                            </div>
                        </div>
                        <div class="vote-divider"></div>
                        <div class="vote-stat-item">
                            <div class="vote-count">
                                <span class="vote-count-number"><?php echo $feedback['downvotes']; ?></span>
                                <span class="vote-count-label">Downvotes</span>
                            </div>
                        </div>
                        <?php 
                        $total_votes = $feedback['upvotes'] + $feedback['downvotes'];
                        if ($total_votes > 0) {
                            $upvote_percentage = round(($feedback['upvotes'] / $total_votes) * 100);
                        ?>
                        <div class="vote-divider"></div>
                        <div class="vote-stat-item">
                            <div class="vote-count">
                                <span class="vote-count-number"><?php echo $upvote_percentage; ?>%</span>
                                <span class="vote-count-label">Approval</span>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    
                    <?php if ($user_vote): ?>
                    <div style="margin-top: 15px; padding: 10px 15px; background: white; border-radius: 6px; border-left: 3px solid <?php echo $user_vote === 'upvote' ? 'var(--accent-green-light)' : '#dc3545'; ?>; font-size: 13px; color: #495057;">
                        <strong>Your vote:</strong> <?php echo $user_vote === 'upvote' ? 'Upvoted' : 'Downvoted'; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_hr_admin): ?>
                <div class="action-buttons">
                    <a href="manage_feedback_tags.php?id=<?php echo $feedback_id; ?>" class="button btn-primary">Manage Tags & Priority</a>
                    <?php if (!$feedback['is_resolved']): ?>
                        <form method="POST" action="resolve_feedback.php" style="display: inline;">
                            <input type="hidden" name="feedback_id" value="<?php echo $feedback_id; ?>">
                            <button type="submit" class="button btn-primary">Mark as Resolved</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php elseif ($is_owner && !$feedback['is_resolved']): ?>
                <div class="action-buttons">
                    <a href="edit_feedback.php?id=<?php echo $feedback_id; ?>" class="button btn-primary">Edit Feedback</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Comments Section with Threading -->
            <div class="card">
                <h2>Discussion Thread (<?php echo count($all_comments); ?>)</h2>
                
                <div class="comments-section">
                    <?php if (count($root_comments) > 0): ?>
                        <?php 
                        foreach ($root_comments as $comment) {
                            displayComment($comment, $current_user_id, $is_hr_admin, $current_user_role, $feedback_id);
                        }
                        ?>
                    <?php else: ?>
                        <div class="no-comments">
                            <h3>No comments yet</h3>
                            <p>Be the first to start the discussion!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add Comment Form -->
            <div class="add-comment-form">
                <h3>Add Comment</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="comment_text">Your Comment</label>
                        <textarea id="comment_text" name="comment_text" required placeholder="Type your comment here..."></textarea>
                    </div>
                    
                    <?php if ($is_hr_admin): ?>
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_internal" name="is_internal">
                        <label for="is_internal">
                            <strong>Internal Comment</strong> (Only visible to HR and Admin)
                        </label>
                    </div>
                    <span class="help-text">Uncheck to make this comment visible to the feedback submitter</span>
                    <?php endif; ?>
                    
                    <button type="submit" name="add_comment" class="button btn-primary">Post Comment</button>
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
