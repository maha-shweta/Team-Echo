<?php
session_start();
require_once('../config.php');

// Ensure only logged-in users can access this page
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/user/login.php');
    exit;
}

include('../db/db.php'); 

if (!$conn) { 
    die("Database connection failed."); 
}

// --- 1. FILTER LOGIC ---
$filter_cat = $_GET['category'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$where_clauses = ["1=1"];

if (!empty($start_date)) $where_clauses[] = "f.submitted_at >= '" . $conn->real_escape_string($start_date) . " 00:00:00'";
if (!empty($end_date)) $where_clauses[] = "f.submitted_at <= '" . $conn->real_escape_string($end_date) . " 23:59:59'";
if (!empty($filter_cat)) $where_clauses[] = "f.category_id = " . intval($filter_cat);

$where_sql = implode(" AND ", $where_clauses);

// --- 2. TREND & ALERT CALCULATIONS ---
$current_week = $conn->query("SELECT COUNT(*) as total FROM feedback f WHERE f.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND $where_sql")->fetch_assoc()['total'];
$prev_week = $conn->query("SELECT COUNT(*) as total FROM feedback f WHERE f.submitted_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND f.submitted_at < DATE_SUB(NOW(), INTERVAL 7 DAY) AND $where_sql")->fetch_assoc()['total'];

$diff = $current_week - $prev_week;
$percent_change = ($prev_week > 0) ? round(($diff / $prev_week) * 100) : ($current_week * 100);

// Thresholds
$alert_threshold_volume = 50; 
$volume_alert = ($percent_change >= $alert_threshold_volume);

// --- 3. CHART DATA ---
$total_mentions = $conn->query("SELECT COUNT(*) as total FROM feedback f WHERE $where_sql")->fetch_assoc()['total'];
$neg_data = $conn->query("SELECT COUNT(*) as count FROM feedback f WHERE $where_sql AND sentiment_label = 'Negative'")->fetch_assoc()['count'];
$neg_percent = $total_mentions > 0 ? round(($neg_data / $total_mentions) * 100) : 0;
$sentiment_alert = ($neg_percent >= 25);

// Chart Arrays
$s_res = $conn->query("SELECT sentiment_label, COUNT(*) as count FROM feedback f WHERE $where_sql GROUP BY sentiment_label");
$s_labels = []; 
$s_counts = [];
while($r = $s_res->fetch_assoc()){ 
    $s_labels[] = $r['sentiment_label']; 
    $s_counts[] = $r['count']; 
}

$line_res = $conn->query("SELECT DATE(f.submitted_at) as date, COUNT(*) as count FROM feedback f WHERE $where_sql GROUP BY DATE(f.submitted_at) ORDER BY date ASC LIMIT 7");
$l_labels = []; 
$l_values = [];
while($r = $line_res->fetch_assoc()){ 
    $l_labels[] = date("M d", strtotime($r['date'])); 
    $l_values[] = $r['count']; 
}

// --- 4. TRENDING TOPICS / WORD CLOUD DATA FROM FEEDBACK TEXT ---
function extractKeywords($text) {
    $stopWords = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'as', 'are', 'was', 'were', 
                  'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
                  'could', 'should', 'may', 'might', 'can', 'to', 'of', 'in', 'for', 'with',
                  'and', 'or', 'but', 'not', 'this', 'that', 'by', 'from', 'it', 'its'];
    
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text);
    
    $keywords = array_filter($words, function($word) use ($stopWords) {
        return strlen($word) > 3 && !in_array($word, $stopWords);
    });
    
    return $keywords;
}

$trending_query = "SELECT feedback_text FROM feedback f WHERE $where_sql";
$trending_result = $conn->query($trending_query);

$all_keywords = [];
while($row = $trending_result->fetch_assoc()) {
    $keywords = extractKeywords($row['feedback_text']);
    foreach($keywords as $word) {
        if(isset($all_keywords[$word])) {
            $all_keywords[$word]++;
        } else {
            $all_keywords[$word] = 1;
        }
    }
}

arsort($all_keywords);
$trending_topics = array_slice($all_keywords, 0, 20, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Analytics Dashboard - Anonymous Feedback System</title>
    <link rel="stylesheet" href="ai_analytics_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="<?php echo BASE_URL; ?>/hr/hr_dashboard.php" class="nav-item">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/hr/analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="<?php echo BASE_URL; ?>/hr/ai_analytics_dashboard.php" class="nav-item active">Team-Echo AI</a>
            <a href="<?php echo BASE_URL; ?>/feedback/export_feedback.php" class="nav-item">Export Data</a>
            <a href="<?php echo BASE_URL; ?>/user/profile.php" class="nav-item">Profile</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="header-buttons">
                <a href="<?php echo BASE_URL; ?>/user/profile.php" class="btn-header">Profile</a>
                <a href="<?php echo BASE_URL; ?>/user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <!-- Page Header with Filters -->
            <div class="page-header">
                <h1><i class="fa fa-robot"></i> Team-Echo AI Analytics</h1>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="<?php echo BASE_URL; ?>/hr/hr_dashboard.php" class="btn-back">← Back to Dashboard</a>
                </div>
            </div>

            <!-- Filter Form -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
                <form class="filter-form" method="GET">
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" placeholder="Start Date">
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" placeholder="End Date">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php 
                        $cats = $conn->query("SELECT * FROM category ORDER BY category_name");
                        while($c = $cats->fetch_assoc()) {
                            $sel = ($filter_cat == $c['category_id']) ? 'selected' : '';
                            echo "<option value='{$c['category_id']}' $sel>" . htmlspecialchars($c['category_name']) . "</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="filter-btn"><i class="fa fa-filter"></i> Apply Filters</button>
                    <a href="<?php echo BASE_URL; ?>/hr/ai_analytics_dashboard.php" class="reset-link"><i class="fa fa-redo"></i> Reset</a>
                </form>
            </div>

            <!-- Dashboard Layout -->
            <div class="dashboard-layout">
                <!-- Sidebar Metrics -->
                <div class="sidebar-metrics">
                    <div class="metric-box">
                        <div class="metric-label">System Health</div>
                        <div class="metric-value" style="font-size: 16px; color: <?php echo ($volume_alert || $sentiment_alert) ? '#e74c3c' : '#2ecc71'; ?>;">
                            <i class="fa <?php echo ($volume_alert || $sentiment_alert) ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                            <?php echo ($volume_alert || $sentiment_alert) ? 'Attention Needed' : 'All Stable'; ?>
                        </div>
                    </div>

                    <div class="metric-box">
                        <div class="metric-label">Total Mentions</div>
                        <div class="metric-value">
                            <?php echo number_format($total_mentions); ?>
                            <span class="trend-badge" style="color:<?php echo ($diff >= 0) ? '#2ecc71' : '#e74c3c'; ?>;">
                                <i class="fa <?php echo ($diff >= 0) ? 'fa-caret-up' : 'fa-caret-down'; ?>"></i> 
                                <?php echo abs($percent_change); ?>%
                            </span>
                        </div>
                    </div>

                    <div class="metric-box">
                        <div class="metric-label">Department Heatmap</div>
                        <?php
                        $h_res = $conn->query("SELECT c.category_name, AVG(f.sentiment_score) as avg FROM feedback f JOIN category c ON f.category_id = c.category_id WHERE $where_sql GROUP BY c.category_id");
                        if($h_res && $h_res->num_rows > 0):
                            while($h = $h_res->fetch_assoc()):
                                $avg_score = $h['avg'] ?? 0;
                                $bg = ($avg_score < -0.1) ? '#f8d7da' : (($avg_score > 0.1) ? '#d4edda' : '#fff');
                            ?>
                            <div class="heatmap-item" style="background:<?php echo $bg; ?>;">
                                <?php echo htmlspecialchars($h['category_name']); ?> 
                                <strong><?php echo round($avg_score, 1); ?></strong>
                            </div>
                            <?php endwhile;
                        else: ?>
                            <div class="no-data-message">No data available</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Charts -->
                <div class="main-charts">
                    <!-- Sentiment Chart -->
                    <div class="card <?php echo $sentiment_alert ? 'alert-active' : ''; ?>">
                        <div class="card-title">Sentiment Distribution</div>
                        <div class="chart-container">
                            <canvas id="sentimentChart"></canvas>
                        </div>
                    </div>

                    <!-- Volume Trends -->
                    <div class="card <?php echo $volume_alert ? 'alert-active' : ''; ?>">
                        <div class="card-title">
                            <span>Volume Trends (Last 7 Days)</span>
                            <div class="card-summary">
                                <span class="summary-value"><?php echo $current_week; ?></span>
                                <span class="summary-trend" style="color:<?php echo ($diff >= 0) ? '#2ecc71' : '#e74c3c'; ?>;">
                                    <i class="fa <?php echo ($diff >= 0) ? 'fa-caret-up' : 'fa-caret-down'; ?>"></i> 
                                    <?php echo abs($percent_change); ?>%
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>

                    <!-- Trending Topics Word Cloud -->
                    <div class="card word-cloud-wide">
                        <div class="card-title">Trending Topics (from Feedback Text)</div>
                        <div class="word-cloud-container">
                            <?php 
                            if(!empty($trending_topics)) {
                                $max_count = max($trending_topics);
                                $min_count = min($trending_topics);
                                
                                foreach($trending_topics as $word => $count) {
                                    $size = 12 + (($count - $min_count) / max(1, ($max_count - $min_count))) * 16;
                                    echo "<span class='word-item' style='font-size: {$size}px;' title='Mentioned $count times'>" . 
                                         htmlspecialchars($word) . " <small>($count)</small></span>";
                                }
                            } else {
                                echo "<div class='no-data-message'>No feedback data available for trending topics</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Chart.js Configuration
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#6c757d';

    // Sentiment Doughnut Chart
    new Chart(document.getElementById('sentimentChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($s_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($s_counts); ?>,
                backgroundColor: ['#2ecc71', '#e74c3c', '#f1c40f'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '75%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15
                    }
                }
            }
        },
        plugins: [{
            id: 'centerText',
            afterDraw: (chart) => {
                const { ctx, chartArea: { left, top, width, height } } = chart;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.font = 'bold 24px Inter';
                ctx.fillStyle = '#0c4f3b';
                ctx.fillText('<?php echo $total_mentions; ?>', left + width / 2, top + height / 2);
                ctx.font = 'bold 12px Inter';
                ctx.fillStyle = '<?php echo $sentiment_alert ? "#e74c3c" : "#6c757d"; ?>';
                ctx.fillText('NEG: <?php echo $neg_percent; ?>%', left + width / 2, top + height / 2 + 25);
                ctx.restore();
            }
        }]
    });

    // Volume Trends Line Chart
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($l_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($l_values); ?>,
                borderColor: '#0c4f3b',
                backgroundColor: 'rgba(12, 79, 59, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: '#0c4f3b',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
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
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

</body>
</html>
<?php $conn->close(); ?>