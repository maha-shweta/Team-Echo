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

    if (empty($category_name)) {
        $error_message = "Category name is required.";
    } elseif (strlen($category_name) < 3) {
        $error_message = "Category name must be at least 3 characters.";
    } else {
        // Check if category already exists
        $check_sql = "SELECT category_id FROM category WHERE category_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $category_name);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = "This category already exists.";
        } else {
            $sql = "INSERT INTO category (category_name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $category_name);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Category added successfully!";
                header('Location: manage_category.php');
                exit;
            } else {
                $error_message = "Database error occurred.";
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body {
            background-color: #0C4F3B;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(30, 179, 134, 0.2) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .category-card {
            background: rgba(255, 255, 255, 0.98);
            width: 100%;
            max-width: 480px;
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 8px;
            background: linear-gradient(90deg, #1eb386, #0C4F3B);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .icon-badge {
            width: 70px;
            height: 70px;
            background: #e6f0ed;
            color: #0C4F3B;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(12, 79, 59, 0.1);
        }

        h2 { color: #1a1a1a; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        p { color: #718096; font-size: 14px; margin-top: 4px; }

        .alert-error {
            background: #fff1f1;
            color: #c53030;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
            text-align: center;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 12px; font-weight: 800; color: #4a5568; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .input-box {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid #edf2f7;
            border-radius: 16px;
            font-size: 15px;
            background: #f8fafc;
            transition: 0.3s;
            color: #1a202c;
        }

        .input-box:focus {
            outline: none;
            border-color: #0C4F3B;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(12, 79, 59, 0.1);
        }

        .char-counter {
            text-align: right;
            font-size: 11px;
            color: #a0aec0;
            margin-top: 6px;
            font-weight: 600;
        }

        .btn-add {
            width: 100%;
            background: #0C4F3B;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(12, 79, 59, 0.2);
        }

        .btn-add:hover {
            background: #127a5b;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(12, 79, 59, 0.3);
        }

        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #718096;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-cancel:hover { color: #0C4F3B; }

        .hint-box {
            background: #fdfaf6;
            border-radius: 12px;
            padding: 15px;
            margin-top: 25px;
            border: 1px solid #f6eadd;
        }

        .hint-box h4 { font-size: 12px; color: #8a6d3b; margin-bottom: 5px; text-transform: uppercase; }
        .hint-box ul { list-style: none; font-size: 12px; color: #718096; }
        .hint-box li { margin-bottom: 3px; display: flex; align-items: center; }
        .hint-box li::before { content: '•'; color: #0C4F3B; margin-right: 8px; font-weight: bold; }
    </style>
</head>
<body>

<div class="category-card">
    <div class="header">
        <div class="icon-badge">📂</div>
        <h2>New Category</h2>
        <p>Organize user feedback efficiently</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert-error">⚠️ <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Category Name</label>
            <input 
                type="text" 
                name="category_name" 
                class="input-box"
                placeholder="e.g. Campus Facilities" 
                maxlength="100" 
                oninput="document.getElementById('count').innerText = this.value.length"
                required
            >
            <div class="char-counter"><span id="count">0</span> / 100</div>
        </div>

        <button type="submit" class="btn-add">Confirm & Add</button>
    </form>

    <div class="hint-box">
        <h4>💡 Suggestions:</h4>
        <ul>
            <li>Academic Support</li>
            <li>Student Wellness</li>
            <li>IT Infrastructure</li>
        </ul>
    </div>

    <a href="manage_category.php" class="btn-cancel">Discard Changes</a>
</div>

</body>
</html>