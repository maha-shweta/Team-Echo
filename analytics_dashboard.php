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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="analytics_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">Feedback System</div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
            <a href="manage_users.php" class="nav-item">Manage Users</a>
            <a href="../category/manage_category.php" class="nav-item">Manage Categories</a>
            <a href="manage_tags.php" class="nav-item">Manage Tags</a>
            <a href="analytics_dashboard.php" class="nav-item active">Analytics</a>
            <a href="../admin/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
             <a href="../feedback/export_feedback.php<?php 
                $params = [];
                if (!empty($category_filter)) $params[] = 'category=' . $category_filter;
                if ($status_filter !== '') $params[] = 'status=' . $status_filter;
                if (!empty($priority_filter)) $params[] = 'priority=' . $priority_filter;
                if (!empty($search_query)) $params[] = 'search=' . urlencode($search_query);
                if (!empty($date_from)) $params[] = 'date_from=' . $date_from;
                if (!empty($date_to)) $params[] = 'date_to=' . $date_to;
                echo !empty($params) ? '?' . implode('&', $params) : '';
            ?>" class="nav-item">Export Feedback</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="topbar-actions">
                <a href="../user/profile.php" class="topbar-btn">Profile</a>
                <a href="../user/logout.php" class="topbar-btn">Logout</a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            <div class="analytics-header">
                <h1>Feedback Intelligence</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <p>Inflow</p>
                    <h2><?php echo $total_feedback; ?></h2>
                </div>
                <div class="stat-card">
                    <p>Resolved</p>
                    <h2 class="resolved"><?php echo $resolved_feedback; ?></h2>
                </div>
                <div class="stat-card">
                    <p>Pending</p>
                    <h2 class="pending"><?php echo $unresolved_feedback; ?></h2>
                </div>
                <div class="stat-card">
                    <p>Close Rate</p>
                    <h2><?php echo ($total_feedback > 0) ? round(($resolved_feedback/$total_feedback)*100) : 0; ?>%</h2>
                </div>
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
        </main>
    </div>
</div>

<script>
    // Universal Chart Settings
    Chart.defaults.font.size = 10;
    Chart.defaults.color = '#718096';

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
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