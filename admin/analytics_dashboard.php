<?php
session_start();

// Ensure user is logged in (all roles can access)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get overall statistics
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
$resolved_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_resolved = 1")->fetch_assoc()['count'];
$unresolved_feedback = $total_feedback - $resolved_feedback;
$total_categories = $conn->query("SELECT COUNT(*) as count FROM category")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM management_user")->fetch_assoc()['count'];

// Get feedback by category
$category_sql = "SELECT c.category_name, COUNT(f.feedback_id) as count 
                 FROM category c
                 LEFT JOIN feedback f ON c.category_id = f.category_id
                 GROUP BY c.category_id, c.category_name
                 ORDER BY count DESC
                 LIMIT 10";
$category_result = $conn->query($category_sql);

$category_labels = [];
$category_data = [];
while ($row = $category_result->fetch_assoc()) {
    $category_labels[] = $row['category_name'];
    $category_data[] = $row['count'];
}

// Get feedback over time (last 30 days)
$timeline_sql = "SELECT DATE(submitted_at) as date, COUNT(*) as count 
                 FROM feedback 
                 WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY DATE(submitted_at)
                 ORDER BY date ASC";
$timeline_result = $conn->query($timeline_sql);

$timeline_labels = [];
$timeline_data = [];
while ($row = $timeline_result->fetch_assoc()) {
    $timeline_labels[] = date('M d', strtotime($row['date']));
    $timeline_data[] = $row['count'];
}

// Get sentiment analysis data (if available)
$sentiment_sql = "SELECT sentiment_label, COUNT(*) as count 
                  FROM feedback 
                  WHERE sentiment_label IS NOT NULL AND sentiment_label != ''
                  GROUP BY sentiment_label";
$sentiment_result = $conn->query($sentiment_sql);

$sentiment_labels = [];
$sentiment_data = [];

while ($row = $sentiment_result->fetch_assoc()) {
    $sentiment_labels[] = ucfirst($row['sentiment_label']);
    $sentiment_data[] = $row['count'];
}

// Get resolution rate by category
$resolution_sql = "SELECT c.category_name,
                   COUNT(f.feedback_id) as total,
                   SUM(CASE WHEN f.is_resolved = 1 THEN 1 ELSE 0 END) as resolved
                   FROM category c
                   LEFT JOIN feedback f ON c.category_id = f.category_id
                   GROUP BY c.category_id, c.category_name
                   HAVING total > 0
                   ORDER BY c.category_name";
$resolution_result = $conn->query($resolution_sql);

$resolution_labels = [];
$resolution_rates = [];
while ($row = $resolution_result->fetch_assoc()) {
    $resolution_labels[] = $row['category_name'];
    $rate = ($row['total'] > 0) ? round(($row['resolved'] / $row['total']) * 100, 1) : 0;
    $resolution_rates[] = $rate;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        
        /* Navigation */
        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .button {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-white {
            background: white;
            color: #667eea;
        }
        
        .btn-white:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .stat-card h3 {
            font-size: 36px;
            margin-bottom: 5px;
        }
        
        .stat-card.purple h3 { color: #667eea; }
        .stat-card.green h3 { color: #28a745; }
        .stat-card.orange h3 { color: #ffc107; }
        .stat-card.blue h3 { color: #17a2b8; }
        .stat-card.indigo h3 { color: #6f42c1; }
        
        .stat-card p {
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Chart Containers */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chart-container h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <h1>📊 Analytics Dashboard</h1>
                <p>Comprehensive feedback analytics and insights for <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            </div>
            <div class="header-buttons">
                <a href="../user/profile.php" class="button btn-white">👤 Profile</a>
                <a href="../user/logout.php" class="button btn-danger">🚪 Logout</a>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="nav-buttons">
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="admin_dashboard.php" class="button btn-secondary">← Back to Admin Dashboard</a>
            <a href="export_users.php" class="button btn-success">📥 Export Users</a>
            <a href="../category/export_categories.php" class="button btn-success">📥 Export Categories</a>
        <?php elseif ($_SESSION['role'] == 'hr'): ?>
            <a href="../hr/hr_dashboard.php" class="button btn-secondary">← Back to HR Dashboard</a>
        <?php else: ?>
            <a href="../user/user_dashboard.php" class="button btn-secondary">← Back to Dashboard</a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'hr'): ?>
            <a href="../feedback/export_feedback.php" class="button btn-success">📥 Export Feedback</a>
        <?php endif; ?>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card purple">
            <div class="icon">💬</div>
            <h3><?php echo $total_feedback; ?></h3>
            <p>Total Feedback</p>
        </div>
        
        <div class="stat-card green">
            <div class="icon">✅</div>
            <h3><?php echo $resolved_feedback; ?></h3>
            <p>Resolved</p>
        </div>
        
        <div class="stat-card orange">
            <div class="icon">⏳</div>
            <h3><?php echo $unresolved_feedback; ?></h3>
            <p>Unresolved</p>
        </div>
        
        <div class="stat-card blue">
            <div class="icon">📁</div>
            <h3><?php echo $total_categories; ?></h3>
            <p>Categories</p>
        </div>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <div class="stat-card indigo">
            <div class="icon">👥</div>
            <h3><?php echo $total_users; ?></h3>
            <p>Users</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Charts Grid -->
    <div class="charts-grid">
        <!-- Feedback by Category -->
        <div class="chart-container">
            <h3>📊 Feedback by Category</h3>
            <div class="chart-wrapper">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
        
        <!-- Status Distribution -->
        <div class="chart-container">
            <h3>📈 Status Distribution</h3>
            <div class="chart-wrapper">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        
        <!-- Feedback Timeline -->
        <div class="chart-container full-width">
            <h3>📅 Feedback Timeline (Last 30 Days)</h3>
            <div class="chart-wrapper">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>
        
        <?php if (count($sentiment_data) > 0): ?>
        <!-- Sentiment Analysis -->
        <div class="chart-container">
            <h3>😊 Sentiment Analysis</h3>
            <div class="chart-wrapper">
                <canvas id="sentimentChart"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Resolution Rate by Category -->
        <div class="chart-container <?php echo count($sentiment_data) > 0 ? '' : 'full-width'; ?>">
            <h3>🎯 Resolution Rate by Category</h3>
            <div class="chart-wrapper">
                <canvas id="resolutionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    // Chart.js Global Configuration
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.color = '#666';
    
    // 1. Feedback by Category (Bar Chart)
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{
                label: 'Feedback Count',
                data: <?php echo json_encode($category_data); ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // 2. Status Distribution (Doughnut Chart)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Resolved', 'Unresolved'],
            datasets: [{
                data: [<?php echo $resolved_feedback; ?>, <?php echo $unresolved_feedback; ?>],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // 3. Feedback Timeline (Line Chart)
    const timelineCtx = document.getElementById('timelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($timeline_labels); ?>,
            datasets: [{
                label: 'Feedback Submitted',
                data: <?php echo json_encode($timeline_data); ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    <?php if (count($sentiment_data) > 0): ?>
    // 4. Sentiment Analysis (Pie Chart)
    const sentimentCtx = document.getElementById('sentimentChart').getContext('2d');
    new Chart(sentimentCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($sentiment_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($sentiment_data); ?>,
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    <?php endif; ?>
    
    // 5. Resolution Rate by Category (Horizontal Bar Chart)
    const resolutionCtx = document.getElementById('resolutionChart').getContext('2d');
    new Chart(resolutionCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($resolution_labels); ?>,
            datasets: [{
                label: 'Resolution Rate (%)',
                data: <?php echo json_encode($resolution_rates); ?>,
                backgroundColor: 'rgba(23, 162, 184, 0.8)',
                borderColor: 'rgba(23, 162, 184, 1)',
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>