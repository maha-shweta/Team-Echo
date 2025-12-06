<?php
session_start();

// Ensure only logged-in users can access this page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit;
}

include('../db/db.php'); // Include the database connection file

// Fetch feedback data for users to view
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name 
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id WHERE f.is_anonymous = 1";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h1 {
            color: #333;
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
    </style>
</head>
<body>

<h1>Welcome, <?php echo $_SESSION['name']; ?></h1>

<!-- Button to submit feedback -->
<a href="../feedback/submit_feedback.php" class="button">Submit Feedback</a>

<!-- Feedback Section -->
<h2>Feedback List (Anonymously)</h2>
<?php
if ($result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>Feedback ID</th>
                <th>Category</th>
                <th>Feedback</th>
                <th>Submitted At</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["feedback_id"] . "</td>
                <td>" . $row["category_name"] . "</td>
                <td>" . $row["feedback_text"] . "</td>
                <td>" . $row["submitted_at"] . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No feedback found!</p>";
}

// Close connection
$conn->close();
?>

</body>
</html>
