<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

// Fetch user details from management_user table
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

    if (empty($name) || empty($email)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check if email already exists (excluding current user)
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
                $_SESSION['name'] = $name; 
                $success_message = "Profile updated successfully!";
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $error_message = "Error updating profile.";
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background-color: #0C4F3B;
            background-image: radial-gradient(circle at 10% 20%, rgba(30, 179, 134, 0.2) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 40%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .profile-card {
            background: rgba(255, 255, 255, 0.98); width: 100%; max-width: 480px;
            border-radius: 32px; padding: 40px; box-shadow: 0 40px 100px rgba(0, 0, 0, 0.4);
            position: relative; overflow: hidden;
        }
        .profile-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 8px;
            background: linear-gradient(90deg, #1eb386, #0C4F3B);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .avatar {
            width: 70px; height: 70px; background: #0C4F3B; color: white;
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 700; margin: 0 auto 15px; transform: rotate(-3deg);
        }
        .role-badge {
            display: inline-block; background: rgba(12, 79, 59, 0.08); color: #0C4F3B;
            padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-top: 8px;
        }
        .alert { padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; text-align: center; font-weight: 600; }
        .alert-success { background: #e6f7ed; color: #1e7d4d; }
        .alert-error { background: #fff1f1; color: #c53030; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 12px; font-weight: 700; color: #4a5568; margin-bottom: 8px; text-transform: uppercase; }
        input {
            width: 100%; padding: 14px 18px; border: 2px solid #edf2f7; border-radius: 14px;
            font-size: 15px; background: #f8fafc; transition: 0.3s;
        }
        input:focus { outline: none; border-color: #0C4F3B; background: #fff; box-shadow: 0 0 0 4px rgba(12, 79, 59, 0.1); }
        .btn-save {
            width: 100%; background: #0C4F3B; color: white; border: none; padding: 16px;
            border-radius: 16px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .btn-save:hover { background: #127a5b; transform: translateY(-2px); }
        .footer { display: flex; justify-content: space-between; margin-top: 25px; padding-top: 20px; border-top: 1px solid #edf2f7; }
        .link { color: #0C4F3B; text-decoration: none; font-size: 14px; font-weight: 700; }
        .btn-back { background: #f1f5f9; color: #718096; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>

<div class="profile-card">
    <div class="header">
        <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
        <h2>Profile Details</h2>
        <span class="role-badge"><?php echo htmlspecialchars($user['role']); ?></span>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">✨ <?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">⚠️ <?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <button type="submit" class="btn-save">Save Changes</button>
    </form>

    <div class="footer">
        <a href="change_password.php" class="link">🔒 Security</a>
        <?php
            $dash = "user_dashboard.php";
            if($user['role'] == 'admin') $dash = "../admin/admin_dashboard.php";
            elseif($user['role'] == 'hr') $dash = "../hr/hr_dashboard.php";
        ?>
        <a href="<?php echo $dash; ?>" class="btn-back">Dashboard</a>
    </div>
</div>

</body>
</html>