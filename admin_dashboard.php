<?php

session_start();

// Ensure only Admin can access the Admin dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build SQL query with filters
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name, 
               f.is_resolved, f.priority, f.sentiment_label,
        (SELECT GROUP_CONCAT(t.tag_name SEPARATOR ', ') 
         FROM feedback_tags ft 
         JOIN tags t ON ft.tag_id = t.tag_id 
         WHERE ft.feedback_id = f.feedback_id) as tags,
        (SELECT COUNT(*) FROM feedback_comments WHERE feedback_id = f.feedback_id) as comment_count
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id
        WHERE 1=1";

// Add category filter
if (!empty($category_filter)) {
    $sql .= " AND f.category_id = " . intval($category_filter);
}

// Add status filter
if ($status_filter !== '') {
    if ($status_filter == '1') {
        $sql .= " AND f.is_resolved = 1";
    } elseif ($status_filter == '0') {
        $sql .= " AND f.is_resolved = 0";
    }
}

// Add priority filter
if (!empty($priority_filter)) {
    $sql .= " AND f.priority = '" . $conn->real_escape_string($priority_filter) . "'";
}

// Add search filter
if (!empty($search_query)) {
    $search_query_escaped = $conn->real_escape_string($search_query);
    $sql .= " AND (f.feedback_text LIKE '%$search_query_escaped%' OR c.category_name LIKE '%$search_query_escaped%')";
}

// Add date range filter
if (!empty($date_from)) {
    $sql .= " AND DATE(f.submitted_at) >= '" . $conn->real_escape_string($date_from) . "'";
}
if (!empty($date_to)) {
    $sql .= " AND DATE(f.submitted_at) <= '" . $conn->real_escape_string($date_to) . "'";
}

$sql .= " ORDER BY 
          CASE f.priority 
            WHEN 'critical' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
          END,
          f.submitted_at DESC";

$result = $conn->query($sql);

// Fetch all categories for filter dropdown
$categories_sql = "SELECT * FROM category ORDER BY category_name";
$categories_result = $conn->query($categories_sql);

// Get statistics
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
$resolved_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_resolved = 1")->fetch_assoc()['count'];
$unresolved_feedback = $total_feedback - $resolved_feedback;
$critical_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE priority = 'critical' AND is_resolved = 0")->fetch_assoc()['count'];
$high_priority = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE priority = 'high' AND is_resolved = 0")->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_dashboard.css">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item active">Dashboard</a>
            <a href="manage_users.php" class="nav-item">Manage Users</a>
            <a href="../category/manage_category.php" class="nav-item">Manage Categories</a>
            <a href="manage_tags.php" class="nav-item">Manage Tags</a>
            <a href="analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
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

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Welcome, Admin <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="header-buttons">
                <a href="../user/profile.php" class="btn-header">Profile</a>
                <a href="../user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <!-- Messages -->
            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="message success">✓ ' . htmlspecialchars($_SESSION['success']) . '</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="message error">✗ ' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3><?php echo $total_feedback; ?></h3>
                    <p>Total Feedback</p>
                </div>
                <div class="stat-card resolved">
                    <h3><?php echo $resolved_feedback; ?></h3>
                    <p>Resolved</p>
                </div>
                <div class="stat-card unresolved">
                    <h3><?php echo $unresolved_feedback; ?></h3>
                    <p>Unresolved</p>
                </div>
                <div class="stat-card critical">
                    <h3><?php echo $critical_feedback; ?></h3>
                    <p>Critical Priority</p>
                </div>
                <div class="stat-card high">
                    <h3><?php echo $high_priority; ?></h3>
                    <p>High Priority</p>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-container">
                <h3>Filter & Search Feedback</h3>
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" placeholder="Search feedback..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">All Categories</option>
                                <?php
                                $categories_result->data_seek(0);
                                while ($cat = $categories_result->fetch_assoc()) {
                                    $selected = ($category_filter == $cat['category_id']) ? 'selected' : '';
                                    echo "<option value='" . $cat['category_id'] . "' $selected>" . htmlspecialchars($cat['category_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="0" <?php echo ($status_filter === '0') ? 'selected' : ''; ?>>Unresolved</option>
                                <option value="1" <?php echo ($status_filter === '1') ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                <option value="">All Priorities</option>
                                <option value="critical" <?php echo ($priority_filter == 'critical') ? 'selected' : ''; ?>>Critical</option>
                                <option value="high" <?php echo ($priority_filter == 'high') ? 'selected' : ''; ?>>High</option>
                                <option value="medium" <?php echo ($priority_filter == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="low" <?php echo ($priority_filter == 'low') ? 'selected' : ''; ?>>Low</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">From Date</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">To Date</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="button btn-primary">Apply Filters</button>
                        <a href="admin_dashboard.php" class="button btn-success">Clear Filters</a>
                    </div>
                </form>
            </div>

            <!-- Feedback Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Feedback Management</h2>
                    <span class="results-count"><?php echo $result->num_rows; ?> results found</span>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Priority</th>
                                <th>Category</th>
                                <th>Sentiment</th>
                                <th>Feedback</th>
                                <th>Tags</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $row_class = '';
                                if (!$row['is_resolved']) {
                                    $row_class .= 'unresolved ';
                                }
                                if ($row['priority'] == 'critical') {
                                    $row_class .= 'priority-critical ';
                                } elseif ($row['priority'] == 'high') {
                                    $row_class .= 'priority-high ';
                                }
                            ?>
                                <tr class="<?php echo trim($row_class); ?>">
                                    <!-- ID Column -->
                                    <td><strong>#<?php echo htmlspecialchars($row['feedback_id']); ?></strong></td>
                                    
                                    <!-- Priority Column -->
                                    <td>
                                        <span class="priority-badge priority-<?php echo $row['priority'] ?? 'medium'; ?>">
                                            <span class="priority-dot"></span>
                                            <?php echo strtoupper($row['priority'] ?? 'medium'); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Category Column -->
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    
                                    <!-- Sentiment Column -->
                                    <td>
                                        <?php 
                                        $sentiment = $row['sentiment_label'] ?? 'Pending';
                                        $sentiment_class = strtolower($sentiment);
                                        ?>
                                        <span class="sentiment-badge sentiment-<?php echo $sentiment_class; ?>">
                                            <?php echo htmlspecialchars($sentiment); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Feedback Text Column -->
                                    <td><?php echo htmlspecialchars(substr($row['feedback_text'], 0, 80)) . (strlen($row['feedback_text']) > 80 ? '...' : ''); ?></td>
                                    
                                    <!-- Tags Column -->
                                    <td>
                                        <?php 
                                        if (!empty($row['tags'])) {
                                            $tags = explode(', ', $row['tags']);
                                            foreach (array_slice($tags, 0, 2) as $tag) {
                                                echo '<span class="tag-mini">' . htmlspecialchars($tag) . '</span>';
                                            }
                                            if (count($tags) > 2) {
                                                echo '<span class="tag-mini">+' . (count($tags) - 2) . '</span>';
                                            }
                                        } else {
                                            echo '<span style="color: #6c757d; font-size: 12px;">No tags</span>';
                                        }
                                        ?>
                                    </td>
                                    
                                    <!-- Submitted Date Column -->
                                    <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                                    
                                    <!-- Status Column -->
                                    <td>
                                        <?php if ($row['is_resolved']): ?>
                                            <span class="badge badge-resolved">Resolved</span>
                                        <?php else: ?>
                                            <span class="badge badge-unresolved">Unresolved</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Actions Column -->
                                    <td>
                                        <a href="../feedback/view_feedback.php?id=<?php echo $row['feedback_id']; ?>" class="action-link">
                                            View
                                            <?php if ($row['comment_count'] > 0): ?>
                                                <span style="background: var(--primary-green-dark); color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                                                    <?php echo $row['comment_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </a>
                                        <a href="../feedback/manage_feedback_tags.php?id=<?php echo $row['feedback_id']; ?>" class="action-link manage-link">Manage</a>
                                        <?php if (!$row['is_resolved']): ?>
                                            <a href="../feedback/resolve_feedback.php?id=<?php echo $row['feedback_id']; ?>" class="action-link">Resolve</a>
                                        <?php endif; ?>
                                        <a href="../feedback/delete_feedback.php?id=<?php echo $row['feedback_id']; ?>" class="action-link delete-link" onclick="return confirm('Are you sure you want to delete this feedback?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <h3>No feedback found</h3>
                        <p>Try adjusting your filters or search criteria</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include('../includes/chatbot.php'); ?>
</body>
</html>

<?php
$conn->close();
?>