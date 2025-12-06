<?php
session_start();

// Ensure only logged-in users can submit feedback
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit;
}

include('../db/db.php'); // Include the database connection file

// Fetch feedback categories from the database
$sql = "SELECT * FROM category";
$result = $conn->query($sql);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = $_POST['category_id'];
    $feedback_text = $_POST['feedback_text'];

    // Validate the inputs
    if (empty($category_id) || empty($feedback_text)) {
        echo "Please fill out all fields.";
        exit;
    }

    // Insert feedback into the database (anonymously)
    $sql = "INSERT INTO feedback (category_id, feedback_text, is_anonymous) VALUES (?, ?, 1)"; // is_anonymous = 1 for anonymous feedback
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $category_id, $feedback_text); // Bind parameters: category_id (integer), feedback_text (string)
        $stmt->execute();
        echo "Feedback submitted successfully!";
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<!-- HTML form for submitting feedback -->
<h2>Submit Feedback (Anonymous)</h2>
<form method="POST" action="">
    <label for="category_id">Category:</label>
    <select name="category_id" required>
        <option value="">Select a category</option>
        <?php
        // Populate the categories dropdown
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row["category_id"] . "'>" . $row["category_name"] . "</option>";
            }
        }
        ?>
    </select><br>

    <label for="feedback_text">Your Feedback:</label><br>
    <textarea name="feedback_text" rows="4" cols="50" required></textarea><br>

    <input type="submit" value="Submit Feedback">
</form>
