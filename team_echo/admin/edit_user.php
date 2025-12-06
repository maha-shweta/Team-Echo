<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

include('../db/db.php');  // Include database connection file

// Check if the user_id is provided
if (!isset($_GET['id'])) {
    echo "User ID is required.";
    exit;
}

// Fetch user details from the database based on the ID
$user_id = $_GET['id'];
$sql = "SELECT * FROM management_user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found.";
    exit;
}

// Handle the form submission to update user details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($role)) {
        echo "All fields are required.";
        exit;
    }

    // Update user data
    $update_sql = "UPDATE management_user SET name = ?, email = ?, role = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssi", $name, $email, $role, $user_id);

    if ($stmt->execute()) {
        echo "User updated successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!-- Form for editing user -->
<h2>Edit User</h2>
<form method="POST" action="">
    <label for="name">Name:</label>
    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required><br>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>

    <label for="role">Role:</label>
    <select id="role" name="role">
        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="hr" <?php echo $user['role'] == 'hr' ? 'selected' : ''; ?>>HR</option>
        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
    </select><br>

    <input type="submit" value="Update User">
</form>
