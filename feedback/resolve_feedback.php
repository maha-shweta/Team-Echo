<?php
session_start();

// Ensure only HR/Admin can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'hr')) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Check if feedback ID is provided via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['feedback_id']) || !is_numeric($_POST['feedback_id'])) {
    $_SESSION['error'] = "Invalid feedback ID.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/admin_dashboard.php'));
    exit;
}

$feedback_id = intval($_POST['feedback_id']);
$resolved_by = $_SESSION['user_id'];
$resolved_at = date('Y-m-d H:i:s');

// Check if the feedback exists and is not already resolved
$check_sql = "SELECT is_resolved FROM feedback WHERE feedback_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $feedback_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['error'] = "Feedback not found.";
    $check_stmt->close();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/admin_dashboard.php'));
    exit;
}

$feedback = $check_result->fetch_assoc();

// If already resolved, redirect with an error message
if ($feedback['is_resolved'] == 1) {
    $_SESSION['error'] = "This feedback is already resolved.";
    $check_stmt->close();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/admin_dashboard.php'));
    exit;
}

$check_stmt->close();

// Update feedback status to resolved
$sql = "UPDATE feedback SET is_resolved = 1, resolved_by = ?, resolved_at = ? WHERE feedback_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("isi", $resolved_by, $resolved_at, $feedback_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Feedback marked as resolved successfully!";
    } else {
        $_SESSION['error'] = "Error resolving feedback: " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "Database error: " . $conn->error;
}

$conn->close();

// Redirect back to the referring page or to the admin dashboard
$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../admin/admin_dashboard.php';
header('Location: ' . $redirect_url);
exit;
?>