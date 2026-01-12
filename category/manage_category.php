<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get filter/search parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query with search
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM feedback WHERE category_id = c.category_id) as feedback_count
        FROM category c
        WHERE 1=1";

// Add search filter
if (!empty($search_query)) {
    $search_query = $conn->real_escape_string($search_query);
    $sql .= " AND c.category_name LIKE '%$search_query%'";
}

$sql .= " ORDER BY c.created_at DESC";
$result = $conn->query($sql);

// Get total statistics
$total_categories = $conn->query("SELECT COUNT(*) as count FROM category")->fetch_assoc()['count'];
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];

// Get username safely
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="manage_category.css">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="../admin/admin_dashboard.php" class="nav-item">Dashboard</a>
            <a href="manage_category.php" class="nav-item active">Categories</a>
            <a href="../admin/manage_tags.php" class="nav-item">Manage Tags</a>
            <a href="../admin/manage_users.php" class="nav-item">Users</a>
            
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Header -->
        <header class="top-header">
            <div class="welcome-text">
                Welcome, <?php echo htmlspecialchars($username); ?>
            </div>
            <div class="header-buttons">
                <a href="add_category.php" class="btn-header">+ Add New Category</a>
                <a href="../user/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Category Management</h1>
                <p>Manage feedback categories for your anonymous feedback system</p>
            </div>

            <!-- Messages -->
            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="message success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="icon">📁</div>
                    <h3><?php echo $total_categories; ?></h3>
                    <p>Total Categories</p>
                </div>
                <div class="stat-card">
                    <div class="icon">💬</div>
                    <h3><?php echo $total_feedback; ?></h3>
                    <p>Total Feedback</p>
                </div>
                <div class="stat-card">
                    <div class="icon">📊</div>
                    <h3><?php echo $result->num_rows; ?></h3>
                    <p>Search Results</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-container">
                <form method="GET" action="" class="search-form">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Search categories..." 
                        value="<?php echo htmlspecialchars($search_query); ?>"
                    >
                    <button type="submit" class="search-btn">Search</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="manage_category.php" class="clear-search">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Category List</h2>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Feedback Count</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($row['category_id']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['category_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo $row['feedback_count']; ?> feedback(s)
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_category.php?id=<?php echo $row['category_id']; ?>" class="action-link">
                                            Edit
                                        </a>
                                        <a 
                                            href="delete_category.php?id=<?php echo $row['category_id']; ?>" 
                                            class="action-link delete-link"
                                            onclick="return confirm('Are you sure you want to delete this category?\n\nWarning: This category has <?php echo $row['feedback_count']; ?> feedback(s) associated with it.');"
                                        >
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <h3>No categories found</h3>
                        <p>
                            <?php if (!empty($search_query)): ?>
                                No categories match your search "<?php echo htmlspecialchars($search_query); ?>"
                            <?php else: ?>
                                Start by adding your first category
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>