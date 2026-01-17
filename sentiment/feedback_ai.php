<?php
// feedback_ai.php
include('db/db.php'); // Your DB connection

$query = "SELECT message, category, created_at FROM feedback ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$total_feedback = mysqli_num_rows($result);
$cumulative_score = 0;
$pos_count = 0;
$neg_count = 0;

$feedback_list = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Call Python Script
    $message = escapeshellarg($row['message']);
    $python_output = shell_exec("python3 nltk_engine.py $message");
    $analysis = json_decode($python_output, true);
    
    // Aggregate Data
    $cumulative_score += $analysis['score'];
    if ($analysis['label'] == 'Positive') $pos_count++;
    if ($analysis['label'] == 'Negative') $neg_count++;
    
    $row['analysis'] = $analysis;
    $feedback_list[] = $row;
}

$average_mood = ($total_feedback > 0) ? round($cumulative_score / $total_feedback) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Sentiment Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; padding: 40px; }
        .container { max-width: 900px; margin: auto; }
        .summary-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; text-align: center; }
        .mood-bar { height: 20px; background: #ddd; border-radius: 10px; overflow: hidden; margin: 20px 0; }
        .mood-fill { height: 100%; background: linear-gradient(90deg, #ff416c, #ff4b2b, #a8ff78, #78ffd6); transition: 1s; }
        .feedback-item { background: #fff; padding: 15px; border-left: 5px solid; margin-bottom: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="summary-card">
            <h1>Team Mood: <?php echo $average_mood; ?>% Positive</h1>
            <div class="mood-bar">
                <div class="mood-fill" style="width: <?php echo $average_mood; ?>%"></div>
            </div>
            <p>Based on <?php echo $total_feedback; ?> entries analyzed via NLTK.</p>
        </div>

        <h3>Recent Feedback Analysis</h3>
        <?php foreach ($feedback_list as $item): ?>
            <div class="feedback-item" style="border-left-color: <?php echo $item['analysis']['color']; ?>">
                <strong>[<?php echo $item['category']; ?>]</strong> 
                <em>"<?php echo $item['message']; ?>"</em>
                <span style="float:right; font-weight:bold; color: <?php echo $item['analysis']['color']; ?>">
                    <?php echo $item['analysis']['label']; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
