<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit;
}

include('../db/db.php'); 

// Fetch all anonymous feedback for the user
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name, f.is_resolved
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id 
        WHERE f.is_anonymous = 1
        ORDER BY f.submitted_at DESC";
$result = $conn->query($sql);

// Statistics
$totalFeedback = $result->num_rows;

$sqlResolved = "SELECT COUNT(*) as count FROM feedback WHERE is_anonymous=1 AND is_resolved=1";
$resResolved = $conn->query($sqlResolved);
$resolved = $resResolved->fetch_assoc()['count'];

$sqlPending = "SELECT COUNT(*) as count FROM feedback WHERE is_anonymous=1 AND is_resolved=0";
$resPending = $conn->query($sqlPending);
$pending = $resPending->fetch_assoc()['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Inter', sans-serif;
    margin: 0;
    background: #f6f8f9;
}

.header {
    background: linear-gradient(135deg, #064c44, #0ca986);
    color: white;
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
}

.header .logout-btn {
    background: #ffffff22;
    border: 1px solid #ffffff44;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    color: white;
    transition: 0.3s;
}

.header .logout-btn:hover {
    background: #ffffff33;
}

.container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px;
}

/* Stats Cards */
.stats {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.card {
    flex: 1;
    min-width: 150px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
}

.card h3 {
    margin: 0;
    font-size: 18px;
    color: #888;
    font-weight: 500;
}

.card p {
    font-size: 24px;
    font-weight: 700;
    color: #064c44;
    margin: 10px 0 0;
}

/* Submit button */
.submit-btn {
    background: linear-gradient(135deg, #064c44, #0ca986);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: 0.3s;
    margin-bottom: 20px;
}

.submit-btn:hover {
    background: linear-gradient(135deg, #043d36, #08a68f);
}

/* Feedback Table */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0px 3px 8px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

th {
    background: #064c44;
    color: white;
    padding: 14px;
    font-size: 15px;
    text-align: left;
}

td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    color: #333;
}

tr:hover td {
    background: #f3fdfa;
}

/* Responsive */
@media(max-width: 768px){
    .header {
        flex-direction: column;
        align-items: flex-start;
    }
    .header h1 {
        margin-bottom: 10px;
    }
    .stats {
        flex-direction: column;
        gap: 15px;
    }
    table, th, td {
        font-size: 13px;
    }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
    <form action="../logout.php" method="post">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>

<div class="container">

    <!-- Stats Cards -->
    <div class="stats">
        <div class="card">
            <h3>Total Feedback</h3>
            <p><?php echo $totalFeedback; ?></p>
        </div>
        <div class="card">
            <h3>Resolved</h3>
            <p><?php echo $resolved; ?></p>
        </div>
        <div class="card">
            <h3>Pending</h3>
            <p><?php echo $pending; ?></p>
        </div>
    </div>

    <!-- Submit Feedback Button -->
    <a href="../feedback/submit_feedback.php" class="submit-btn">Submit Feedback</a>

    <!-- Feedback Table -->
    <h2>Anonymous Feedback List</h2>
    <?php
    if ($totalFeedback > 0) {
        echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Feedback</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                </tr>";
        foreach($result as $row){
            echo "<tr>
                    <td>{$row['feedback_id']}</td>
                    <td>{$row['category_name']}</td>
                    <td>{$row['feedback_text']}</td>
                    <td>{$row['submitted_at']}</td>
                    <td>" . ($row['is_resolved'] ? "✔ Resolved" : "⏳ Pending") . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No feedback found!</p>";
    }
    ?>
</div>

</body>
</html>
