<?php
session_start();

// Ensure only logged-in users can access this page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'all'; // 'all' or 'my'
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Check if feedback table has user_id column
$columns_check = $conn->query("SHOW COLUMNS FROM feedback LIKE 'user_id'");
$has_user_id = ($columns_check->num_rows > 0);

// Build SQL query with filters (all anonymous feedback with tags and priority)
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name, f.is_resolved, f.priority,
        (SELECT GROUP_CONCAT(t.tag_name SEPARATOR ', ') FROM feedback_tags ft JOIN tags t ON ft.tag_id = t.tag_id WHERE ft.feedback_id = f.feedback_id) as tags,
        (SELECT GROUP_CONCAT(t.tag_color SEPARATOR ',') FROM feedback_tags ft JOIN tags t ON ft.tag_id = t.tag_id WHERE ft.feedback_id = f.feedback_id) as tag_colors,
        (SELECT COUNT(*) FROM feedback_comments WHERE feedback_id = f.feedback_id AND is_internal = 0) as comment_count";

if ($has_user_id) {
    $sql .= ", f.user_id";
}

$sql .= " FROM feedback f JOIN category c ON f.category_id = c.category_id WHERE f.is_anonymous = 1";

// Add view mode filter (show only user's feedback or all)
if ($view_mode == 'my' && $has_user_id) {
    $sql .= " AND f.user_id = " . intval($_SESSION['user_id']);
}

// Add category filter
if (!empty($category_filter)) {
    $sql .= " AND f.category_id = " . intval($category_filter);
}

// Add priority filter
if (!empty($priority_filter)) {
    $sql .= " AND f.priority = '" . $conn->real_escape_string($priority_filter) . "'";
}

// Add status filter
if ($status_filter !== '') {
    if ($status_filter == '1') {
        $sql .= " AND f.is_resolved = 1";
    } elseif ($status_filter == '0') {
        $sql .= " AND f.is_resolved = 0";
    }
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

$sql .= " ORDER BY f.submitted_at DESC";
$result = $conn->query($sql);

// Fetch all categories for filter dropdown
$categories_sql = "SELECT * FROM category ORDER BY category_name";
$categories_result = $conn->query($categories_sql);

// Get statistics
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_anonymous = 1")->fetch_assoc()['count'];

if ($has_user_id) {
    $my_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_anonymous = 1 AND user_id = " . intval($_SESSION['user_id']))->fetch_assoc()['count'];
    $my_resolved = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_anonymous = 1 AND user_id = " . intval($_SESSION['user_id']) . " AND is_resolved = 1")->fetch_assoc()['count'];
    $my_unresolved = $my_feedback - $my_resolved;
} else {
    $my_feedback = 0;
    $my_resolved = 0;
    $my_unresolved = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Anonymous Feedback System</title>
    <link rel="stylesheet" href="user_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="user_dashboard.php" class="nav-item active">Dashboard</a>
            <a href="../feedback/submit_feedback.php" class="nav-item">Submit Feedback</a>
            <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="profile.php" class="nav-item">My Profile</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="header-buttons">
                <a href="profile.php" class="btn-header">Profile</a>
                <a href="logout.php" class="logout-btn">Logout</a>
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

            <!-- Quick Action Button -->
            <div style="margin-bottom: 25px;">
                <a href="../feedback/submit_feedback.php" class="button btn-success" style="font-size: 15px; padding: 12px 25px;">
                    Submit New Feedback
                </a>
            </div>

            <!-- Statistics Section -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3><?php echo $total_feedback; ?></h3>
                    <p>Total Community Feedback</p>
                </div>
                <div class="stat-card my">
                    <h3><?php echo $my_feedback; ?></h3>
                    <p>My Submissions</p>
                </div>
                <div class="stat-card resolved">
                    <h3><?php echo $my_resolved; ?></h3>
                    <p>My Resolved Feedback</p>
                </div>
                <div class="stat-card unresolved">
                    <h3><?php echo $my_unresolved; ?></h3>
                    <p>My Pending Feedback</p>
                </div>
            </div>

            <!-- View Toggle -->
            <div class="view-toggle">
                <h3>Viewing Mode</h3>
                <div class="toggle-buttons">
                    <?php
                    // Build query parameters for maintaining filters
                    $params = [];
                    if (!empty($category_filter)) $params[] = 'category=' . $category_filter;
                    if ($status_filter !== '') $params[] = 'status=' . $status_filter;
                    if (!empty($priority_filter)) $params[] = 'priority=' . $priority_filter;
                    if (!empty($search_query)) $params[] = 'search=' . urlencode($search_query);
                    if (!empty($date_from)) $params[] = 'date_from=' . $date_from;
                    if (!empty($date_to)) $params[] = 'date_to=' . $date_to;
                    $query_string = !empty($params) ? '&' . implode('&', $params) : '';
                    ?>
                    <a href="?view=all<?php echo $query_string; ?>" 
                       class="toggle-btn <?php echo ($view_mode == 'all') ? 'active' : ''; ?>">
                        All Community Feedback
                    </a>
                    <a href="?view=my<?php echo $query_string; ?>" 
                       class="toggle-btn <?php echo ($view_mode == 'my') ? 'active' : ''; ?>">
                        My Feedback Only
                    </a>
                </div>
            </div>

            <!-- Filter Container -->
            <div class="filter-container">
                <h3>Search & Filter Feedback</h3>
                <form method="GET" action="">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" 
                                   placeholder="Search feedback..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">All Categories</option>
                                <?php 
                                $categories_result->data_seek(0);
                                while ($cat = $categories_result->fetch_assoc()) { 
                                    $selected = ($category_filter == $cat['category_id']) ? 'selected' : '';
                                    echo "<option value='" . $cat['category_id'] . "' $selected>" 
                                         . htmlspecialchars($cat['category_name']) . "</option>";
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
                            <input type="date" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">To Date</label>
                            <input type="date" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="button btn-primary">Apply Filters</button>
                        <a href="user_dashboard.php?view=<?php echo $view_mode; ?>" 
                           class="button btn-success">Clear Filters</a>
                    </div>
                </form>
            </div>

            <!-- Feedback Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2><?php echo $view_mode == 'my' ? 'My Feedback' : 'Community Feedback'; ?></h2>
                    <span class="results-count"><?php echo $result->num_rows; ?> results found</span>
                </div>

                <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Priority</th>
                            <th>Category</th>
                            <th>Feedback</th>
                            <th>Tags</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 
                            $is_mine = $has_user_id && isset($row['user_id']) && ($row['user_id'] == $_SESSION['user_id']);
                            $row_class = '';
                            if ($is_mine) {
                                $row_class .= 'my-feedback ';
                            }
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
                            <td>
                                <strong>#<?php echo htmlspecialchars($row['feedback_id']); ?></strong>
                                <?php if ($is_mine): ?>
                                    <span class="badge badge-mine" title="Your feedback">You</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?php echo $row['priority'] ?? 'medium'; ?>">
                                    <span class="priority-dot"></span>
                                    <?php echo strtoupper($row['priority'] ?? 'medium'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td>
                                <?php 
                                echo htmlspecialchars(substr($row['feedback_text'], 0, 80)) 
                                     . (strlen($row['feedback_text']) > 80 ? '...' : ''); 
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($row['tags'])) {
                                    $tags = explode(', ', $row['tags']);
                                    $colors = explode(',', $row['tag_colors']);
                                    for ($i = 0; $i < min(count($tags), 2); $i++) {
                                        $color = isset($colors[$i]) ? $colors[$i] : '#667eea';
                                        echo '<span class="tag-mini" style="background-color: ' 
                                             . htmlspecialchars($color) . '; color: white;">' 
                                             . htmlspecialchars($tags[$i]) . '</span>';
                                    }
                                    if (count($tags) > 2) {
                                        echo '<span class="tag-mini" style="background-color: #6c757d; color: white;">+' 
                                             . (count($tags) - 2) . '</span>';
                                    }
                                } else {
                                    echo '<span style="color: #6c757d; font-size: 12px;">No tags</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                            <td>
                                <?php if ($row['is_resolved']): ?>
                                    <span class="badge badge-resolved">Resolved</span>
                                <?php else: ?>
                                    <span class="badge badge-unresolved">Unresolved</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../feedback/view_feedback.php?id=<?php echo $row['feedback_id']; ?>" 
                                   class="action-link">
                                    View
                                    <?php if ($row['comment_count'] > 0): ?>
                                        <span style="background: var(--primary-green-dark); color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                                            <?php echo $row['comment_count']; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <?php if ($view_mode == 'my' && $is_mine && !$row['is_resolved']): ?>
                                    <a href="../feedback/edit_feedback.php?id=<?php echo $row['feedback_id']; ?>" 
                                       class="action-link">
                                        Edit
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-results">
                    <h3>No feedback found</h3>
                    <p>
                        <?php 
                        echo $view_mode == 'my' 
                            ? 'You haven\'t submitted any feedback yet.' 
                            : 'Try adjusting your filters or be the first to submit feedback!'; 
                        ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>
