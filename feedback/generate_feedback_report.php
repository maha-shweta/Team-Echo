<?php
session_start();

include('../db/db.php');

// Check if the user is logged in and has the correct role (admin, HR)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'hr')) {
    header('Location: ../user/login.php');
    exit;
}

// Fetch all feedback records
$query = "SELECT f.feedback_id, c.category_name, f.feedback_text, f.priority, f.is_resolved, 
                 f.submitted_at, u.name as user_name
          FROM feedback f
          JOIN category c ON f.category_id = c.category_id
          LEFT JOIN management_user u ON f.user_id = u.user_id
          ORDER BY f.submitted_at DESC";
$result = $conn->query($query);

// Check if there are any feedback records
if ($result->num_rows > 0) {
    // Define the filename for the CSV report
    $filename = "feedback_report_" . date('Y-m-d_H-i-s') . ".csv";

    // Set the headers to download the CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Open PHP output stream to write the CSV data
    $output = fopen('php://output', 'w');

    // Output the header row for the CSV file
    fputcsv($output, ['Feedback ID', 'Category', 'Feedback Text', 'Priority', 'Resolved', 'Submitted At', 'Submitted By']);

    // Loop through the result and output each feedback record as a row in the CSV
    while ($row = $result->fetch_assoc()) {
        // Prepare data for the current row
        $resolved_status = $row['is_resolved'] ? 'Yes' : 'No';
        $feedback_data = [
            $row['feedback_id'],
            $row['category_name'],
            $row['feedback_text'],
            ucfirst($row['priority']),
            $resolved_status,
            date('M d, Y H:i', strtotime($row['submitted_at'])),
            $row['user_name'] ? $row['user_name'] : 'Anonymous'
        ];
        
        // Write the row to the CSV file
        fputcsv($output, $feedback_data);
    }

    // Close the output stream
    fclose($output);
} else {
    $_SESSION['error'] = "No feedback data found.";
    header('Location: ' . ($_SESSION['role'] == 'admin' ? '../admin/admin_dashboard.php' : '../hr/hr_dashboard.php'));
    exit;
}

$conn->close();
?>
