<?php
session_start();

// Ensure only Admin can access the Admin dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php'); // Redirect to login page if not logged in or not Admin
    exit;
}

include('../db/db.php'); // Include the database connection file

// Fetch all feedback data for Admin
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name, f.is_resolved 
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h1 {
            color: #333;
        }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-align: center;
            border: none;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        .button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h1>Welcome, Admin <?php echo $_SESSION['name']; ?></h1>

<!-- Button to manage users and HR -->
<a href="manage_users.php" class="button">Manage Users and HR</a>

<!-- Feedback Section -->
<h2>Feedback List</h2>
<?php
if ($result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>Feedback ID</th>
                <th>Category</th>
                <th>Feedback</th>
                <th>Submitted At</th>
                <th>Resolved</th>
                <th>Actions</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["feedback_id"] . "</td>
                <td>" . $row["category_name"] . "</td>
                <td>" . $row["feedback_text"] . "</td>
                <td>" . $row["submitted_at"] . "</td>
                <td>" . ($row["is_resolved"] ? "Yes" : "No") . "</td>
                <td>
                    <a href='../feedback/resolve_feedback.php?id=" . $row["feedback_id"] . "'>Mark as Resolved</a> | 
                    <a href='../feedback/delete_feedback.php?id=" . $row["feedback_id"] . "'>Delete</a>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No feedback found!</p>";
}

$conn->close();
?>

</body>
</html>
