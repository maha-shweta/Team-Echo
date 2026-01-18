<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Get filter parameters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query with filters
$sql = "SELECT user_id, name, email, role, created_at FROM management_user WHERE 1=1";

// Add role filter
if (!empty($role_filter)) {
    $sql .= " AND role = '" . $conn->real_escape_string($role_filter) . "'";
}

// Add search filter
if (!empty($search_query)) {
    $search_query_escaped = $conn->real_escape_string($search_query);
    $sql .= " AND (name LIKE '%$search_query_escaped%' OR email LIKE '%$search_query_escaped%')";
}

$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Anonymous Feedback System</title>
    <link rel="stylesheet" href="manage_users.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
            <a href="manage_users.php" class="nav-item active">Manage Users</a>
            <a href="../category/manage_category.php" class="nav-item">Manage Categories</a>
            <a href="manage_tags.php" class="nav-item">Manage Tags</a>
            <a href="analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
            <a href="../feedback/export_feedback.php" class="nav-item">Export Feedback</a>
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
            <!-- Page Header -->
            <div class="page-header">
                <h1>User Management</h1>
                <p>Manage system users and their roles</p>
            </div>

            <!-- Back Button -->
            <a href="admin_dashboard.php" class="button btn-back">← Back to Dashboard</a>

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

            <!-- User Controls -->
            <div class="user-controls">
                <form method="GET" action="" style="display: contents;">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Search by name or email..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                    
                    <select name="role" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo ($role_filter == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="hr" <?php echo ($role_filter == 'hr') ? 'selected' : ''; ?>>HR</option>
                        <option value="user" <?php echo ($role_filter == 'user') ? 'selected' : ''; ?>>User</option>
                    </select>
                    
                    <button type="submit" class="button btn-primary" style="padding: 10px 20px;">Search</button>
                    <a href="manage_users.php" class="button btn-success" style="padding: 10px 20px;">Clear</a>
                </form>
                
                <a href="../user/register.php" class="add-user-btn">+ Add New User</a>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>User List</h2>
                    <span class="results-count"><?php echo $result->num_rows; ?> users found</span>
                </div>

                <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($row['user_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <span class="role-<?php echo $row['role']; ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['role'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" 
                                   class="action-link edit-btn">
                                    Edit
                                </a>
                                <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" 
                                   class="action-link delete-link" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-results">
                    <h3>No users found</h3>
                    <p>Try adjusting your search or filter criteria</p>
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