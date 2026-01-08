<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM management_user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $_SESSION['error'] = "User not found.";
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // Validate inputs
    if (empty($name) || empty($email)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check if email already exists (except for current user)
        $check_sql = "SELECT user_id FROM management_user WHERE email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Email already exists for another user.";
        } else {
            // Update user data
            $update_sql = "UPDATE management_user SET name = ?, email = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $name, $email, $user_id);

            if ($update_stmt->execute()) {
                $_SESSION['name'] = $name; // Update session
                $success_message = "Profile updated successfully!";
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $error_message = "Error updating profile: " . $update_stmt->error;
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
    <title>My Profile</title>
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
            max-width: 600px;
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
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
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
        
        form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }
        
        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        input[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        
        input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-warning:hover {
            background: #e0a800;
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
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="icon">👤</div>
        <h2>My Profile</h2>
        <p>Manage your account information</p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="message success">✅ <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error">❌ <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="info-box">
        <h4>📊 Account Information</h4>
        <div class="info-item">
            <span>User ID:</span>
            <span>#<?php echo htmlspecialchars($user['user_id']); ?></span>
        </div>
        <div class="info-item">
            <span>Role:</span>
            <span class="badge"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
        </div>
        <div class="info-item">
            <span>Member Since:</span>
            <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
        </div>
    </div>

    <form method="POST" action="">
        <label for="name">Full Name *</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

        <label for="email">Email Address *</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <input type="submit" value="💾 Update Profile">
    </form>

    <div class="button-group">
        <a href="change_password.php" class="btn btn-warning">🔒 Change Password</a>
        <?php
        // Dynamic back link based on role
        if ($_SESSION['role'] == 'admin') {
            echo '<a href="../admin/admin_dashboard.php" class="btn btn-secondary">← Back</a>';
        } elseif ($_SESSION['role'] == 'hr') {
            echo '<a href="../hr/hr_dashboard.php" class="btn btn-secondary">← Back</a>';
        } else {
            echo '<a href="user_dashboard.php" class="btn btn-secondary">← Back</a>';
        }
        ?>
    </div>
</div>

</body>
</html>
