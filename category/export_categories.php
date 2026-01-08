<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Fetch all categories with feedback count
$sql = "SELECT c.category_id, c.category_name, c.created_at,
        (SELECT COUNT(*) FROM feedback WHERE category_id = c.category_id) as feedback_count,
        (SELECT COUNT(*) FROM feedback WHERE category_id = c.category_id AND is_resolved = 1) as resolved_count,
        (SELECT COUNT(*) FROM feedback WHERE category_id = c.category_id AND is_resolved = 0) as unresolved_count
        FROM category c
        ORDER BY c.category_name";
$result = $conn->query($sql);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=categories_export_' . date('Y-m-d_H-i-s') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, array(
    'Category ID',
    'Category Name',
    'Total Feedback',
    'Resolved Feedback',
    'Unresolved Feedback',
    'Created At'
));

// Add data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['category_id'],
            $row['category_name'],
            $row['feedback_count'],
            $row['resolved_count'],
            $row['unresolved_count'],
            $row['created_at']
        ));
    }
}

fclose($output);
$conn->close();
exit;
?>
