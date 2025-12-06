<?php
include('../db/db.php');

$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name 
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Feedback ID: " . $row["feedback_id"] . " - " . $row["category_name"] . " - " . $row["feedback_text"] . " - " . $row["submitted_at"] . "<br>";
    }
} else {
    echo "No feedback found!";
}

$conn->close();
?>
