<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

// Check if category_id is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid category ID.";
    header('Location: manage_category.php');
    exit;
}

$category_id = intval($_GET['id']);

// Fetch category details
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM feedback WHERE category_id = c.category_id) as feedback_count
        FROM category c 
        WHERE c.category_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $category = $result->fetch_assoc();
} else {
    $_SESSION['error'] = "Category not found.";
    header('Location: manage_category.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = trim($_POST['category_name']);

    // Validate input
    if (empty($category_name)) {
        $error_message = "Category name is required.";
    } elseif (strlen($category_name) < 3) {
        $error_message = "Category name must be at least 3 characters long.";
    } elseif (strlen($category_name) > 100) {
        $error_message = "Category name must not exceed 100 characters.";
    } else {
        // Check if category name already exists (except for current category)
        $check_sql = "SELECT category_id FROM category WHERE category_name = ? AND category_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $category_name, $category_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Category name already exists.";
        } else {
            // Update category
            $update_sql = "UPDATE category SET category_name = ? WHERE category_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $category_name, $category_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Category updated successfully!";
                header('Location: manage_category.php');
                exit;
            } else {
                $error_message = "Error updating category: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Anonymous Feedback System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f5f7f8;
        }

        :root {
            --primary-green-dark: #0c4f3b; 
            --accent-green-light: #32b25e;
            --pending-color: #f39c12;
            --critical-color: #dc3545;
            --text-dark: #333;
            --text-muted: #6c757d;
            --border-light: #e1e8ed;
            --bg-white: #ffffff;
        }

        .main-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background-color: var(--bg-white);
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            padding: 20px 0;
            flex-shrink: 0;
        }

        .system-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-green-dark);
            padding: 0 20px 30px 20px;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
        }

        .nav-item {
            padding: 12px 20px;
            text-decoration: none;
            color: #555;
            font-size: 15px;
            transition: background-color 0.2s, color 0.2s;
            border-left: 5px solid transparent;
        }

        .nav-item.active {
            background-color: #f0f8ff;
            color: var(--primary-green-dark);
            font-weight: 600;
            border-left-color: var(--primary-green-dark);
        }

        .nav-item:hover:not(.active) {
            background-color: #f9f9f9;
        }

        .content-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .top-header {
            background-color: var(--primary-green-dark);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text {
            font-size: 18px;
            font-weight: 600;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .logout-btn,
        .btn-header {
            background-color: var(--primary-green-dark);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 8px 22px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.3px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.10);
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover,
        .btn-header:hover {
            background-color: #085826;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .button {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-back {
            background: white;
            color: var(--primary-green-dark);
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 2px solid var(--border-light);
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-green-dark);
        }

        .dashboard-content {
            padding: 30px;
            max-width: 100%;
            width: 100%;
        }

        .container {
            max-width: 100%;
            width: 100%;
        }

        .page-header {
            margin-bottom: 15px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-green-dark);
            margin-bottom: 8px;
        }

        .page-header p {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--critical-color);
        }

        .info-box {
            background: var(--bg-white);
            padding: 20px 25px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-green-dark);
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .info-box h4 {
            color: var(--primary-green-dark);
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e1e8ed;
            font-size: 14px;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item span:first-child {
            color: var(--text-muted);
            font-weight: 500;
        }

        .info-item span:last-child {
            color: var(--text-dark);
            font-weight: 600;
        }

        .info-item .badge {
            background: var(--primary-green-dark);
            color: white !important;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid var(--pending-color);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .warning-icon {
            font-size: 24px;
            color: var(--pending-color);
            flex-shrink: 0;
            line-height: 1;
        }

        .warning-content {
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }

        .warning-content strong {
            font-weight: 600;
        }

        .form-card {
            background: var(--bg-white);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .form-card h3 {
            color: var(--primary-green-dark);
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #495057;
            font-size: 13px;
        }

        .required {
            color: var(--critical-color);
            margin-left: 3px;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--border-light);
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-green-dark);
            box-shadow: 0 0 0 3px rgba(12, 79, 59, 0.1);
        }

        .char-count {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
            text-align: right;
        }

        .char-count span {
            font-weight: 600;
        }

        .btn-primary {
            width: 100%;
            padding: 12px 24px;
            background: var(--primary-green-dark);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background: #085826;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(12, 79, 59, 0.3);
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn-danger {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            flex: 1;
            min-width: 180px;
            background: var(--critical-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
        }

        .btn-secondary {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            flex: 1;
            min-width: 180px;
            background: var(--text-muted);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .dashboard-content {
                padding: 15px;
            }

            .top-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .header-buttons {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .form-card,
            .info-box,
            .warning-box {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-danger,
            .btn-secondary {
                min-width: 100%;
            }

            .info-item {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>

<div class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="system-title">Feedback System</div>
        <nav class="nav-menu">
            <a href="../admin/admin_dashboard.php" class="nav-item">Dashboard</a>
            <a href="../admin/manage_users.php" class="nav-item">Manage Users</a>
            <a href="manage_category.php" class="nav-item active">Manage Categories</a>
            <a href="../admin/manage_tags.php" class="nav-item">Manage Tags</a>
            <a href="../admin/analytics_dashboard.php" class="nav-item">Analytics</a>
            <a href="../dashboard/ai_analytics_dashboard.php" class="nav-item">Team-Echo AI</a>
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
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>Edit Category</h1>
                    <p>Update category information</p>
                </div>

                <!-- Back Button -->
                <a href="manage_category.php" class="button btn-back">← Back to Manage Categories</a>

                <!-- Messages -->
                <?php if (isset($error_message)): ?>
                    <div class="message error">✗ <?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <!-- Category Information -->
                <div class="info-box">
                    <h4>Category Information</h4>
                    <div class="info-item">
                        <span>Category ID:</span>
                        <span>#<?php echo htmlspecialchars($category['category_id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span>Current Name:</span>
                        <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span>Feedback Count:</span>
                        <span class="badge"><?php echo $category['feedback_count']; ?> feedback(s)</span>
                    </div>
                    <div class="info-item">
                        <span>Created At:</span>
                        <span><?php echo date('M d, Y', strtotime($category['created_at'])); ?></span>
                    </div>
                </div>
                
                <!-- Warning if category has feedback -->
                <?php if ($category['feedback_count'] > 0): ?>
                <div class="warning-box">
                    <div class="warning-icon">⚠</div>
                    <div class="warning-content">
                        <strong>Warning:</strong> This category has <strong><?php echo $category['feedback_count']; ?></strong> feedback(s) associated with it. Changing the name will affect all related feedback entries.
                    </div>
                </div>
                <?php endif; ?>

                <!-- Edit Form -->
                <div class="form-card">
                    <h3>Update Category Details</h3>
                    <form method="POST" action="" id="categoryForm">
                        <div class="form-group">
                            <label for="category_name">Category Name <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="category_name" 
                                name="category_name" 
                                required
                                minlength="3"
                                maxlength="100"
                                placeholder="Enter category name"
                                value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : htmlspecialchars($category['category_name']); ?>"
                                oninput="updateCharCount()"
                            >
                            <div class="char-count">
                                <span id="charCount">0</span> / 100 characters
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div class="button-group">
                    <a href="delete_category.php?id=<?php echo $category['category_id']; ?>" 
                       class="btn-danger"
                       onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone and may affect existing feedback.')">
                        Delete Category
                    </a>
                    <a href="manage_category.php" class="btn-secondary">Cancel & Go Back</a>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function updateCharCount() {
        const input = document.getElementById('category_name');
        const count = document.getElementById('charCount');
        count.textContent = input.value.length;
        
        if (input.value.length > 100) {
            count.style.color = '#dc3545';
        } else if (input.value.length > 80) {
            count.style.color = '#ffc107';
        } else {
            count.style.color = '#6c757d';
        }
    }
    
    // Initialize character count on page load
    window.addEventListener('DOMContentLoaded', function() {
        updateCharCount();
    });
</script>

</body>
</html>