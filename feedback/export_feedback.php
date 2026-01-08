<?php
session_start();

// Ensure only Admin/HR can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'hr')) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get filter parameters (same as dashboard filters)
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build SQL query with filters
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name, 
        f.is_resolved, f.sentiment_score, f.sentiment_label,
        CASE WHEN f.is_resolved = 1 THEN u.name ELSE 'N/A' END as resolved_by_name,
        f.resolved_at
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id
        LEFT JOIN management_user u ON f.resolved_by = u.user_id
        WHERE 1=1";

// Apply filters
if (!empty($category_filter)) {
    $sql .= " AND f.category_id = " . intval($category_filter);
}

if ($status_filter !== '') {
    if ($status_filter == '1') {
        $sql .= " AND f.is_resolved = 1";
    } elseif ($status_filter == '0') {
        $sql .= " AND f.is_resolved = 0";
    }
}

if (!empty($search_query)) {
    $search_query = $conn->real_escape_string($search_query);
    $sql .= " AND (f.feedback_text LIKE '%$search_query%' OR c.category_name LIKE '%$search_query%')";
}

if (!empty($date_from)) {
    $sql .= " AND DATE(f.submitted_at) >= '" . $conn->real_escape_string($date_from) . "'";
}
if (!empty($date_to)) {
    $sql .= " AND DATE(f.submitted_at) <= '" . $conn->real_escape_string($date_to) . "'";
}

$sql .= " ORDER BY f.submitted_at DESC";
$result = $conn->query($sql);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=feedback_export_' . date('Y-m-d_H-i-s') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 (helps Excel display special characters correctly)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, array(
    'Feedback ID',
    'Category',
    'Feedback Text',
    'Submitted At',
    'Status',
    'Sentiment Score',
    'Sentiment Label',
    'Resolved By',
    'Resolved At'
));

// Add data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['feedback_id'],
            $row['category_name'],
            $row['feedback_text'],
            $row['submitted_at'],
            $row['is_resolved'] ? 'Resolved' : 'Unresolved',
            $row['sentiment_score'] ?? 'N/A',
            $row['sentiment_label'] ?? 'N/A',
            $row['resolved_by_name'],
            $row['resolved_at'] ?? 'N/A'
        ));
    }
}

fclose($output);
$conn->close();
exit;
?>
