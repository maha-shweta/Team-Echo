<?php
session_start();

// --- START: ORIGINAL BACKEND LOGIC (UNTOUCHED) ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
$resolved_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_resolved = 1")->fetch_assoc()['count'];
$unresolved_feedback = $total_feedback - $resolved_feedback;
$total_categories = $conn->query("SELECT COUNT(*) as count FROM category")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM management_user")->fetch_assoc()['count'];

$category_sql = "SELECT c.category_name, COUNT(f.feedback_id) as count 
                 FROM category c
                 LEFT JOIN feedback f ON c.category_id = f.category_id
                 GROUP BY c.category_id, c.category_name
                 ORDER BY count DESC
                 LIMIT 10";
$category_result = $conn->query($category_sql);

$category_labels = []; $category_data = [];
while ($row = $category_result->fetch_assoc()) {
    $category_labels[] = $row['category_name'];
    $category_data[] = $row['count'];
}

$timeline_sql = "SELECT DATE(submitted_at) as date, COUNT(*) as count 
                 FROM feedback 
                 WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY DATE(submitted_at)
                 ORDER BY date ASC";
$timeline_result = $conn->query($timeline_sql);

$timeline_labels = []; $timeline_data = [];
while ($row = $timeline_result->fetch_assoc()) {
    $timeline_labels[] = date('M d', strtotime($row['date']));
    $timeline_data[] = $row['count'];
}

$resolution_sql = "SELECT c.category_name,
                   COUNT(f.feedback_id) as total,
                   SUM(CASE WHEN f.is_resolved = 1 THEN 1 ELSE 0 END) as resolved
                   FROM category c
                   LEFT JOIN feedback f ON c.category_id = f.category_id
                   GROUP BY c.category_id, c.category_name
                   HAVING total > 0
                   ORDER BY c.category_name";
$resolution_result = $conn->query($resolution_sql);

$resolution_labels = []; $resolution_rates = []; $res_raw_ok = []; $res_raw_pending = [];
while ($row = $resolution_result->fetch_assoc()) {
    $resolution_labels[] = $row['category_name'];
    $rate = ($row['total'] > 0) ? round(($row['resolved'] / $row['total']) * 100, 1) : 0;
    $resolution_rates[] = $rate;
    $res_raw_ok[] = $row['resolved'];
    $res_raw_pending[] = $row['total'] - $row['resolved'];
}
$conn->close();
// --- END: ORIGINAL BACKEND LOGIC ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Screen-Fit Analytics | Team Echo</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #0C4F3B; --accent: #1eb386; --bg: #f3f6f5; }
        
        /* Force no scroll and fix height to screen */
        html, body { 
            height: 100vh; 
            width: 100vw; 
            margin: 0; 
            padding: 0; 
            overflow: hidden; 
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .container { 
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 15px 25px;
            box-sizing: border-box;
        }

        .dashboard-head { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            height: 40px;
            margin-bottom: 10px;
        }
        .dashboard-head h1 { font-size: 18px; color: var(--primary); font-weight: 800; }

        /* Metric Tiles - Small and Fixed */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 15px; 
            height: 80px;
            margin-bottom: 15px;
        }
        .card { 
            background: white; 
            padding: 10px 15px; 
            border-radius: 12px; 
            border: 1px solid #eef1f0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .card p { font-size: 9px; color: #718096; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; font-weight: 800; }
        .card h2 { font-size: 20px; color: var(--primary); margin: 0; }

        /* Charts Layout - Occupies remaining 100vh space */
        .charts-container { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            flex-grow: 1; /* Fills remaining space */
            padding-bottom: 10px;
        }
        .chart-box { 
            background: white; 
            padding: 12px; 
            border-radius: 16px; 
            border: 1px solid #eef1f0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chart-box h3 { font-size: 12px; margin-bottom: 8px; color: #4a5568; font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .chart-box h3::before { content: ''; width: 3px; height: 12px; background: var(--accent); border-radius: 4px; }
        
        .chart-wrapper { 
            flex-grow: 1; /* Makes the canvas expand to fill the card */
            position: relative; 
            width: 100%;
        }

        .btn-exit { font-size: 11px; color: var(--primary); font-weight: 700; text-decoration: none; border: 1px solid var(--primary); padding: 5px 10px; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container">
    <div class="dashboard-head">
        <h1>Feedback Intelligence</h1>
        <a href="admin_dashboard.php" class="btn-exit">← Exit</a>
    </div>

    <div class="stats-grid">
        <div class="card"><p>Inflow</p><h2><?php echo $total_feedback; ?></h2></div>
        <div class="card"><p>Resolved</p><h2 style="color: var(--accent);"><?php echo $resolved_feedback; ?></h2></div>
        <div class="card"><p>Pending</p><h2 style="color: #e53e3e;"><?php echo $unresolved_feedback; ?></h2></div>
        <div class="card"><p>Close Rate</p><h2><?php echo ($total_feedback > 0) ? round(($resolved_feedback/$total_feedback)*100) : 0; ?>%</h2></div>
    </div>

    <div class="charts-container">
        <div class="chart-box">
            <h3>Submission Velocity</h3>
            <div class="chart-wrapper"><canvas id="lineChart"></canvas></div>
        </div>

        <div class="chart-box">
            <h3>Volume by Category</h3>
            <div class="chart-wrapper"><canvas id="categoryBarChart"></canvas></div>
        </div>

        <div class="chart-box">
            <h3>Resolution Progress</h3>
            <div class="chart-wrapper"><canvas id="stackedBarChart"></canvas></div>
        </div>

        <div class="chart-box">
            <h3>Success % per Category</h3>
            <div class="chart-wrapper"><canvas id="resolutionRateChart"></canvas></div>
        </div>
    </div>
</div>

<script>
    // Universal Chart Settings
    Chart.defaults.font.size = 10;
    Chart.defaults.color = '#718096';

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false, // Essential for matching screen height
        plugins: { legend: { display: false } }
    };

    // 1. Line Chart
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($timeline_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($timeline_data); ?>,
                borderColor: '#0C4F3B',
                backgroundColor: 'rgba(12, 79, 59, 0.05)',
                fill: true, tension: 0.4, borderWidth: 2, pointRadius: 1
            }]
        },
        options: chartOptions
    });

    // 2. Horizontal Bar
    new Chart(document.getElementById('categoryBarChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{ data: <?php echo json_encode($category_data); ?>, backgroundColor: '#1eb386', borderRadius: 4 }]
        },
        options: { ...chartOptions, indexAxis: 'y' }
    });

    // 3. Stacked Bar
    new Chart(document.getElementById('stackedBarChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_slice($resolution_labels, 0, 5)); ?>,
            datasets: [
                { label: 'Resolved', data: <?php echo json_encode(array_slice($res_raw_ok, 0, 5)); ?>, backgroundColor: '#0C4F3B' },
                { label: 'Pending', data: <?php echo json_encode(array_slice($res_raw_pending, 0, 5)); ?>, backgroundColor: '#edf2f7' }
            ]
        },
        options: { 
            ...chartOptions, 
            scales: { x: { stacked: true }, y: { stacked: true } },
            plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 8 } } }
        }
    });

    // 4. Rate Line
    new Chart(document.getElementById('resolutionRateChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($resolution_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($resolution_rates); ?>,
                borderColor: '#1eb386',
                borderDash: [4, 4],
                pointBackgroundColor: '#0C4F3B',
                fill: false,
                tension: 0.1
            }]
        },
        options: { 
            ...chartOptions, 
            scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } } 
        }
    });
</script>
</body>
</html>