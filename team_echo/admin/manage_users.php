<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php'); // Redirect to login page if not logged in or not Admin
    exit;
}

include('../db/db.php'); // Include the database connection file

// Fetch all users and HR members from the management_user table
$sql = "SELECT user_id, name, email, role FROM management_user";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users and HR</title>
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

<h1>Manage Users and HR</h1>

<!-- Table displaying the list of users and HR members -->
<table>
    <tr>
        <th>User ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row["user_id"] . "</td>
                    <td>" . $row["name"] . "</td>
                    <td>" . $row["email"] . "</td>
                    <td>" . $row["role"] . "</td>
                    <td>
                        <a href='edit_user.php?id=" . $row["user_id"] . "'>Edit</a> | 
                        <a href='delete_user.php?id=" . $row["user_id"] . "'>Delete</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No users found.</td></tr>";
    }
    ?>
</table>

</body>
</html>

<?php
$conn->close();
?>
