<?php
session_start();

// Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../user/login.php');
    exit;
}

include('../db/db.php');

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
        // Check if category already exists
        $check_sql = "SELECT category_id FROM category WHERE category_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $category_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Category already exists.";
        } else {
            // Insert new category
            $sql = "INSERT INTO category (category_name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $category_name);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Category '" . htmlspecialchars($category_name) . "' added successfully!";
                header('Location: manage_category.php');
                exit;
            } else {
                $error_message = "Error adding category: " . $stmt->error;
            }
            $stmt->close();
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
    <title>Add Category</title>
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
            max-width: 500px;
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
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .examples {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .examples h4 {
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .examples ul {
            list-style: none;
            padding: 0;
        }
        
        .examples li {
            padding: 5px 0;
            color: #6c757d;
            font-size: 13px;
        }
        
        .examples li:before {
            content: "💡 ";
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="icon">📁</div>
        <h2>Add New Category</h2>
        <p>Create a new feedback category</p>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="error">
            ❌ <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="categoryForm">
        <div class="form-group">
            <label for="category_name">Category Name *</label>
            <div class="input-wrapper">
                <span class="input-icon">📂</span>
                <input 
                    type="text" 
                    id="category_name" 
                    name="category_name" 
                    required
                    minlength="3"
                    maxlength="100"
                    placeholder="e.g., Campus Facilities"
                    value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : ''; ?>"
                    oninput="updateCharCount()"
                >
            </div>
            <div class="char-count">
                <span id="charCount">0</span> / 100 characters
            </div>
        </div>
        
        <div class="button-group">
            <a href="manage_category.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Category</button>
        </div>
    </form>
    
    <div class="examples">
        <h4>💡 Example Categories:</h4>
        <ul>
            <li>Campus Facilities</li>
            <li>Teaching Quality</li>
            <li>Student Services</li>
            <li>Academic Support</li>
            <li>Campus Safety</li>
        </ul>
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
    
    // Initialize character count
    updateCharCount();
</script>

</body>
</html>
