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
    <title>Edit Category</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 550px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #6c757d;
            font-size: 14px;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box h4 {
            font-size: 14px;
            color: #0066cc;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #333;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item span:first-child {
            font-weight: 600;
            color: #0066cc;
        }
        
        .info-item span:last-child {
            color: #333;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .warning-icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 18px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .char-count {
            text-align: right;
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background: #667eea;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            
            .header h2 {
                font-size: 24px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="icon">✏️</div>
        <h2>Edit Category</h2>
        <p>Update category information</p>
    </div>
    
    <!-- Category Information -->
    <div class="info-box">
        <h4>📊 Category Information</h4>
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
    <div class="warning">
        <span class="warning-icon">⚠️</span>
        <div>
            <strong>Warning:</strong> This category has <strong><?php echo $category['feedback_count']; ?></strong> feedback(s) associated with it. Changing the name will affect all related feedback entries.
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Error Message -->
    <?php if (isset($error_message)): ?>
        <div class="error">
            ❌ <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Edit Form -->
    <form method="POST" action="" id="categoryForm">
        <div class="form-group">
            <label for="category_name">New Category Name *</label>
            <div class="input-wrapper">
                <span class="input-icon">📂</span>
                <input 
                    type="text" 
                    id="category_name" 
                    name="category_name" 
                    required
                    minlength="3"
                    maxlength="100"
                    placeholder="Enter new category name"
                    value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : htmlspecialchars($category['category_name']); ?>"
                    oninput="updateCharCount()"
                >
            </div>
            <div class="char-count">
                <span id="charCount">0</span> / 100 characters
            </div>
        </div>
        
        <div class="button-group">
            <a href="manage_category.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        </div>
    </form>
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
