<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Fetch all users
$sql = "SELECT user_id, name, email, role, created_at, last_login FROM management_user ORDER BY created_at DESC";
$result = $conn->query($sql);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_export_' . date('Y-m-d_H-i-s') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, array(
    'User ID',
    'Name',
    'Email',
    'Role',
    'Created At',
    'Last Login'
));

// Add data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['user_id'],
            $row['name'],
            $row['email'],
            ucfirst($row['role']),
            $row['created_at'],
            $row['last_login'] ?? 'Never'
        ));
    }
}

fclose($output);
$conn->close();
exit;
?>