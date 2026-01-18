<?php
session_start();
include('../db/db.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote_type'])) {
    $feedback_id = $_GET['id'];  // Get feedback ID from URL
    $vote_type = $_POST['vote_type'];  // Get the type of vote: 'upvote' or 'downvote'

    // Check if the user has already voted
    $check_vote_sql = "SELECT * FROM feedback_votes WHERE feedback_id = ? AND user_id = ?";
    $check_vote_stmt = $conn->prepare($check_vote_sql);
    $check_vote_stmt->bind_param("ii", $feedback_id, $_SESSION['user_id']);
    $check_vote_stmt->execute();
    $check_vote_result = $check_vote_stmt->get_result();

    if ($check_vote_result->num_rows > 0) {
        // User has already voted, update the vote
        $existing_vote = $check_vote_result->fetch_assoc();
        if ($existing_vote['vote_type'] == $vote_type) {
            $_SESSION['error'] = "You already voted this way.";
        } else {
            // Update the existing vote
            $update_vote_sql = "UPDATE feedback_votes SET vote_type = ? WHERE feedback_id = ? AND user_id = ?";
            $update_vote_stmt = $conn->prepare($update_vote_sql);
            $update_vote_stmt->bind_param("sii", $vote_type, $feedback_id, $_SESSION['user_id']);
            $update_vote_stmt->execute();
            $_SESSION['success'] = "Your vote has been updated!";
        }
    } else {
        // User has not voted yet, insert a new vote
        $insert_vote_sql = "INSERT INTO feedback_votes (feedback_id, user_id, vote_type) VALUES (?, ?, ?)";
        $insert_vote_stmt = $conn->prepare($insert_vote_sql);
        $insert_vote_stmt->bind_param("iis", $feedback_id, $_SESSION['user_id'], $vote_type);
        $insert_vote_stmt->execute();
        $_SESSION['success'] = "Your vote has been submitted!";
    }

    // Redirect back to the feedback page
    header('Location: view_feedback.php?id=' . $feedback_id);
    exit;
}
?>
