<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Check if category_id is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid category ID.";
    header('Location: manage_category.php');
    exit;
}

$category_id = intval($_GET['id']);

// Fetch category details
$check_sql = "SELECT c.category_name, 
              (SELECT COUNT(*) FROM feedback WHERE category_id = c.category_id) as feedback_count
              FROM category c 
              WHERE c.category_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $category_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    $_SESSION['error'] = "Category not found.";
    header('Location: manage_category.php');
    exit;
}

$category = $check_result->fetch_assoc();

// Check if category has any feedback associated with it
if ($category['feedback_count'] > 0) {
    $_SESSION['error'] = "Cannot delete category '" . htmlspecialchars($category['category_name']) . "'. There are " . $category['feedback_count'] . " feedback entries using this category. Please delete or reassign the feedback first.";
    header('Location: manage_category.php');
    exit;
}

// Delete the category
$sql = "DELETE FROM category WHERE category_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Category '" . htmlspecialchars($category['category_name']) . "' deleted successfully!";
    } else {
        $_SESSION['error'] = "Category not found or already deleted.";
    }
} else {
    $_SESSION['error'] = "Error deleting category: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to manage categories page
header("Location: manage_category.php");
exit;
?>