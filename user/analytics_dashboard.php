<?php
session_start();

// Ensure only logged-in users can access this page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Statistics
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
$resolved_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_resolved = 1")->fetch_assoc()['count'];
$unresolved_feedback = $total_feedback - $resolved_feedback;
$total_categories = $conn->query("SELECT COUNT(*) as count FROM category")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM management_user")->fetch_assoc()['count'];

// Category Distribution
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

// Timeline Data
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

// Resolution Progress
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
$res_raw_ok = [];
$res_raw_pending = [];
while ($row = $resolution_result->fetch_assoc()) {
    $resolution_labels[] = $row['category_name'];
    $rate = ($row['total'] > 0) ? round(($row['resolved'] / $row['total']) * 100, 1) : 0;
    $resolution_rates[] = $rate;
    $res_raw_ok[] = $row['resolved'];
    $res_raw_pending[] = $row['total'] - $row['resolved'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Anonymous Feedback System</title>
    <link rel="stylesheet" href="analytics_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
   <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="user_dashboard.php" class="nav-item">Dashboard</a>
            <a href="../feedback/submit_feedback.php" class="nav-item">Submit Feedback</a>
            <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="ai_analytics_dashboard.php" class="nav-item active">Team-Echo AI</a>
            <a href="profile.php" class="nav-item">My Profile</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="header-buttons">
                <a href="../user/profile.php" class="btn-header">Profile</a>
                <a href="../user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Feedback Intelligence</h1>
                <?php
                // Dynamic back link based on role
                if ($_SESSION['role'] == 'admin') {
                    echo '<a href="admin_dashboard.php" class="btn-back">← Back to Dashboard</a>';
                } elseif ($_SESSION['role'] == 'hr') {
                    echo '<a href="../hr/hr_dashboard.php" class="btn-back">← Back to Dashboard</a>';
                } else {
                    echo '<a href="../user/user_dashboard.php" class="btn-back">← Back to Dashboard</a>';
                }
                ?>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <p>Total Inflow</p>
                    <h2><?php echo $total_feedback; ?></h2>
                </div>
                <div class="stat-card resolved">
                    <p>Resolved</p>
                    <h2><?php echo $resolved_feedback; ?></h2>
                </div>
                <div class="stat-card pending">
                    <p>Pending</p>
                    <h2><?php echo $unresolved_feedback; ?></h2>
                </div>
                <div class="stat-card rate">
                    <p>Close Rate</p>
                    <h2><?php echo ($total_feedback > 0) ? round(($resolved_feedback/$total_feedback)*100) : 0; ?>%</h2>
                </div>
            </div>

            <!-- Charts Container -->
            <div class="charts-container">
                <!-- Submission Timeline -->
                <div class="chart-box">
                    <h3>Submission Velocity (Last 30 Days)</h3>
                    <div class="chart-wrapper">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>

                <!-- Category Distribution -->
                <div class="chart-box">
                    <h3>Volume by Category</h3>
                    <div class="chart-wrapper">
                        <canvas id="categoryBarChart"></canvas>
                    </div>
                </div>

                <!-- Resolution Progress -->
                <div class="chart-box">
                    <h3>Resolution Progress by Category</h3>
                    <div class="chart-wrapper">
                        <canvas id="stackedBarChart"></canvas>
                    </div>
                </div>

                <!-- Resolution Rate -->
                <div class="chart-box">
                    <h3>Success Rate per Category</h3>
                    <div class="chart-wrapper">
                        <canvas id="resolutionRateChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Universal Chart Settings
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#6c757d';

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    };

    // 1. Submission Timeline - Line Chart
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($timeline_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($timeline_data); ?>,
                borderColor: '#0c4f3b',
                backgroundColor: 'rgba(12, 79, 59, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#0c4f3b',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // 2. Category Distribution - Horizontal Bar Chart
    new Chart(document.getElementById('categoryBarChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($category_data); ?>,
                backgroundColor: '#32b25e',
                borderRadius: 4
            }]
        },
        options: {
            ...chartOptions,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // 3. Resolution Progress - Stacked Bar Chart
    new Chart(document.getElementById('stackedBarChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_slice($resolution_labels, 0, 8)); ?>,
            datasets: [
                {
                    label: 'Resolved',
                    data: <?php echo json_encode(array_slice($res_raw_ok, 0, 8)); ?>,
                    backgroundColor: '#0c4f3b',
                    borderRadius: 4
                },
                {
                    label: 'Pending',
                    data: <?php echo json_encode(array_slice($res_raw_pending, 0, 8)); ?>,
                    backgroundColor: '#e1e8ed',
                    borderRadius: 4
                }
            ]
        },
        options: {
            ...chartOptions,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // 4. Resolution Rate - Line Chart
    new Chart(document.getElementById('resolutionRateChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($resolution_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($resolution_rates); ?>,
                borderColor: '#32b25e',
                borderDash: [5, 5],
                pointBackgroundColor: '#0c4f3b',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: false,
                tension: 0.1,
                borderWidth: 2
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: {
                    min: 0,
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